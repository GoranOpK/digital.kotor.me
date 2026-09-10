<?php

namespace Tests\Unit\Identity;

use App\Identity\LegacyIdentityAdapter;
use App\Models\Role;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class LegacyIdentityAdapterTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_adapter_copies_raw_users_fields_without_canonicalizing(): void
    {
        $user = $this->makeKorisnik([
            'residential_status' => 'non-resident',
            'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'company_name' => 'Jedno Ime',
            'jmb' => null,
            'pib' => '12345678',
        ]);

        $snapshot = (new LegacyIdentityAdapter)->forUser($user);

        $this->assertNotNull($snapshot);
        $this->assertTrue($snapshot->isRegisteredSubject);
        $this->assertNull($snapshot->subjectType);
        $this->assertNull($snapshot->physicalPerson);
        $this->assertNull($snapshot->legalEntity);
        $this->assertNull($snapshot->foreignBranch);
        $this->assertSame('non-resident', $snapshot->legacyFacts?->residentialStatus);
        $this->assertSame(UserType::LEGACY_ASSOCIATION_BUNDLE, $snapshot->legacyFacts?->userType);
        $this->assertSame('Jedno Ime', $snapshot->legacyFacts?->companyName);
        $this->assertSame($user->phone, $snapshot->legacyFacts?->phone);
        $this->assertSame($user->address, $snapshot->streetAndNumber);
        $this->assertNull($snapshot->legacyFacts?->jmb);
    }

    public function test_staff_without_user_type_has_no_registered_subject_snapshot(): void
    {
        $roleId = Role::query()->where('name', 'komisija')->value('id');
        $user = User::factory()->create([
            'role_id' => $roleId,
            'user_type' => null,
            'residential_status' => null,
            'first_name' => 'Član',
            'last_name' => 'Komisije',
        ]);

        $this->assertNull((new LegacyIdentityAdapter)->forUser($user->fresh(['role'])));
    }

    public function test_commission_legacy_user_type_fact_is_preserved_as_is(): void
    {
        $roleId = Role::query()->where('name', 'komisija')->value('id');
        $user = User::factory()->create([
            'role_id' => $roleId,
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
            'first_name' => 'Član',
            'last_name' => 'Komisije',
        ]);

        $snapshot = (new LegacyIdentityAdapter)->forUser($user->fresh(['role']));

        $this->assertNotNull($snapshot);
        $this->assertFalse($snapshot->isRegisteredSubject);
        $this->assertNull($snapshot->subjectType);
        $this->assertSame(UserType::PHYSICAL_PERSON, $snapshot->legacyFacts?->userType);
        $this->assertSame('resident', $snapshot->legacyFacts?->residentialStatus);
    }

    public function test_jmb_value_is_encrypted_first_and_does_not_use_desynced_plaintext(): void
    {
        $encrypted = $this->validJmb(41);
        $plaintext = $this->validJmb(42);
        $user = $this->makeKorisnik([
            'jmb' => $encrypted,
            'email' => 'legacy-jmb-read@example.test',
        ]);
        DB::table('users')->where('id', $user->id)->update(['jmb' => $plaintext]);

        $snapshot = (new LegacyIdentityAdapter)->forUser($user->fresh());

        $this->assertSame($encrypted, $snapshot?->legacyFacts?->jmb);
        $this->assertNotSame($plaintext, $snapshot?->legacyFacts?->jmb);
    }

    public function test_decrypt_failure_does_not_fall_back_to_plaintext(): void
    {
        $jmb = $this->validJmb(43);
        $user = $this->makeKorisnik([
            'jmb' => $jmb,
            'email' => 'legacy-jmb-fail@example.test',
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'jmb_encrypted' => 'jmb:v1:tampered',
        ]);

        $this->expectException(JmbEncryptedReadException::class);
        (new LegacyIdentityAdapter)->forUser($user->fresh());
    }
}
