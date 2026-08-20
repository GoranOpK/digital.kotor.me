<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class CanonicalUserModelProfileDashboardTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_entrepreneur_profile_follows_natural_person_semantics_without_required_pib(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => 'resident',
            'jmb' => '0303990123456',
            'pib' => null,
            'company_name' => null,
        ]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="physicalPersonFields"', $html);
        $this->assertStringContainsString('id="residentialStatusGroup"', $html);
        $this->assertStringContainsString('Poslovno ime', $html);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Petar',
                'last_name' => 'Preduzetnik',
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::ENTREPRENEUR,
                'residential_status' => 'resident',
                'jmb' => $user->jmb,
                'company_name' => 'Radnja Petar',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue($user->isNaturalPerson());
        $this->assertTrue($user->isEntrepreneur());
        $this->assertFalse($user->isLegalEntity());
        $this->assertNull($user->pib);
        $this->assertSame('Radnja Petar', $user->company_name);
        $this->assertSame('resident', $user->residential_status);
    }

    public function test_legal_entity_profile_does_not_require_residential_status_and_does_not_fallback(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'residential_status' => null,
            'jmb' => null,
            'pib' => '12345678',
            'company_name' => 'Firma DOO',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
                'residential_status' => 'resident',
                'pib' => '12345678',
                'company_name' => 'Firma DOO',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertNull($user->refresh()->residential_status);
    }

    public function test_existing_legal_entity_resident_row_is_not_bulk_cleared_on_profile_save(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::JOINT_STOCK_COMPANY,
            'residential_status' => 'resident',
            'jmb' => null,
            'pib' => '87654321',
            'company_name' => 'AD Test',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::JOINT_STOCK_COMPANY,
                'pib' => '87654321',
                'company_name' => 'AD Test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('resident', $user->refresh()->residential_status);
    }

    public function test_legacy_storage_remains_readable_but_new_legacy_writes_are_blocked(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
            'residential_status' => 'resident',
            'jmb' => null,
            'pib' => '13572468',
            'company_name' => 'Legacy Udruženje',
        ]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="'.UserType::LEGACY_ASSOCIATION_BUNDLE.'"', $html);
        $this->assertStringNotContainsString('value="'.UserType::LEGACY_INSTITUTION_BUNDLE.'"', $html);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::LEGACY_INSTITUTION_BUNDLE,
                'pib' => '13572468',
                'company_name' => 'Legacy Udruženje',
            ])
            ->assertSessionHasErrors('user_type');

        $this->assertSame(
            UserType::LEGACY_ASSOCIATION_BUNDLE,
            $user->refresh()->user_type
        );

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'user_type' => UserType::LEGACY_ASSOCIATION_BUNDLE,
                'pib' => '13572468',
                'company_name' => 'Legacy Udruženje',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            UserType::LEGACY_ASSOCIATION_BUNDLE,
            $user->refresh()->user_type
        );
    }

    public function test_dashboard_does_not_route_entrepreneur_as_legal_entity(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'residential_status' => 'resident',
        ]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Preduzetnik', $html);
        $this->assertStringNotContainsString('Servisi za pravna lica', $html);
    }

    public function test_staff_without_business_identity_can_update_profile_without_user_type(): void
    {
        $user = $this->makeKorisnik([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'user_type' => null,
            'residential_status' => null,
            'jmb' => null,
        ]);

        $html = $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="user_type"', $html);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Staff',
                'last_name' => 'Admin',
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame('Staff', $user->first_name);
        $this->assertNull($user->user_type);
        $this->assertNull($user->residential_status);
    }
}
