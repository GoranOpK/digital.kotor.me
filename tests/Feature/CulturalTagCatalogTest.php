<?php

namespace Tests\Feature;

use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-007 Korak 1 — katalog Oznaka (Urednik = kk_admin).
 */
class CulturalTagCatalogTest extends TestCase
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

    public function test_editor_can_create_tag(): void
    {
        $response = $this->actingAs($this->editor)->post(route('cultural-tags.store'), [
            'naziv' => '  Besplatno  ',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-tags.index'));
        $tag = CulturalTag::query()->first();
        $this->assertNotNull($tag);
        $this->assertSame('Besplatno', $tag->naziv);
        $this->assertNull($tag->opis);
        $this->assertTrue($tag->isActive());
    }

    public function test_editor_can_update_tag(): void
    {
        $tag = CulturalTag::create([
            'naziv' => 'Porodično',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->put(route('cultural-tags.update', $tag), [
            'naziv' => 'Za porodicu',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('cultural-tags.index'));
        $this->assertSame('Za porodicu', $tag->fresh()->naziv);
    }

    public function test_create_and_edit_forms_omit_opis_field(): void
    {
        $tag = CulturalTag::create([
            'naziv' => 'Za uređivanje',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->get(route('cultural-tags.create'))
            ->assertOk()
            ->assertSee('Naziv', false)
            ->assertSee('Status', false)
            ->assertDontSee('name="opis"', false)
            ->assertDontSee('Opis (opciono)', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-tags.edit', $tag))
            ->assertOk()
            ->assertSee('Naziv', false)
            ->assertSee('Status', false)
            ->assertDontSee('name="opis"', false)
            ->assertDontSee('Opis (opciono)', false);
    }

    public function test_update_preserves_existing_opis_in_database(): void
    {
        $tag = CulturalTag::create([
            'naziv' => 'Stara',
            'opis' => 'Historijski opis',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->put(route('cultural-tags.update', $tag), [
                'naziv' => 'Nova',
                'status' => CulturalTag::STATUS_INACTIVE,
                // Legacy/malicious payload must not overwrite DB opis.
                'opis' => 'Pokušaj brisanja',
            ])
            ->assertRedirect(route('cultural-tags.index'));

        $fresh = $tag->fresh();
        $this->assertSame('Nova', $fresh->naziv);
        $this->assertTrue($fresh->isInactive());
        $this->assertSame('Historijski opis', $fresh->opis);
    }

    public function test_editor_can_deactivate_and_reactivate_tag(): void
    {
        $tag = CulturalTag::create([
            'naziv' => 'Na otvorenom',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-tags.deactivate', $tag))
            ->assertRedirect(route('cultural-tags.index'));
        $this->assertTrue($tag->fresh()->isInactive());

        $this->actingAs($this->editor)
            ->post(route('cultural-tags.activate', $tag))
            ->assertRedirect(route('cultural-tags.index'));
        $this->assertTrue($tag->fresh()->isActive());
    }

    public function test_active_duplicate_create_is_rejected(): void
    {
        CulturalTag::create([
            'naziv' => 'Večernji',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)
            ->from(route('cultural-tags.create'))
            ->post(route('cultural-tags.store'), [
                'naziv' => ' večernji ',
                'status' => CulturalTag::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('cultural-tags.create'));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame(1, CulturalTag::count());
    }

    public function test_update_into_active_duplicate_is_rejected(): void
    {
        CulturalTag::create([
            'naziv' => 'Dnevni',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);
        $other = CulturalTag::create([
            'naziv' => 'Jutarnji',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)
            ->from(route('cultural-tags.edit', $other))
            ->put(route('cultural-tags.update', $other), [
                'naziv' => 'dnevni',
                'status' => CulturalTag::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('cultural-tags.edit', $other));
        $response->assertSessionHasErrors('naziv');
        $this->assertSame('Jutarnji', $other->fresh()->naziv);
    }

    public function test_inactive_may_share_name_with_active(): void
    {
        CulturalTag::create([
            'naziv' => 'VIP',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-tags.store'), [
            'naziv' => 'VIP',
            'status' => CulturalTag::STATUS_INACTIVE,
        ]);

        $response->assertRedirect(route('cultural-tags.index'));
        $this->assertSame(2, CulturalTag::count());
    }

    public function test_reactivation_blocked_when_active_duplicate_exists(): void
    {
        $inactive = CulturalTag::create([
            'naziv' => 'Premijera',
            'status' => CulturalTag::STATUS_INACTIVE,
        ]);
        CulturalTag::create([
            'naziv' => 'premijera',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-tags.activate', $inactive));

        $response->assertRedirect(route('cultural-tags.edit', $inactive));
        $response->assertSessionHasErrors('naziv');
        $this->assertTrue($inactive->fresh()->isInactive());
    }

    public function test_editor_can_access_index(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-tags.index'))
            ->assertOk()
            ->assertSee('Katalog oznaka', false);
    }

    public function test_regular_user_cannot_access_catalog(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-tags.index'))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-tags.store'), [
                'naziv' => 'Nedozvoljeno',
                'status' => CulturalTag::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }
}
