<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Package A — cultural-calendar.day canonical cutover (Phase B1: flag removed).
 */
class CulturalCalendarDayCanonicalCutoverTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $editor;

    private User $creator;

    private CulturalCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->creator = $this->editor;

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_canonical_entry_appears_on_correct_day_and_not_wrong_day(): void
    {
        $entry = $this->makePublishedEntry('DAY_CORRECT_TITLE', '2026-08-15', '18:30:00');

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk()
            ->assertViewIs('cultural-calendar.day')
            ->assertSee('DAY_CORRECT_TITLE', false)
            ->assertSee('18:30', false)
            ->assertSee('Koncerti', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-16']))
            ->assertOk()
            ->assertDontSee('DAY_CORRECT_TITLE', false);

        $this->assertDatabaseMissing('cultural_events', ['naslov' => 'DAY_CORRECT_TITLE']);
        $this->assertSame($entry->id, CulturalEventEntry::query()->where('naslov', 'DAY_CORRECT_TITLE')->value('id'));
    }

    public function test_multiple_matching_occ_same_day_renders_one_card_with_occurrence_on_date_time(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'DAY_MULTI_OCC');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'vrijeme_od' => '20:00:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'vrijeme_od' => '10:00:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk();

        $events = $response->viewData('events');
        $this->assertCount(1, $events);
        $this->assertSame($entry->id, $events->first()->id);
        // occurrenceOnDate prefers earliest Planned by time → 10:00
        $response->assertSee('DAY_MULTI_OCC', false);
        $response->assertSee('10:00', false);
        $response->assertDontSee('20:00', false);
    }

    public function test_postponed_and_published_cancelled_occ_excluded_cancelled_entry_included(): void
    {
        $planned = $this->makePublishedEntry('DAY_PLANNED', '2026-08-15', '12:00:00');

        $postponed = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'DAY_POSTPONED');
        $this->makeOccurrence($postponed, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $occCancelled = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'DAY_OCC_CANCELLED');
        $this->makeOccurrence($occCancelled, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $entryCancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'DAY_ENTRY_CANCELLED');
        $this->makeOccurrence($entryCancelled, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk();

        $ids = $response->viewData('events')->pluck('id')->all();
        $this->assertContains($planned->id, $ids);
        $this->assertContains($entryCancelled->id, $ids);
        $this->assertNotContains($postponed->id, $ids);
        $this->assertNotContains($occCancelled->id, $ids);

        $response->assertSee('DAY_PLANNED', false);
        $response->assertSee('DAY_ENTRY_CANCELLED', false);
        $response->assertDontSee('DAY_POSTPONED', false);
        $response->assertDontSee('DAY_OCC_CANCELLED', false);
        $response->assertDontSee('kk-public-status-badge', false);
    }

    public function test_legacy_event_does_not_leak_into_canonical_day_result(): void
    {
        $this->makePublishedEntry('DAY_CANONICAL_ONLY', '2026-08-15', '11:00:00');
        CulturalEvent::create([
            'naslov' => 'DAY_LEGACY_LEAK',
            'opis' => 'Ne smije na canonical day',
            'datum_od' => '2026-08-15',
            'datum_do' => null,
            'vrijeme' => '09:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk();

        $response->assertSee('DAY_CANONICAL_ONLY', false);
        $response->assertDontSee('DAY_LEGACY_LEAK', false);
        $this->assertTrue(
            $response->viewData('events')->every(fn ($e) => $e instanceof CulturalEventEntry)
        );
    }

    public function test_manifestation_linked_canonical_entry_remains_visible(): void
    {
        $mf = CulturalManifestation::create([
            'naziv' => 'DAY_MF',
            'status' => CulturalManifestation::STATUS_PUBLISHED,
            'created_by' => $this->creator->id,
            'published_at' => now(),
        ]);
        $entry = $this->makePublishedEntry('DAY_MF_LINKED_EVENT', '2026-08-15', '16:00:00', [
            'manifestation_id' => $mf->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk()
            ->assertSee('DAY_MF_LINKED_EVENT', false);

        $this->assertSame($entry->id, CulturalEventEntry::query()
            ->where('manifestation_id', $mf->id)
            ->where('naslov', 'DAY_MF_LINKED_EVENT')
            ->value('id'));
    }

    public function test_invalid_date_returns_404_and_kk_admin_redirect_unchanged(): void
    {
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-13-40']))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => 'not-a-date']))
            ->assertNotFound();

        $this->actingAs($this->editor)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertRedirect(route('cultural-event-entries.create'));
    }

    public function test_day_view_has_no_detail_links(): void
    {
        $entry = $this->makePublishedEntry('DAY_NO_DETAIL_LINK', '2026-08-15', '13:00:00');

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.day', ['date' => '2026-08-15']))
            ->assertOk()
            ->assertSee('DAY_NO_DETAIL_LINK', false)
            ->getContent();

        $this->assertStringNotContainsString(
            route('cultural-calendar.show', $entry),
            $html
        );
        $this->assertStringNotContainsString('kk-public-status-badge', $html);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedEntry(
        string $naslov,
        string $datum,
        string $vrijemeOd,
        array $extra = []
    ): CulturalEventEntry {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, $naslov, $extra);
        $this->makeOccurrence($entry, [
            'datum' => $datum,
            'vrijeme_od' => $vrijemeOd,
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'location_manual_name' => 'Trg od Oružja',
        ]);

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $status, string $naslov, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'opis' => 'Opis '.$naslov,
            'status' => $status,
            'category_id' => $this->category->id,
            'created_by' => $this->creator->id,
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOccurrence(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }
}
