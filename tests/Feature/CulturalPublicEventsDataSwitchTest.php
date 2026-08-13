<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-06 — canonical Pretraga (Phase B1: flag removed).
 */
class CulturalPublicEventsDataSwitchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->creator = $this->user;

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_events_list_uses_canonical_and_excludes_legacy_rows(): void
    {
        $this->makeLegacyEvent(['naslov' => 'LEGACY_ONLY_TITLE']);
        $this->makePublishedEntry('CANONICAL_ONLY_TITLE');

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));

        $response->assertOk();
        $response->assertSee('CANONICAL_ONLY_TITLE', false);
        $response->assertDontSee('LEGACY_ONLY_TITLE', false);
    }

    public function test_legacy_event_not_shown_on_events_list(): void
    {
        $this->makeLegacyEvent(['naslov' => 'EVENT_HIDDEN_IN_CANONICAL']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']))
            ->assertOk()
            ->assertDontSee('EVENT_HIDDEN_IN_CANONICAL', false);
    }

    public function test_canonical_shows_published_and_cancelled_entries(): void
    {

        $published = $this->makePublishedEntry('Pub Entry');
        $cancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Can Entry');
        $this->makeOccurrence($published, ['datum' => '2026-08-20']);
        $this->makeOccurrence($cancelled, ['datum' => '2026-08-21']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));

        $response->assertOk();
        $response->assertSee('Pub Entry', false);
        $response->assertSee('Can Entry', false);
        $response->assertSee('Otkazan', false);
    }

    public function test_canonical_hides_draft_pending_and_archived(): void
    {

        $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Draft Hidden');
        $this->makeEntry(CulturalEventEntry::STATUS_PENDING_APPROVAL, 'Pending Hidden');
        $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'Archived Hidden');
        $this->makePublishedEntry('Visible Pub');

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));

        $response->assertOk();
        $response->assertSee('Visible Pub', false);
        $response->assertDontSee('Draft Hidden', false);
        $response->assertDontSee('Pending Hidden', false);
        $response->assertDontSee('Archived Hidden', false);
    }

    public function test_canonical_q_filter_works(): void
    {

        $this->makePublishedEntry('Alpha Festival');
        $this->makePublishedEntry('Beta Night');

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji', 'q' => 'Festival']))
            ->assertOk()
            ->assertSee('Alpha Festival', false)
            ->assertDontSee('Beta Night', false);
    }

    public function test_canonical_category_and_location_filters_work(): void
    {

        $catA = $this->makeCategory('Koncert');
        $catB = $this->makeCategory('Izložba');
        $match = $this->makePublishedEntry('Match CatLoc', ['category_id' => $catA->id]);
        $other = $this->makePublishedEntry('Other CatLoc', ['category_id' => $catB->id]);
        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Palata',
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Trg',
        ]);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', [
                'tip' => 'dogadjaji',
                'category' => 'Koncert',
                'location' => 'Palata',
            ]))
            ->assertOk()
            ->assertSee('Match CatLoc', false)
            ->assertDontSee('Other CatLoc', false);
    }

    public function test_canonical_date_week_month_filters_work(): void
    {

        $onDay = $this->makePublishedEntry('On Day');
        $inWeek = $this->makePublishedEntry('In Week');
        $inMonth = $this->makePublishedEntry('In Month');
        $outside = $this->makePublishedEntry('Outside');

        $this->makeOccurrence($onDay, ['datum' => '2026-08-15']);
        $this->makeOccurrence($inWeek, ['datum' => '2026-08-12']);
        $this->makeOccurrence($inMonth, ['datum' => '2026-08-28']);
        $this->makeOccurrence($outside, ['datum' => '2026-09-02']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji', 'date' => '2026-08-15']))
            ->assertOk()
            ->assertSee('On Day', false)
            ->assertDontSee('Outside', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', [
                'tip' => 'dogadjaji',
                'week_start' => '2026-08-10',
                'week_end' => '2026-08-16',
            ]))
            ->assertOk()
            ->assertSee('In Week', false)
            ->assertSee('On Day', false)
            ->assertDontSee('Outside', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji', 'month' => '2026-08']))
            ->assertOk()
            ->assertSee('In Month', false)
            ->assertDontSee('Outside', false);
    }

    public function test_canonical_sort_follows_next_relevant_occurrence(): void
    {

        $later = $this->makePublishedEntry('Later Event');
        $earlier = $this->makePublishedEntry('Earlier Event');
        $this->makeOccurrence($later, ['datum' => '2026-08-25']);
        $this->makeOccurrence($earlier, ['datum' => '2026-08-12']);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']))
            ->assertOk()
            ->getContent();

        $posEarlier = strpos($html, 'Earlier Event');
        $posLater = strpos($html, 'Later Event');

        $this->assertNotFalse($posEarlier);
        $this->assertNotFalse($posLater);
        $this->assertLessThan($posLater, $posEarlier);
    }

    public function test_canonical_pagination_is_twelve(): void
    {

        for ($i = 1; $i <= 13; $i++) {
            $entry = $this->makePublishedEntry('Page Item '.$i);
            $this->makeOccurrence($entry, ['datum' => sprintf('2026-08-%02d', min(28, $i + 10))]);
        }

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));
        $response->assertOk();
        $events = $response->viewData('events');

        $this->assertSame(12, $events->perPage());
        $this->assertSame(13, $events->total());
        $this->assertCount(12, $events->items());
    }

    public function test_canonical_empty_dataset_does_not_crash(): void
    {

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']))
            ->assertOk()
            ->assertSee('Trenutno nema objavljenih događaja.', false);
    }

    public function test_canonical_card_shows_next_occurrence_and_additional_count(): void
    {

        $cat = $this->makeCategory('Teatar');
        $one = $this->makePublishedEntry('One Term', ['category_id' => $cat->id]);
        $three = $this->makePublishedEntry('Three Terms', ['category_id' => $cat->id]);

        $this->makeOccurrence($one, [
            'datum' => '2026-08-18',
            'vrijeme_od' => '19:30',
            'cjelodnevno' => false,
            'location_manual_name' => 'Scena',
        ]);

        $this->makeOccurrence($three, [
            'datum' => '2026-08-12',
            'vrijeme_od' => '10:00',
            'cjelodnevno' => false,
            'location_manual_name' => 'Forum',
        ]);
        $this->makeOccurrence($three, ['datum' => '2026-08-13', 'location_manual_name' => 'Forum']);
        $this->makeOccurrence($three, ['datum' => '2026-08-14', 'location_manual_name' => 'Forum']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'q' => 'One Term',
        ]));
        $response->assertOk();
        $response->assertSee('One Term', false);
        $response->assertSee('18.08.2026', false);
        $response->assertSee('19:30', false);
        $response->assertSee('Scena', false);
        $response->assertSee('Teatar', false);
        $response->assertDontSee('+ još', false);

        $responseThree = $this->actingAs($this->user)->get(route('cultural-calendar.events', [
            'tip' => 'dogadjaji',
            'q' => 'Three Terms',
        ]));
        $responseThree->assertOk();
        $responseThree->assertSee('Three Terms', false);
        $responseThree->assertSee('12.08.2026', false);
        $responseThree->assertSee('Forum', false);
        $responseThree->assertSee('+ još 2 termina', false);
    }

    public function test_canonical_category_options_come_from_active_catalog(): void
    {

        $this->makeCategory('Aktivna Kat');
        CulturalCategory::create([
            'naziv' => 'Neaktivna Kat',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.events', ['tip' => 'dogadjaji']));
        $options = $response->viewData('categoryOptions');

        $this->assertContains('Aktivna Kat', $options);
        $this->assertNotContains('Neaktivna Kat', $options);
        $this->assertNotContains('Nešto drugo', $options);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLegacyEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Legacy event',
            'opis' => 'Opis',
            'datum_od' => '2026-08-20',
            'datum_do' => null,
            'vrijeme' => '18:00:00',
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedEntry(string $naslov, array $extra = []): CulturalEventEntry
    {
        return $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, $naslov, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $status, string $naslov, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'status' => $status,
            'created_by' => $this->creator->id,
        ], $extra));
    }

    private function makeCategory(string $naziv): CulturalCategory
    {
        return CulturalCategory::create([
            'naziv' => $naziv,
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
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
