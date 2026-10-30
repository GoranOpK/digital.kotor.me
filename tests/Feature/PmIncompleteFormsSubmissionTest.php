<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmIncompleteFormsSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private const WARNING = 'Jedan ili više obrazaca vaše prijave nijesu u potpunosti popunjeni.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_pm_draft_forms_submit_only_after_explicit_confirmation(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: false, m2Complete: false);
        $this->attachRequiredDocuments($application);

        $page = $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->getContent();
        $this->assertStringContainsString(self::WARNING, $page);
        $this->assertStringContainsString('name="confirm_incomplete_forms"', $page);
        $this->assertStringContainsString('Povratak na uređivanje', $page);
        $this->assertStringContainsString('Nacrt', $page);
        $this->assertStringNotContainsString('Podnesena', $page);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHas('omladinsko_confirm_incomplete_forms');
        $this->assertSame('draft', $application->fresh()->status);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
            ])
            ->assertSessionHasNoErrors();
        $submitted = $application->fresh();
        $this->assertSame('submitted', $submitted->status);

        $after = $this->actingAs($owner)->get(route('applications.show', $submitted))->assertOk()->getContent();
        $this->assertStringContainsString('Podnesena', $after);
        $this->assertStringNotContainsString(self::WARNING, $after);
    }

    public function test_pm_warning_appears_when_only_one_form_is_draft(): void
    {
        [$owner, $m2Draft] = $this->pmApplication(m1Complete: true, m2Complete: false);
        $m2Page = $this->actingAs($owner)->get(route('applications.show', $m2Draft))->assertOk()->getContent();
        $this->assertStringContainsString('Obrazac M1a popunjen', $m2Page);
        $this->assertStringContainsString(self::WARNING, $m2Page);

        [$owner, $m1Draft] = $this->pmApplication(m1Complete: false, m2Complete: true);
        $m1Page = $this->actingAs($owner)->get(route('applications.show', $m1Draft))->assertOk()->getContent();
        $this->assertStringContainsString('Obrazac M1a - Nacrt', $m1Page);
        $this->assertStringContainsString('Popunjen', $m1Page);
        $this->assertStringContainsString(self::WARNING, $m1Page);
    }

    public function test_pm_complete_forms_do_not_show_the_incompleteness_warning(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: true, m2Complete: true);
        $this->attachRequiredDocuments($application);

        $page = $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::WARNING, $page);
        $this->assertStringNotContainsString('name="confirm_incomplete_forms"', $page);
        $this->assertStringContainsString('Obrazac M1a popunjen', $page);
        $this->assertStringContainsString('Popunjen', $page);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('omladinsko_confirm_incomplete_forms');
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_pm_unconfirmed_finances_notice_still_blocks_submission(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: true, m2Complete: false, finances: false);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_zensko_still_requires_a_complete_form_and_ignores_the_pm_confirmation(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: false, m2Complete: false, type: 'zensko');

        $page = $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::WARNING, $page);
        $this->assertStringContainsString('Obrazac 1a/1b - Nacrt', $page);
        $this->assertStringContainsString('Biznis Plan - nacrt', $page);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_pm_without_a_business_plan_is_marked_not_created(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: true, m2Complete: false, withPlan: false);
        $page = $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->getContent();
        $this->assertStringContainsString('Nije kreiran', $page);
        $this->assertStringContainsString('Nacrt', $page);
        $this->assertStringNotContainsString(self::WARNING, $page);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function pmApplication(bool $m1Complete, bool $m2Complete, bool $finances = true, string $type = 'omladinsko', bool $withPlan = true): array
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $competition = Competition::create([
            'title' => 'Status obrazaca '.$type.' '.uniqid(),
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
            'accuracy_declaration' => $m1Complete,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
        ]);
        if ($withPlan) {
            BusinessPlan::create([
                'application_id' => $application->id,
                'business_idea_name' => 'Ideja',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => '0101990123456',
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
                'summary' => $m2Complete ? 'Potpun sažetak' : null,
                'realization_type' => 'stvori_novi',
                'funding_sources_table' => [
                    ['type' => 'Laptop', 'price' => '1200'],
                ],
                'finances_notice_confirmed' => $finances,
            ]);
        }

        return [$owner, $application->fresh(['businessPlan', 'competition'])];
    }

    private function attachRequiredDocuments(Application $application): void
    {
        foreach ($application->getStrictlyRequiredDocuments() as $type) {
            $path = 'documents/'.$application->id.'_'.$type.'.pdf';
            Storage::disk('local')->put($path, '%PDF-1.4 test');
            ApplicationDocument::create([
                'application_id' => $application->id,
                'name' => $type.'.pdf',
                'file_path' => $path,
                'document_type' => $type,
                'is_required' => true,
            ]);
        }
        $application->unsetRelation('documents');
    }
}
