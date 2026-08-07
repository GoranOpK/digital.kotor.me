<?php

namespace Tests\Feature;

use App\Models\CulturalLocation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-006 Korak 1 — katalog Lokacija (Urednik = kk_admin).
 */
class CulturalLocationCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
    }

    public function test_editor_can_create_location(): void
    {
        $response = $this->actingAs($this->editor)->post(route('cultural-locations.store'), [
            'naziv' => '  Trg od Oružja  ',
            'opis' => 'Centar grada',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-locations.index'));

        $location = CulturalLocation::query()->first();
        $this->assertNotNull($location);
        $this->assertSame('Trg od Oružja', $location->naziv);
        $this->assertSame('Centar grada', $location->opis);
        $this->assertSame(CulturalLocation::STATUS_ACTIVE, $location->status);
    }

    public function test_editor_can_update_location(): void
    {
        $location = CulturalLocation::create([
            'naziv' => 'Stari Grad',
            'opis' => null,
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->put(route('cultural-locations.update', $location), [
            'naziv' => 'Stari Grad Kotor',
            'opis' => 'Opis ažuriran',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-locations.index'));
        $location->refresh();
        $this->assertSame('Stari Grad Kotor', $location->naziv);
        $this->assertSame('Opis ažuriran', $location->opis);
    }

    public function test_editor_can_deactivate_location(): void
    {
        $location = CulturalLocation::create([
            'naziv' => 'Park',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-locations.deactivate', $location));

        $response->assertRedirect(route('cultural-locations.index'));
        $this->assertTrue($location->fresh()->isDeactivated());
    }

    public function test_active_duplicate_name_is_rejected_case_insensitive(): void
    {
        CulturalLocation::create([
            'naziv' => 'Katedrala Svetog Tripuna',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->from(route('cultural-locations.create'))->post(route('cultural-locations.store'), [
            'naziv' => '  katedrala svetog tripuna  ',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-locations.create'));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame(1, CulturalLocation::count());
    }

    public function test_deactivated_location_may_share_name_with_active_when_creating_deactivated(): void
    {
        CulturalLocation::create([
            'naziv' => 'Muzej',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-locations.store'), [
            'naziv' => 'Muzej',
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);

        $response->assertRedirect(route('cultural-locations.index'));
        $this->assertSame(2, CulturalLocation::count());
    }

    public function test_reactivation_blocked_when_active_duplicate_exists(): void
    {
        $deactivated = CulturalLocation::create([
            'naziv' => 'Luka',
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);
        CulturalLocation::create([
            'naziv' => 'luka',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-locations.activate', $deactivated));

        $response->assertRedirect(route('cultural-locations.edit', $deactivated));
        $response->assertSessionHasErrors('naziv');
        $this->assertTrue($deactivated->fresh()->isDeactivated());
    }

    public function test_editor_can_access_catalog_index(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-locations.index'))
            ->assertOk()
            ->assertSee('Katalog lokacija', false);
    }

    public function test_regular_user_cannot_access_catalog(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-locations.index'))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-locations.store'), [
                'naziv' => 'Nedozvoljeno',
                'status' => CulturalLocation::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cultural-locations.index'))
            ->assertRedirect();
    }
}
