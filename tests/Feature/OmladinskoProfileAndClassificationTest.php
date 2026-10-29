<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\KnApplicationStartContextFactory;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationStartContext;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoProfileAndClassificationTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CompetitionProgramCatalog::clearTestOverrides();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_omladinsko_profile_is_active_on_konkurs_admin_hub(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
        $html = $response->getContent();
        $text = $this->visiblePageText($html);

        $response->assertSee('Podrška preduzetništvu mladih', false);
        $response->assertSee('Podrška ženskom preduzetništvu', false);
        $response->assertSee('Podrška mikro, malim i srednjim preduzećima', false);
        $this->assertStringContainsString('Aktivan modul', $text);
        $this->assertSame(2, substr_count($text, 'Aktivan modul'));
        // Only the canonical MMSP card remains DEVELOPMENT on the hub.
        $this->assertSame(1, substr_count($text, 'U razvoju'));
        $this->assertSame(1, substr_count($text, 'Profil je u implementaciji i još nije pušten korisnicima.'));

        $omladinskoCard = $this->programCardHtml($html, 'omladinsko');
        $omladinskoText = $this->visiblePageText($omladinskoCard);
        $this->assertStringContainsString('program-badge-active', $omladinskoCard);
        $this->assertStringContainsString('Aktivan modul', $omladinskoText);
        $this->assertStringNotContainsString('program-badge-development', $omladinskoCard);
        $this->assertStringNotContainsString('U razvoju', $omladinskoText);
        $this->assertStringNotContainsString('Profil je u implementaciji i još nije pušten korisnicima.', $omladinskoText);
        $this->assertStringContainsString(
            'Konkurs za podršku preduzetništvu mladih — prijave, dokumentacija, komisija i evaluacija.',
            $text
        );
        $this->assertSame(1, substr_count($html, 'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.'));
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertContains('omladinsko', CompetitionProgramCatalog::publiclyAvailableTypes());
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()['zensko']['status']
        );
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['preduzetnici_mikro_mala_i_srednja_preduzeca']['status']
        );
        $this->assertSame(
            'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
            CompetitionProgramCatalog::definitions()['zensko']['description']
        );
    }

    public function test_public_catalog_shows_started_omladinsko_and_hides_draft(): void
    {
        $zensko = $this->openCompetition('zensko', 'Poziv žene');
        $omladinsko = $this->openCompetition('omladinsko', 'Poziv mladi');
        $draft = $this->openCompetition('omladinsko', 'Skica mladi', 'draft');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(400)]);

        $this->get(route('competitions.index'))->assertRedirect(route('login'));

        $hub = $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Konkursi za podršku preduzetništvu', $hub);
        $this->assertStringNotContainsString($zensko->title, $hub);
        $this->assertStringNotContainsString($omladinsko->title, $hub);
        $this->assertStringNotContainsString($draft->title, $hub);
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $hub);

        $youth = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($omladinsko->title, $youth);
        $this->assertStringNotContainsString($zensko->title, $youth);
        $this->assertStringNotContainsString($draft->title, $youth);
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $youth);

        $women = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($zensko->title, $women);
        $this->assertStringNotContainsString($omladinsko->title, $women);
        $this->assertStringNotContainsString($draft->title, $women);
        $this->assertStringContainsString(route('competitions.guide.pdf', absolute: false), $women);
    }

    public function test_draft_omladinsko_is_not_public_and_does_not_accept_applications(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(401)]);
        $draft = $this->openCompetition('omladinsko', 'Skica mladi javno', 'draft');
        $zensko = $this->openCompetition('zensko', 'Poziv žene javni');

        $this->get(route('competitions.show', $draft))->assertRedirect(route('login'));
        $this->get(route('competitions.show', $zensko))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('competitions.show', $draft))->assertNotFound();
        $this->actingAs($user)->get(route('competitions.show', $zensko))
            ->assertOk()
            ->assertSee($zensko->title)
            ->assertSee('ženskom preduzetništvu')
            ->assertSee('Uputstvo za podnosioce (PDF)');

        $this->actingAs($user)
            ->from(route('competitions.index'))
            ->post(route('applications.start', $draft), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ])
            ->assertNotFound();
        $this->assertSame(0, Application::query()->count());

        $this->actingAs($user)
            ->get(route('applications.create', $draft))
            ->assertNotFound();
        $this->assertSame(0, Application::query()->count());

        $this->actingAs($user)
            ->from(route('competitions.index'))
            ->post(route('applications.store', $draft), $this->draftPayload([
                'applicant_type' => 'fizicko_lice',
            ]))
            ->assertRedirect(route('competitions.index'))
            ->assertSessionHasErrors('error');
        $this->assertSame(0, Application::query()->count());
    }

    public function test_active_omladinsko_catalog_enables_public_flow_without_controller_change(): void
    {
        $omladinsko = $this->openCompetition('omladinsko', 'Poziv mladi aktivan');
        $zensko = $this->openCompetition('zensko', 'Poziv žene ostaje');
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(402)]);

        $this->get(route('competitions.index'))->assertRedirect(route('login'));

        $hub = $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($omladinsko->title, $hub);
        $this->assertStringNotContainsString($zensko->title, $hub);
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $hub);

        $youthList = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($omladinsko->title, $youthList);
        $this->assertStringNotContainsString($zensko->title, $youthList);
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $youthList);

        $womenList = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($zensko->title, $womenList);
        $this->assertStringNotContainsString($omladinsko->title, $womenList);
        $this->assertStringContainsString(route('competitions.guide.pdf', absolute: false), $womenList);

        $omladinskoShow = $this->actingAs($user)
            ->get(route('competitions.show', $omladinsko))
            ->assertOk()
            ->assertSee($omladinsko->title)
            ->assertSee('preduzetništvu mladih')
            ->assertDontSee('Uputstvo za podnosioce (PDF)')
            ->getContent();
        $this->assertStringNotContainsString(route('competitions.guide.pdf', absolute: false), $omladinskoShow);

        $this->actingAs($user)
            ->get(route('competitions.show', $zensko))
            ->assertOk()
            ->assertSee('Uputstvo za podnosioce (PDF)');

        $start = $this->actingAs($user)
            ->post(route('applications.start', $omladinsko), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ]);
        $start->assertRedirect();
        $query = [];
        parse_str((string) parse_url((string) $start->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('start_token', $query);
        $this->assertSame(0, Application::query()->count());
    }

    public function test_unpublished_upcoming_closed_and_completed_omladinsko_calls_do_not_accept_applications(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(403)]);
        $upcoming = $this->openCompetition('omladinsko', 'Poziv mladi budući');
        $upcoming->update([
            'start_date' => now()->addDays(5)->toDateString(),
            'published_at' => now(),
        ]);
        $closed = $this->openCompetition('omladinsko', 'Poziv mladi zatvoren', 'closed');
        $completed = $this->openCompetition('omladinsko', 'Poziv mladi zavrsen', 'completed');

        $index = $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($upcoming->title, $index);
        $this->assertStringNotContainsString($closed->title, $index);
        $this->assertStringNotContainsString($completed->title, $index);

        $youthList = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($upcoming->title, $youthList);
        $this->assertStringNotContainsString($closed->title, $youthList);
        $this->assertStringNotContainsString($completed->title, $youthList);

        $this->actingAs($user)->get(route('competitions.show', $upcoming))->assertNotFound();
        $this->actingAs($user)->get(route('competitions.show', $closed))->assertNotFound();
        $this->actingAs($user)->get(route('competitions.show', $completed))->assertNotFound();

        $this->actingAs($user)
            ->post(route('applications.start', $upcoming), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ])
            ->assertRedirect(route('competitions.show', $upcoming))
            ->assertSessionHasErrors('error');
        $this->assertSame(
            'Konkurs još nije počeo. Prijave će biti moguće od datuma početka konkursa.',
            session('errors')->first('error')
        );
        $this->assertSame(0, Application::query()->count());

        $this->actingAs($user)
            ->post(route('applications.start', $closed), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ])
            ->assertNotFound();
        $this->assertSame(0, Application::query()->count());

        $this->actingAs($user)
            ->post(route('applications.start', $completed), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ])
            ->assertNotFound();
        $this->assertSame(0, Application::query()->count());
    }

    public function test_activation_does_not_create_second_call_or_mutate_existing_call_fields(): void
    {
        $first = $this->openCompetition('omladinsko', 'Prvi poziv za fingerprint', 'draft');
        $first->forceFill([
            'call_number' => 1,
            'budget' => '100000.00',
            'annual_budget' => '100000.00',
        ])->save();
        $snapshot = $first->fresh()->only(['budget', 'annual_budget', 'call_number', 'status']);

        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertSame(1, Competition::query()->where('type', 'omladinsko')->count());
        $this->assertSame(0, Competition::query()->where('type', 'omladinsko')->where('call_number', 2)->count());
        $this->assertSame(0, Application::query()->count());
        $this->assertSame($snapshot, $first->fresh()->only(['budget', 'annual_budget', 'call_number', 'status']));
        $this->assertSame('draft', $first->fresh()->status);
        $this->assertNotSame('published', $first->fresh()->status);
    }

    public function test_zensko_pdf_route_remains_available(): void
    {
        $this->get(route('competitions.guide.pdf'))->assertRedirect(route('login'));

        $user = $this->makeKorisnik(['jmb' => $this->validJmb(399)]);
        $response = $this->actingAs($user)->get(route('competitions.guide.pdf'));
        if ($response->getStatusCode() === 200) {
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

            return;
        }

        $response->assertNotFound();
        $this->assertStringContainsString(
            'PDF uputstvo nije pronađeno.',
            (string) $response->exception?->getMessage()
        );
    }

    public function test_flow_a_unregistered_future_entrepreneur_stores_fizicko_lice_m1a(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(501)]);
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertStringContainsString('Obrazac 1a', $html);
        $this->assertStringContainsString('id="kn_locked_applicant_type"', $html);
        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="fizicko_lice"/', $html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'fizicko_lice',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertNull($application->company_legal_form);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertSame('omladinsko', $application->competition->type);
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

    #[DataProvider('plannedCompanyProvider')]
    public function test_flow_b_planned_company_stores_privredno_drustvo_without_company_block(string $form, string $label): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(510 + ord($form[0]))]);
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => $form,
        ]);

        $html = $this->createHtml($user, $competition, $token);
        $this->assertStringContainsString('Obrazac 1b', $html);
        $this->assertCompanyBlockInputsAbsent($html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => $label,
                'doo_jmbg' => $user->jmb,
                'doo_name' => 'Nosilac plana '.$form,
                'doo_phone' => '+38267111001',
                'doo_email' => $form.'-omladinsko@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'accuracy_declaration' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['crps_number', 'pib', 'founder_name', 'director_name', 'company_seat']);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('privredno_drustvo', $application->applicant_type);
        $this->assertSame($form, $application->company_legal_form);
        $this->assertFalse($application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertNull($application->pib);
        $this->assertNull($application->crps_number);
        $this->assertNull($application->founder_name);
        $this->assertNull($application->director_name);
        $this->assertNull($application->company_seat);
        $this->assertTrue($application->isObrazacComplete());
    }

    public function test_flow_c_registered_entrepreneur_stores_preduzetnik_and_locks_stage(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(520),
            'jmb' => $this->validJmb(521),
        ]);
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'razvoj']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="preduzetnik"/', $html);
        $this->assertStringContainsString('Obrazac 1a', $html);
        $this->assertStringContainsString('>Preduzetnik<', $html);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnik',
                'registration_form' => UserType::ENTREPRENEUR,
                'business_stage' => 'započinjanje',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_stage');

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnik',
                'registration_form' => UserType::ENTREPRENEUR,
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('preduzetnik', $application->applicant_type);
        $this->assertNull($application->company_legal_form);
        $this->assertTrue($application->is_registered);
        $this->assertSame('razvoj', $application->business_stage);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function registeredCompanyProvider(): array
    {
        return [
            'doo' => [UserType::LIMITED_LIABILITY_COMPANY, 'doo', UserType::LIMITED_LIABILITY_COMPANY],
            'ad' => [UserType::JOINT_STOCK_COMPANY, 'ad', UserType::JOINT_STOCK_COMPANY],
            'od' => [UserType::GENERAL_PARTNERSHIP, 'od', UserType::GENERAL_PARTNERSHIP],
            'kd' => [UserType::LIMITED_PARTNERSHIP, 'kd', UserType::LIMITED_PARTNERSHIP],
        ];
    }

    #[DataProvider('registeredCompanyProvider')]
    public function test_flow_d_registered_company_requires_company_block(string $userType, string $form, string $label): void
    {
        $user = $this->makeKorisnik([
            'user_type' => $userType,
            'pib' => $this->validPib(530 + ord($form[0])),
            'company_name' => 'Registrovano '.$form,
            'residential_status' => null,
            'jmb' => $this->validJmb(530 + ord($form[0])),
        ]);
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertStringContainsString('Obrazac 1b', $html);
        $this->assertCompanyBlockInputsPresent($html);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => $label,
                'doo_jmbg' => $user->jmb,
                'doo_name' => 'Nosilac '.$form,
                'doo_phone' => '+38267111002',
                'doo_email' => $form.'-reg@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'accuracy_declaration' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['crps_number', 'pib', 'founder_name', 'director_name', 'company_seat']);

        $crps = $this->validCrps(6, 50 + ord($form[0]));
        $pib = $this->validPib(540 + ord($form[0]));
        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => $label,
                'doo_jmbg' => $user->jmb,
                'doo_name' => 'Nosilac '.$form,
                'doo_phone' => '+38267111002',
                'doo_email' => $form.'-reg@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'founder_name' => 'Osnivač '.$form,
                'director_name' => 'Direktor '.$form,
                'company_seat' => 'Sjedište 8, Kotor',
                'crps_number' => $crps,
                'pib' => $pib,
                'accuracy_declaration' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['crps_number', 'pib', 'founder_name']);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('privredno_drustvo', $application->applicant_type);
        $this->assertSame($form, $application->company_legal_form);
        $this->assertTrue($application->is_registered);
        $this->assertSame($crps, $application->crps_number);
        $this->assertSame($pib, $application->pib);
        $this->assertSame('Osnivač '.$form, $application->founder_name);
        $this->assertSame('Direktor '.$form, $application->director_name);
        $this->assertSame('Sjedište 8, Kotor', $application->company_seat);
        $this->assertTrue($application->isObrazacComplete());
    }

    public function test_ostalo_is_forbidden_only_for_omladinsko(): void
    {
        $omladinskoUser = $this->makeKorisnik(['jmb' => $this->validJmb(550)]);
        $omladinsko = $this->openCompetition('omladinsko');
        $omladinskoToken = $this->issueStartToken($omladinskoUser, $omladinsko, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        $this->actingAs($omladinskoUser)
            ->from(route('applications.create', $omladinsko))
            ->post(route('applications.store', $omladinsko), $this->draftPayload([
                'start_context_token' => $omladinskoToken,
                'applicant_type' => 'ostalo',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('applicant_type');
        $this->assertSame(0, Application::query()->where('competition_id', $omladinsko->id)->count());

        $zenskoUser = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Test',
            'pib' => $this->validPib(551),
            'jmb' => $this->validJmb(552),
        ]);
        $zensko = $this->openCompetition('zensko');
        $zenskoToken = $this->issueStartToken($zenskoUser, $zensko, ['business_stage' => 'započinjanje']);

        $this->actingAs($zenskoUser)
            ->post(route('applications.store', $zensko), $this->draftPayload([
                'start_context_token' => $zenskoToken,
                'applicant_type' => 'ostalo',
                'registration_form' => UserType::NGO_ASSOCIATION,
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $zenskoApplication = Application::query()->where('user_id', $zenskoUser->id)->firstOrFail();
        $this->assertSame('ostalo', $zenskoApplication->applicant_type);
        $this->assertNull($zenskoApplication->company_legal_form);
        $this->assertSame(UserType::NGO_ASSOCIATION, $zenskoApplication->registration_form);
    }

    public function test_zensko_ostalo_store_keeps_canonical_registration_form_without_privredno_drustvo_rules(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Store',
            'pib' => $this->validPib(555),
            'jmb' => $this->validJmb(556),
        ]);
        $competition = $this->openCompetition('zensko');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);
        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="ostalo"/', $html);
        $this->assertCompanyBlockInputsPresent($html);

        $crps = $this->validCrps(7, 56);
        $pib = $this->validPib(556);
        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'ostalo',
                'registration_form' => UserType::NGO_ASSOCIATION,
                'doo_jmbg' => $user->jmb,
                'doo_name' => 'Nosilac NVO',
                'doo_phone' => '+38267111003',
                'doo_email' => 'nvo-ostalo@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'founder_name' => 'Osnivač NVO',
                'director_name' => 'Direktor NVO',
                'company_seat' => 'Sjedište 8, Kotor',
                'crps_number' => $crps,
                'pib' => $pib,
                'accuracy_declaration' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['registration_form', 'applicant_type', 'company_legal_form']);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('ostalo', $application->applicant_type);
        $this->assertNull($application->company_legal_form);
        $this->assertSame(UserType::NGO_ASSOCIATION, $application->registration_form);
        $this->assertNotSame('privredno_drustvo', $application->applicant_type);
        $this->assertTrue($application->is_registered);
        $this->assertTrue($application->isObrazacComplete());
    }

    public function test_zensko_ostalo_does_not_use_omladinsko_m1b_registration_default(): void
    {
        $zenskoUser = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Test',
            'pib' => $this->validPib(553),
            'jmb' => $this->validJmb(554),
        ]);
        $zensko = $this->openCompetition('zensko');
        $token = $this->issueStartToken($zenskoUser, $zensko, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($zenskoUser, $zensko, $token);

        $this->assertStringContainsString('function knUsesCompanyRegistrationDefault(type)', $html);
        $this->assertStringContainsString("return type === 'doo' || type === 'privredno_drustvo';", $html);
        $this->assertStringContainsString('} else if (knUsesCompanyRegistrationDefault(selectedType)) {', $html);
        $this->assertStringContainsString("return type === 'doo' || type === 'ostalo' || type === 'privredno_drustvo';", $html);
        $this->assertDoesNotMatchRegularExpression(
            '/function setRegistrationForm\(\) \{[\s\S]*else if \(knIsM1b\(selectedType\)\) \{/',
            $html
        );
        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="ostalo"/', $html);
        $this->assertStringContainsString('Obrazac 1b', $html);
        $this->assertStringContainsString(UserType::NGO_ASSOCIATION, $html);
    }

    public function test_unsupported_omladinsko_identity_does_not_create_draft(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Mladi',
            'pib' => $this->validPib(560),
            'jmb' => $this->validJmb(561),
        ]);
        $competition = $this->openCompetition('omladinsko');

        $this->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('applications.start', $competition), ['business_stage' => 'započinjanje'])
            ->assertRedirect()
            ->assertSessionHasErrors('planned_intent');

        $this->assertSame(0, Application::query()->count());

        $show = $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(
            KnApplicationStartContextFactory::UNSUPPORTED_OMLADINSKO_IDENTITY_MESSAGE,
            $show
        );
        $this->assertStringNotContainsString('Prijavi se na konkurs', $show);
    }

    public function test_live_identity_change_does_not_rewrite_locked_omladinsko_draft(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(570)]);
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'fizicko_lice',
            ]))
            ->assertRedirect();

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $user->update([
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(571),
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition).'?application_id='.$application->id)
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="fizicko_lice"/', $html);
        $this->assertStringContainsString('Obrazac 1a', $html);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'preduzetnik',
                'registration_form' => UserType::ENTREPRENEUR,
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $application->refresh();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertFalse((bool) $application->is_registered);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertNull($application->company_legal_form);
    }

    public function test_submitted_omladinsko_application_keeps_locked_classification(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(580)]);
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Podneseno',
            'applicant_type' => 'fizicko_lice',
            'company_legal_form' => null,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->draftPayload([
                'application_id' => $application->id,
                'applicant_type' => 'privredno_drustvo',
                'planned_company_form' => 'doo',
                'business_stage' => 'razvoj',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $application->refresh();
        $this->assertSame('fizicko_lice', $application->applicant_type);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('započinjanje', $application->business_stage);
    }

    public function test_existing_zensko_applications_remain_unchanged_beside_omladinsko_draft(): void
    {
        $zenskoUser = $this->makeKorisnik(['jmb' => $this->validJmb(590)]);
        $zensko = $this->openCompetition('zensko');
        $zenskoApplication = Application::create([
            'competition_id' => $zensko->id,
            'user_id' => $zenskoUser->id,
            'business_plan_name' => 'Ženski nacrt',
            'applicant_type' => 'preduzetnica',
            'company_legal_form' => null,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::ENTREPRENEUR,
            'is_registered' => false,
            'status' => 'draft',
        ]);

        $omladinskoUser = $this->makeKorisnik(['jmb' => $this->validJmb(591)]);
        $omladinsko = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($omladinskoUser, $omladinsko, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'kd',
        ]);
        $this->actingAs($omladinskoUser)
            ->post(route('applications.store', $omladinsko), $this->draftPayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => UserType::LIMITED_PARTNERSHIP,
            ]))
            ->assertRedirect();

        $zenskoApplication->refresh();
        $this->assertSame('preduzetnica', $zenskoApplication->applicant_type);
        $this->assertNull($zenskoApplication->company_legal_form);
        $this->assertSame('zensko', $zenskoApplication->competition->type);

        $omladinskoApplication = Application::query()->where('user_id', $omladinskoUser->id)->firstOrFail();
        $this->assertSame('privredno_drustvo', $omladinskoApplication->applicant_type);
        $this->assertSame('kd', $omladinskoApplication->company_legal_form);
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
    private function finalStorePayload(array $overrides = []): array
    {
        $payload = $this->draftPayload($overrides);
        unset($payload['save_as_draft']);

        return $payload;
    }

    private function visiblePageText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function programCardHtml(string $html, string $type): string
    {
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $type);

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query(
            '//a[contains(concat(" ", normalize-space(@class), " "), " program-card ") and contains(@href, "type='.$type.'")]'
        );
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, 'Očekivana je tačno jedna kartica profila '.$type);
        $card = $dom->saveHTML($nodes->item(0));
        $this->assertIsString($card);
        $this->assertNotSame('', trim($card));

        return $card;
    }

    private function assertCompanyBlockInputsAbsent(string $html): void
    {
        foreach (['pib', 'crps_number', 'company_seat', 'founder_name', 'director_name'] as $name) {
            $this->assertDoesNotMatchRegularExpression(
                '/<input[^>]*name="'.preg_quote($name, '/').'"/',
                $html,
                'Company-block input '.$name.' must not be rendered for a planned company.'
            );
        }
    }

    private function assertCompanyBlockInputsPresent(string $html): void
    {
        foreach (['pib', 'crps_number', 'company_seat', 'founder_name', 'director_name'] as $name) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*name="'.preg_quote($name, '/').'"/',
                $html,
                'Company-block input '.$name.' must be rendered for a registered company.'
            );
        }
    }

    private function openCompetition(string $type, ?string $title = null, string $status = 'published'): Competition
    {
        return Competition::create([
            'title' => $title ?? ('KN omladinsko '.$type),
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => $status,
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => $status === 'published' ? now()->subDays(2) : null,
        ]);
    }
}
