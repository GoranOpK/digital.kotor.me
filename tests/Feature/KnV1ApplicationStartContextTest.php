<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Services\KnApplicationStartContextStore;
use App\Support\KnApplicationStartContext;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class KnV1ApplicationStartContextTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_fl_future_entrepreneur_opens_1a_checked_and_cannot_switch(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(101)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertRadioChecked($html, 'applicant_type_fizicko_lice');
        $this->assertRadioDisabled($html, 'applicant_type_fizicko_lice');
        $this->assertRadioDisabled($html, 'applicant_type_doo');
        $this->assertStringNotContainsString('id="applicant_type_ostalo"', $html);
        $this->assertStringContainsString('id="fizickoLiceFields"', $html);
        $this->assertObrazacRegistracijaHeading($html, '(za oblik registracije PREDUZETNIK)');
        $this->assertAdditionalDataSectionHidden($html);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('applicant_type');

        $this->assertSame(0, Application::query()->count());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function plannedCompanyProvider(): array
    {
        return [
            'doo' => ['doo', UserType::LIMITED_LIABILITY_COMPANY],
            'ad' => ['ad', UserType::JOINT_STOCK_COMPANY],
            'od' => ['od', UserType::GENERAL_PARTNERSHIP],
            'kd' => ['kd', UserType::LIMITED_PARTNERSHIP],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('plannedCompanyProvider')]
    public function test_fl_planned_company_opens_1b_preselected_and_cannot_switch(string $form, string $label): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(110 + ord($form[0]))]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => $form,
        ]);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertRadioChecked($html, 'applicant_type_doo');
        $this->assertRadioDisabled($html, 'applicant_type_doo');
        $this->assertStringContainsString('id="obrazac1b"', $html);
        $this->assertMatchesRegularExpression(
            '/value="'.preg_quote($label, '/').'"[^>]*selected/s',
            $html
        );
        $this->assertStringNotContainsString('id="applicant_type_ostalo"', $html);
        $this->assertObrazacRegistracijaHeading($html, '(za oblik registracije '.strtoupper($form).')');
        $this->assertAdditionalDataSectionHidden($html);

        $other = $form === 'doo' ? 'ad' : 'doo';
        $otherLabel = $other === 'doo' ? UserType::LIMITED_LIABILITY_COMPANY : UserType::JOINT_STOCK_COMPANY;

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => $other === 'doo' ? 'doo' : 'ostalo',
                'registration_form' => $otherLabel,
                'planned_company_form' => $other,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertSame(0, Application::query()->count());
    }

    public function test_forged_store_future_entrepreneur_with_company_is_rejected(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(120)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        foreach (['doo', 'ad', 'od', 'kd'] as $form) {
            $this->actingAs($user)
                ->from(route('applications.create', $competition))
                ->post(route('applications.store', $competition), $this->draftPayload([
                    'start_context_token' => $token,
                    'applicant_type' => $form === 'doo' ? 'doo' : 'ostalo',
                    'planned_company_form' => $form,
                    'registration_form' => \App\Support\KnCommercialCompanyForm::registrationFormLabel($form),
                ]))
                ->assertSessionHasErrors();
        }

        $this->assertSame(0, Application::query()->count());
    }

    public function test_forged_store_planned_doo_with_ad_is_rejected(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(121)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'doo',
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'ostalo',
                'planned_company_form' => 'ad',
                'registration_form' => UserType::JOINT_STOCK_COMPANY,
            ]))
            ->assertSessionHasErrors();

        $this->assertSame(0, Application::query()->count());
    }

    public function test_forged_store_planned_ad_with_doo_is_rejected(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(122)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'ad',
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'doo',
                'planned_company_form' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            ]))
            ->assertSessionHasErrors();

        $this->assertSame(0, Application::query()->count());
    }

    public function test_existing_draft_saved_type_cannot_switch(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(123)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::ENTREPRENEUR,
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'planned_company_form' => 'doo',
            ]))
            ->assertSessionHasErrors('applicant_type');

        $application->refresh();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame(UserType::ENTREPRENEUR, $application->registration_form);
        $this->assertFalse($application->is_registered);
    }

    public function test_canonical_entrepreneur_opens_1a_locked(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(124),
            'jmb' => $this->validJmb(124),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertRadioChecked($html, 'applicant_type_preduzetnica');
        $this->assertRadioDisabled($html, 'applicant_type_preduzetnica');
        $this->assertStringContainsString('id="obrazac1a"', $html);
        $this->assertObrazacRegistracijaHeading($html, '(za oblik registracije PREDUZETNIK)');
        $this->assertAdditionalDataSectionVisible($html);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function canonicalCompanyProvider(): array
    {
        return [
            'doo' => [UserType::LIMITED_LIABILITY_COMPANY, 'doo', UserType::LIMITED_LIABILITY_COMPANY],
            'ad' => [UserType::JOINT_STOCK_COMPANY, 'ad', UserType::JOINT_STOCK_COMPANY],
            'od' => [UserType::GENERAL_PARTNERSHIP, 'od', UserType::GENERAL_PARTNERSHIP],
            'kd' => [UserType::LIMITED_PARTNERSHIP, 'kd', UserType::LIMITED_PARTNERSHIP],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('canonicalCompanyProvider')]
    public function test_canonical_company_opens_1b_locked(string $userType, string $form, string $label): void
    {
        $user = $this->makeKorisnik([
            'user_type' => $userType,
            'pib' => $this->validPib(130 + ord($form[0])),
            'company_name' => 'Test '.$form,
            'residential_status' => null,
            'jmb' => $this->validJmb(130 + ord($form[0])),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertRadioChecked($html, 'applicant_type_doo');
        $this->assertRadioDisabled($html, 'applicant_type_doo');
        $this->assertStringContainsString('id="obrazac1b"', $html);
        $this->assertMatchesRegularExpression(
            '/value="'.preg_quote($label, '/').'"[^>]*selected/s',
            $html
        );
        $this->assertStringNotContainsString('id="applicant_type_ostalo"', $html);
        $this->assertObrazacRegistracijaHeading($html, '(za oblik registracije '.strtoupper($form).')');
        $this->assertAdditionalDataSectionVisible($html);
    }

    public function test_two_start_context_tokens_stay_independent(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(140)]);
        $competition = $this->openCompetition();
        $tokenA = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);
        $tokenB = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'ad',
        ]);

        $store = app(KnApplicationStartContextStore::class);
        $a = $store->get($tokenA);
        $b = $store->get($tokenB);

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertSame('fizicko_lice', $a->applicantType);
        $this->assertSame('1a', $a->targetForm);
        $this->assertSame('ostalo', $b->applicantType);
        $this->assertSame('ad', $b->commercialForm);
        $this->assertSame('1b', $b->targetForm);

        $htmlA = $this->createHtml($user, $competition, $tokenA);
        $htmlB = $this->createHtml($user, $competition, $tokenB);
        $this->assertRadioChecked($htmlA, 'applicant_type_fizicko_lice');
        $this->assertRadioChecked($htmlB, 'applicant_type_doo');
        $this->assertMatchesRegularExpression(
            '/value="'.preg_quote(UserType::JOINT_STOCK_COMPANY, '/').'"[^>]*selected/s',
            $htmlB
        );

        $this->assertNotNull($store->get($tokenA));
        $this->assertNotNull($store->get($tokenB));
    }

    public function test_ostalo_is_not_shown_as_new_user_option(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(141)]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertSee('Planiram registraciju kao preduzetnik')
            ->assertSee('Planiram osnivanje privrednog društva')
            ->assertSee('value="doo"', false)
            ->assertSee('value="ad"', false)
            ->assertSee('value="od"', false)
            ->assertSee('value="kd"', false)
            ->assertDontSee('Planiram osnivanje DOO')
            ->assertDontSee('id="applicant_type_ostalo"', false)
            ->assertDontSee('name="planned_form_preview"', false);

        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);
        $html = $this->createHtml($user, $competition, $token);
        $this->assertStringNotContainsString('id="applicant_type_ostalo"', $html);
        $this->assertStringNotContainsString('for="applicant_type_ostalo"', $html);
    }

    public function test_historical_ostalo_remains_readable_without_reinterpretation(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(142)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Istorijska ostalo',
            'applicant_type' => 'ostalo',
            'business_stage' => 'započinjanje',
            'registration_form' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'is_registered' => true,
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Ostalo', false);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'applicant_type' => 'ostalo',
            'registration_form' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'status' => 'approved',
        ]);
    }

    public function test_historical_unrelated_ostalo_draft_keeps_legacy_heading(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(145)]);
        $competition = $this->openCompetition();
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Istorijska nvo',
            'applicant_type' => 'ostalo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'is_registered' => true,
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertObrazacRegistracijaHeading($html, '(za ostale pravne subjekte)');
    }

    public function test_existing_ad_draft_keeps_ad_heading(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(146)]);
        $competition = $this->openCompetition();
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt AD',
            'applicant_type' => 'ostalo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertObrazacRegistracijaHeading($html, '(za oblik registracije AD)');
        $this->assertAdditionalDataSectionHidden($html);
    }

    public function test_unregistered_1b_store_ignores_stale_additional_data(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(147)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'ad',
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'ostalo',
                'registration_form' => UserType::JOINT_STOCK_COMPANY,
                'website' => 'not-a-url',
                'bank_account' => '510-123',
                'vat_number' => 'ME123',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['website', 'bank_account', 'vat_number']);

        $application = Application::query()->first();
        $this->assertNotNull($application);
        $this->assertFalse((bool) $application->is_registered);
        $this->assertNull($application->website);
        $this->assertNull($application->bank_account);
        $this->assertNull($application->vat_number);
    }

    public function test_unregistered_draft_hides_additional_data_without_db_remediation(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(148)]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt neregistrovani 1b',
            'applicant_type' => 'doo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'is_registered' => false,
            'status' => 'draft',
            'website' => 'https://stari-sajt.example',
            'bank_account' => '510-0000000000123-45',
            'vat_number' => 'ME123456789',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertAdditionalDataSectionHidden($html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'website' => 'not-a-url',
                'bank_account' => 'forged',
                'vat_number' => 'forged',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['website', 'bank_account', 'vat_number']);

        $application->refresh();
        $this->assertSame('https://stari-sajt.example', $application->website);
        $this->assertSame('510-0000000000123-45', $application->bank_account);
        $this->assertSame('ME123456789', $application->vat_number);
        $this->assertFalse((bool) $application->is_registered);
    }

    public function test_fl_future_entrepreneur_create_has_no_editable_stage_and_stores_zapocinjanje(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(160)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);
        $this->assertStartContextLockedStage($token, 'započinjanje');

        $html = $this->createHtml($user, $competition, $token);
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, 'započinjanje');

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                'start_context_token' => $token,
                'applicant_type' => 'fizicko_lice',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors('business_stage');

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('fizicko_lice', $application->applicant_type);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('plannedCompanyProvider')]
    public function test_fl_planned_company_create_has_no_editable_stage_and_stores_zapocinjanje(string $form, string $label): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(170 + ord($form[0]))]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => $form,
        ]);
        $this->assertStartContextLockedStage($token, 'započinjanje');

        $html = $this->createHtml($user, $competition, $token);
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, 'započinjanje');
        $this->assertStringContainsString('id="obrazac1b"', $html);

        $applicantType = $form === 'doo' ? 'doo' : 'ostalo';
        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                'start_context_token' => $token,
                'applicant_type' => $applicantType,
                'registration_form' => $label,
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors('business_stage');

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame($applicantType, $application->applicant_type);
        $this->assertSame($label, $application->registration_form);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lockedShowStageProvider(): array
    {
        return [
            'zapocinjanje' => ['započinjanje'],
            'razvoj' => ['razvoj'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lockedShowStageProvider')]
    public function test_canonical_entrepreneur_create_locks_show_stage(string $stage): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(180 + strlen($stage)),
            'jmb' => $this->validJmb(180 + strlen($stage)),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => $stage]);
        $this->assertStartContextLockedStage($token, $stage);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, $stage);
        $this->assertStringContainsString('id="obrazac1a"', $html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnica',
                'registration_form' => 'Preduzetnik',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors('business_stage');

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($stage, $application->business_stage);
        $this->assertSame('preduzetnica', $application->applicant_type);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lockedShowStageProvider')]
    public function test_canonical_doo_create_locks_show_stage(string $stage): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(190 + strlen($stage)),
            'company_name' => 'Stage DOO',
            'residential_status' => null,
            'jmb' => $this->validJmb(190 + strlen($stage)),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => $stage]);
        $this->assertStartContextLockedStage($token, $stage);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, $stage);
        $this->assertStringContainsString('id="obrazac1b"', $html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                'start_context_token' => $token,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors('business_stage');

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($stage, $application->business_stage);
        $this->assertSame('doo', $application->applicant_type);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function canonicalAdOdKdProvider(): array
    {
        return [
            'ad' => [UserType::JOINT_STOCK_COMPANY, 'ad', UserType::JOINT_STOCK_COMPANY],
            'od' => [UserType::GENERAL_PARTNERSHIP, 'od', UserType::GENERAL_PARTNERSHIP],
            'kd' => [UserType::LIMITED_PARTNERSHIP, 'kd', UserType::LIMITED_PARTNERSHIP],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('canonicalAdOdKdProvider')]
    public function test_canonical_ad_od_kd_create_locks_both_show_stages(string $userType, string $form, string $label): void
    {
        foreach (['započinjanje', 'razvoj'] as $index => $stage) {
            $user = $this->makeKorisnik([
                'email' => $form.'-'.$index.'@stage.test',
                'user_type' => $userType,
                'pib' => $this->validPib(200 + ord($form[0]) + $index),
                'company_name' => 'Test '.$form,
                'residential_status' => null,
                'jmb' => $this->validJmb(200 + ord($form[0]) + $index),
            ]);
            $competition = $this->openCompetition();
            $token = $this->issueStartToken($user, $competition, ['business_stage' => $stage]);
            $this->assertStartContextLockedStage($token, $stage);

            $html = $this->createHtml($user, $competition, $token);
            $this->assertNoEditableBusinessStageChoice($html);
            $this->assertLockedBusinessStageValue($html, $stage);
            $this->assertStringContainsString('id="obrazac1b"', $html);

            $this->actingAs($user)
                ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                    'start_context_token' => $token,
                    'applicant_type' => 'ostalo',
                    'registration_form' => $label,
                ]))
                ->assertRedirect()
                ->assertSessionDoesntHaveErrors('business_stage');

            $application = Application::query()->where('user_id', $user->id)->firstOrFail();
            $this->assertSame($stage, $application->business_stage);
            $this->assertSame('ostalo', $application->applicant_type);
            $this->assertSame($label, $application->registration_form);
        }
    }

    public function test_forged_store_cannot_change_context_zapocinjanje_to_razvoj(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(210),
            'jmb' => $this->validJmb(210),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnica',
                'registration_form' => 'Preduzetnik',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_forged_store_cannot_change_context_razvoj_to_zapocinjanje(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(211),
            'company_name' => 'Forge stage DOO',
            'residential_status' => null,
            'jmb' => $this->validJmb(211),
        ]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'razvoj']);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_unregistered_forged_razvoj_is_still_rejected(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(212)]);
        $competition = $this->openCompetition();
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $this->assertSame(0, Application::query()->count());
    }

    public function test_existing_draft_zapocinjanje_cannot_switch_to_razvoj(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(213),
            'jmb' => $this->validJmb(213),
        ]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt započinjanje',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => 'Preduzetnik',
            'is_registered' => true,
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, 'započinjanje');

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'preduzetnica',
                'registration_form' => 'Preduzetnik',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $application->refresh();
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('draft', $application->status);
    }

    public function test_existing_draft_razvoj_cannot_switch_to_zapocinjanje(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(214),
            'company_name' => 'Draft razvoj DOO',
            'residential_status' => null,
            'jmb' => $this->validJmb(214),
        ]);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Nacrt razvoj',
            'applicant_type' => 'doo',
            'business_stage' => 'razvoj',
            'business_area' => 'Usluge',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'is_registered' => true,
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();
        $this->assertNoEditableBusinessStageChoice($html);
        $this->assertLockedBusinessStageValue($html, 'razvoj');

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->storePayloadWithoutStage([
                'application_id' => $application->id,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors('business_stage');

        $application->refresh();
        $this->assertSame('razvoj', $application->business_stage);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'doo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $application->refresh();
        $this->assertSame('razvoj', $application->business_stage);
        $this->assertSame('draft', $application->status);
        $this->assertSame('Biznis plan', $application->business_plan_name);
    }

    public function test_competition_show_keeps_stage_choice_for_canonical_registered(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(215),
            'jmb' => $this->validJmb(215),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertSee('name="business_stage_preview"', false)
            ->assertSee('id="business_stage_zapocinjanje_preview"', false)
            ->assertSee('id="business_stage_razvoj_preview"', false)
            ->assertSee('Preduzetnica koja započinje biznis', false)
            ->assertSee('Preduzetnica koja planira razvoj poslovanja', false);
    }

    public function test_obrazac_2_q3_planned_paths_are_ne_and_registered_are_da(): void
    {
        $fl = $this->makeKorisnik(['jmb' => $this->validJmb(143)]);
        $competition = $this->openCompetition();

        $plannedEntrepreneur = Application::create($this->completeObrazacAttributes($fl, $competition, [
            'applicant_type' => 'fizicko_lice',
            'is_registered' => false,
            'registration_form' => UserType::ENTREPRENEUR,
        ]));
        $this->assertQ3IsNe($fl, $plannedEntrepreneur);

        $plannedCompany = Application::create($this->completeObrazacAttributes($fl, $competition, [
            'applicant_type' => 'ostalo',
            'is_registered' => false,
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
        ]));
        $this->assertQ3IsNe($fl, $plannedCompany);

        $entrepreneur = $this->makeKorisnik([
            'email' => 'q3-ent@example.test',
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(144),
            'jmb' => $this->validJmb(144),
        ]);
        $registered = Application::create($this->completeObrazacAttributes($entrepreneur, $competition, [
            'applicant_type' => 'preduzetnica',
            'is_registered' => true,
            'registration_form' => 'Preduzetnik',
        ]));
        $this->assertQ3IsDa($entrepreneur, $registered);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issueStartToken(\App\Models\User $user, Competition $competition, array $payload = []): string
    {
        $response = $this->actingAs($user)->post(route('applications.start', $competition), $payload);
        $response->assertRedirect();
        $query = [];
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('start_token', $query);

        return $query['start_token'];
    }

    private function createHtml(\App\Models\User $user, Competition $competition, string $token): string
    {
        return $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->assertOk()
            ->getContent();
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function storePayloadWithoutStage(array $overrides = []): array
    {
        $payload = $this->draftPayload($overrides);
        unset($payload['business_stage']);

        return $payload;
    }

    private function assertNoEditableBusinessStageChoice(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*type="radio"[^>]*name="business_stage"/i',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="business_stage"[^>]*type="radio"/i',
            $html
        );
        $this->assertStringNotContainsString('id="business_stage_zapocinjanje_1a"', $html);
        $this->assertStringNotContainsString('id="business_stage_zapocinjanje_1b"', $html);
        $this->assertStringNotContainsString('id="business_stage_razvoj_1a"', $html);
        $this->assertStringNotContainsString('id="business_stage_razvoj_1b"', $html);
        $this->assertStringNotContainsString('id="business_stage_zapocinjanje_fizicko"', $html);
        $this->assertStringNotContainsString('id="business_stage_zapocinjanje_fizicko_old"', $html);
    }

    private function assertLockedBusinessStageValue(string $html, string $stage): void
    {
        $this->assertMatchesRegularExpression(
            '/id="kn_locked_business_stage"[^>]*value="'.preg_quote($stage, '/').'"/',
            $html
        );
    }

    private function assertStartContextLockedStage(string $token, string $stage): void
    {
        $context = app(KnApplicationStartContextStore::class)->get($token);
        $this->assertNotNull($context);
        $this->assertTrue($context->stageLocked);
        $this->assertSame($stage, $context->businessStage);
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

    private function assertQ3IsNe(\App\Models\User $user, Application $application): void
    {
        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    private function assertQ3IsDa(\App\Models\User $user, Application $application): void
    {
        $html = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    private function assertRadioChecked(string $html, string $id): void
    {
        $this->assertMatchesRegularExpression(
            '/id="'.preg_quote($id, '/').'"[^>]*\bchecked\b/s',
            $html
        );
    }

    private function assertRadioDisabled(string $html, string $id): void
    {
        $this->assertMatchesRegularExpression(
            '/id="'.preg_quote($id, '/').'"[^>]*\bdisabled\b/s',
            $html
        );
    }

    private function assertObrazacRegistracijaHeading(string $html, string $heading): void
    {
        $this->assertMatchesRegularExpression(
            '/id="obrazacRegistracijaHeader">'.preg_quote($heading, '/').'<\/span>/',
            $html
        );
    }

    private function assertAdditionalDataSectionHidden(string $html): void
    {
        $this->assertStringNotContainsString('id="additional-data-section"', $html);
        $this->assertStringNotContainsString('<h2>Dodatni podaci</h2>', $html);
    }

    private function assertAdditionalDataSectionVisible(string $html): void
    {
        $this->assertStringContainsString('id="additional-data-section"', $html);
        $this->assertStringContainsString('<h2>Dodatni podaci</h2>', $html);
        $this->assertStringContainsString('name="bank_account"', $html);
        $this->assertStringContainsString('name="vat_number"', $html);
        $this->assertStringContainsString('name="website"', $html);
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'KN V1 start context',
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
