<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Models\Application;
use App\Models\Competition;
use App\Models\PhysicalPersonIdentity;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class KnV1ApplicationClassificationRuntimeTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_unregistered_fl_default_stores_fizicko_lice_false_zapocinjanje(): void
    {
        $user = $this->makeKorisnik();
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_unregistered_fl_planned_doo_stores_doo_false_zapocinjanje_and_keeps_fl_identity(): void
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
        $user->refresh();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertFalse($user->isEntrepreneur());
    }

    public function test_unregistered_fl_cannot_store_live_preduzetnica(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(43)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'preduzetnica',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertFalse($application->is_registered);
    }

    public function test_unregistered_fl_cannot_choose_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(22)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect(route('applications.create', $competition))
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_unregistered_fl_planned_doo_cannot_choose_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(44)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'doo',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect(route('applications.create', $competition))
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_unregistered_fl_cannot_forge_is_registered_true(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(45)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'is_registered' => '1',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($application->is_registered);
        $this->assertSame('fizicko_lice', $application->applicant_type);
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

    public function test_existing_entrepreneur_forge_fizicko_lice_stays_locked_preduzetnica(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(33),
            'jmb' => $this->validJmb(46),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('preduzetnica', $application->applicant_type);
        $this->assertTrue($application->is_registered);
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

    public function test_existing_doo_forge_fizicko_lice_stays_locked_doo(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(34),
            'company_name' => 'Forge DOO',
            'residential_status' => null,
            'jmb' => $this->validJmb(47),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('doo', $application->applicant_type);
        $this->assertTrue($application->is_registered);
        $this->assertSame('razvoj', $application->business_stage);
    }

    public function test_document_catalog_supports_four_v1_flows(): void
    {
        $registrationDocs = ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun', 'statut', 'karton_potpisa'];

        $a = Application::getRequiredDocumentsForType('fizicko_lice', 'započinjanje', false);
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

        $registeredPreduzetnicaZapocinjanje = Application::getRequiredDocumentsForType('preduzetnica', 'započinjanje', true);
        $this->assertContains('crps_resenje', $registeredPreduzetnicaZapocinjanje);
    }

    public function test_competition_show_unregistered_fl_cannot_choose_razvoj(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(25)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', 'fizicko_lice')
            ->assertViewHas('knFormApplicantType', 'fizicko_lice')
            ->assertViewHas('knAllowsRazvoj', false)
            ->assertViewHas('knIsRegisteredBusiness', false)
            ->assertSee('disabled', false);
    }

    public function test_competition_show_unregistered_fl_can_choose_planned_doo(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(26)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('knCanChoosePlannedForm', true)
            ->assertDontSee('Planirani oblik poslovanja')
            ->assertDontSee('planirani oblik Preduzetnica')
            ->assertSee('Fizičko lice (nema registrovanu djelatnost)')
            ->assertSee('Planiram osnivanje DOO')
            ->assertSee('name="planned_form_preview"', false)
            ->assertSee('value="fizicko_lice"', false)
            ->assertSee('value="doo"', false);
    }

    public function test_obrazac_2_q3_matches_application_is_registered_and_is_locked(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(27)]);
        $competition = $this->openCompetition();
        $application = Application::create($this->completeObrazacAttributes($user, $competition, [
            'applicant_type' => 'fizicko_lice',
            'is_registered' => false,
        ]));

        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="has_registered_business_display"', $html);
        $this->assertStringContainsString('name="has_registered_business"', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_obrazac_2_q3_planned_doo_is_ne(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(48)]);
        $competition = $this->openCompetition();
        $application = Application::create($this->completeObrazacAttributes($user, $competition, [
            'applicant_type' => 'doo',
            'is_registered' => false,
        ]));

        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_obrazac_2_q3_registered_preduzetnica_is_da(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(35),
            'jmb' => $this->validJmb(49),
        ]);
        $competition = $this->openCompetition();
        $application = Application::create($this->completeObrazacAttributes($user, $competition, [
            'applicant_type' => 'preduzetnica',
            'is_registered' => true,
            'registration_form' => 'Preduzetnik',
        ]));

        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="1"', $html);
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
            'applicant_type' => 'fizicko_lice',
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
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($application->is_registered);
        $this->assertSame('fizicko_lice', $application->applicant_type);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'fizicko_lice',
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
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('draft', $application->status);

        $createUrl = route('applications.create', $competition);
        $this->actingAs($user)
            ->from($createUrl)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'razvoj',
                'is_registered' => '1',
            ]))
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(1, Application::query()->where('user_id', $user->id)->count());
        $application->refresh();
        $this->assertFalse($application->is_registered);
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_unregistered_fl_draft_can_switch_to_planned_doo_without_identity_change(): void
    {        $user = $this->makeKorisnik(['jmb' => $this->validJmb(42)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt',
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
                'business_plan_name' => 'Nacrt ažuriran',
                'is_registered' => '1',
            ]))
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('doo', $application->applicant_type);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $user->refresh();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
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

    public function test_canonical_fl_create_prefills_physical_person_jmbg_from_identity_when_users_jmb_is_null(): void
    {
        [$user, $canonicalJmb] = $this->makeCanonicalPhysicalPerson();
        $competition = $this->openCompetition();

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertNull($user->jmb);
        $this->assertSame($canonicalJmb, PhysicalPersonIdentity::query()->where('jmb', $canonicalJmb)->value('jmb'));
        $this->assertInputHasValue($html, 'physical_person_jmbg', $canonicalJmb);
    }

    public function test_canonical_fl_store_falls_back_to_canonical_jmb_when_physical_person_jmbg_empty(): void
    {
        [$user, $canonicalJmb] = $this->makeCanonicalPhysicalPerson($this->validJmb(81));
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'physical_person_jmbg' => '',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame($canonicalJmb, $application->physical_person_jmbg);
        $this->assertNull($user->fresh()->jmb);
    }

    public function test_canonical_fl_store_keeps_explicit_physical_person_jmbg_over_canonical_jmb(): void
    {
        [$user] = $this->makeCanonicalPhysicalPerson($this->validJmb(82));
        $manualJmb = $this->validJmb(83);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'physical_person_jmbg' => $manualJmb,
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($manualJmb, $application->physical_person_jmbg);
    }

    public function test_canonical_fl_create_prefers_saved_physical_person_jmbg_over_canonical_jmb(): void
    {
        [$user, $canonicalJmb] = $this->makeCanonicalPhysicalPerson($this->validJmb(84));
        $savedJmb = $this->validJmb(85);
        $competition = $this->openCompetition();
        Application::create($this->completeObrazacAttributes($user, $competition, [
            'physical_person_jmbg' => $savedJmb,
        ]));

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertInputHasValue($html, 'physical_person_jmbg', $savedJmb);
        $this->assertDoesNotMatchRegularExpression(
            '/name="physical_person_jmbg"[^>]*value="'.preg_quote($canonicalJmb, '/').'"/s',
            $html
        );
    }

    public function test_canonical_preduzetnica_create_still_prefills_preduzetnik_jmbg(): void
    {
        $canonicalJmb = $this->validJmb(86);
        $user = $this->makeKorisnik([
            'jmb' => null,
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(86),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'jmb' => $canonicalJmb,
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Ana',
                'pib' => $this->validPib(86),
                'crpsNumber' => $this->validCrps(1, 86),
            ],
        ]));
        config(['identity.canonical_read' => true]);
        $competition = $this->openCompetition();

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertInputHasValue($html, 'preduzetnik_jmbg', $canonicalJmb);
    }

    public function test_canonical_doo_create_still_prefills_doo_jmbg(): void
    {
        $canonicalJmb = $this->validJmb(87);
        $user = $this->makeKorisnik([
            'jmb' => null,
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(87),
            'company_name' => 'Test DOO',
            'residential_status' => null,
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user, [
            'jmb' => $canonicalJmb,
        ]));
        config(['identity.canonical_read' => true]);
        $competition = $this->openCompetition();

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertInputHasValue($html, 'doo_jmbg', $canonicalJmb);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function completeObrazacAttributes(\App\Models\User $user, Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'accuracy_declaration' => true,
            'status' => 'draft',
            'physical_person_name' => $user->name,
            'physical_person_jmbg' => $user->jmb,
            'physical_person_phone' => $user->phone,
            'physical_person_email' => $user->email,
            'founder_name' => $user->name,
            'director_name' => $user->name,
            'company_seat' => 'Kotor',
            'applicant_jmbg' => $user->jmb,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function draftPayload(array $overrides = []): array
    {
        return array_merge([
            'save_as_draft' => '1',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_plan_name' => 'Biznis plan',
            'business_area' => 'Usluge',
        ], $overrides);
    }

    /**
     * @return array{0: \App\Models\User, 1: string}
     */
    private function makeCanonicalPhysicalPerson(?string $canonicalJmb = null): array
    {
        $canonicalJmb ??= $this->validJmb(80);
        $user = $this->makeKorisnik(['jmb' => null]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $canonicalJmb],
        ]));
        config(['identity.canonical_read' => true]);
        $user->refresh();

        return [$user, $canonicalJmb];
    }

    private function assertInputHasValue(string $html, string $name, string $expected): void
    {
        $this->assertMatchesRegularExpression(
            '/name="'.preg_quote($name, '/').'"[^>]*value="'.preg_quote($expected, '/').'"/s',
            $html
        );
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
