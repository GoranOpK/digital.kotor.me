<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPlanSaveRedirectAndNoticeTest extends TestCase
{
    use RefreshDatabase;

    private const NOTICE = 'Status „Popunjen“ potvrđuje da su uneseni osnovni obavezni podaci biznis plana.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_successful_draft_and_complete_saves_open_the_application_status(): void
    {
        foreach (['zensko', 'omladinsko'] as $type) {
            [$owner, $application] = $this->application($type);

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), $this->draftPayload())
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHasNoErrors();
            $this->assertSame('draft', $application->fresh()->status);
            $this->assertFalse($application->fresh()->businessPlan->isComplete());

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), $this->completePayload($owner, $type))
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHasNoErrors();
            $this->assertTrue($application->fresh()->businessPlan->isComplete());
            $this->assertSame('draft', $application->fresh()->status);

            $this->actingAs($owner)
                ->post(route('applications.business-plan.store', $application), $this->completePayload($owner, $type, 'Izmjena sačuvanog plana'))
                ->assertRedirect(route('applications.show', $application))
                ->assertSessionHasNoErrors();
            $this->assertSame('Izmjena sačuvanog plana', $application->fresh()->businessPlan->business_idea_name);
        }
    }

    public function test_failed_validation_stays_on_the_business_plan_form(): void
    {
        foreach (['zensko', 'omladinsko'] as $type) {
            [$owner, $application] = $this->application($type);

            $this->actingAs($owner)
                ->from(route('applications.business-plan.create', $application))
                ->post(route('applications.business-plan.store', $application), [
                    'save_as_draft' => '1',
                    'has_registered_business' => '0',
                    'applicant_email' => 'nije-email',
                ])
                ->assertRedirect(route('applications.business-plan.create', $application))
                ->assertSessionHasErrors('applicant_email');

            $this->assertNull($application->fresh()->businessPlan);
        }
    }

    public function test_filled_notice_shows_for_a_complete_plan_with_empty_other_chapters(): void
    {
        foreach (['zensko', 'omladinsko'] as $type) {
            [$owner, $application] = $this->application($type);
            BusinessPlan::create([
                'application_id' => $application->id,
                'business_idea_name' => 'Samo osnovni podaci',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => '0101990123456',
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
                'summary' => 'Sažetak bez ostalih poglavlja',
                'finances_notice_confirmed' => true,
            ]);

            $plan = $application->fresh()->businessPlan;
            $this->assertTrue($plan->isComplete());
            $this->assertNull($plan->realization_type);
            $this->assertNull($plan->promotion);
            $this->assertNull($plan->funding_sources_table);

            $page = $this->actingAs($owner)
                ->get(route('applications.show', $application))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(self::NOTICE, $page);
            $this->assertStringContainsString('id="business-plan-filled-notice"', $page);
        }
    }

    public function test_filled_notice_is_hidden_for_a_draft_plan_and_for_mmsp(): void
    {
        [$owner, $application] = $this->application('omladinsko');
        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Samo naziv',
            'finances_notice_confirmed' => true,
        ]);
        $this->assertFalse($application->fresh()->businessPlan->isComplete());

        $this->actingAs($owner)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertDontSee(self::NOTICE, false);

        [$mmspOwner, $mmsp] = $this->application(CompetitionProfileResolver::TYPE_CANONICAL);
        BusinessPlan::create([
            'application_id' => $mmsp->id,
            'business_idea_name' => 'MMSP plan',
            'applicant_name' => $mmspOwner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $mmspOwner->email,
            'summary' => 'Sažetak',
            'finances_notice_confirmed' => true,
        ]);

        $this->actingAs($mmspOwner)
            ->get(route('applications.show', $mmsp))
            ->assertOk()
            ->assertDontSee(self::NOTICE, false);
    }

    public function test_pm_incomplete_form_confirmation_and_zp_submit_stay_unchanged(): void
    {
        [$pmOwner, $pm] = $this->application('omladinsko');
        BusinessPlan::create([
            'application_id' => $pm->id,
            'business_idea_name' => 'PM bez sažetka',
            'applicant_name' => $pmOwner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $pmOwner->email,
            'finances_notice_confirmed' => true,
        ]);
        $this->assertTrue($pm->fresh()->isObrazacComplete());
        $this->assertFalse($pm->fresh()->businessPlan->isComplete());

        $this->actingAs($pmOwner)
            ->post(route('applications.final-submit', $pm))
            ->assertRedirect(route('applications.show', $pm))
            ->assertSessionHas('omladinsko_confirm_incomplete_forms');
        $this->assertSame('draft', $pm->fresh()->status);

        [$zpOwner, $zp] = $this->application('zensko');
        BusinessPlan::create([
            'application_id' => $zp->id,
            'business_idea_name' => 'ŽP osnovni plan',
            'applicant_name' => $zpOwner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $zpOwner->email,
            'summary' => 'Sažetak',
            'finances_notice_confirmed' => true,
        ]);
        $this->assertTrue($zp->fresh()->isObrazacComplete());

        $this->actingAs($zpOwner)
            ->post(route('applications.final-submit', $zp))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('omladinsko_confirm_incomplete_forms');
        $this->assertSame('submitted', $zp->fresh()->status);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function application(string $type): array
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => 'Čuvanje '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan '.$type,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'accuracy_declaration' => true,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);

        return [$owner, $application];
    }

    /**
     * @return array<string, string>
     */
    private function draftPayload(): array
    {
        return [
            'save_as_draft' => '1',
            'has_registered_business' => '0',
            'business_idea_name' => 'Nacrt ideje',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function completePayload(User $owner, string $type, string $idea = 'Popunjena ideja'): array
    {
        $payload = [
            'has_registered_business' => '0',
            'business_idea_name' => $idea,
            'applicant_name' => $owner->name,
            'applicant_jmbg' => '0101990123456',
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'summary' => 'Sažetak bez ostalih poglavlja',
            'finances_notice_confirmed' => '1',
        ];
        if ($type === 'omladinsko') {
            $payload['save_as_draft'] = '1';
        }

        return $payload;
    }
}
