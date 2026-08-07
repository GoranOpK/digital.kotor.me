<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-007 Korak 1 — katalog Kategorija (Urednik = kk_admin).
 */
class CulturalCategoryCatalogTest extends TestCase
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

    public function test_editor_can_create_category(): void
    {
        $response = $this->actingAs($this->editor)->post(route('cultural-categories.store'), [
            'naziv' => '  Koncerti  ',
            'opis' => 'Opis',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-categories.index'));
        $category = CulturalCategory::query()->first();
        $this->assertNotNull($category);
        $this->assertSame('Koncerti', $category->naziv);
        $this->assertTrue($category->isActive());
    }

    public function test_editor_can_update_category(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Predstave',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->put(route('cultural-categories.update', $category), [
            'naziv' => 'Pozorišne predstave',
            'opis' => 'Ažurirano',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-categories.index'));
        $this->assertSame('Pozorišne predstave', $category->fresh()->naziv);
    }

    public function test_editor_can_deactivate_and_reactivate_category(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Izložbe',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-categories.deactivate', $category))
            ->assertRedirect(route('cultural-categories.index'));
        $this->assertTrue($category->fresh()->isInactive());

        $this->actingAs($this->editor)
            ->post(route('cultural-categories.activate', $category))
            ->assertRedirect(route('cultural-categories.index'));
        $this->assertTrue($category->fresh()->isActive());
    }

    public function test_active_duplicate_create_is_rejected(): void
    {
        CulturalCategory::create([
            'naziv' => 'Radionice',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)
            ->from(route('cultural-categories.create'))
            ->post(route('cultural-categories.store'), [
                'naziv' => '  radionice  ',
                'status' => CulturalCategory::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('cultural-categories.create'));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame(1, CulturalCategory::count());
    }

    public function test_update_into_active_duplicate_is_rejected(): void
    {
        CulturalCategory::create([
            'naziv' => 'Performansi',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $other = CulturalCategory::create([
            'naziv' => 'Paneli',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)
            ->from(route('cultural-categories.edit', $other))
            ->put(route('cultural-categories.update', $other), [
                'naziv' => 'performansi',
                'status' => CulturalCategory::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('cultural-categories.edit', $other));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame('Paneli', $other->fresh()->naziv);
    }

    public function test_inactive_may_share_name_with_active(): void
    {
        CulturalCategory::create([
            'naziv' => 'Muzej',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-categories.store'), [
            'naziv' => 'Muzej',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $response->assertRedirect(route('cultural-categories.index'));
        $this->assertSame(2, CulturalCategory::count());
    }

    public function test_reactivation_blocked_when_active_duplicate_exists(): void
    {
        $inactive = CulturalCategory::create([
            'naziv' => 'Festival',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);
        CulturalCategory::create([
            'naziv' => 'festival',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-categories.activate', $inactive));

        $response->assertRedirect(route('cultural-categories.edit', $inactive));
        $response->assertSessionHasErrors('naziv');
        $this->assertTrue($inactive->fresh()->isInactive());
    }

    public function test_nesto_drugo_is_forbidden(): void
    {
        $response = $this->actingAs($this->editor)
            ->from(route('cultural-categories.create'))
            ->post(route('cultural-categories.store'), [
                'naziv' => '  nešto drugo  ',
                'status' => CulturalCategory::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('cultural-categories.create'));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame(0, CulturalCategory::count());
    }

    public function test_editor_can_access_index(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-categories.index'))
            ->assertOk()
            ->assertSee('Katalog kategorija', false);
    }

    public function test_regular_user_cannot_access_catalog(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-categories.index'))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-categories.store'), [
                'naziv' => 'Nedozvoljeno',
                'status' => CulturalCategory::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }
}
