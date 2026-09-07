<?php

namespace Tests\Feature\Identity;

use App\Console\Commands\IdentityProductionReconcileCommand;
use App\Enums\PaymentAvailabilityOutcome;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Cutover\IdentityCutoverAttestation;
use App\Identity\Runtime\IdentityMutationDeniedException;
use App\Identity\Runtime\IdentityMutationGuard;
use App\Models\Application;
use App\Models\PaymentAccount;
use App\Models\PaymentTransaction;
use App\Models\PaymentType;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\PaymentAvailabilityService;
use App\Services\Payments\PaymentDraftService;
use App\Services\Payments\PaymentStartService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class Step8CutoverCapabilityTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.identity_write_freeze'));
        $this->assertSame(false, config('identity.ep_identity_flows'));
    }

    public function test_defaults_preserve_legacy_registration_without_canonical_write(): void
    {
        $this->post('/register', $this->registrationPayload('legacy@example.com'))
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'legacy@example.com')->firstOrFail();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame('resident', $user->residential_status);
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_canonical_read_off_does_not_change_kn_applicant_type(): void
    {
        $user = $this->makeKorisnik();
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', 'fizicko_lice');
    }

    public function test_canonical_write_off_blocks_http_canonical_registration_graph(): void
    {
        $this->post('/register', $this->registrationPayload('no-graph@example.com'))->assertRedirect();
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_write_on_read_off_fails_closed_with_zero_identity_mutation(): void
    {
        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);

        $this->assertFalse(app(IdentityMutationGuard::class)->canonicalHttpWriteAllowed());
        $this->assertFalse(app(IdentityMutationGuard::class)->legacyIdentityMutationAllowed());

        $this->post('/register', $this->registrationPayload('interlock@example.com', $this->validJmb(31)))
            ->assertForbidden();
        $this->assertFalse(User::query()->where('email', 'interlock@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());

        $user = $this->makeKorisnik(['phone' => '+38267000001']);
        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => $user->email,
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
                'jmb' => $user->jmb,
            ])
            ->assertForbidden();
        $this->assertSame('Ana', $user->fresh()->first_name);
        $this->assertSame('+38267000001', $user->fresh()->phone);
        $this->assertSame(0, PlatformIdentity::query()->count());

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'first_name' => 'Novo',
                'last_name' => 'Ime',
                'email' => $user->email,
                'phone' => '+38267999999',
                'role_id' => $user->role_id,
                'activation_status' => 'active',
            ])
            ->assertRedirect(route('admin.users.show', $user));
        $this->assertSame('Ana', $user->fresh()->first_name);
        $this->assertSame('+38267000001', $user->fresh()->phone);
    }

    public function test_read_on_write_off_is_reader_only_without_canonical_write(): void
    {
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => false,
        ]);

        $this->assertFalse(app(IdentityMutationGuard::class)->canonicalHttpWriteAllowed());
        $this->assertTrue(app(IdentityMutationGuard::class)->legacyIdentityMutationAllowed());

        $this->post('/register', $this->registrationPayload('reader-only@example.com'))
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'reader-only@example.com')->firstOrFail();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame('resident', $user->residential_status);
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_registration_canonical_path_creates_graph_without_legacy_identity_columns(): void
    {
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true, 'identity.identity_write_freeze' => true]);

        $this->post('/register', $this->registrationPayload('canon-reg@example.com', $this->validJmb(21)))
            ->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'canon-reg@example.com')->firstOrFail();
        $this->assertNull($user->jmb);
        $this->assertNull($user->residential_status);
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertSame(1, PlatformIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSame($this->validJmb(21), PhysicalPersonIdentity::query()->value('jmb'));
    }

    public function test_registration_rolls_back_account_when_canonical_graph_fails(): void
    {
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true, 'identity.identity_write_freeze' => true]);

        $thrown = false;
        PlatformIdentity::creating(function () use (&$thrown): void {
            if ($thrown) {
                return;
            }
            $thrown = true;
            throw new CanonicalIdentityWriteException('Canonical identity create failed.');
        });

        $this->post('/register', $this->registrationPayload('rollback@example.com', $this->validJmb(98)))
            ->assertRedirect();

        $this->assertFalse(User::query()->where('email', 'rollback@example.com')->exists());
        $this->assertSame(0, PlatformIdentity::query()->count());
    }

    public function test_dead_breeze_registration_cannot_become_active(): void
    {
        $this->assertFalse(
            collect(Route::getRoutes())->contains(
                fn ($route) => str_contains((string) $route->getActionName(), 'RegisteredUserController')
            )
        );

        try {
            app(RegisteredUserController::class)->store(Request::create('/register', 'POST'));
            $this->fail('Breeze store must abort.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertTrue(true);
        }
    }

    public function test_profile_canonical_update_same_branch_and_rejects_branch_transition(): void
    {
        $user = $this->makeKorisnik(['jmb' => $this->validJmb(22)]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $this->validJmb(22)],
        ]));

        config(['identity.canonical_read' => true, 'identity.canonical_write' => true, 'identity.identity_write_freeze' => true]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => $user->email,
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
                'jmb' => $this->validJmb(22),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Petar', PhysicalPersonIdentity::query()->value('first_name'));
        $this->assertSame('Ana', $user->fresh()->first_name);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => $user->email,
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
                'company_name' => 'Primjer DOO',
                'pib' => '12345672',
            ])
            ->assertForbidden();
    }

    public function test_kn_missing_canonical_identity_fails_closed_not_ostalo(): void
    {
        $user = $this->makeKorisnik(['user_type' => null, 'jmb' => null]);
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', null);

        $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertRedirect(route('competitions.show', $competition));
    }

    public function test_kn_canonical_first_prefill_uses_canonical_jmb(): void
    {
        $user = $this->makeKorisnik(['jmb' => '0202990123456']);
        $canonicalJmb = $this->validJmb(23);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $canonicalJmb],
        ]));
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();

        $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertViewHas('applicantType', 'fizicko_lice');
    }

    public function test_dashboard_non_subject_does_not_require_graph(): void
    {
        $komisija = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => null,
            'first_name' => null,
            'last_name' => null,
        ]);
        config(['identity.canonical_read' => true]);

        $this->actingAs($komisija)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_commission_create_does_not_invent_fl_resident(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), [
                'name' => 'Komisija Step8',
                'year' => 2026,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'members' => [
                    [
                        'name' => 'Član Jedan',
                        'email' => 'clan.step8@example.com',
                        'password' => 'password',
                        'position' => 'predsjednik',
                        'member_type' => 'opstina',
                    ],
                ],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'clan.step8@example.com')->firstOrFail();
        $this->assertNull($created->user_type);
        $this->assertNull($created->residential_status);
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $created->id)->count());
    }

    public function test_admin_account_edit_works_while_freeze_skips_identity_fields(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $target = $this->makeKorisnik(['email' => 'target.step8@example.com', 'phone' => '+38267000001']);
        config(['identity.identity_write_freeze' => true]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'first_name' => 'Novo',
                'last_name' => 'Ime',
                'email' => 'target.step8.new@example.com',
                'phone' => '+38267999999',
                'role_id' => $target->role_id,
                'activation_status' => 'deactivated',
                'password' => 'new-admin-pass',
                'password_confirmation' => 'new-admin-pass',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertSame('target.step8.new@example.com', $target->email);
        $this->assertSame('deactivated', $target->activation_status);
        $this->assertTrue(Hash::check('new-admin-pass', $target->password));
        $this->assertSame('+38267000001', $target->phone);
        $this->assertSame('Ana', $target->first_name);
        $this->assertSame('Anić', $target->last_name);
    }

    public function test_commission_create_under_freeze_remains_account_only(): void
    {
        config(['identity.identity_write_freeze' => true]);

        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), [
                'name' => 'Komisija Freeze',
                'year' => 2026,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'members' => [
                    [
                        'name' => 'Član Freeze',
                        'email' => 'clan.freeze@example.com',
                        'password' => 'password',
                        'position' => 'predsjednik',
                        'member_type' => 'opstina',
                    ],
                ],
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'clan.freeze@example.com')->firstOrFail();
        $this->assertNull($created->user_type);
        $this->assertNull($created->residential_status);
        $this->assertNull($created->jmb);
        $this->assertSame('Član Freeze', $created->name);
        $this->assertSame(0, PlatformIdentity::query()->where('user_id', $created->id)->count());
    }

    public function test_ep_durable_disable_overrides_admin_db_toggle_and_denies_d10(): void
    {
        $user = $this->makeKorisnik(['residential_status' => null]);
        app(EpModuleSettings::class)->setNewPaymentsEnabled(true);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertViewIs('payments.disabled');

        $this->actingAs($user)
            ->get(route('payments.declaration.create'))
            ->assertRedirect(route('payments.index'));

        $this->actingAs($user)
            ->post(route('payments.declaration.store'), ['residential_status' => 'resident'])
            ->assertRedirect(route('payments.index'));

        $this->assertNull($user->fresh()->residential_status);

        $type = PaymentType::factory()->create(['is_active' => true, 'code' => 'syn-step8-gate']);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('payments.start', $type))
            ->assertRedirect(route('payments.index'));
        $this->actingAs($user)
            ->post(route('payments.account.store', $type), ['payment_account_id' => $account->id])
            ->assertRedirect(route('payments.index'));
        $this->actingAs($user)
            ->get(route('payments.amount.edit'))
            ->assertRedirect(route('payments.index'));
        $this->actingAs($user)
            ->post(route('payments.amount.store'), ['amount' => '10.00'])
            ->assertRedirect(route('payments.index'));
        $this->actingAs($user)
            ->get(route('payments.preview'))
            ->assertRedirect(route('payments.index'));
        $this->actingAs($user)
            ->post(route('payments.launch'))
            ->assertRedirect(route('payments.index'));

        $this->assertSame(
            PaymentAvailabilityOutcome::NotAvailable,
            app(PaymentAvailabilityService::class)->evaluateType($user, $type)
        );
        $this->assertSame(
            PaymentAvailabilityOutcome::NotAvailable,
            app(PaymentAvailabilityService::class)->evaluateAccount($user, $account)
        );

        $request = Request::create('/payments', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session.store']);
        try {
            app(PaymentDraftService::class)->put($request, ['payment_type_id' => $type->id]);
            $this->fail('Draft construction must fail closed while EP identity flows are disabled.');
        } catch (IdentityMutationDeniedException) {
            $this->assertTrue(true);
        }

        try {
            app(PaymentDraftService::class)->payerLabel($user);
            $this->fail('Live payer label must fail closed while EP identity flows are disabled.');
        } catch (IdentityMutationDeniedException) {
            $this->assertTrue(true);
        }

        try {
            app(PaymentStartService::class)->snapshot($user, $type, $account, '10.00');
            $this->fail('Live payment snapshot source must fail closed while EP identity flows are disabled.');
        } catch (IdentityMutationDeniedException) {
            $this->assertTrue(true);
        }

        $launchRequest = Request::create('/payments/pregled/pokreni', 'POST');
        $launchRequest->setUserResolver(fn () => $user);
        try {
            app(PaymentStartService::class)->launch($launchRequest);
            $this->fail('Payment start service must fail closed while EP identity flows are disabled.');
        } catch (IdentityMutationDeniedException) {
            $this->assertTrue(true);
        }

        $historical = PaymentTransaction::factory()->create([
            'user_id' => $user->id,
            'snapshot' => [
                'payer_label' => 'Historical Payer',
                'user_type_label' => 'Fizičko lice',
                'payment_type_name' => 'Historical type',
                'account_number' => '000',
                'account_name' => 'Historical',
                'amount' => '5.00',
                'currency' => 'EUR',
            ],
        ]);
        $this->actingAs($user)
            ->get(route('payments.history'))
            ->assertOk()
            ->assertSee('Historical type', false);
        $this->actingAs($user)
            ->get(route('payments.result', $historical))
            ->assertOk()
            ->assertSee('Historical Payer', false);
    }

    public function test_freeze_denies_d10_residential_declaration(): void
    {
        config(['identity.identity_write_freeze' => true]);

        $user = $this->makeKorisnik(['residential_status' => null]);

        $this->actingAs($user)
            ->post(route('payments.declaration.store'), ['residential_status' => 'resident'])
            ->assertForbidden();
        $this->assertNull($user->fresh()->residential_status);
    }

    public function test_freeze_denies_legacy_identity_writes_and_new_subjects_but_allows_password(): void
    {
        config(['identity.identity_write_freeze' => true]);

        $this->post('/register', $this->registrationPayload('frozen@example.com'))
            ->assertForbidden();
        $this->assertFalse(User::query()->where('email', 'frozen@example.com')->exists());

        $user = $this->makeKorisnik();
        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Petrović',
                'email' => $user->email,
                'phone' => '+38267111222',
                'address' => 'Njegoševa 12',
                'city' => 'Kotor',
                'user_type' => UserType::PHYSICAL_PERSON,
                'residential_status' => 'resident',
                'jmb' => $user->jmb,
            ])
            ->assertForbidden();
        $this->assertSame('Ana', $user->fresh()->first_name);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('profile.edit'));
    }

    public function test_canonical_writer_authorized_while_legacy_writers_remain_denied(): void
    {
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => true,
        ]);

        $this->assertFalse(app(IdentityMutationGuard::class)->legacyIdentityMutationAllowed());
        $this->assertTrue(app(IdentityMutationGuard::class)->canonicalHttpWriteAllowed());

        $this->post('/register', $this->registrationPayload('cutover-reg@example.com', $this->validJmb(24)))
            ->assertRedirect(route('verification.notice', absolute: false));
        $this->assertSame(1, PlatformIdentity::query()->count());
    }

    public function test_phones_normalize_is_guarded(): void
    {
        config(['identity.canonical_write' => true]);

        $this->artisan('phones:normalize')
            ->assertFailed();
    }

    public function test_phones_normalize_is_guarded_when_freeze_on(): void
    {
        config(['identity.identity_write_freeze' => true]);

        $this->artisan('phones:normalize')
            ->assertFailed();
    }

    public function test_user_type_mirror_only_for_representable_branches(): void
    {
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true, 'identity.identity_write_freeze' => true]);

        $this->post('/register', $this->registrationPayload('mirror@example.com', $this->validJmb(25)))
            ->assertRedirect();

        $user = User::query()->where('email', 'mirror@example.com')->firstOrFail();
        $this->assertSame(UserType::PHYSICAL_PERSON, $user->user_type);
        $this->assertNull($user->jmb);
        $this->assertNull($user->pib);
        $this->assertNull($user->residential_status);
        $this->assertNull($user->passport_number);
        $this->assertNull($user->address);
        $this->assertNull($user->city);
        $this->assertNull($user->phone);
    }

    public function test_canonical_read_does_not_fall_back_to_stale_legacy_identity(): void
    {
        $user = $this->makeKorisnik(['first_name' => 'Stale', 'jmb' => '0202990123456']);
        config(['identity.canonical_read' => true]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('0202990123456', false);
    }

    public function test_dashboard_read_off_preserves_legacy_identity_labels(): void
    {
        $user = $this->makeKorisnik();

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Fizičko lice (Rezident)', $html);
        $this->assertStringContainsString('Kotor', $html);
        $this->assertStringContainsString('+38267000001', $html);
    }

    public function test_dashboard_read_on_uses_canonical_type_and_residency_only(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => 'non-resident',
            'jmb' => $this->validJmb(41),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'jmb' => $this->validJmb(41),
                'firstName' => 'Kanon',
                'city' => 'Podgorica',
            ],
        ]));
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Fizičko lice (Rezident)', $html);
        $this->assertStringNotContainsString('Preduzetnik', $html);
        $this->assertStringNotContainsString('Fizičko lice (Nerezident)', $html);
        $this->assertStringContainsString('Podgorica', $html);
    }

    public function test_dashboard_read_on_missing_subject_does_not_show_stale_legacy_identity(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => 'resident',
            'company_name' => 'Stale DOO',
            'pib' => '12345672',
        ]);
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Fizičko lice (Rezident)', $html);
        $this->assertStringNotContainsString('Društvo sa ograničenom odgovornošću', $html);
        $this->assertStringNotContainsString('Stale DOO', $html);
        $this->assertStringNotContainsString('12345672', $html);
        $this->assertStringNotContainsString('+38267000001', $html);
        $this->assertStringContainsString('N/A', $html);
    }

    public function test_profile_read_off_preserves_legacy_form_branch(): void
    {
        $user = $this->makeKorisnik(['user_type' => UserType::ENTREPRENEUR]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="physicalPersonFields"', $html);
        $this->assertStringContainsString('id="user_type"', $html);
        $this->assertStringContainsString('Poslovno ime', $html);
    }

    public function test_profile_read_on_form_branch_is_canonical_not_legacy_fallback(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => '12345672',
            'company_name' => 'Stale DOO',
            'jmb' => $this->validJmb(42),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $this->validJmb(42)],
        ]));
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="physicalPersonFields"', $html);
        $this->assertStringContainsString('id="residentialStatusGroup"', $html);
        $this->assertStringNotContainsString('Stale DOO', $html);
        $this->assertMatchesRegularExpression('/value="Fizičko lice"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="Društvo sa ograničenom odgovornošću"\s+selected/', $html);
    }

    public function test_profile_read_on_missing_subject_does_not_use_collects_business_identity_fallback(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Stale Radnja',
            'jmb' => '0202990123456',
        ]);
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="user_type"', $html);
        $this->assertStringNotContainsString('Stale Radnja', $html);
        $this->assertStringNotContainsString('0202990123456', $html);
        $this->assertStringNotContainsString('id="physicalPersonFields"', $html);
    }

    public function test_admin_subject_edit_read_on_uses_canonical_contact_only(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeKorisnik([
            'first_name' => 'Stale',
            'last_name' => 'Ime',
            'phone' => '+38267000001',
            'jmb' => $this->validJmb(43),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'firstName' => 'KanonIme',
                'lastName' => 'KanonPrezime',
                'jmb' => $this->validJmb(43),
            ],
            'mobilePhone' => '+38267111000',
        ]));
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="KanonIme"', $html);
        $this->assertStringContainsString('value="KanonPrezime"', $html);
        $this->assertStringContainsString('value="+38267111000"', $html);
        $this->assertStringNotContainsString('value="Stale"', $html);
        $this->assertStringNotContainsString('value="Ime"', $html);
        $this->assertStringNotContainsString('value="+38267000001"', $html);
    }

    public function test_admin_subject_missing_graph_does_not_fall_back_to_users_identity(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeKorisnik([
            'first_name' => 'Stale',
            'last_name' => 'Ime',
            'phone' => '+38267000001',
        ]);
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('value="Stale"', $html);
        $this->assertStringNotContainsString('value="Ime"', $html);
        $this->assertStringNotContainsString('value="+38267000001"', $html);
    }

    public function test_admin_staff_account_only_edit_still_uses_account_fields(): void
    {
        $admin = $this->makeAdmin();
        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => null,
            'first_name' => 'Komisija',
            'last_name' => 'Član',
            'phone' => '+38267888000',
        ]);
        config(['identity.canonical_read' => true]);

        $html = $this->actingAs($admin)
            ->get(route('admin.users.edit', $staff))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="Komisija"', $html);
        $this->assertStringContainsString('value="Član"', $html);
        $this->assertStringContainsString('value="+38267888000"', $html);
    }

    public function test_kn_current_phone_and_pib_prefill_read_on_uses_canonical(): void
    {
        $user = $this->makeKorisnik([
            'phone' => '+38267000998',
            'pib' => '87654321',
            'jmb' => $this->validJmb(44),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'jmb' => $this->validJmb(44),
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Ana',
                'pib' => '12345672',
                'crpsNumber' => '10000001',
            ],
            'mobilePhone' => '+38267111000',
        ]));
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="+38267111000"', $html);
        $this->assertStringNotContainsString('value="+38267000998"', $html);
        $this->assertStringNotContainsString('value="87654321"', $html);
        $this->assertStringContainsString('value="12345672"', $html);
    }

    public function test_kn_existing_application_current_phone_uses_canonical_not_users_phone(): void
    {
        $user = $this->makeKorisnik([
            'phone' => '+38267000997',
            'jmb' => $this->validJmb(46),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => [
                'jmb' => $this->validJmb(46),
                'isEntrepreneur' => true,
                'entrepreneurBusinessName' => 'Radnja Ana',
                'pib' => '12345672',
                'crpsNumber' => '10000001',
            ],
            'mobilePhone' => '+38267111001',
        ]));
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'application_id' => $application->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="+38267111001"', $html);
        $this->assertStringNotContainsString('value="+38267000997"', $html);
    }

    public function test_kn_missing_canonical_does_not_fall_back_to_users_phone_or_pib(): void
    {
        $user = $this->makeKorisnik([
            'phone' => '+38267000999',
            'pib' => '12345672',
            'user_type' => UserType::ENTREPRENEUR,
        ]);
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'application_id' => $application->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('+38267000999', $html);
        $this->assertStringNotContainsString('value="12345672"', $html);
    }

    public function test_kn_historical_application_snapshot_phone_and_pib_remain_unchanged(): void
    {
        $user = $this->makeKorisnik([
            'phone' => '+38267000001',
            'pib' => '11111117',
            'jmb' => $this->validJmb(45),
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $this->validJmb(45)],
            'mobilePhone' => '+38267111000',
        ]));
        config(['identity.canonical_read' => true]);

        $competition = $this->openCompetition();
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
            'physical_person_phone' => '+38267999000',
            'pib' => '99999993',
        ]);

        $html = $this->actingAs($user)
            ->get(route('applications.create', ['competition' => $competition, 'application_id' => $application->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="+38267999000"', $html);
        $this->assertStringContainsString('value="99999993"', $html);
        $this->assertStringNotContainsString('value="+38267000001"', $html);
    }

    public function test_corrective_02_does_not_enable_identity_switches_or_ep(): void
    {
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.identity_write_freeze'));
        $this->assertSame(false, config('identity.ep_identity_flows'));
        $this->assertFalse(app(\App\Identity\Runtime\IdentityMutationGuard::class)->canonicalHttpWriteAllowed());
        $this->assertFalse(app(\App\Identity\Runtime\EpIdentityFlowGuard::class)->enabled());
    }

    public function test_step7_still_cannot_claim_cutover_readiness(): void
    {
        $report = (new IdentityCutoverAttestation)->report();
        $this->assertFalse($report['cutover_ready']);
        $this->assertFalse($report['population_boundary_protected']);

        $command = file_get_contents((new \ReflectionClass(IdentityProductionReconcileCommand::class))->getFileName());
        $this->assertStringContainsString('Does not authorize cutover', $command);
    }

    public function test_attestation_command_never_sets_cutover_ready(): void
    {
        $this->artisan('identity:cutover-attestation')
            ->assertSuccessful()
            ->expectsOutputToContain('"cutover_ready": false');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(string $email, ?string $jmb = null): array
    {
        return $this->registrationHttpPayload($email, [
            'jmb' => $jmb ?? $this->validJmb(10),
        ]);
    }

    private function openCompetition(): \App\Models\Competition
    {
        return \App\Models\Competition::create([
            'title' => 'Step8 KN',
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
