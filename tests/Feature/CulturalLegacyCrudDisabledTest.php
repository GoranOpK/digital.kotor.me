<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase B2 — legacy CulturalEvent admin CRUD routes removed.
 */
class CulturalLegacyCrudDisabledTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_legacy_cultural_events_route_names_do_not_exist(): void
    {
        foreach ([
            'cultural-events.index',
            'cultural-events.create',
            'cultural-events.store',
            'cultural-events.edit',
            'cultural-events.update',
            'cultural-events.destroy',
        ] as $name) {
            $this->assertNull(
                Route::getRoutes()->getByName($name),
                "Legacy route should not be registered: {$name}"
            );
        }
    }

    public function test_cultural_event_controller_class_does_not_exist(): void
    {
        $this->assertFalse(
            class_exists(\App\Http\Controllers\CulturalEventController::class),
            'CulturalEventController must be removed'
        );
    }

    public function test_legacy_crud_url_returns_404(): void
    {
        $this->actingAs($this->editor)
            ->get('/kalendar-kulture/dogadjaji')
            ->assertNotFound();

        $this->actingAs($this->regularUser)
            ->get('/kalendar-kulture/dogadjaji')
            ->assertNotFound();

        $this->actingAs($this->editor)
            ->get('/kalendar-kulture/dogadjaji/create')
            ->assertNotFound();
    }

    public function test_canonical_admin_index_and_create_remain_ok(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk();

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.create'))
            ->assertOk()
            ->assertSee('Novi događaj', false);
    }

    public function test_canonical_store_flow_still_works(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->editor)->post(route('cultural-event-entries.store'), [
            'naslov' => 'Canonical after legacy removal',
            'opis' => 'Opis',
            'category_id' => $category->id,
        ]);

        $entry = CulturalEventEntry::query()->where('naslov', 'Canonical after legacy removal')->firstOrFail();
        $response->assertRedirect(route('cultural-event-entries.edit', $entry));
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
    }

    public function test_canonical_public_portal_and_detail_remain_ok(): void
    {
        $entry = CulturalEventEntry::create([
            'naslov' => 'Canonical public OK',
            'opis' => 'Opis',
            'status' => CulturalEventEntry::STATUS_PUBLISHED,
            'created_by' => $this->editor->id,
            'featured' => false,
        ]);
        CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-08-20',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'cjelodnevno' => true,
        ]);

        $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('Canonical public OK', false);

        $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.show', ['event' => $entry->id]))
            ->assertOk()
            ->assertSee('Canonical public OK', false);
    }

    public function test_navigation_has_no_legacy_crud_href(): void
    {
        $html = $this->actingAs($this->editor)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('/kalendar-kulture/dogadjaji"', $html);
        $this->assertStringNotContainsString("'/kalendar-kulture/dogadjaji'", $html);
        $this->assertStringContainsString(
            'href="'.e(route('cultural-calendar.events')).'"',
            $html
        );
    }
}
