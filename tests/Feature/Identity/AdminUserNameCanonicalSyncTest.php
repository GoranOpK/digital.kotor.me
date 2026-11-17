<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriter;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class AdminUserNameCanonicalSyncTest extends TestCase
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

    public function test_physical_person_admin_rename_updates_canonical_identity_and_users_name(): void
    {
        $this->enableCanonicalHttpWrite();
        $admin = $this->admin();
        $user = $this->staleNamedUser();
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user));

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba'))
            ->assertRedirect(route('admin.users.show', $user));

        $person = $this->physicalPerson($user);
        $user->refresh();

        $this->assertSame('Didije', $person->first_name);
        $this->assertSame('Drogba', $person->last_name);
        $this->assertSame('Didije', $user->first_name);
        $this->assertSame('Drogba', $user->last_name);
        $this->assertSame('Didije Drogba', $user->name);
        $this->assertSame('+38267000001', $user->phone);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Didije Drogba')
            ->assertDontSee('Sistem 48');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Didije Drogba')
            ->assertDontSee('Sistem 48');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('value="Didije"', false)
            ->assertSee('value="Drogba"', false);
    }

    public function test_registered_entrepreneur_admin_rename_syncs_person_name_without_business_name(): void
    {
        $this->enableCanonicalHttpWrite();
        $user = $this->staleNamedUser(['company_name' => 'Radnja Ana']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->entrepreneurSnapshot($user));

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba'))
            ->assertRedirect(route('admin.users.show', $user));

        $person = $this->physicalPerson($user);
        $user->refresh();

        $this->assertSame('Didije', $person->first_name);
        $this->assertSame('Drogba', $person->last_name);
        $this->assertSame('Radnja Ana', $person->entrepreneur_business_name);
        $this->assertSame('Didije Drogba', $user->name);
        $this->assertSame('Radnja Ana', $user->company_name);
    }

    public function test_legal_entity_admin_rename_changes_authorized_person_not_company_name(): void
    {
        $this->enableCanonicalHttpWrite();
        $user = $this->staleNamedUser(['company_name' => 'Primjer DOO']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user));

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba'))
            ->assertRedirect(route('admin.users.show', $user));

        $platform = PlatformIdentity::query()->where('user_id', $user->id)->firstOrFail();
        $entity = LegalEntityIdentity::query()->where('platform_identity_id', $platform->id)->firstOrFail();
        $authorized = LegalEntityAuthorizedPerson::query()
            ->where('legal_entity_identity_id', $entity->id)
            ->firstOrFail();
        $user->refresh();

        $this->assertSame('Primjer DOO', $entity->legal_name);
        $this->assertSame('Didije', $authorized->first_name);
        $this->assertSame('Drogba', $authorized->last_name);
        $this->assertSame('Didije', $user->first_name);
        $this->assertSame('Drogba', $user->last_name);
        $this->assertSame('Didije Drogba', $user->name);
        $this->assertSame('Primjer DOO', $user->company_name);
    }

    public function test_failed_account_save_rolls_back_canonical_name_and_users_name(): void
    {
        $this->enableCanonicalHttpWrite();
        $user = $this->staleNamedUser(['email' => 'stari@example.com']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['firstName' => 'Sistem', 'lastName' => '48'],
        ]));

        $event = 'eloquent.saving: '.User::class;
        $dispatcher = User::getEventDispatcher();
        $original = $dispatcher->getRawListeners()[$event] ?? [];
        $dispatcher->listen($event, function (User $saving) use ($user) {
            if ((int) $saving->id === (int) $user->id && $saving->first_name === 'Didije') {
                throw new RuntimeException('forced user save failure');
            }
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($this->admin())
                ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba', [
                    'email' => 'novi@example.com',
                ]));
            $this->fail('Expected the account save to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('forced user save failure', $e->getMessage());
        } finally {
            $dispatcher->forget($event);
            foreach ($original as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }

        $person = $this->physicalPerson($user);
        $user->refresh();

        $this->assertSame('Sistem', $person->first_name);
        $this->assertSame('48', $person->last_name);
        $this->assertSame('Sistem', $user->first_name);
        $this->assertSame('48', $user->last_name);
        $this->assertSame('Sistem 48', $user->name);
        $this->assertSame('stari@example.com', $user->email);
    }

    public function test_resaving_the_same_canonical_name_still_syncs_stale_users_name(): void
    {
        $this->enableCanonicalHttpWrite();
        $user = $this->staleNamedUser();
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['firstName' => 'Didije', 'lastName' => 'Drogba'],
        ]));

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba'))
            ->assertRedirect(route('admin.users.show', $user));

        $person = $this->physicalPerson($user);
        $user->refresh();

        $this->assertSame('Didije', $person->first_name);
        $this->assertSame('Drogba', $person->last_name);
        $this->assertSame('Didije Drogba', $user->name);
    }

    public function test_canonical_write_off_keeps_legacy_users_update_and_does_not_create_identity(): void
    {
        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => false,
            'identity.identity_write_freeze' => false,
        ]);

        $user = $this->staleNamedUser();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba'))
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();

        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame('Didije', $user->first_name);
        $this->assertSame('Drogba', $user->last_name);
        $this->assertSame('Didije Drogba', $user->name);
        $this->assertSame('+38268111000', $user->phone);
    }

    public function test_missing_subject_graph_rejects_the_entire_admin_update(): void
    {
        $this->enableCanonicalHttpWrite();
        $user = $this->staleNamedUser();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), $this->payload($user, 'Didije', 'Drogba', [
                'email' => 'nalog@example.com',
            ]))
            ->assertSessionHasErrors('first_name')->assertSessionMissing('success');

        $user->refresh();

        $this->assertSame(0, PlatformIdentity::query()->count());
        $this->assertSame('Sistem', $user->first_name);
        $this->assertSame('48', $user->last_name);
        $this->assertSame('Sistem 48', $user->name);
        $this->assertNotSame('nalog@example.com', $user->email);
    }

    private function physicalPerson(User $user): PhysicalPersonIdentity
    {
        $platform = PlatformIdentity::query()->where('user_id', $user->id)->firstOrFail();

        return PhysicalPersonIdentity::query()
            ->where('platform_identity_id', $platform->id)
            ->firstOrFail();
    }

    private function enableCanonicalHttpWrite(): void
    {
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function staleNamedUser(array $overrides = []): User
    {
        return $this->makeKorisnik(array_merge([
            'first_name' => 'Sistem',
            'last_name' => '48',
            'name' => 'Sistem 48',
            'phone' => '+38267000001',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $user, string $firstName, string $lastName, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone' => '+38268111000',
            'role_id' => $user->role_id,
            'activation_status' => 'active',
        ], $overrides);
    }
}
