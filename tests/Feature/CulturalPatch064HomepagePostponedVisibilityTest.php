<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-064 — informativna naslovna vidljivost Odgođenog (TM-JP-23…38 subset).
 */
class CulturalPatch064HomepagePostponedVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $editor;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private CulturalPublicEventQuery $publicQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->publicQuery = app(CulturalPublicEventQuery::class);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_planned_only_standard_card(): void
    {
        $entry = $this->makePublished('Planned Only', ['2026-08-20']);

        $cards = $this->publicQuery->homepageUpcomingCards();
        $this->assertCount(1, $cards);
        $this->assertSame('planned', $cards[0]->homepage_card_mode);
        $this->assertSame('2026-08-20', $cards[0]->homepage_ranking_date);
        $this->assertSame($entry->id, $cards[0]->id);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('Planned Only', false)
            ->getContent();

        $this->assertStringNotContainsString('Prvobitni termin:', $html);
        $this->assertStringNotContainsString('+ još', $html);
    }

    public function test_planned_plus_postponed_same_entry_uses_planned_only(): void
    {
        $entry = $this->makePublished('Mixed Entry', ['2026-08-15', '2026-08-25']);
        $this->occurrenceLifecycle->postpone(
            $entry->occurrences()->whereDate('datum', '2026-08-15')->firstOrFail()
        );

        $cards = $this->publicQuery->homepageUpcomingCards();
        $match = $cards->firstWhere('id', $entry->id);

        $this->assertNotNull($match);
        $this->assertSame('planned', $match->homepage_card_mode);
        $this->assertSame('2026-08-25', $match->homepage_ranking_date);
        $this->assertCount(1, $cards->where('id', $entry->id));
    }

    public function test_postponed_today_and_tomorrow_visible_yesterday_not(): void
    {
        $today = $this->makePublished('Info Today', ['2026-08-10']);
        $this->occurrenceLifecycle->postpone($today->occurrences()->firstOrFail());

        $tomorrow = $this->makePublished('Info Tomorrow', ['2026-08-11']);
        $this->occurrenceLifecycle->postpone($tomorrow->occurrences()->firstOrFail());

        $yesterday = $this->makePublished('Info Yesterday', ['2026-08-09']);
        $this->occurrenceLifecycle->postpone($yesterday->occurrences()->firstOrFail());

        $cards = $this->publicQuery->homepageUpcomingCards();
        $titles = $cards->pluck('naslov')->all();

        $this->assertContains('Info Today', $titles);
        $this->assertContains('Info Tomorrow', $titles);
        $this->assertNotContains('Info Yesterday', $titles);

        $todayCard = $cards->firstWhere('naslov', 'Info Today');
        $this->assertSame('postponed_info', $todayCard->homepage_card_mode);
        $this->assertSame('2026-08-10', $todayCard->homepage_ranking_date);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('Info Today', false)
            ->assertSee('Odgođeno', false)
            ->assertSee('Prvobitni termin:', false)
            ->assertSee('10.08.2026', false)
            ->assertDontSee('Info Yesterday', false)
            ->getContent();

        $this->assertStringNotContainsString('+ još', $html);
        $this->assertStringContainsString(
            route('cultural-calendar.show', $today->id, false),
            $html
        );
    }

    public function test_multi_postponed_nearest_then_next_after_expiry_then_none(): void
    {
        $entry = $this->makePublished('Multi Postponed', [
            '2026-08-09',
            '2026-08-10',
            '2026-08-13',
        ]);
        foreach ($entry->occurrences as $occurrence) {
            $this->occurrenceLifecycle->postpone($occurrence);
        }

        $todayCards = $this->publicQuery->homepageUpcomingCards();
        $todayMatch = $todayCards->firstWhere('id', $entry->id);
        $this->assertNotNull($todayMatch);
        $this->assertSame('postponed_info', $todayMatch->homepage_card_mode);
        $this->assertSame('2026-08-10', $todayMatch->homepage_ranking_date);

        Carbon::setTestNow(Carbon::parse('2026-08-11 12:00:00', config('app.timezone')));
        $nextCards = $this->publicQuery->homepageUpcomingCards();
        $nextMatch = $nextCards->firstWhere('id', $entry->id);
        $this->assertNotNull($nextMatch);
        $this->assertSame('2026-08-13', $nextMatch->homepage_ranking_date);

        Carbon::setTestNow(Carbon::parse('2026-08-14 12:00:00', config('app.timezone')));
        $afterCards = $this->publicQuery->homepageUpcomingCards();
        $this->assertNull($afterCards->firstWhere('id', $entry->id));
    }

    public function test_shared_pool_sort_max3_no_mode_priority(): void
    {
        $plannedA = $this->makePublished('Planned +5', ['2026-08-15']);
        $infoB = $this->makePublished('Info +1', ['2026-08-11']);
        $this->occurrenceLifecycle->postpone($infoB->occurrences()->firstOrFail());
        $plannedC = $this->makePublished('Planned +2', ['2026-08-12']);
        $plannedD = $this->makePublished('Planned +10', ['2026-08-20']);

        $cards = $this->publicQuery->homepageUpcomingCards();
        $this->assertCount(3, $cards);
        $this->assertSame(
            ['Info +1', 'Planned +2', 'Planned +5'],
            $cards->pluck('naslov')->all()
        );
        $this->assertSame('postponed_info', $cards[0]->homepage_card_mode);
        $this->assertSame('planned', $cards[1]->homepage_card_mode);
        $this->assertSame('planned', $cards[2]->homepage_card_mode);
        $this->assertNotContains($plannedD->id, $cards->pluck('id')->all());
        $this->assertContains($plannedA->id, $cards->pluck('id')->all());
        $this->assertContains($infoB->id, $cards->pluck('id')->all());
        $this->assertContains($plannedC->id, $cards->pluck('id')->all());
    }

    public function test_same_date_tie_uses_entry_id_not_mode(): void
    {
        $a = $this->makePublished('Tie A', ['2026-08-15']);
        $b = $this->makePublished('Tie B', ['2026-08-15']);
        $this->occurrenceLifecycle->postpone($b->occurrences()->firstOrFail());

        $lowerId = min($a->id, $b->id);
        $higherId = max($a->id, $b->id);
        $lowerMode = $a->id === $lowerId ? 'planned' : 'postponed_info';
        $higherMode = $a->id === $higherId ? 'planned' : 'postponed_info';

        $cards = $this->publicQuery->homepageUpcomingCards();
        $pair = $cards->whereIn('id', [$a->id, $b->id])->values();
        $this->assertCount(2, $pair);
        $this->assertSame($lowerId, $pair[0]->id);
        $this->assertSame($lowerMode, $pair[0]->homepage_card_mode);
        $this->assertSame($higherId, $pair[1]->id);
        $this->assertSame($higherMode, $pair[1]->homepage_card_mode);

        $infoFirst = $this->makePublished('Tie Info First', ['2026-08-18']);
        $this->occurrenceLifecycle->postpone($infoFirst->occurrences()->firstOrFail());
        $plannedSecond = $this->makePublished('Tie Planned Second', ['2026-08-18']);

        $cards2 = $this->publicQuery->homepageUpcomingCards(null, 10);
        $pair2 = $cards2->whereIn('id', [$infoFirst->id, $plannedSecond->id])->values();
        $this->assertSame(min($infoFirst->id, $plannedSecond->id), $pair2[0]->id);
        $this->assertSame(max($infoFirst->id, $plannedSecond->id), $pair2[1]->id);
        $this->assertNotSame($pair2[0]->homepage_card_mode, $pair2[1]->homepage_card_mode);
    }

    public function test_resume_new_termin_becomes_planned_mode(): void
    {
        $entry = $this->makePublished('Resume Entry', ['2026-08-12']);
        $occurrence = $entry->occurrences()->firstOrFail();
        $this->occurrenceLifecycle->postpone($occurrence);

        $before = $this->publicQuery->homepageUpcomingCards()->firstWhere('id', $entry->id);
        $this->assertSame('postponed_info', $before->homepage_card_mode);

        $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => '2026-08-28',
            'cjelodnevno' => true,
        ]);

        $after = $this->publicQuery->homepageUpcomingCards()->firstWhere('id', $entry->id);
        $this->assertNotNull($after);
        $this->assertSame('planned', $after->homepage_card_mode);
        $this->assertSame('2026-08-28', $after->homepage_ranking_date);
    }

    public function test_postponed_not_standard_card_relevant_but_may_be_homepage_info(): void
    {
        $entry = $this->makePublished('Criteria Boundary', ['2026-08-14']);
        $this->occurrenceLifecycle->postpone($entry->occurrences()->firstOrFail());

        $this->assertNull($entry->fresh(['occurrences'])->nextRelevantOccurrence());
        $this->assertNotContains(
            'Criteria Boundary',
            $this->publicQuery->upcomingForPublicIndex()->pluck('naslov')->all()
        );

        $card = $this->publicQuery->homepageUpcomingCards()->firstWhere('naslov', 'Criteria Boundary');
        $this->assertNotNull($card);
        $this->assertSame('postponed_info', $card->homepage_card_mode);
    }

    public function test_calendar_search_and_detail_regression_for_postponed(): void
    {
        $planned = $this->makePublished('Cal Planned', ['2026-08-15']);
        $postponed = $this->makePublished('Cal Postponed', ['2026-08-15']);
        $this->occurrenceLifecycle->postpone($postponed->occurrences()->firstOrFail());

        $dateIds = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();
        $this->assertContains($planned->id, $dateIds);
        $this->assertNotContains($postponed->id, $dateIds);

        $counts = $this->publicQuery->distinctPublicEntryCountsByOccurrenceDate('2026-08-15', '2026-08-15');
        $this->assertSame(1, $counts['2026-08-15'] ?? 0);

        // Pretraga i dalje uključuje published postponed-only (postojeće ponašanje); nije card-relevant.
        $this->assertNull($postponed->fresh(['occurrences'])->nextRelevantOccurrence());
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertSee('Cal Postponed', false)
            ->assertSee('Cal Planned', false);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $postponed->id))
            ->assertOk()
            ->assertSee('Odgođeno', false)
            ->assertSee('Prvobitni termin:', false);
    }

    /**
     * @param  list<string>  $dates
     */
    private function makePublished(string $naslov, array $dates): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        foreach ($dates as $datum) {
            $this->occurrenceWriter->create($entry->fresh(), [
                'datum' => $datum,
                'cjelodnevno' => true,
            ]);
        }

        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }
}
