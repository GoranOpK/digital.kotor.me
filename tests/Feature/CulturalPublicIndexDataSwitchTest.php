<?php

namespace Tests\Feature;

use App\Models\CulturalEvent;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-07 — DATA SWITCH naslovne (legacy XOR canonical).
 */
class CulturalPublicIndexDataSwitchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_legacy_flag_uses_legacy_index_data(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::LEGACY]);

        $this->makeLegacyEvent(['naslov' => 'LEGACY_INDEX_ONLY']);
        $entry = $this->makePublishedEntry('CANONICAL_INDEX_ONLY');
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('LEGACY_INDEX_ONLY', false);
        $response->assertDontSee('CANONICAL_INDEX_ONLY', false);
    }

    public function test_canonical_flag_uses_canonical_index_data(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $this->makeLegacyEvent(['naslov' => 'LEGACY_INDEX_ONLY']);
        $entry = $this->makePublishedEntry('CANONICAL_INDEX_ONLY');
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('CANONICAL_INDEX_ONLY', false);
        $response->assertDontSee('LEGACY_INDEX_ONLY', false);
    }

    public function test_invalid_config_fail_safe_stays_legacy_on_index(): void
    {
        config(['cultural_calendar.public_read_source' => 'bogus']);

        $this->makeLegacyEvent(['naslov' => 'FAILSAFE_LEGACY_HOME']);
        $entry = $this->makePublishedEntry('FAILSAFE_CANONICAL_HOME');
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('FAILSAFE_LEGACY_HOME', false)
            ->assertDontSee('FAILSAFE_CANONICAL_HOME', false);
    }

    public function test_canonical_today_week_month_counts(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $todayEntry = $this->makePublishedEntry('Today Entry');
        $this->makeOccurrence($todayEntry, ['datum' => '2026-08-10']);
        $this->makeOccurrence($todayEntry, [
            'datum' => '2026-08-10',
            'vrijeme_od' => '20:00',
            'cjelodnevno' => false,
        ]);

        $weekEntry = $this->makePublishedEntry('Week Entry');
        $this->makeOccurrence($weekEntry, ['datum' => '2026-08-14']);

        $monthEntry = $this->makePublishedEntry('Month Entry');
        $this->makeOccurrence($monthEntry, ['datum' => '2026-08-28']);

        $outside = $this->makePublishedEntry('Outside');
        $this->makeOccurrence($outside, ['datum' => '2026-09-02']);

        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Draft Today');
        $this->makeOccurrence($draft, ['datum' => '2026-08-10']);

        $cancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Cancelled Today');
        $this->makeOccurrence($cancelled, ['datum' => '2026-08-10']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $response->assertOk();

        $this->assertSame(2, $response->viewData('todayCount')); // today + cancelled; draft excluded; no dup
        $this->assertGreaterThanOrEqual(3, $response->viewData('weekCount'));
        $this->assertGreaterThanOrEqual(4, $response->viewData('monthCount'));
        $this->assertSame(0, collect($response->viewData('calendarDays'))->firstWhere('date', '2026-09-02')['event_count'] ?? 0);
    }

    public function test_canonical_calendar_counts_distinct_entry_per_day(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $entry = $this->makePublishedEntry('Same Day Twice');
        $this->makeOccurrence($entry, ['datum' => '2026-08-15']);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'vrijeme_od' => '21:00',
            'cjelodnevno' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $days = collect($response->viewData('calendarDays'))->keyBy('date');

        $this->assertSame(1, $days['2026-08-15']['event_count'] ?? null);
    }

    public function test_canonical_featured_rules(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $featured = $this->makePublishedEntry('Featured Aktuelan', ['featured' => true]);
        $this->makeOccurrence($featured, ['datum' => '2026-08-20']);

        $notFeatured = $this->makePublishedEntry('Not Featured', ['featured' => false]);
        $this->makeOccurrence($notFeatured, ['datum' => '2026-08-18']);

        $cancelledFeatured = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Cancelled Featured', [
            'featured' => true,
        ]);
        $this->makeOccurrence($cancelledFeatured, ['datum' => '2026-08-19']);

        $pastFeatured = $this->makePublishedEntry('Past Featured', ['featured' => true]);
        $this->makeOccurrence($pastFeatured, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $featuredEvents = $response->viewData('featuredEvents');

        $titles = $featuredEvents->pluck('naslov')->all();
        $this->assertContains('Featured Aktuelan', $titles);
        $this->assertNotContains('Not Featured', $titles);
        $this->assertNotContains('Cancelled Featured', $titles);
        $this->assertNotContains('Past Featured', $titles);
    }

    public function test_canonical_featured_max_three_and_order_by_next_occ(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        foreach ([
            ['naslov' => 'Feat C', 'datum' => '2026-08-25'],
            ['naslov' => 'Feat A', 'datum' => '2026-08-12'],
            ['naslov' => 'Feat B', 'datum' => '2026-08-18'],
            ['naslov' => 'Feat D', 'datum' => '2026-08-28'],
        ] as $row) {
            $entry = $this->makePublishedEntry($row['naslov'], ['featured' => true]);
            $this->makeOccurrence($entry, ['datum' => $row['datum']]);
        }

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $featured = $response->viewData('featuredEvents');

        $this->assertCount(3, $featured);
        $this->assertSame(['Feat A', 'Feat B', 'Feat C'], $featured->pluck('naslov')->all());
    }

    public function test_canonical_upcoming_sort_and_exclusions(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $later = $this->makePublishedEntry('Upcoming Later');
        $earlier = $this->makePublishedEntry('Upcoming Earlier');
        $this->makeOccurrence($later, ['datum' => '2026-08-22']);
        $this->makeOccurrence($earlier, ['datum' => '2026-08-11']);

        $expiredOnly = $this->makePublishedEntry('Expired Only');
        $this->makeOccurrence($expiredOnly, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $postponedOnly = $this->makePublishedEntry('Postponed Only');
        $this->makeOccurrence($postponedOnly, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $multi = $this->makePublishedEntry('Multi Occ');
        $this->makeOccurrence($multi, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $this->makeOccurrence($multi, ['datum' => '2026-08-16']);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));
        $upcoming = $response->viewData('upcomingEvents');
        $titles = $upcoming->pluck('naslov')->all();

        $this->assertContains('Upcoming Earlier', $titles);
        $this->assertNotContains('Expired Only', $titles);
        $this->assertNotContains('Postponed Only', $titles);
        $this->assertLessThan(
            array_search('Upcoming Later', $titles, true),
            array_search('Upcoming Earlier', $titles, true)
        );

        $multiPos = array_search('Multi Occ', $titles, true);
        if ($multiPos !== false) {
            $this->assertSame(
                '2026-08-16',
                $upcoming[$multiPos]->nextRelevantOccurrence()->datum->format('Y-m-d')
            );
        }

        $this->assertLessThanOrEqual(3, $upcoming->count());
    }

    public function test_canonical_empty_index_keeps_hero_and_sections(): void
    {
        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $response = $this->actingAs($this->user)->get(route('cultural-calendar.index'));

        $response->assertOk();
        $response->assertSee('Logo Kalendara kulture', false);
        $response->assertSee('Danas', false);
        $response->assertSee('Ove sedmice', false);
        $response->assertSee('Naredni događaji', false);
        $response->assertSee('Istaknuti događaji', false);
        $response->assertSee('Trenutno nema istaknutih događaja.', false);
        $response->assertSee('0 događaja', false);
        $this->assertSame(0, $response->viewData('todayCount'));
        $this->assertCount(0, $response->viewData('upcomingEvents'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLegacyEvent(array $overrides = []): CulturalEvent
    {
        return CulturalEvent::create(array_merge([
            'naslov' => 'Legacy home',
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
            'created_by' => $this->user->id,
            'featured' => false,
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
