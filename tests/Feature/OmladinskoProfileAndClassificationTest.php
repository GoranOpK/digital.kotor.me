<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Models\Application;
use App\Models\Competition;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Services\KnApplicationStartContextFactory;
use App\Support\CompetitionProgramCatalog;
use App\Support\KnApplicationClassification;
use App\Support\KnApplicationStartContext;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class OmladinskoProfileAndClassificationTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
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

    public function test_completed_pm_m2_notice_uses_classified_form_and_zensko_keeps_1a_1b(): void
    {
        $m1aMessage = 'Obrazac M1a je kompletno popunjen. Nastavite sa popunjavanjem Obrasca M2 – Biznis plan.';
        $m1bMessage = 'Obrazac M1b je kompletno popunjen. Nastavite sa popunjavanjem Obrasca M2 – Biznis plan.';
        $zenskoMessage = 'Obrazac 1a/1b je kompletno popunjen. Sada popunite biznis plan.';

        $m1aUser = $this->makeKorisnik(['jmb' => $this->validJmb(610)]);
        $m1aCompetition = $this->openCompetition('omladinsko');
        $m1aToken = $this->issueStartToken($m1aUser, $m1aCompetition, [
            'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
        ]);
        $m1aStore = $this->actingAs($m1aUser)
            ->post(route('applications.store', $m1aCompetition), $this->finalStorePayload([
                'start_context_token' => $m1aToken,
                'applicant_type' => 'fizicko_lice',
                'physical_person_name' => 'Ana Anić',
                'physical_person_jmbg' => $m1aUser->jmb,
                'physical_person_phone' => '+38267111011',
                'physical_person_email' => 'm1a@example.test',
                'physical_person_address' => 'Njegoševa 1, Kotor',
                'accuracy_declaration' => '1',
            ]));
        $m1aApplication = Application::query()->where('user_id', $m1aUser->id)->firstOrFail();
        $this->assertSame('fizicko_lice', $m1aApplication->applicant_type);
        $this->assertSame(
            'Obrazac M1a',
            KnApplicationClassification::applicationFormDisplayName($m1aApplication->applicant_type, 'omladinsko')
        );
        $this->assertTrue($m1aApplication->isObrazacComplete());
        $m1aStore->assertRedirect(route('applications.business-plan.create', $m1aApplication));
        $m1aStore->assertSessionHas('success', $m1aMessage);
        $this->actingAs($m1aUser)
            ->get(route('applications.business-plan.create', $m1aApplication))
            ->assertOk()
            ->assertSee($m1aMessage, false)
            ->assertDontSee($m1bMessage, false)
            ->assertDontSee($zenskoMessage, false);

        $m1bUser = $this->makeKorisnik(['jmb' => $this->validJmb(611)]);
        $m1bCompetition = $this->openCompetition('omladinsko');
        $m1bToken = $this->issueStartToken($m1bUser, $m1bCompetition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'doo',
        ]);
        $m1bStore = $this->actingAs($m1bUser)
            ->post(route('applications.store', $m1bCompetition), $this->finalStorePayload([
                'start_context_token' => $m1bToken,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'doo_jmbg' => $m1bUser->jmb,
                'doo_name' => 'Nosilac plana doo',
                'doo_phone' => '+38267111012',
                'doo_email' => 'm1b@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'accuracy_declaration' => '1',
            ]));
        $m1bApplication = Application::query()->where('user_id', $m1bUser->id)->firstOrFail();
        $this->assertSame('privredno_drustvo', $m1bApplication->applicant_type);
        $this->assertSame(
            'Obrazac M1b',
            KnApplicationClassification::applicationFormDisplayName($m1bApplication->applicant_type, 'omladinsko')
        );
        $this->assertTrue($m1bApplication->isObrazacComplete());
        $m1bStore->assertRedirect(route('applications.business-plan.create', $m1bApplication));
        $m1bStore->assertSessionHas('success', $m1bMessage);
        $this->actingAs($m1bUser)
            ->get(route('applications.business-plan.create', $m1bApplication))
            ->assertOk()
            ->assertSee($m1bMessage, false)
            ->assertDontSee($m1aMessage, false)
            ->assertDontSee($zenskoMessage, false);

        $zenskoUser = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Poruka',
            'pib' => $this->validPib(670),
            'jmb' => $this->validJmb(671),
        ]);
        $zensko = $this->openCompetition('zensko');
        $zenskoToken = $this->issueStartToken($zenskoUser, $zensko, ['business_stage' => 'započinjanje']);
        $zenskoStore = $this->actingAs($zenskoUser)
            ->post(route('applications.store', $zensko), $this->finalStorePayload([
                'start_context_token' => $zenskoToken,
                'applicant_type' => 'ostalo',
                'registration_form' => UserType::NGO_ASSOCIATION,
                'doo_jmbg' => $zenskoUser->jmb,
                'doo_name' => 'Nosilac NVO',
                'doo_phone' => '+38267111013',
                'doo_email' => 'zp-notice@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'founder_name' => 'Osnivač NVO',
                'director_name' => 'Direktor NVO',
                'company_seat' => 'Sjedište 8, Kotor',
                'crps_number' => $this->validCrps(7, 70),
                'pib' => $this->validPib(671),
                'accuracy_declaration' => '1',
            ]));
        $zenskoApplication = Application::query()->where('user_id', $zenskoUser->id)->firstOrFail();
        $this->assertSame('ostalo', $zenskoApplication->applicant_type);
        $this->assertSame(
            'Obrazac 1b',
            KnApplicationClassification::applicationFormDisplayName($zenskoApplication->applicant_type, 'zensko')
        );
        $this->assertTrue($zenskoApplication->isObrazacComplete());
        $zenskoStore->assertRedirect(route('applications.business-plan.create', $zenskoApplication));
        $zenskoStore->assertSessionHas('success', $zenskoMessage);
        $this->actingAs($zenskoUser)
            ->get(route('applications.business-plan.create', $zenskoApplication))
            ->assertOk()
            ->assertSee($zenskoMessage, false)
            ->assertDontSee('Obrazac M1a je kompletno popunjen', false)
            ->assertDontSee('Obrazac M1b je kompletno popunjen', false)
            ->assertDontSee('Obrasca M2', false);
    }

    public function test_physical_person_planning_doo_sees_m1b_notice_from_the_selected_form(): void
    {
        $m1aMessage = 'Obrazac M1a je kompletno popunjen. Nastavite sa popunjavanjem Obrasca M2 – Biznis plan.';
        $m1bMessage = 'Obrazac M1b je kompletno popunjen. Nastavite sa popunjavanjem Obrasca M2 – Biznis plan.';

        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'jmb' => $this->validJmb(612),
        ]);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $registration = KnApplicationClassification::fromUserType($user->user_type, 'omladinsko');
        $this->assertSame(KnApplicationClassification::FORM_FIZICKO_LICE, $registration->defaultFormApplicantType());
        $this->assertSame(
            'Obrazac M1a',
            KnApplicationClassification::applicationFormDisplayName($registration->defaultFormApplicantType(), 'omladinsko')
        );

        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, [
            'planned_intent' => KnApplicationStartContext::INTENT_PLANNED_COMPANY,
            'planned_company_form' => 'doo',
        ]);
        $form = $this->createHtml($user, $competition, $token);
        $this->assertMatchesRegularExpression('/id="kn_locked_applicant_type"[^>]*value="privredno_drustvo"/', $form);
        $this->assertStringContainsString('id="obrazacLabelHeader">Obrazac M1b</div>', $form);
        $this->assertStringNotContainsString('id="obrazacLabelHeader">Obrazac M1a</div>', $form);

        $store = $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'doo_jmbg' => $user->jmb,
                'doo_name' => 'Planirano DOO',
                'doo_phone' => '+38267111014',
                'doo_email' => 'planirano-doo@example.test',
                'doo_address' => 'Njegoševa 12, Kotor',
                'accuracy_declaration' => '1',
            ]));

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->fresh()->user_type);
        $this->assertSame('privredno_drustvo', $application->applicant_type);
        $this->assertSame('doo', $application->company_legal_form);
        $this->assertSame('započinjanje', $application->business_stage);
        $this->assertFalse($application->is_registered);
        $this->assertTrue($application->isObrazacComplete());
        $this->assertSame(
            'Obrazac M1b',
            KnApplicationClassification::applicationFormDisplayName($application->applicant_type, 'omladinsko')
        );
        $this->assertNotSame(
            KnApplicationClassification::applicationFormDisplayName($registration->defaultFormApplicantType(), 'omladinsko'),
            KnApplicationClassification::applicationFormDisplayName($application->applicant_type, 'omladinsko')
        );

        $store->assertRedirect(route('applications.business-plan.create', $application));
        $store->assertSessionHas('success', $m1bMessage);
        $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->assertSee($m1bMessage, false)
            ->assertDontSee($m1aMessage, false);
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

    public function test_pm_company_form_keeps_officer_names_distinct_from_the_company(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'name' => 'Stefan doo',
            'company_name' => 'Stefan doo',
            'pib' => $this->validPib(880),
            'residential_status' => null,
            'jmb' => $this->validJmb(880),
        ]);
        $this->attachLegalName($user, 'Stefan doo');
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertSame('', $this->inputValue($html, 'founder_name'));
        $this->assertSame('', $this->inputValue($html, 'director_name'));
        $this->assertStringNotContainsString('checked', $this->inputTag($html, 'registered_user_is_founder'));
        $this->assertStringNotContainsString('checked', $this->inputTag($html, 'registered_user_is_director'));
        $this->assertStringNotContainsString('value="Stefan doo"', $this->inputTag($html, 'founder_name'));
        $this->assertStringNotContainsString('value="Stefan doo"', $this->inputTag($html, 'director_name'));

        $base = [
            'start_context_token' => $token,
            'applicant_type' => 'privredno_drustvo',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'doo_jmbg' => $user->jmb,
            'doo_name' => 'Nosilac prijave',
            'doo_phone' => '+38267111002',
            'doo_email' => 'pm-officer@example.test',
            'doo_address' => 'Njegoševa 12, Kotor',
            'company_seat' => 'Sjedište 8, Kotor',
            'crps_number' => $this->validCrps(6, 880),
            'pib' => $this->validPib(881),
            'accuracy_declaration' => '1',
        ];

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload($base))
            ->assertRedirect()
            ->assertSessionHasErrors(['founder_name', 'director_name']);
        $this->assertNull(Application::query()->where('user_id', $user->id)->first());

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => '  stefan   DOO ',
                'director_name' => 'Ana Anić',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'founder_name' => YouthDecisionApplicantIdentity::COMPANY_NAME_AS_FOUNDER,
            ]);
        $this->assertNull(Application::query()->where('user_id', $user->id)->first());

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => 'Ana Anić',
                'director_name' => 'stefan doo',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'director_name' => YouthDecisionApplicantIdentity::COMPANY_NAME_AS_DIRECTOR,
            ]);
        $this->assertNull(Application::query()->where('user_id', $user->id)->first());

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => 'Ana Anić',
                'director_name' => 'Ana Anić',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['founder_name', 'director_name']);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Ana Anić', $application->founder_name);
        $this->assertSame('Ana Anić', $application->director_name);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => 'Ana Anić',
                'director_name' => 'Marko Marković',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['founder_name', 'director_name']);

        $application->refresh();
        $this->assertSame('Ana Anić', $application->founder_name);
        $this->assertSame('Marko Marković', $application->director_name);

        $savedHtml = $this->createHtml($user, $competition, $token);
        $this->assertSame('Ana Anić', $this->inputValue($savedHtml, 'founder_name'));
        $this->assertSame('Marko Marković', $this->inputValue($savedHtml, 'director_name'));

        $zenskoUser = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'first_name' => 'Ženska',
            'last_name' => 'Firma',
            'name' => 'Ženska Firma',
            'company_name' => 'Ženska Firma',
            'pib' => $this->validPib(882),
            'residential_status' => null,
            'jmb' => $this->validJmb(882),
        ]);
        $this->attachLegalName($zenskoUser, 'Ženska Firma');
        $zensko = $this->openCompetition('zensko');
        $zenskoToken = $this->issueStartToken($zenskoUser, $zensko, ['business_stage' => 'započinjanje']);
        $zenskoHtml = $this->createHtml($zenskoUser, $zensko, $zenskoToken);
        $this->assertSame('Ženska Firma', $this->inputValue($zenskoHtml, 'founder_name'));
        $this->assertSame('Ženska Firma', $this->inputValue($zenskoHtml, 'director_name'));
        $this->assertSame(0, preg_match('/<input\b[^>]*\bname="registered_user_is_founder"/', $zenskoHtml));
        $this->assertSame(0, preg_match('/<input\b[^>]*\bname="registered_user_is_director"/', $zenskoHtml));
    }

    public function test_pm_doo_roles_copy_the_authorized_person_only_when_confirmed(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'first_name' => null,
            'last_name' => null,
            'name' => 'Stefan doo',
            'company_name' => 'Stefan doo',
            'pib' => $this->validPib(890),
            'residential_status' => null,
            'jmb' => $this->validJmb(890),
        ]);
        $legal = $this->attachLegalName($user, 'Stefan doo');
        $this->attachAuthorizedPerson($legal, 'Stefan', 'Stefanović');
        $competition = $this->openCompetition('omladinsko');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);
        $html = $this->createHtml($user, $competition, $token);

        $this->assertSame('', $this->inputValue($html, 'founder_name'));
        $this->assertSame('', $this->inputValue($html, 'director_name'));
        $this->assertStringNotContainsString('checked', $this->inputTag($html, 'registered_user_is_founder'));
        $this->assertStringNotContainsString('checked', $this->inputTag($html, 'registered_user_is_director'));
        $this->assertStringContainsString('data-person-name="Stefan Stefanović"', $this->inputTag($html, 'registered_user_is_founder'));
        $this->assertStringNotContainsString('value="Stefan doo"', $this->inputTag($html, 'founder_name'));

        $base = [
            'start_context_token' => $token,
            'applicant_type' => 'privredno_drustvo',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'doo_jmbg' => $user->jmb,
            'doo_name' => 'Nosilac prijave',
            'doo_phone' => '+38267111003',
            'doo_email' => 'pm-role@example.test',
            'doo_address' => 'Njegoševa 12, Kotor',
            'company_seat' => 'Sjedište 8, Kotor',
            'crps_number' => $this->validCrps(6, 890),
            'pib' => $this->validPib(891),
            'accuracy_declaration' => '1',
        ];

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'registered_user_is_founder' => '1',
                'registered_user_is_director' => '1',
                'founder_name' => 'Stefan doo',
                'director_name' => 'Stefan doo',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['founder_name', 'director_name']);

        $application = Application::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Stefan Stefanović', $application->founder_name);
        $this->assertSame('Stefan Stefanović', $application->director_name);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => 'Marko Marković',
                'director_name' => 'Jovo Jović',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['founder_name', 'director_name']);

        $application->refresh();
        $this->assertSame('Marko Marković', $application->founder_name);
        $this->assertSame('Jovo Jović', $application->director_name);

        $savedHtml = $this->createHtml($user, $competition, $token);
        $this->assertSame('Marko Marković', $this->inputValue($savedHtml, 'founder_name'));
        $this->assertSame('Jovo Jović', $this->inputValue($savedHtml, 'director_name'));
        $this->assertStringNotContainsString('checked', $this->inputTag($savedHtml, 'registered_user_is_founder'));
        $this->assertStringNotContainsString('checked', $this->inputTag($savedHtml, 'registered_user_is_director'));

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'founder_name' => 'Stefan doo',
                'director_name' => 'Jovo Jović',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'founder_name' => YouthDecisionApplicantIdentity::COMPANY_NAME_AS_FOUNDER,
            ]);
        $application->refresh();
        $this->assertSame('Marko Marković', $application->founder_name);
        $this->assertSame('Jovo Jović', $application->director_name);

        $this->actingAs($user)
            ->post(route('applications.store', $competition), $this->finalStorePayload($base + [
                'registered_user_is_founder' => '1',
                'founder_name' => 'Stefan doo',
                'director_name' => 'Jovo Jović',
            ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors(['founder_name', 'director_name']);
        $application->refresh();
        $this->assertSame('Stefan Stefanović', $application->founder_name);
        $this->assertSame('Jovo Jović', $application->director_name);
    }

    public function test_locked_pm_application_keeps_scores_and_officer_names(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'first_name' => null,
            'last_name' => null,
            'name' => 'Stefan doo',
            'company_name' => 'Stefan doo',
            'pib' => $this->validPib(892),
            'residential_status' => null,
            'jmb' => $this->validJmb(892),
        ]);
        $legal = $this->attachLegalName($user, 'Stefan doo');
        $this->attachAuthorizedPerson($legal, 'Stefan', 'Stefanović');
        $competition = $this->openCompetition('omladinsko');
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'It programiranje',
            'applicant_type' => 'privredno_drustvo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => true,
            'founder_name' => 'Stefan doo',
            'director_name' => 'Stefan doo',
            'status' => 'submitted',
            'final_score' => 42,
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 1500,
        ]);

        $this->actingAs($user)
            ->from(route('applications.create', $competition))
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'application_id' => $application->id,
                'applicant_type' => 'privredno_drustvo',
                'registered_user_is_founder' => '1',
                'registered_user_is_director' => '1',
                'founder_name' => 'Stefan Stefanović',
                'director_name' => 'Stefan Stefanović',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $application->refresh();
        $this->assertSame('Stefan doo', $application->founder_name);
        $this->assertSame('Stefan doo', $application->director_name);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('42.00', $application->final_score);
        $this->assertSame('podrzava_potpuno', $application->commission_decision);
        $this->assertSame('1500.00', $application->approved_amount);
    }

    private function attachLegalName(User $user, string $legalName): LegalEntityIdentity
    {
        $platform = PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);

        return LegalEntityIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => $legalName,
            'street_and_number' => 'Njegoševa 12',
            'city' => 'Kotor',
        ]);
    }

    private function attachAuthorizedPerson(LegalEntityIdentity $legal, string $firstName, string $lastName): void
    {
        LegalEntityAuthorizedPerson::query()->create([
            'legal_entity_identity_id' => $legal->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function inputTag(string $html, string $name): string
    {
        $this->assertSame(1, preg_match(
            '/<input\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>/',
            $html,
            $matches
        ));

        return $matches[0];
    }

    private function inputValue(string $html, string $name): string
    {
        $tag = $this->inputTag($html, $name);
        $this->assertSame(1, preg_match('/\bvalue="([^"]*)"/', $tag, $matches));

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function test_pm_m1a_prefills_crps_from_identity_without_overwriting_a_saved_application(): void
    {
        $identityCrps = $this->validCrps(1, 86);
        $identityPib = $this->validPib(86);
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'jmb' => null,
            'pib' => null,
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'streetAndNumber' => 'Njegoševa 12',
                'city' => 'Kotor',
                'jmb' => $this->validJmb(86),
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Ana',
                'pib' => $identityPib,
                'crpsNumber' => $identityCrps,
            ],
        ]));
        config(['identity.canonical_read' => true]);
        $competition = $this->openCompetition('omladinsko', 'PM CRPS prefill');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);

        $html = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->assertOk()
            ->getContent();

        $m1a = $this->formSectionHtml($html, 'obrazac1a', 'obrazac1b');
        $m1b = $this->formSectionHtml($html, 'obrazac1b', 'additional-data-section');
        $this->assertInputValue($m1a, 'crps_number', $identityCrps);
        $this->assertInputValue($m1a, 'pib', $identityPib);
        $this->assertInputValue($m1b, 'crps_number', $identityCrps);
        $this->assertInputValue($m1b, 'pib', $identityPib);

        $typedCrps = $this->validCrps(1, 87);
        $this->actingAs($user)
            ->from(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'preduzetnik',
                'registration_form' => UserType::ENTREPRENEUR,
                'business_plan_name' => '',
                'crps_number' => $typedCrps,
                'pib' => $identityPib,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_plan_name');

        $oldHtml = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->assertOk()
            ->getContent();
        $this->assertInputValue($this->formSectionHtml($oldHtml, 'obrazac1a', 'obrazac1b'), 'crps_number', $typedCrps);
        $this->assertSame(0, Application::query()->where('user_id', $user->id)->count());

        $savedCrps = $this->validCrps(1, 88);
        $savedPib = $this->validPib(88);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Sačuvan plan',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::ENTREPRENEUR,
            'is_registered' => true,
            'status' => 'draft',
            'crps_number' => $savedCrps,
            'pib' => $savedPib,
        ]);

        $savedHtml = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();
        $savedM1a = $this->formSectionHtml($savedHtml, 'obrazac1a', 'obrazac1b');
        $this->assertInputValue($savedM1a, 'crps_number', $savedCrps);
        $this->assertInputValue($savedM1a, 'pib', $savedPib);
        $this->assertStringNotContainsString('value="'.$identityCrps.'"', $savedM1a);

        $emptyUser = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'jmb' => null,
            'pib' => null,
        ]);
        (new CanonicalIdentityWriter)->createForUser($emptyUser, $this->flSnapshot($emptyUser, [
            'person' => [
                'jmb' => $this->validJmb(89),
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Prazna',
                'pib' => $this->validPib(89),
                'crpsNumber' => $this->validCrps(1, 89),
            ],
        ]));
        $emptyCompetition = $this->openCompetition('omladinsko', 'PM CRPS empty snapshot');
        Application::create([
            'competition_id' => $emptyCompetition->id,
            'user_id' => $emptyUser->id,
            'business_plan_name' => 'Prazan CRPS',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::ENTREPRENEUR,
            'is_registered' => true,
            'status' => 'draft',
            'crps_number' => null,
            'pib' => $this->validPib(90),
        ]);

        $emptyHtml = $this->actingAs($emptyUser)
            ->get(route('applications.create', $emptyCompetition))
            ->assertOk()
            ->getContent();
        $emptyM1a = $this->formSectionHtml($emptyHtml, 'obrazac1a', 'obrazac1b');
        $this->assertInputValue($emptyM1a, 'crps_number', '');
        $this->assertStringNotContainsString('value="'.$this->validCrps(1, 89).'"', $emptyM1a);
    }

    public function test_pm_m1b_prefills_crps_from_legal_entity_without_overwriting_a_saved_application(): void
    {
        $identityCrps = $this->validCrps(5, 91);
        $identityPib = $this->validPib(91);
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'jmb' => null,
            'pib' => null,
            'company_name' => 'Mladi DOO',
            'residential_status' => null,
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user, [
            'jmb' => $this->validJmb(91),
        ], [
            'streetAndNumber' => 'Njegoševa 12',
            'city' => 'Kotor',
            'legalName' => 'Mladi DOO',
            'pib' => $identityPib,
            'crpsNumber' => $identityCrps,
        ]));
        config(['identity.canonical_read' => true]);
        $competition = $this->openCompetition('omladinsko', 'PM M1b CRPS prefill');
        $token = $this->issueStartToken($user, $competition, ['business_stage' => 'započinjanje']);

        $html = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->assertOk()
            ->getContent();

        $m1b = $this->formSectionHtml($html, 'obrazac1b', 'additional-data-section');
        $this->assertInputValue($m1b, 'crps_number', $identityCrps);
        $this->assertInputValue($m1b, 'pib', $identityPib);

        $typedCrps = $this->validCrps(5, 92);
        $this->actingAs($user)
            ->from(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->post(route('applications.store', $competition), $this->finalStorePayload([
                'start_context_token' => $token,
                'applicant_type' => 'privredno_drustvo',
                'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
                'business_plan_name' => '',
                'crps_number' => $typedCrps,
                'pib' => $identityPib,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('business_plan_name');

        $oldHtml = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
            ->assertOk()
            ->getContent();
        $this->assertInputValue($this->formSectionHtml($oldHtml, 'obrazac1b', 'additional-data-section'), 'crps_number', $typedCrps);
        $this->assertSame(0, Application::query()->where('user_id', $user->id)->count());

        $savedCrps = $this->validCrps(5, 93);
        $savedPib = $this->validPib(93);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Sačuvan plan društva',
            'applicant_type' => 'privredno_drustvo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_legal_form' => 'doo',
            'is_registered' => true,
            'status' => 'draft',
            'crps_number' => $savedCrps,
            'pib' => $savedPib,
        ]);

        $savedHtml = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();
        $savedM1b = $this->formSectionHtml($savedHtml, 'obrazac1b', 'additional-data-section');
        $this->assertInputValue($savedM1b, 'crps_number', $savedCrps);
        $this->assertInputValue($savedM1b, 'pib', $savedPib);
        $this->assertStringNotContainsString('value="'.$identityCrps.'"', $savedM1b);

        $emptyUser = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'jmb' => null,
            'pib' => null,
            'residential_status' => null,
        ]);
        (new CanonicalIdentityWriter)->createForUser($emptyUser, $this->plSnapshot($emptyUser, [
            'jmb' => $this->validJmb(94),
        ], [
            'pib' => $this->validPib(94),
            'crpsNumber' => $this->validCrps(5, 94),
        ]));
        $emptyCompetition = $this->openCompetition('omladinsko', 'PM M1b CRPS empty snapshot');
        Application::create([
            'competition_id' => $emptyCompetition->id,
            'user_id' => $emptyUser->id,
            'business_plan_name' => 'Prazan CRPS društva',
            'applicant_type' => 'privredno_drustvo',
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_legal_form' => 'doo',
            'is_registered' => true,
            'status' => 'draft',
            'crps_number' => null,
            'pib' => $this->validPib(95),
        ]);

        $emptyHtml = $this->actingAs($emptyUser)
            ->get(route('applications.create', $emptyCompetition))
            ->assertOk()
            ->getContent();
        $emptyM1b = $this->formSectionHtml($emptyHtml, 'obrazac1b', 'additional-data-section');
        $this->assertInputValue($emptyM1b, 'crps_number', '');
        $this->assertStringNotContainsString('value="'.$this->validCrps(5, 94).'"', $emptyM1b);
    }

    private function formSectionHtml(string $html, string $startId, string $endId): string
    {
        $start = strpos($html, 'id="'.$startId.'"');
        $end = strpos($html, 'id="'.$endId.'"');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $this->assertGreaterThan($start, $end);

        return substr($html, $start, $end - $start);
    }

    private function assertInputValue(string $html, string $name, string $expected): void
    {
        $this->assertMatchesRegularExpression(
            '/name="'.preg_quote($name, '/').'"[^>]*value="'.preg_quote($expected, '/').'"/s',
            $html
        );
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
