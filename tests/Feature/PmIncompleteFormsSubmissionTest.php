<?php

namespace Tests\Feature;

use App\Http\Controllers\ApplicationController;
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

    public function test_pm_incomplete_m1_blocks_submit_even_with_confirmation(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: false, m2Complete: true);
        $this->attachRequiredDocuments($application);
        $this->assertFalse($application->isObrazacComplete());

        $page = $this->actingAs($owner)->get(route('applications.show', $application))->assertOk()->getContent();
        $this->assertStringContainsString(ApplicationController::PM_M1_INCOMPLETE_MESSAGE, $page);
        $this->assertStringContainsString('Povratak na uređivanje', $page);
        $this->assertStringContainsString(route('applications.create', $application->competition_id).'?application_id='.$application->id, $page);
        $this->assertStringNotContainsString('name="confirm_incomplete_forms"', $page);
        $this->assertStringNotContainsString(self::WARNING, $page);
        $this->assertStringContainsString('Nacrt', $page);
        $this->assertStringNotContainsString('Podnesena', $page);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasErrors([
                'error' => ApplicationController::PM_M1_INCOMPLETE_MESSAGE,
            ]);
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
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
        $this->assertStringContainsString(ApplicationController::PM_M1_INCOMPLETE_MESSAGE, $m1Page);
        $this->assertStringNotContainsString(self::WARNING, $m1Page);
        $this->assertStringNotContainsString('name="confirm_incomplete_forms"', $m1Page);
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

    public function test_registered_entrepreneur_incomplete_m1_stays_draft(): void
    {
        [$owner, $application] = $this->pmApplication(
            m1Complete: true,
            m2Complete: true,
            applicantType: 'preduzetnik',
            registered: true,
            blank: 'preduzetnik_name',
        );
        $this->assertFalse($application->isObrazacComplete());

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasErrors([
                'error' => ApplicationController::PM_M1_INCOMPLETE_MESSAGE,
            ]);
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_planned_company_blocks_required_m1_fields_but_not_registration_fields(): void
    {
        [$blockedOwner, $blocked] = $this->pmApplication(
            m1Complete: true,
            m2Complete: true,
            applicantType: 'privredno_drustvo',
            registered: false,
            blank: 'doo_name',
        );
        $this->assertFalse($blocked->isObrazacComplete());
        $this->assertNull($blocked->crps_number);
        $this->assertNull($blocked->pib);
        $this->assertNull($blocked->founder_name);
        $this->assertNull($blocked->director_name);
        $this->assertNull($blocked->company_seat);

        $this->actingAs($blockedOwner)
            ->post(route('applications.final-submit', $blocked), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasErrors([
                'error' => ApplicationController::PM_M1_INCOMPLETE_MESSAGE,
            ]);
        $this->assertSame('draft', $blocked->fresh()->status);

        [$owner, $application] = $this->pmApplication(
            m1Complete: true,
            m2Complete: true,
            applicantType: 'privredno_drustvo',
            registered: false,
        );
        $this->assertTrue($application->isObrazacComplete());
        $this->assertNull($application->crps_number);
        $this->assertNull($application->pib);
        $this->assertNull($application->founder_name);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('omladinsko_confirm_missing_documents')
            ->assertSessionMissing('omladinsko_confirm_incomplete_forms');
        $this->assertSame('draft', $application->fresh()->status);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_registered_company_incomplete_m1_stays_draft(): void
    {
        [$owner, $application] = $this->pmApplication(
            m1Complete: true,
            m2Complete: true,
            applicantType: 'privredno_drustvo',
            registered: true,
            blank: 'founder_name',
        );
        $this->assertFalse($application->isObrazacComplete());

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertSessionHasErrors([
                'error' => ApplicationController::PM_M1_INCOMPLETE_MESSAGE,
            ]);
        $this->assertSame('draft', $application->fresh()->status);
    }

    public function test_incomplete_m1_can_still_be_saved_as_draft(): void
    {
        [$owner, $application] = $this->pmApplication(m1Complete: true, m2Complete: true);

        $this->actingAs($owner)
            ->post(route('applications.store', $application->competition), [
                'save_as_draft' => '1',
                'application_id' => $application->id,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Nacrt bez izjave',
                'business_area' => 'Usluge',
                'physical_person_name' => $owner->name,
                'physical_person_phone' => '067000000',
                'physical_person_email' => $owner->email,
                'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            ])
            ->assertSessionHasNoErrors();

        $saved = $application->fresh();
        $this->assertSame('draft', $saved->status);
        $this->assertNull($saved->submitted_at);
        $this->assertSame('Nacrt bez izjave', $saved->business_plan_name);
        $this->assertFalse($saved->accuracy_declaration);
        $this->assertFalse($saved->isObrazacComplete());
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
    private function pmApplication(
        bool $m1Complete,
        bool $m2Complete,
        bool $finances = true,
        string $type = 'omladinsko',
        bool $withPlan = true,
        string $applicantType = 'fizicko_lice',
        bool $registered = false,
        ?string $blank = null,
    ): array
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
        $application = Application::create($this->applicationAttributes(
            $owner,
            $competition,
            $type,
            $m1Complete,
            $applicantType,
            $registered,
            $blank,
        ));
        if ($withPlan) {
            BusinessPlan::create([
                'application_id' => $application->id,
                'business_idea_name' => 'Ideja',
                'applicant_name' => $owner->name,
                'applicant_jmbg' => '0101990123456',
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => $m2Complete ? '067000000' : null,
                'applicant_email' => $owner->email,
                'summary' => $m2Complete ? 'Potpun sažetak' : null,
                'realization_type' => 'stvori_novi',
                'has_business_space' => 'nemam',
                'has_seasonal_workers' => false,
                'funding_sources_table' => [
                    ['type' => 'Laptop', 'price' => '1200'],
                ],
                'finances_notice_confirmed' => $finances,
            ]);
        }

        return [$owner, $application->fresh(['businessPlan', 'competition'])];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationAttributes(
        User $owner,
        Competition $competition,
        string $type,
        bool $m1Complete,
        string $applicantType,
        bool $registered,
        ?string $blank,
    ): array {
        $kotor = 'Njegoševa 1, 85330 Kotor';
        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'status' => 'draft',
            'business_plan_name' => 'Plan '.$type,
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => $registered,
            'accuracy_declaration' => $m1Complete,
            'physical_person_name' => $owner->name,
            'physical_person_jmbg' => '0101990123456',
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => $kotor,
        ];

        if ($applicantType === 'preduzetnik') {
            $attributes = array_merge($attributes, [
                'preduzetnik_name' => $owner->name,
                'preduzetnik_phone' => '067000000',
                'preduzetnik_email' => $owner->email,
                'preduzetnik_address' => $kotor,
                'applicant_jmbg' => '0101990123456',
                'registration_form' => $registered ? 'Preduzetnik' : null,
                'crps_number' => $registered ? '10000001' : null,
                'pib' => $registered ? '12345678' : null,
            ]);
        }

        if ($applicantType === 'privredno_drustvo') {
            $attributes = array_merge($attributes, [
                'doo_name' => 'Društvo mladih',
                'doo_phone' => '067000000',
                'doo_email' => $owner->email,
                'doo_address' => $kotor,
                'applicant_jmbg' => '0101990123456',
                'founder_name' => $registered ? 'Osnivač' : null,
                'director_name' => $registered ? 'Direktor' : null,
                'company_seat' => $registered ? $kotor : null,
                'registration_form' => $registered ? 'DOO' : null,
                'crps_number' => $registered ? '50000001' : null,
                'pib' => $registered ? '12345678' : null,
            ]);
        }

        if ($blank !== null) {
            $attributes[$blank] = null;
        }

        return $attributes;
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
