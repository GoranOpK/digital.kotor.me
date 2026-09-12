<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\IdentitySnapshot;
use App\Identity\LegalEntitySnapshot;
use App\Identity\Runtime\ExistingSubjectIdentityCompletionService;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use App\Identity\Runtime\ExistingSubjectIdentitySnapshotMapper;
use App\Identity\Runtime\ExistingSubjectIdentityStoredJmb;
use App\Models\Application;
use App\Models\Competition;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Support\UserType;
use App\Security\JmbLookupService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class ExistingSubjectIdentityCompletionTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => false,
            'identity.identity_write_freeze' => false,
            'identity.ep_identity_flows' => false,
        ]);
        $this->assertFalse(config('identity.ep_identity_flows'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->enableCanonicalHttp();

        $this->get(route('identity.completion.create'))->assertRedirect(route('login'));
        $this->post(route('identity.completion.store'), [])->assertRedirect(route('login'));
    }

    public function test_ineligible_actors_are_forbidden(): void
    {
        $this->enableCanonicalHttp();

        $inactive = $this->makeDooUser(['activation_status' => 'deactivated']);
        $staff = $this->makeDooUser(['role_id' => Role::where('name', 'admin')->firstOrFail()->id]);
        $superadmin = $this->makeDooUser(['role_id' => Role::where('name', 'superadmin')->firstOrFail()->id]);
        $fl = $this->makeKorisnik(['jmb' => $this->validJmb($this->jmbSeq++)]);
        $staffFl = $this->makeKorisnik([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'user_type' => UserType::PHYSICAL_PERSON,
            'jmb' => $this->validJmb($this->jmbSeq++),
        ]);
        $unsupported = $this->makeKorisnik([
            'user_type' => UserType::JOINT_STOCK_COMPANY,
            'jmb' => $this->validJmb($this->jmbSeq++),
            'pib' => $this->uniqueValidPib(),
        ]);

        $this->actingAs($inactive)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($superadmin)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($staffFl)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($fl)->get(route('identity.completion.create'))
            ->assertOk()
            ->assertSee('Dopuna podataka');
        $this->actingAs($unsupported)->get(route('identity.completion.create'))->assertForbidden();
    }

    public function test_flags_off_and_freeze_on_fail_closed(): void
    {
        $user = $this->makeDooUser();

        $this->actingAs($user)->get(route('identity.completion.create'))->assertForbidden();

        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => true,
        ]);

        $this->actingAs($user)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($user)->post(route('identity.completion.store'), $this->dooPayload())->assertForbidden();
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_doo_eligible_get_and_two_gets_allowed(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $first = $this->actingAs($user)->get(route('identity.completion.create'));
        $first->assertOk()
            ->assertSee('Dopunite podatke o subjektu')
            ->assertSee('Nalog postoji, ali je potrebno dopuniti podatke o subjektu')
            ->assertSee('Ovo nije nova registracija')
            ->assertSee('Pravno lice')
            ->assertSee('DOO — Društvo sa ograničenom odgovornošću')
            ->assertSee('Puni naziv pravnog lica')
            ->assertSee('Ovlašćeno lice')
            ->assertSee('Ime i prezime nosioca naloga se automatski ne tretiraju kao ovlašćeno lice')
            ->assertSee('Sačuvaj i nastavi')
            ->assertSee('Odustani')
            ->assertSee('Primjer DOO')
            ->assertSee('Crna Gora')
            ->assertDontSee('name="residential_status"', false)
            ->assertDontSee('name="user_type"', false)
            ->assertDontSee('name="subject_type"', false);

        $this->actingAs($user)->get(route('identity.completion.create'))->assertOk();
    }

    public function test_doo_success_creates_canonical_graph_and_does_not_dual_write_legacy_identity(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser([
            'phone' => '+38267000999',
            'address' => 'Stara 1',
            'city' => 'Kotor',
            'company_name' => 'Stari Naziv DOO',
            'pib' => '12345672',
            'first_name' => 'Nosioc',
            'last_name' => 'Naloga',
            'residential_status' => 'resident',
        ]);
        $before = $this->leftoverIdentity($user);

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->dooPayload([
                'legal_name' => 'Novi Naziv DOO',
                'city' => 'Budva',
                'residential_status' => 'resident',
                'subject_type' => 'physical_person',
                'user_type' => UserType::PHYSICAL_PERSON,
                'user_id' => $user->id + 99,
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, LegalEntityIdentity::query()->count());
        $this->assertSame(1, LegalEntityAuthorizedPerson::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());

        $platform = PlatformIdentity::query()->where('user_id', $user->id)->first();
        $this->assertSame(PlatformIdentity::SUBJECT_LEGAL_ENTITY, $platform->subject_type);
        $this->assertSame('+38164123456', $platform->mobile_phone);

        $legal = LegalEntityIdentity::query()->first();
        $this->assertSame(LegalEntityIdentity::FORM_DOO, $legal->legal_form);
        $this->assertSame('Novi Naziv DOO', $legal->legal_name);
        $this->assertSame('12345672', $legal->pib);
        $this->assertSame('50000001', $legal->crps_number);
        $this->assertSame('Budva', $legal->city);

        $ap = LegalEntityAuthorizedPerson::query()->first();
        $this->assertSame('Marko', $ap->first_name);
        $this->assertSame('Marković', $ap->last_name);
        $this->assertSame('jmb', $ap->id_document_type);
        $this->assertSame('0000000000000', $ap->jmb);
        $this->assertNotSame('Nosioc', $ap->first_name);

        $fresh = $user->fresh();
        $this->assertSame(UserType::LIMITED_LIABILITY_COMPANY, $fresh->user_type);
        $this->assertSame($before, $this->leftoverIdentity($fresh));
        $this->assertSame('Stari Naziv DOO', $fresh->company_name);
        $this->assertSame('+38267000999', $fresh->phone);
        $this->assertSame('resident', $fresh->residential_status);
        $this->assertFalse(config('identity.ep_identity_flows'));
    }

    public function test_doo_validation_matrix(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['legal_name' => '']))
            ->assertSessionHasErrors('legal_name');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['pib' => '12345670']))
            ->assertSessionHasErrors('pib');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['crps_number' => '']))
            ->assertSessionHasErrors('crps_number');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['crps_number' => '50000000']))
            ->assertSessionHasErrors('crps_number');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['crps_number' => '10000001']))
            ->assertSessionHasErrors('crps_number');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload(['authorized_jmb' => '0000000000001']))
            ->assertSessionHasErrors('authorized_jmb');

        $this->assertSame(0, PlatformIdentity::query()->count());

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->dooPayload(['pib' => '12345672', 'city' => 'Nikšić']))
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('Nikšić', LegalEntityIdentity::query()->value('city'));
    }

    public function test_doo_authorized_person_passport_success_and_missing_issuing_country(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->dooPayload([
                'authorized_id_document_type' => 'passport',
                'authorized_jmb' => '0000000000000',
                'authorized_passport_number' => 'AB123456',
                'authorized_passport_issuing_country_code' => '',
            ]))
            ->assertSessionHasErrors('authorized_passport_issuing_country_code');

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->dooPayload([
                'authorized_id_document_type' => 'passport',
                'authorized_jmb' => 'should-be-ignored',
                'authorized_passport_number' => 'AB123456',
                'authorized_passport_issuing_country_code' => 'IT',
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $ap = LegalEntityAuthorizedPerson::query()->first();
        $this->assertSame('passport', $ap->id_document_type);
        $this->assertSame('AB123456', $ap->passport_number);
        $this->assertSame('IT', $ap->passport_issuing_country_code);
        $this->assertNull($ap->jmb);
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_preduzetnik_resident_jmb_success_and_country_not_persisted(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makePreduzetnikUser();
        $before = $this->leftoverIdentity($user);

        $this->actingAs($user)->get(route('identity.completion.create'))
            ->assertOk()
            ->assertSee('Fizičko lice')
            ->assertSee('Registrovani preduzetnik')
            ->assertSee('Lični identitet')
            ->assertSee('Poslovanje');

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->preduzetnikPayload([
                'residence_country_code' => 'ME',
                'is_entrepreneur' => '0',
                'subject_type' => 'legal_entity',
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertTrue((bool) $fl->is_entrepreneur);
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_RESIDENT, $fl->residential_status);
        $this->assertNull($fl->residence_country_code);
        $this->assertSame('10000001', $fl->crps_number);
        $this->assertSame('Bar', $fl->city);
        $this->assertSame($before, $this->leftoverIdentity($user->fresh()));
        $this->assertSame(UserType::ENTREPRENEUR, $user->fresh()->user_type);
    }

    public function test_preduzetnik_non_resident_jmb_and_passport_and_no_silent_me(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makePreduzetnikUser();

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload([
                'residential_status' => 'non-resident',
                'id_document_type' => 'jmb',
                'residence_country_code' => '',
            ]))
            ->assertSessionHasErrors('residence_country_code');

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->preduzetnikPayload([
                'residential_status' => 'non-resident',
                'id_document_type' => 'jmb',
                'residence_country_code' => 'RS',
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $fl = PhysicalPersonIdentity::query()->first();
        $this->assertSame(PhysicalPersonIdentity::RESIDENTIAL_NON_RESIDENT, $fl->residential_status);
        $this->assertSame('RS', $fl->residence_country_code);
        $this->assertNotSame('ME', $fl->residence_country_code);
        $this->assertSame('jmb', $fl->id_document_type);

        PhysicalPersonIdentity::query()->delete();
        PlatformIdentity::query()->delete();
        $second = $this->makePreduzetnikUser();

        $this->actingAs($second)
            ->post(route('identity.completion.store'), $this->preduzetnikPayload([
                'residential_status' => 'non-resident',
                'id_document_type' => 'passport',
                'jmb' => '',
                'passport_number' => 'XY987654',
                'residence_country_code' => 'DE',
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $passportFl = PhysicalPersonIdentity::query()->first();
        $this->assertSame('passport', $passportFl->id_document_type);
        $this->assertSame('XY987654', $passportFl->passport_number);
        $this->assertSame('DE', $passportFl->residence_country_code);
        $this->assertNull($passportFl->jmb);
    }

    public function test_preduzetnik_validation_matrix(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makePreduzetnikUser();

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['jmb' => '0000000000001']))
            ->assertSessionHasErrors('jmb');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['pib' => '12345670']))
            ->assertSessionHasErrors('pib');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['crps_number' => '']))
            ->assertSessionHasErrors('crps_number');

        $this->actingAs($user)->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['crps_number' => '50000001']))
            ->assertSessionHasErrors('crps_number');

        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_current_graph_get_redirects_and_post_is_idempotent_without_overlay(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $this->actingAs($user)
            ->get(route('identity.completion.create'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->dooPayload([
                'legal_name' => 'Overlay DOO',
            ]))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame('Primjer DOO', LegalEntityIdentity::query()->value('legal_name'));
    }

    public function test_malformed_graph_fails_closed(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();
        PlatformIdentity::query()->create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            'mobile_phone' => '+38267000002',
        ]);

        $this->actingAs($user)->get(route('identity.completion.create'))->assertForbidden();
        $this->actingAs($user)->post(route('identity.completion.store'), $this->dooPayload())->assertForbidden();
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(0, LegalEntityIdentity::query()->count());
    }

    public function test_double_post_cannot_create_two_graphs(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $this->actingAs($user)->post(route('identity.completion.store'), $this->dooPayload())->assertRedirect();
        $this->actingAs($user)->post(route('identity.completion.store'), $this->dooPayload([
            'legal_name' => 'Drugi pokušaj DOO',
        ]))->assertRedirect();

        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame('Novi Naziv DOO', LegalEntityIdentity::query()->value('legal_name'));
    }

    public function test_writer_and_mirror_failures_roll_back_outer_transaction(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $this->app->instance(ExistingSubjectIdentitySnapshotMapper::class, new class extends ExistingSubjectIdentitySnapshotMapper
        {
            public function fromValidated(User $user, string $branch, array $validated): IdentitySnapshot
            {
                $snapshot = parent::fromValidated($user, $branch, $validated);

                return new IdentitySnapshot(
                    userId: $snapshot->userId,
                    isRegisteredSubject: $snapshot->isRegisteredSubject,
                    subjectType: $snapshot->subjectType,
                    mobilePhone: $snapshot->mobilePhone,
                    streetAndNumber: $snapshot->streetAndNumber,
                    city: $snapshot->city,
                    physicalPerson: $snapshot->physicalPerson,
                    legalEntity: $snapshot->legalEntity,
                    foreignBranch: $snapshot->foreignBranch,
                    legacyFacts: new \App\Identity\LegacyUserFacts(
                        userType: 'x',
                        residentialStatus: null,
                        firstName: null,
                        lastName: null,
                        companyName: null,
                        jmb: null,
                        pib: null,
                        passportNumber: null,
                        phone: null,
                        address: null,
                        city: null,
                    ),
                );
            }
        });

        try {
            $this->app->make(ExistingSubjectIdentityCompletionService::class)
                ->complete($user, $this->dooPayload());
            $this->fail('Writer failure must not commit.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertSame(0, PlatformIdentity::query()->count());
            $this->assertSame(0, LegalEntityIdentity::query()->count());
        }
    }

    public function test_mirror_failure_rolls_back_created_graph(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();
        $before = $this->leftoverIdentity($user);

        // Eligible leftover type already matches a DOO snapshot, so real
        // DerivedUserTypeMirror::sync() would no-op. A test-only mapper
        // produces an otherwise valid PL graph whose representable mirror
        // type differs, forcing the real final mirror to call User::save().
        $this->app->instance(ExistingSubjectIdentitySnapshotMapper::class, new class extends ExistingSubjectIdentitySnapshotMapper
        {
            public function fromValidated(User $user, string $branch, array $validated): IdentitySnapshot
            {
                $snapshot = parent::fromValidated($user, $branch, $validated);
                $legal = $snapshot->legalEntity;

                return new IdentitySnapshot(
                    userId: $snapshot->userId,
                    isRegisteredSubject: $snapshot->isRegisteredSubject,
                    subjectType: $snapshot->subjectType,
                    mobilePhone: $snapshot->mobilePhone,
                    streetAndNumber: $snapshot->streetAndNumber,
                    city: $snapshot->city,
                    legalEntity: new LegalEntitySnapshot(
                        legalForm: LegalEntityIdentity::FORM_AD,
                        legalName: $legal->legalName,
                        streetAndNumber: $legal->streetAndNumber,
                        city: $legal->city,
                        authorizedPerson: $legal->authorizedPerson,
                        pib: $legal->pib,
                        crpsNumber: $legal->crpsNumber,
                    ),
                );
            }
        });

        User::saving(function (): void {
            throw new RuntimeException('forced mirror failure');
        });

        try {
            $this->app->make(ExistingSubjectIdentityCompletionService::class)
                ->complete($user, $this->dooPayload());
            $this->fail('Mirror failure must not commit.');
        } catch (CanonicalIdentityWriteException) {
            $this->assertSame(0, PlatformIdentity::query()->count());
            $this->assertSame(0, LegalEntityIdentity::query()->count());
            $this->assertSame(0, LegalEntityAuthorizedPerson::query()->count());
            $this->assertSame(0, PhysicalPersonIdentity::query()->count());
            $fresh = $user->fresh();
            $this->assertSame(UserType::LIMITED_LIABILITY_COMPANY, $fresh->user_type);
            $this->assertSame($before, $this->leftoverIdentity($fresh));
        }
    }

    public function test_kn_create_and_store_redirect_eligible_users_and_preserve_fail_closed_for_others(): void
    {
        $this->enableCanonicalHttp();
        $doo = $this->makeDooUser();
        $unsupported = $this->makeKorisnik([
            'user_type' => UserType::JOINT_STOCK_COMPANY,
            'jmb' => $this->validJmb($this->jmbSeq++),
            'pib' => $this->uniqueValidPib(),
        ]);
        $competition = $this->openCompetition();

        $this->actingAs($doo)
            ->get(route('applications.create', $competition))
            ->assertRedirect(route('identity.completion.create'));
        $this->assertSame(
            '/competitions/'.$competition->id.'/apply',
            session(ExistingSubjectIdentityReturnTo::SESSION_KEY)
        );

        $this->actingAs($doo)
            ->post(route('applications.store', $competition), ['applicant_type' => 'doo'])
            ->assertRedirect(route('identity.completion.create'));

        $this->actingAs($unsupported)
            ->get(route('applications.create', $competition))
            ->assertRedirect(route('competitions.show', $competition));
    }

    public function test_kn_start_gate_returns_to_get_apply_after_successful_completion(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();
        $competition = $this->openCompetition();
        $apply = '/competitions/'.$competition->id.'/apply';

        $this->actingAs($user)
            ->post(route('applications.start', $competition))
            ->assertRedirect(route('identity.completion.create'));

        $this->assertSame($apply, session(ExistingSubjectIdentityReturnTo::SESSION_KEY));
        $this->assertStringNotContainsString('/apply/start', (string) session(ExistingSubjectIdentityReturnTo::SESSION_KEY));

        $completed = $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->dooPayload());

        $completed->assertRedirect($apply);
        $location = (string) $completed->headers->get('Location');
        $this->assertStringNotContainsString('/apply/start', $location);
        $this->assertNull(session(ExistingSubjectIdentityReturnTo::SESSION_KEY));
    }

    public function test_safe_return_to_and_unsafe_return_rejected_and_direct_defaults_dashboard(): void
    {
        $this->enableCanonicalHttp();
        $competition = $this->openCompetition();
        $user = $this->makeDooUser();

        $this->actingAs($user)
            ->withSession([ExistingSubjectIdentityReturnTo::SESSION_KEY => '/competitions/'.$competition->id.'/apply'])
            ->post(route('identity.completion.store'), $this->dooPayload())
            ->assertRedirect('/competitions/'.$competition->id.'/apply');
        $this->assertNull(session(ExistingSubjectIdentityReturnTo::SESSION_KEY));

        $second = $this->makeDooUser();
        $this->actingAs($second)
            ->withSession([ExistingSubjectIdentityReturnTo::SESSION_KEY => 'https://evil.example/phish'])
            ->post(route('identity.completion.store'), $this->dooPayload())
            ->assertRedirect(route('dashboard', absolute: false));

        $third = $this->makeDooUser();
        $this->actingAs($third)
            ->withSession([ExistingSubjectIdentityReturnTo::SESSION_KEY => '/login'])
            ->post(route('identity.completion.store'), $this->dooPayload())
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_kn_commission_application_id_read_only_path_unchanged(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makePreduzetnikUser(['phone' => '+38267000999', 'pib' => '12345672']);
        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'application_id' => $application->id]))
            ->assertOk()
            ->assertDontSee('Dopunite podatke o subjektu');
    }

    public function test_no_global_login_dashboard_or_profile_gate(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeDooUser();

        $this->get('/login')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertFalse(config('identity.ep_identity_flows'));
    }

    public function test_legacy_physical_person_completion_prefill_gate_and_return(): void
    {
        $this->enableCanonicalHttp();
        $address = '  Stari grad 1, Podgorica  ';
        $user = $this->makeKorisnik([
            'jmb' => null,
            'address' => $address,
            'city' => 'Herceg Novi',
            'residential_status' => 'resident',
            'phone' => '+38267000001',
        ]);
        $competition = $this->openCompetition();
        $apply = '/competitions/'.$competition->id.'/apply';

        $shown = $this->actingAs($user)->get(route('identity.completion.create'));
        $shown->assertOk()
            ->assertSee('Dopuna podataka')
            ->assertSee('Prije nastavka potrebno je da provjerite i dopunite podatke svog profila.')
            ->assertSee('Sačuvaj i nastavi')
            ->assertSee('value="'.$address.'"', false)
            ->assertSee('value="Herceg Novi"', false)
            ->assertSee('value="resident" selected', false)
            ->assertSee('name="jmb"', false)
            ->assertDontSee('Naziv preduzetnika')
            ->assertDontSee('CRPS registracioni broj')
            ->assertDontSee('PIB')
            ->assertDontSee('Ovlašćeno lice')
            ->assertDontSee('legacy')
            ->assertDontSee('kanonski');
        $this->assertSame(0, PlatformIdentity::query()->count());

        $this->actingAs($user)->get(route('identity.completion.create'))->assertOk();
        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());

        $this->actingAs($user)
            ->post(route('applications.start', $competition))
            ->assertRedirect(route('identity.completion.create'));
        $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertRedirect(route('identity.completion.create'));
        $this->actingAs($user)
            ->post(route('applications.store', $competition), [])
            ->assertRedirect(route('identity.completion.create'));
        $this->assertSame($apply, session(ExistingSubjectIdentityReturnTo::SESSION_KEY));

        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->physicalPayload([
                'jmb' => $this->validJmb($this->jmbSeq++),
                'street_and_number' => $address,
                'city' => 'Herceg Novi',
            ]))
            ->assertRedirect($apply);

        $graph = PhysicalPersonIdentity::query()->first();
        $this->assertNotNull($graph);
        $this->assertFalse((bool) $graph->is_entrepreneur);
        $this->assertNull($graph->pib);
        $this->assertNull($graph->crps_number);
        $this->assertSame($address, $graph->street_and_number);
        $this->assertSame('Herceg Novi', $graph->city);
        $this->assertSame(1, PlatformIdentity::query()->count());

        $this->actingAs($user)->get(route('identity.completion.create'))
            ->assertRedirect(route('dashboard', absolute: false));
        $this->actingAs($user)
            ->post(route('identity.completion.store'), $this->physicalPayload([
                'jmb' => $this->validJmb($this->jmbSeq++),
            ]))
            ->assertRedirect();
        $this->assertSame(1, PlatformIdentity::query()->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
    }

    public function test_legacy_physical_person_uses_stored_jmb_pair_without_showing_or_clearing_it(): void
    {
        $this->enableCanonicalHttp();
        $jmb = $this->validJmb($this->jmbSeq++);
        $user = $this->makeKorisnik([
            'email' => 'fl-stored-jmb@example.test',
            'jmb' => $jmb,
        ]);
        $before = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotNull($before->jmb_encrypted);
        $this->assertNotNull($before->jmb_lookup);
        DB::table('users')->where('id', $user->id)->update(['jmb' => null]);
        config(['jmb.plaintext_retirement.enabled' => true]);

        $shown = $this->actingAs($user->fresh())->get(route('identity.completion.create'));
        $shown->assertOk()
            ->assertDontSee('name="jmb"', false)
            ->assertDontSee($jmb);
        $this->assertStringNotContainsString($before->jmb_encrypted, $shown->getContent());
        $this->assertStringNotContainsString($before->jmb_lookup, $shown->getContent());

        $this->actingAs($user->fresh())
            ->post(route('identity.completion.store'), $this->physicalPayload())
            ->assertRedirect(route('dashboard', absolute: false));

        $physical = PhysicalPersonIdentity::query()->first();
        $this->assertNotNull($physical);
        $this->assertNull($physical->jmb);
        $this->assertNotNull($physical->jmb_encrypted);
        $this->assertSame($this->digest($jmb), $physical->jmb_lookup);
        $this->assertFalse((bool) $physical->is_entrepreneur);

        $after = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame($before->jmb_encrypted, $after->jmb_encrypted);
        $this->assertSame($before->jmb_lookup, $after->jmb_lookup);
        $this->assertNull($after->jmb);
    }

    public function test_legacy_physical_person_missing_lookup_requires_entered_jmb_and_ignores_plaintext(): void
    {
        $this->enableCanonicalHttp();
        $plaintext = $this->validJmb($this->jmbSeq++);
        $entered = $this->validJmb($this->jmbSeq++);
        $user = $this->makeKorisnik([
            'email' => 'fl-missing-lookup@example.test',
            'jmb' => $plaintext,
        ]);
        DB::table('users')->where('id', $user->id)->update(['jmb_lookup' => null]);

        $shown = $this->actingAs($user->fresh())->get(route('identity.completion.create'));
        $shown->assertOk()
            ->assertSee('name="jmb"', false)
            ->assertDontSee('value="'.$plaintext.'"', false);

        $this->actingAs($user->fresh())
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->physicalPayload())
            ->assertSessionHasErrors('jmb');
        $this->assertSame(0, PlatformIdentity::query()->count());

        $this->actingAs($user->fresh())
            ->post(route('identity.completion.store'), $this->physicalPayload(['jmb' => $entered]))
            ->assertRedirect(route('dashboard', absolute: false));

        $physical = PhysicalPersonIdentity::query()->first();
        $this->assertSame($this->digest($entered), $physical->jmb_lookup);
        $this->assertNotSame($this->digest($plaintext), $physical->jmb_lookup);
        $this->assertSame($plaintext, DB::table('users')->where('id', $user->id)->value('jmb'));
    }

    public function test_legacy_physical_person_duplicate_jmb_does_not_expose_other_user_and_does_not_self_block(): void
    {
        $this->enableCanonicalHttp();
        $taken = $this->validJmb($this->jmbSeq++);
        $holder = $this->makeKorisnik([
            'email' => 'fl-holder@example.test',
            'first_name' => 'Marko',
            'last_name' => 'Marković',
            'jmb' => $taken,
        ]);
        (new CanonicalIdentityWriter)->createForUser($holder, $this->flSnapshot($holder, [
            'person' => ['jmb' => $taken],
        ]));
        DB::table('users')->where('id', $holder->id)->update([
            'jmb' => null,
            'jmb_lookup' => null,
        ]);

        $actor = $this->makeKorisnik([
            'email' => 'fl-actor@example.test',
            'jmb' => null,
        ]);
        $blocked = $this->actingAs($actor)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->physicalPayload(['jmb' => $taken]));
        $blocked->assertSessionHasErrors('jmb');
        $errors = implode(' ', session('errors')?->all() ?? []);
        $this->assertStringContainsString(ExistingSubjectIdentityStoredJmb::CONFLICT_MESSAGE, $errors);
        $this->assertStringNotContainsString('fl-holder@example.test', $errors);
        $this->assertStringNotContainsString('Marko', $errors);
        $this->assertStringNotContainsString('Marković', $errors);
        $this->assertStringNotContainsString((string) $holder->id, $errors);
        $this->assertStringNotContainsString($taken, $errors);
        $this->assertSame(1, PlatformIdentity::query()->count());

        $own = $this->validJmb($this->jmbSeq++);
        $self = $this->makeKorisnik([
            'email' => 'fl-self@example.test',
            'jmb' => $own,
        ]);
        DB::table('users')->where('id', $self->id)->update(['jmb' => null]);
        config(['jmb.plaintext_retirement.enabled' => true]);

        $this->actingAs($self->fresh())
            ->post(route('identity.completion.store'), $this->physicalPayload())
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame(2, PhysicalPersonIdentity::query()->count());
        $this->assertSame($this->digest($own), PhysicalPersonIdentity::query()->where('jmb_lookup', $this->digest($own))->value('jmb_lookup'));
    }

    public function test_legacy_physical_person_writer_failure_rolls_back_and_profile_login_do_not_create_graph(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeKorisnik(['jmb' => null]);

        $this->get('/login')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->actingAs($user)->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'first_name' => 'Ana',
                'last_name' => 'Anić',
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
            ])
            ->assertRedirect(route('profile.edit'));
        $this->assertSame(0, PlatformIdentity::query()->count());

        PhysicalPersonIdentity::creating(function (): void {
            throw new RuntimeException('forced physical insert failure');
        });

        $this->actingAs($user)->post(route('identity.completion.store'), $this->physicalPayload([
            'jmb' => $this->validJmb($this->jmbSeq++),
        ]))->assertForbidden();

        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
    }

    public function test_missing_city_is_not_parsed_from_address(): void
    {
        $this->enableCanonicalHttp();
        $user = $this->makeKorisnik([
            'jmb' => null,
            'address' => 'Ulica bez grada, Podgorica',
            'city' => null,
            'residential_status' => 'non-resident',
        ]);

        $shown = $this->actingAs($user)->get(route('identity.completion.create'));
        $shown->assertOk()
            ->assertSee('value="Ulica bez grada, Podgorica"', false)
            ->assertDontSee('value="Podgorica"', false)
            ->assertSee('value="non-resident" selected', false);
    }

    private int $pibSeq = 1;

    private int $jmbSeq = 21;

    private function uniqueValidPib(): string
    {
        $prefix = str_pad((string) $this->pibSeq++, 7, '0', STR_PAD_LEFT);
        $product = 10;
        for ($i = 0; $i < 7; $i++) {
            $product = ($product + (int) $prefix[$i]) % 10;
            if ($product === 0) {
                $product = 10;
            }
            $product = ($product * 2) % 11;
        }

        return $prefix.((11 - $product) % 10);
    }

    private function enableCanonicalHttp(): void
    {
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeDooUser(array $overrides = []): User
    {
        return $this->makeKorisnik(array_merge([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'company_name' => 'Primjer DOO',
            'pib' => $this->uniqueValidPib(),
            'first_name' => 'Nosioc',
            'last_name' => 'Naloga',
            'jmb' => null,
            'residential_status' => 'resident',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'phone' => '+38267000001',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePreduzetnikUser(array $overrides = []): User
    {
        return $this->makeKorisnik(array_merge([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => $this->uniqueValidPib(),
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'jmb' => $this->validJmb($this->jmbSeq++),
            'residential_status' => 'resident',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'phone' => '+38267000001',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function dooPayload(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Novi Naziv DOO',
            'pib' => '12345672',
            'crps_number' => '50000001',
            'street_and_number' => 'Slobode 1',
            'city' => 'Budva',
            'phone_calling_code' => '+381',
            'phone_national' => '64123456',
            'authorized_first_name' => 'Marko',
            'authorized_last_name' => 'Marković',
            'authorized_id_document_type' => 'jmb',
            'authorized_jmb' => '0000000000000',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function preduzetnikPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'residential_status' => 'resident',
            'jmb' => '0000000000000',
            'entrepreneur_business_name' => 'Radnja Ana',
            'pib' => '12345672',
            'crps_number' => '10000001',
            'street_and_number' => 'Obala 4',
            'city' => 'Bar',
            'phone_calling_code' => '+382',
            'phone_national' => '67111001',
        ], $overrides);
    }

    private function physicalPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'residential_status' => 'resident',
            'street_and_number' => 'Njegoševa 12',
            'city' => 'Kotor',
            'phone_calling_code' => '+382',
            'phone_national' => '67000001',
        ], $overrides);
    }

    private function digest(string $jmb): string
    {
        return (string) app(JmbLookupService::class)->digest($jmb);
    }

    /**
     * @return array<string, mixed>
     */
    private function leftoverIdentity(User $user): array
    {
        return $user->only([
            'residential_status',
            'first_name',
            'last_name',
            'company_name',
            'jmb',
            'pib',
            'passport_number',
            'phone',
            'address',
            'city',
        ]);
    }

    private function openCompetition(): Competition
    {
        return Competition::create([
            'title' => 'KN completion',
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
