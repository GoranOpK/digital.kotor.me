<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Support\ResidentialStatusDeclaration;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class ResidentialStatusDeclarationTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_null_physical_person_is_applicable(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::PHYSICAL_PERSON,
            'residential_status' => null,
        ]);

        $this->assertTrue(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertFalse(ResidentialStatusDeclaration::isSatisfied($user));
    }

    public function test_null_entrepreneur_is_applicable(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => null,
        ]);

        $this->assertTrue(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertFalse(ResidentialStatusDeclaration::isSatisfied($user));
    }

    public function test_resident_physical_person_is_not_required(): void
    {
        $user = $this->makeKorisnik([
            'residential_status' => 'resident',
        ]);

        $this->assertFalse(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertTrue(ResidentialStatusDeclaration::isSatisfied($user));
    }

    public function test_non_resident_physical_person_is_not_required(): void
    {
        $user = $this->makeKorisnik([
            'residential_status' => 'non-resident',
        ]);

        $this->assertFalse(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertTrue(ResidentialStatusDeclaration::isSatisfied($user));
    }

    public function test_legal_entity_is_not_applicable(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'company_name' => 'Test DOO',
            'pib' => '12345678',
            'jmb' => null,
        ]);

        $this->assertFalse(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertTrue(ResidentialStatusDeclaration::isSatisfied($user));
    }

    public function test_staff_without_user_type_is_not_forced(): void
    {
        $user = $this->makeKorisnik([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'user_type' => null,
            'residential_status' => null,
            'jmb' => null,
        ]);

        $this->assertTrue($user->isStaffAccount());
        $this->assertFalse($user->collectsBusinessIdentity());
        $this->assertFalse(ResidentialStatusDeclaration::isApplicable($user));
        $this->assertTrue(ResidentialStatusDeclaration::isSatisfied($user));
    }
}
