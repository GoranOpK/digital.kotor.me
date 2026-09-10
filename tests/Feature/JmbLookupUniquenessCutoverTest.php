<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbLookupException;
use App\Security\JmbLookupService;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class JmbLookupUniquenessCutoverTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 300;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
        $this->app->forgetInstance(JmbLookupService::class);
    }

    public function test_registration_duplicate_rejected_when_digest_exists_on_users_lookup(): void
    {
        $jmb = $this->nextJmb();
        $holder = User::factory()->create([
            'email' => 'lookup-user-holder@example.test',
            'jmb' => null,
        ]);
        DB::table('users')->where('id', $holder->id)->update(['jmb_lookup' => $this->digest($jmb)]);

        $this->post('/register', $this->registrationHttpPayload('dup.users.lookup@example.test', [
            'jmb' => $jmb,
        ]))->assertSessionHasErrors('jmb');

        $this->assertFalse(User::query()->where('email', 'dup.users.lookup@example.test')->exists());
        $this->assertSessionErrorDoesNotLeak($jmb);
    }

    public function test_registration_duplicate_rejected_when_digest_exists_on_physical_identity_lookup(): void
    {
        $jmb = $this->nextJmb();
        $this->seedPhysicalPerson(['jmb' => $jmb], 'lookup-fl-holder@example.test');

        $this->post('/register', $this->registrationHttpPayload('dup.fl.lookup@example.test', [
            'jmb' => $jmb,
        ]))->assertSessionHasErrors('jmb');

        $this->assertFalse(User::query()->where('email', 'dup.fl.lookup@example.test')->exists());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('jmb_lookup', $this->digest($jmb))->count());
        $this->assertSessionErrorDoesNotLeak($jmb);
    }

    public function test_registration_unique_jmb_is_accepted(): void
    {
        $jmb = $this->nextJmb();
        $this->post('/register', $this->registrationHttpPayload('unique.lookup@example.test', [
            'jmb' => $jmb,
        ]))->assertRedirect(route('verification.notice', absolute: false));

        $this->assertTrue(User::query()->where('email', 'unique.lookup@example.test')->exists());
        $this->assertSame(1, PhysicalPersonIdentity::query()->where('jmb', $jmb)->count());
    }

    public function test_profile_unchanged_own_jmb_is_accepted(): void
    {
        $jmb = $this->nextJmb();
        $user = $this->seedPhysicalPerson(['jmb' => $jmb], 'profile-own@example.test');

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame($jmb, $user->fresh()->jmb);
        $this->assertSame($this->digest($jmb), $user->fresh()->jmb_lookup);
    }

    public function test_profile_changing_to_another_users_jmb_is_rejected(): void
    {
        $taken = $this->nextJmb();
        $this->seedPhysicalPerson(['jmb' => $taken], 'profile-taken-user@example.test');
        $actor = $this->seedPhysicalPerson(['jmb' => $this->nextJmb()], 'profile-actor-user@example.test');

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($actor, ['jmb' => $taken]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotSame($taken, $actor->fresh()->jmb);
        $this->assertSessionErrorDoesNotLeak($taken);
    }

    public function test_profile_changing_to_another_physical_identity_jmb_is_rejected(): void
    {
        $taken = $this->nextJmb();
        $this->seedPhysicalPersonOnly($taken, 'profile-fl-only@example.test');
        $actor = $this->seedPhysicalPerson(['jmb' => $this->nextJmb()], 'profile-actor-fl@example.test');

        $this->actingAs($actor)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->profilePayload($actor, ['jmb' => $taken]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotSame($taken, $actor->fresh()->jmb);
        $this->assertSessionErrorDoesNotLeak($taken);
    }

    public function test_profile_unique_new_jmb_is_accepted(): void
    {
        $user = $this->seedPhysicalPerson(['jmb' => $this->nextJmb()], 'profile-new@example.test');
        $updated = $this->nextJmb();

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, ['jmb' => $updated]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertSame($updated, PhysicalPersonIdentity::query()->whereHas(
            'platformIdentity',
            fn ($q) => $q->where('user_id', $user->id)
        )->value('jmb'));
        $this->assertSame($this->digest($updated), PhysicalPersonIdentity::query()->whereHas(
            'platformIdentity',
            fn ($q) => $q->where('user_id', $user->id)
        )->value('jmb_lookup'));
    }

    public function test_declare_on_use_duplicate_in_users_is_rejected(): void
    {
        $jmb = $this->nextJmb();
        $holder = User::factory()->create([
            'email' => 'declare-user-holder@example.test',
            'jmb' => null,
        ]);
        DB::table('users')->where('id', $holder->id)->update(['jmb_lookup' => $this->digest($jmb)]);

        $actor = $this->makePreduzetnikUser('declare-dup-user@example.test');
        $this->actingAs($actor)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['jmb' => $jmb]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('identity.completion.create'));

        $this->assertSame(0, PhysicalPersonIdentity::query()->count());
        $this->assertSessionErrorDoesNotLeak($jmb);
    }

    public function test_declare_on_use_duplicate_in_physical_identities_is_rejected(): void
    {
        $jmb = $this->nextJmb();
        $this->seedPhysicalPerson(['jmb' => $jmb], 'declare-fl-holder@example.test');
        $actor = $this->makePreduzetnikUser('declare-dup-fl@example.test');

        $this->actingAs($actor)
            ->from(route('identity.completion.create'))
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['jmb' => $jmb]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('identity.completion.create'));

        $this->assertSame(1, PhysicalPersonIdentity::query()->count());
        $this->assertSessionErrorDoesNotLeak($jmb);
    }

    public function test_declare_on_use_unique_jmb_is_accepted(): void
    {
        $actor = $this->makePreduzetnikUser('declare-unique@example.test');
        $jmb = $this->nextJmb();

        $this->actingAs($actor)
            ->post(route('identity.completion.store'), $this->preduzetnikPayload(['jmb' => $jmb]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(1, PhysicalPersonIdentity::query()->where('jmb', $jmb)->count());
    }

    public function test_same_current_subject_in_legacy_and_canonical_rows_is_not_falsely_rejected(): void
    {
        $jmb = $this->nextJmb();
        $user = $this->seedPhysicalPerson(['jmb' => $jmb], 'same-subject@example.test');
        $this->assertSame($this->digest($jmb), $user->jmb_lookup);
        $this->assertSame(
            $this->digest($jmb),
            PhysicalPersonIdentity::query()->whereHas(
                'platformIdentity',
                fn ($q) => $q->where('user_id', $user->id)
            )->value('jmb_lookup')
        );

        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken($jmb, (int) $user->id));

        $this->actingAs($user)
            ->put(route('profile.update'), $this->profilePayload($user, ['jmb' => $jmb]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));
    }

    public function test_genuinely_different_subject_with_same_digest_is_rejected(): void
    {
        $jmb = $this->nextJmb();
        $owner = $this->seedPhysicalPerson(['jmb' => $jmb], 'other-subject-owner@example.test');
        $actor = $this->seedPhysicalPerson(['jmb' => $this->nextJmb()], 'other-subject-actor@example.test');

        $this->assertTrue((new CanonicalIdentifierUniqueness)->jmbTaken($jmb, (int) $actor->id));
        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken($jmb, (int) $owner->id));
    }

    public function test_missing_lookup_key_fails_closed(): void
    {
        $this->assertLookupKeyFailureFailsClosed('');
    }

    public function test_malformed_lookup_key_fails_closed(): void
    {
        $this->assertLookupKeyFailureFailsClosed('base64:@@@not-base64@@@');
    }

    public function test_targeted_uniqueness_service_does_not_use_plaintext_jmb_sql_equality(): void
    {
        $source = file_get_contents(base_path('app/Identity/Runtime/CanonicalIdentifierUniqueness.php'));
        $this->assertIsString($source);
        $this->assertMatchesRegularExpression(
            '/function jmbTaken\([\s\S]*?where\(\'jmb_lookup\'/',
            $source
        );
        $this->assertDoesNotMatchRegularExpression(
            '/function jmbTaken\([\s\S]*?where\(\'jmb\'\)/',
            $source
        );
        $this->assertStringNotContainsString("Rule::unique", $source);
    }

    public function test_lookup_digest_never_appears_in_validation_output_or_logs(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event->message.' '.json_encode($event->context, JSON_UNESCAPED_UNICODE);
        });

        $jmb = $this->nextJmb();
        $digest = $this->digest($jmb);
        $this->seedPhysicalPerson(['jmb' => $jmb], 'log-holder@example.test');

        $this->post('/register', $this->registrationHttpPayload('log.dup@example.test', [
            'jmb' => $jmb,
        ]))->assertSessionHasErrors('jmb');

        $this->assertSessionErrorDoesNotLeak($jmb, $digest);
        $combined = implode("\n", $logged);
        $this->assertStringNotContainsString($jmb, $combined);
        $this->assertStringNotContainsString($digest, $combined);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $combined);
    }

    public function test_users_jmb_unique_and_physical_lookup_non_unique_remain(): void
    {
        $this->assertContains('jmb', $this->uniqueColumns('users'));
        $this->assertContains('jmb_lookup', $this->uniqueColumns('users'));
        $this->assertNotContains('jmb_lookup', $this->uniqueColumns('physical_person_identities'));
        $this->assertTrue(Schema::hasColumn('users', 'jmb'));
        $this->assertTrue(Schema::hasColumn('physical_person_identities', 'jmb_lookup'));
    }

    public function test_null_jmb_is_not_taken(): void
    {
        User::factory()->create(['email' => 'null-jmb@example.test', 'jmb' => null]);
        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken(null));
        $this->assertFalse((new CanonicalIdentifierUniqueness)->jmbTaken(''));
    }

    private function assertLookupKeyFailureFailsClosed(string $key): void
    {
        $jmb = $this->nextJmb();
        $digest = $this->digest($jmb);
        config(['jmb.lookup.key' => $key]);
        $this->app->forgetInstance(JmbLookupService::class);

        try {
            (new CanonicalIdentifierUniqueness)->jmbTaken($jmb);
            $this->fail('Missing or malformed lookup key must fail closed.');
        } catch (JmbLookupException $e) {
            $this->assertStringNotContainsString($jmb, $e->getMessage());
            $this->assertStringNotContainsString($digest, $e->getMessage());
            if ($key !== '') {
                $this->assertStringNotContainsString($key, $e->getMessage());
            }
        }

        $this->post('/register', $this->registrationHttpPayload('failclosed.lookup@example.test', [
            'jmb' => $jmb,
        ]))->assertSessionHasErrors('jmb');

        $this->assertFalse(User::query()->where('email', 'failclosed.lookup@example.test')->exists());
        $this->assertSame(
            CanonicalIdentifierUniqueness::JMB_LOOKUP_UNAVAILABLE_MESSAGE,
            session('errors')?->first('jmb')
        );
        $this->assertSessionErrorDoesNotLeak($jmb, $digest);
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private function seedPhysicalPerson(array $person = [], string $email = 'cutover-fl@example.test'): User
    {
        $jmb = $person['jmb'] ?? $this->nextJmb();
        $user = $this->makeKorisnik([
            'email' => $email,
            'user_type' => UserType::PHYSICAL_PERSON,
            'jmb' => $jmb,
        ]);

        $snapshot = $this->flSnapshot($user, [
            'mobilePhone' => $user->phone,
            'person' => array_merge([
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'streetAndNumber' => $user->address,
                'city' => $user->city,
                'jmb' => $jmb,
            ], $person),
        ]);

        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        return $user->refresh();
    }

    private function seedPhysicalPersonOnly(string $jmb, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'jmb' => null,
            'user_type' => UserType::PHYSICAL_PERSON,
        ]);
        $platform = PlatformIdentity::create([
            'user_id' => $user->id,
            'subject_type' => PlatformIdentity::SUBJECT_PHYSICAL_PERSON,
            'mobile_phone' => '+38267000011',
        ]);
        PhysicalPersonIdentity::create([
            'platform_identity_id' => $platform->id,
            'first_name' => 'Ana',
            'last_name' => 'Test',
            'residential_status' => PhysicalPersonIdentity::RESIDENTIAL_RESIDENT,
            'id_document_type' => PhysicalPersonIdentity::DOCUMENT_JMB,
            'jmb' => $jmb,
            'street_and_number' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);

        return $user->refresh();
    }

    private function makePreduzetnikUser(string $email): User
    {
        return $this->makeKorisnik([
            'email' => $email,
            'user_type' => UserType::ENTREPRENEUR,
            'company_name' => 'Radnja Ana',
            'pib' => $this->validPib($this->jmbSerial + 700),
            'first_name' => 'Ana',
            'last_name' => 'Anić',
            'jmb' => $this->nextJmb(),
            'residential_status' => 'resident',
            'address' => 'Njegoševa 12',
            'city' => 'Kotor',
            'phone' => '+38267000001',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'user_type' => UserType::PHYSICAL_PERSON,
            'registers_as_entrepreneur' => '0',
            'residential_status' => 'resident',
            'jmb' => $user->jmb,
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
            'jmb' => $this->validJmb(12),
            'entrepreneur_business_name' => 'Radnja Ana',
            'pib' => $this->validPib(801),
            'crps_number' => $this->validCrps(1, 91),
            'street_and_number' => 'Obala 4',
            'city' => 'Bar',
            'phone_calling_code' => '+382',
            'phone_national' => '67111001',
        ], $overrides);
    }

    private function digest(string $jmb): string
    {
        return (string) app(JmbLookupService::class)->digest($jmb);
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }

    private function assertSessionErrorDoesNotLeak(string $jmb, ?string $digest = null): void
    {
        $digest ??= $this->digest($jmb);
        $errors = implode(' ', session('errors')?->all() ?? []);
        $this->assertStringNotContainsString($jmb, $errors);
        $this->assertStringNotContainsString($digest, $errors);
        $this->assertStringNotContainsString((string) config('jmb.encryption.key'), $errors);
    }

    /**
     * @return list<string>
     */
    private function uniqueColumns(string $table): array
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Non_unique = 0');
        $columns = [];
        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            $columns[] = (string) $index->Column_name;
        }

        return array_values(array_unique($columns));
    }
}
