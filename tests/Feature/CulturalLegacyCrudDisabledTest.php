<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 6A-CLOSE-02 — Legacy admin CRUD kill-switch (`cultural-events.*` → 403).
 */
class CulturalLegacyCrudDisabledTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private User $moderator;

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

        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $org = $this->makeOrganizer('Mod Org');
        $this->grantModerator($this->moderator, $org);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kk_admin_legacy_index_is_forbidden(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-events.index'))
            ->assertForbidden();
    }

    public function test_kk_admin_legacy_create_is_forbidden(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-events.create'))
            ->assertForbidden();
    }

    public function test_kk_admin_legacy_store_is_forbidden_and_writes_nothing(): void
    {
        $before = CulturalEvent::query()->count();

        $this->actingAs($this->editor)
            ->post(route('cultural-events.store'), $this->legacyPayload())
            ->assertForbidden();

        $this->assertSame($before, CulturalEvent::query()->count());
        $this->assertDatabaseMissing('cultural_events', ['naslov' => 'LEGACY_STORE_BLOCKED']);
    }

    public function test_kk_admin_legacy_edit_is_forbidden(): void
    {
        $event = $this->makeLegacyEvent(['naslov' => 'LEGACY_EDIT_TARGET']);

        $this->actingAs($this->editor)
            ->get(route('cultural-events.edit', ['dogadjaji' => $event->getKey()]))
            ->assertForbidden();
    }

    public function test_kk_admin_legacy_update_is_forbidden_and_writes_nothing(): void
    {
        $event = $this->makeLegacyEvent(['naslov' => 'LEGACY_UPDATE_ORIGINAL']);

        $this->actingAs($this->editor)
            ->put(route('cultural-events.update', ['dogadjaji' => $event->getKey()]), $this->legacyPayload([
                'naslov' => 'LEGACY_UPDATE_HACK',
            ]))
            ->assertForbidden();

        $this->assertSame('LEGACY_UPDATE_ORIGINAL', $event->fresh()->naslov);
        $this->assertDatabaseMissing('cultural_events', ['naslov' => 'LEGACY_UPDATE_HACK']);
    }

    public function test_kk_admin_legacy_destroy_is_forbidden_and_keeps_row(): void
    {
        $event = $this->makeLegacyEvent(['naslov' => 'LEGACY_DESTROY_TARGET']);

        $this->actingAs($this->editor)
            ->delete(route('cultural-events.destroy', ['dogadjaji' => $event->getKey()]))
            ->assertForbidden();

        $this->assertDatabaseHas('cultural_events', [
            'id' => $event->id,
            'naslov' => 'LEGACY_DESTROY_TARGET',
        ]);
    }

    public function test_ordinary_user_legacy_index_is_forbidden(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-events.index'))
            ->assertForbidden();
    }

    public function test_moderator_legacy_index_is_forbidden(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('cultural-events.index'))
            ->assertForbidden();
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
            'naslov' => 'Canonical after kill-switch',
            'opis' => 'Opis',
            'category_id' => $category->id,
        ]);

        $entry = CulturalEventEntry::query()->where('naslov', 'Canonical after kill-switch')->firstOrFail();
        $response->assertRedirect(route('cultural-event-entries.edit', $entry));
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
    }

    public function test_canonical_public_portal_and_detail_remain_ok(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

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

    public function test_navigation_dogadjaji_still_points_to_canonical(): void
    {
        $html = $this->actingAs($this->editor)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.e(route('cultural-event-entries.index')).'"',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.e(route('cultural-events.index')).'"',
            $html
        );
    }

    public function test_legacy_public_read_still_works_while_admin_crud_forbidden(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::LEGACY]);

        $event = $this->makeLegacyEvent(['naslov' => 'LEGACY_PUBLIC_ROLLBACK']);

        $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('LEGACY_PUBLIC_ROLLBACK', false);

        $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.show', ['event' => $event->id]))
            ->assertOk()
            ->assertSee('LEGACY_PUBLIC_ROLLBACK', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-events.index'))
            ->assertForbidden();

        $this->actingAs($this->editor)
            ->post(route('cultural-events.store'), $this->legacyPayload([
                'naslov' => 'LEGACY_PUBLIC_SHOULD_NOT_CREATE',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('cultural_events', [
            'naslov' => 'LEGACY_PUBLIC_SHOULD_NOT_CREATE',
        ]);
    }

    public function test_legacy_routes_remain_registered_with_deny_middleware(): void
    {
        foreach ([
            'cultural-events.index',
            'cultural-events.create',
            'cultural-events.store',
            'cultural-events.edit',
            'cultural-events.update',
            'cultural-events.destroy',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route: {$name}");
            $this->assertContains(
                'legacy_cultural_events_disabled',
                $route->gatherMiddleware(),
                "Expected deny middleware on {$name}"
            );
        }

        $canonical = Route::getRoutes()->getByName('cultural-event-entries.index');
        $this->assertNotNull($canonical);
        $this->assertNotContains(
            'legacy_cultural_events_disabled',
            $canonical->gatherMiddleware()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function legacyPayload(array $overrides = []): array
    {
        return array_merge([
            'naslov' => 'LEGACY_STORE_BLOCKED',
            'opis' => 'Opis',
            'datum_od' => '2026-08-20',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLegacyEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Legacy row',
            'opis' => 'Opis',
            'datum_od' => '2026-08-20',
            'datum_do' => null,
            'vrijeme' => '18:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
            'created_by' => $this->editor->id,
        ], $overrides));
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    private function grantModerator(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'organizer_id' => $organizer->id,
            ],
            [
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );
    }
}
