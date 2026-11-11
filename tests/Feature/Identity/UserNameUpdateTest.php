<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Models\PlatformIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class UserNameUpdateTest extends TestCase
{
    use RefreshDatabase, MakesCanonicalUsers, MakesIdentitySnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
    }

    public static function categories(): array
    {
        return [['physical'], ['entrepreneur'], ['legal'], ['account']];
    }

    #[DataProvider('categories')]
    public function test_self_and_admin_rename_persist_and_preserve_unrelated_identity(string $category): void
    {
        $user = $this->target($category);
        $before = $category === 'account' ? null : (new CanonicalIdentityReader)->forUser($user);
        $email = $user->email;
        $this->actingAs($user)->put(route('profile.name.update'), [
            'first_name' => 'Marko', 'last_name' => 'Marković',
            'user_id' => 999999, 'email' => 'ignored@example.com', 'role_id' => 999999,
        ])->assertRedirect(route('profile.name.edit'))->assertSessionHasNoErrors();
        $this->assertSame('Marko Marković', $user->fresh()->name);
        $this->assertSame($email, $user->fresh()->email);
        $this->actingAs($user->fresh())->get(route('profile.name.edit'))->assertOk()->assertSee('Marković');
        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertOk()->assertSee('Marković');
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk()->assertSee('Marko Marković');
        if ($before) {
            $after = (new CanonicalIdentityReader)->forUser($user);
            $this->assertSame($before->mobilePhone, $after->mobilePhone);
            $this->assertSame($before->physicalPerson?->jmb ?? $before->legalEntity?->authorizedPerson?->jmb,
                $after->physicalPerson?->jmb ?? $after->legalEntity?->authorizedPerson?->jmb);
            $this->assertSame($before->legalEntity?->legalName, $after->legalEntity?->legalName);
            $this->assertSame($before->physicalPerson?->entrepreneurBusinessName, $after->physicalPerson?->entrepreneurBusinessName);
        }
        $admin = $this->account('admin');
        $this->actingAs($admin)->put(route('admin.users.update', $user), $this->adminPayload($user))
            ->assertRedirect(route('admin.users.show', $user))->assertSessionHasNoErrors();
        $this->assertSame('Petar Petrović', $user->fresh()->name);
        $this->get(route('admin.users.show', $user))->assertOk()->assertSee('Petar Petrović');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Petar Petrović');
        $this->get(route('admin.users.edit', $user))->assertOk()->assertSee('Petrović');
        if ($category === 'account') {
            $this->assertFalse(PlatformIdentity::where('user_id', $user->id)->exists());
        } else {
            $saved = (new CanonicalIdentityReader)->forUser($user);
            $this->assertSame('Petar', $saved->physicalPerson?->firstName ?? $saved->legalEntity?->authorizedPerson?->firstName);
        }
    }

    public static function modes(): array
    {
        $cases = [];
        foreach ([false, true] as $read) foreach ([false, true] as $write) foreach ([false, true] as $freeze) {
            $cases[] = [$read, $write, $freeze];
        }
        return $cases;
    }

    #[DataProvider('modes')]
    public function test_subject_modes_never_silently_save_only_account_fields(bool $read, bool $write, bool $freeze): void
    {
        $user = $this->target('physical');
        $admin = $this->account('admin');
        config(['identity.canonical_read' => $read, 'identity.canonical_write' => $write, 'identity.identity_write_freeze' => $freeze]);
        $response = $this->actingAs($admin)->from(route('admin.users.edit', $user))
            ->put(route('admin.users.update', $user), $this->adminPayload($user, ['email' => 'changed@example.com']));
        if ($read && $write) {
            $response->assertSessionHasNoErrors();
            $this->assertSame('Petar Petrović', $user->fresh()->name);
        } else {
            $response->assertSessionHasErrors('first_name')->assertSessionMissing('success');
            $this->assertSame('Didije Drogba', $user->fresh()->name);
            $this->assertSame($user->email, $user->fresh()->email);
        }
        $self = $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković']);
        if ($read && $write) {
            $self->assertSessionHasNoErrors();
        } else {
            $self->assertSessionHasErrors('first_name')->assertSessionMissing('success');
        }
    }

    public function test_missing_broken_and_undecryptable_subject_graphs_fail_without_partial_admin_save(): void
    {
        $admin = $this->account('admin');
        foreach (['missing', 'broken', 'cipher'] as $kind) {
            $user = $this->makeKorisnik(['first_name' => 'Didije', 'last_name' => 'Drogba', 'jmb' => null]);
            if ($kind !== 'missing') {
                (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user));
                if ($kind === 'broken') {
                    PhysicalPersonIdentity::whereIn('platform_identity_id', PlatformIdentity::where('user_id', $user->id)->select('id'))->delete();
                } else {
                    DB::table('physical_person_identities')->whereIn('platform_identity_id', PlatformIdentity::where('user_id', $user->id)->select('id'))
                        ->update(['jmb_encrypted' => 'invalid-ciphertext']);
                }
            }
            $this->actingAs($admin)->put(route('admin.users.update', $user), $this->adminPayload($user, ['email' => 'changed@example.com']))
                ->assertSessionHasErrors('first_name')->assertSessionMissing('success');
            $this->assertSame($user->email, $user->fresh()->email);
            $this->assertSame('Didije Drogba', $user->fresh()->name);
            $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])
                ->assertSessionHasErrors('first_name')->assertSessionMissing('success');
        }
    }

    public function test_unverified_self_update_cannot_target_another_user_and_superadmin_stays_protected(): void
    {
        $user = $this->account('superadmin');
        $user->forceFill(['email_verified_at' => null])->save();
        $other = $this->target('physical');
        $this->actingAs($user)->get(route('profile.name.edit'))->assertOk();
        $this->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković', 'user_id' => $other->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('Didije Drogba', $other->fresh()->name);
        $this->assertSame('Marko Marković', $user->fresh()->name);
        $this->get(route('profile.edit'))->assertRedirect(route('verification.notice'));
        $this->actingAs($this->account('admin'))->put(route('admin.users.update', $user), $this->adminPayload($user))->assertForbidden();
        $this->actingAs($other)->put(route('admin.users.update', $user), $this->adminPayload($user))->assertForbidden();
    }

    public function test_same_authoritative_name_repairs_stale_account_mirror(): void
    {
        $user = $this->target('physical');
        DB::table('users')->where('id', $user->id)->update(['first_name' => 'Old', 'last_name' => 'Old', 'name' => 'Old Old']);
        $this->actingAs($user->fresh())->put(route('profile.name.update'), ['first_name' => 'Didije', 'last_name' => 'Drogba'])->assertSessionHasNoErrors();
        $this->assertSame('Didije Drogba', $user->fresh()->name);
    }

    public function test_guest_and_invalid_names_are_rejected(): void
    {
        $this->get(route('profile.name.edit'))->assertRedirect(route('login'));
        $this->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])->assertRedirect(route('login'));
        $user = $this->target('physical');
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => '', 'last_name' => str_repeat('x', 256)])
            ->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertSame('Didije Drogba', $user->fresh()->name);
    }

    #[DataProvider('modes')]
    public function test_legitimate_account_name_is_independent_of_subject_write_switches(bool $read, bool $write, bool $freeze): void
    {
        $user = $this->account('komisija');
        config(['identity.canonical_read' => $read, 'identity.canonical_write' => $write, 'identity.identity_write_freeze' => $freeze]);
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])->assertSessionHasNoErrors();
        $this->assertSame('Marko Marković', $user->fresh()->name);
        $this->assertFalse(PlatformIdentity::where('user_id', $user->id)->exists());
    }

    public function test_staff_with_subject_evidence_is_not_account_only(): void
    {
        $user = $this->account('komisija');
        $user->forceFill(['pib' => '12345672'])->save();
        $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković'])
            ->assertSessionHasErrors('first_name');
        $this->assertSame('Didije Drogba', $user->fresh()->name);
    }

    public function test_failed_name_save_rolls_back_the_canonical_graph(): void
    {
        $user = $this->target('physical');
        $event = 'eloquent.saving: '.User::class;
        $dispatcher = User::getEventDispatcher();
        $original = $dispatcher->getRawListeners()[$event] ?? [];
        $dispatcher->listen($event, function (User $saving) use ($user) {
            if ($saving->id === $user->id && $saving->first_name === 'Marko') throw new \RuntimeException('forced name save failure');
        });
        try {
            $this->withoutExceptionHandling()->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Marković']);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced name save failure', $e->getMessage());
        } finally {
            $dispatcher->forget($event);
            foreach ($original as $listener) $dispatcher->listen($event, $listener);
        }
        $this->assertSame('Didije Drogba', $user->fresh()->name);
        $this->assertSame('Didije', (new CanonicalIdentityReader)->forUser($user)->physicalPerson->firstName);
    }

    public function test_full_profile_updates_name_and_rolls_back_when_later_account_save_fails(): void
    {
        $user = $this->target('physical');
        $payload = ['first_name' => 'Marko', 'last_name' => 'Marković', 'email' => 'new@example.com',
            'phone' => '+38267111222', 'address' => 'Njegoševa 12', 'city' => 'Kotor',
            'user_type' => \App\Support\UserType::PHYSICAL_PERSON, 'residential_status' => 'resident', 'jmb' => (new CanonicalIdentityReader)->forUser($user)->physicalPerson->jmb];
        $event = 'eloquent.saving: '.User::class;
        $dispatcher = User::getEventDispatcher();
        $original = $dispatcher->getRawListeners()[$event] ?? [];
        $dispatcher->listen($event, function (User $saving) use ($user) {
            if ($saving->id === $user->id && $saving->email === 'new@example.com') throw new \RuntimeException('forced email failure');
        });
        try {
            $this->withoutExceptionHandling()->actingAs($user)->put(route('profile.update'), $payload);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced email failure', $e->getMessage());
        } finally {
            $dispatcher->forget($event);
            foreach ($original as $listener) $dispatcher->listen($event, $listener);
        }
        $this->assertSame('Didije Drogba', $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame('Didije', (new CanonicalIdentityReader)->forUser($user)->physicalPerson->firstName);
        $this->put(route('profile.update'), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Marko Marković', $user->fresh()->name);
    }
    public function test_cancelled_model_saves_cannot_report_success(): void
    {
        $user = $this->target('physical');
        foreach ([User::class, PhysicalPersonIdentity::class] as $model) {
            $event = 'eloquent.saving: '.$model;
            $dispatcher = $model::getEventDispatcher();
            $original = $dispatcher->getRawListeners()[$event] ?? [];
            $dispatcher->listen($event, fn ($saving) => $saving->first_name === 'Marko' ? false : null);
            try {
                $this->actingAs($user)->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Markovic'])
                    ->assertSessionHasErrors('first_name')->assertSessionMissing('success');
            } finally {
                $dispatcher->forget($event);
                foreach ($original as $listener) $dispatcher->listen($event, $listener);
            }
            $this->assertSame('Didije Drogba', $user->fresh()->name);
            $this->assertSame('Didije', (new CanonicalIdentityReader)->forUser($user)->physicalPerson->firstName);
        }
    }
    private function target(string $category): User
    {
        if ($category === 'account') return $this->account('komisija');
        $user = $this->makeKorisnik(['first_name' => 'Didije', 'last_name' => 'Drogba', 'jmb' => null]);
        $snapshot = match ($category) {
            'legal' => $this->plSnapshot($user, ['firstName' => 'Didije', 'lastName' => 'Drogba']),
            'entrepreneur' => $this->entrepreneurSnapshot($user),
            default => $this->flSnapshot($user, ['person' => ['firstName' => 'Didije', 'lastName' => 'Drogba']]),
        };
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        return $user;
    }

    private function account(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $role)->firstOrFail()->id,
            'user_type' => null, 'first_name' => 'Didije', 'last_name' => 'Drogba',
            'activation_status' => 'active', 'email_verified_at' => now()]);
    }

    private function adminPayload(User $user, array $overrides = []): array
    {
        return array_merge(['first_name' => 'Petar', 'last_name' => 'Petrović', 'email' => $user->email,
            'phone' => $user->phone, 'role_id' => $user->role_id, 'activation_status' => 'active'], $overrides);
    }
}
