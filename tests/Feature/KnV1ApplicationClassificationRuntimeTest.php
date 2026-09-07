<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class KnV1ApplicationClassificationRuntimeTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_unregistered_fl_planned_preduzetnik_stores_preduzetnica_false_zapocinjanje(): void
    {
        $user = $this->makeKorisnik();
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_unregistered_fl_planned_doo_stores_doo_false_zapocinjanje(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(21)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'doo',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('doo', $application->applicant_type);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_unregistered_fl_cannot_choose_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(22)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect(route('applications.create', $competition))
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_existing_entrepreneur_is_preduzetnica_and_registered(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(31),
            'jmb' => $this->validJmb(23),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'doo',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertTrue($application->is_registered);
        $this->assertSame('razvoj', $application->business_stage);
    }

    public function test_existing_doo_is_doo_and_registered(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(32),
            'company_name' => 'Test DOO',
            'residential_status' => null,
            'jmb' => $this->validJmb(24),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('doo', $application->applicant_type);
        $this->assertTrue($application->is_registered);
    }

    public function test_document_catalog_supports_four_v1_flows(): void
    {
        $registrationDocs = ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun', 'statut', 'karton_potpisa'];

        $a = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', false);
        $this->assertEmpty(array_intersect($registrationDocs, $a));
        $this->assertContains('licna_karta', $a);

        $b = Application::getRequiredDocumentsForType('doo', 'započinjanje', false);
        $this->assertEmpty(array_intersect($registrationDocs, $b));
        $this->assertContains('licna_karta', $b);

        $c = Application::getRequiredDocumentsForType('preduzetnica', 'razvoj', true);
        $this->assertContains('crps_resenje', $c);
        $this->assertContains('pib_resenje', $c);

        $d = Application::getRequiredDocumentsForType('doo', 'razvoj', true);
        $this->assertContains('crps_resenje', $d);
        $this->assertContains('statut', $d);
    }

    public function test_competition_show_unregistered_fl_cannot_choose_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(25)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', 'fizicko_lice')
            ->assertViewHas('knAllowsRazvoj', false)
            ->assertViewHas('knIsRegisteredBusiness', false)
            ->assertSee('disabled', false);
    }

    public function test_competition_show_unregistered_fl_can_choose_planned_form(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(26)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('knCanChoosePlannedForm', true)
            ->assertSee('Planirani oblik poslovanja')
            ->assertSee('name="planned_form_preview"', false)
            ->assertSee('value="preduzetnica"', false)
            ->assertSee('value="doo"', false);
    }

    public function test_obrazac_2_q3_matches_application_is_registered_and_is_locked(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(27)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'applicant_jmbg' => $user->jmb,
            'accuracy_declaration' => true,
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="has_registered_business_display"', $html);
        $this->assertStringContainsString('name="has_registered_business"', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_obrazac_2_rejects_contradictory_registered_business_answer(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(28)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->from(route('applications.business-plan.create', $application))
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'has_registered_business' => '1',
            ])
            ->assertRedirect(route('applications.business-plan.create', $application))
            ->assertSessionHasErrors('has_registered_business');
    }

    public function test_historical_fizicko_lice_approved_and_rejected_remain_readable_without_conversion(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(29)]);
        $competition = $this->openCompetition();

        $approved = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Istorijska approved',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('applications.show', $approved))
            ->assertOk()
            ->assertSee('Fizičko lice', false);

        $this->assertDatabaseHas('applications', [
            'id' => $approved->id,
            'applicant_type' => 'fizicko_lice',
            'is_registered' => 0,
            'status' => 'approved',
        ]);

        $other = $this->makeKorisnik([
            'email' => 'hist-rej@example.test',
            'jmb' => $this->validJmb(30),
        ]);
        $rejected = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $other->id,
            'business_plan_name' => 'Istorijska rejected',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'is_registered' => false,
            'status' => 'rejected',
        ]);

        $this->actingAs($other)
            ->get(route('applications.show', $rejected))
            ->assertOk();

        $this->assertDatabaseHas('applications', [
            'id' => $rejected->id,
            'applicant_type' => 'fizicko_lice',
            'is_registered' => 0,
            'status' => 'rejected',
        ]);
    }

    public function test_existing_v1_draft_second_store_keeps_classification_and_rejects_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(41)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($application->is_registered);
        $this->assertSame('preduzetnica', $application->applicant_type);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Ažurirani nacrt',
                'is_registered' => '1',
            ]))
            ->assertRedirect();

        $this->assertSame(1, Application::query()->where('user_id', $user->id)->count());
        $application->refresh();
        $this->assertSame($application->id, Application::query()->where('user_id', $user->id)->value('id'));
        $this->assertSame('Ažurirani nacrt', $application->business_plan_name);
        $this->assertFalse($application->is_registered);
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('draft', $application->status);

        $createUrl = route('applications.create', $competition);
        $this->actingAs($user)
            ->from($createUrl)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'razvoj',
                'is_registered' => '1',
            ]))
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(1, Application::query()->where('user_id', $user->id)->count());
        $application->refresh();
        $this->assertFalse($application->is_registered);
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_historical_fizicko_lice_draft_store_does_not_convert_snapshot(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(42)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Istorijski nacrt',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'doo',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Istorijski nacrt ažuriran',
                'is_registered' => '1',
            ]))
            ->assertRedirect();

        $this->assertSame(1, Application::query()->where('user_id', $user->id)->count());
        $application->refresh();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('draft', $application->status);
        $this->assertSame('Istorijski nacrt ažuriran', $application->business_plan_name);
    }

    public function test_registered_preduzetnica_zapocinjanje_keeps_registration_documents(): void
    {
        $registrationDocs = ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun'];

        $unregistered = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', false);
        $registered = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', true);

        $this->assertEmpty(array_intersect($registrationDocs, $unregistered));
        $this->assertContains('licna_karta', $unregistered);

        foreach ($registrationDocs as $documentType) {
            $this->assertContains($documentType, $registered);
        }
        $this->assertContains('licna_karta', $registered);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function draftPayload(array $overrides = []): array
    {
        return array_merge([
            'save_as_draft' => '1',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'business_plan_name' => 'Biznis plan',
            'business_area' => 'Usluge',
        ], $overrides);
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'KN V1 runtime',
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ]);
    }
}
