<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicCardOccurrenceCriteria;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-03 / TM-JP-05…09 — next relevant OCC, +N, sort.
 */
class CulturalPublicCardRelevantOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private CulturalPublicEventQuery $publicQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->publicQuery = app(CulturalPublicEventQuery::class);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_single_future_planned_is_next(): void
    {
        $entry = $this->makePublishedEntry('Jedan');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($occ->is($entry->nextRelevantOccurrence()));
        $this->assertSame(1, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_earliest_of_multiple_planned_is_next(): void
    {
        $entry = $this->makePublishedEntry('Više');
        $later = $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $earlier = $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($earlier->is($entry->nextRelevantOccurrence()));
        $this->assertFalse($later->is($entry->nextRelevantOccurrence()));
        $this->assertSame(2, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(1, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_additional_count_only_counts_valid_candidates(): void
    {
        $entry = $this->makePublishedEntry('Count');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-13',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-14',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $this->assertSame(4, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(3, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_expired_planned_is_ignored(): void
    {
        $entry = $this->makePublishedEntry('Istekao');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-09',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $future = $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($future->is($entry->nextRelevantOccurrence()));
        $this->assertSame(1, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_postponed_is_ignored_for_next_and_count(): void
    {
        $entry = $this->makePublishedEntry('Odgođen');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);
        $planned = $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($planned->is($entry->nextRelevantOccurrence()));
        $this->assertSame(1, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_cancelled_occurrence_is_ignored(): void
    {
        $entry = $this->makePublishedEntry('OCC otkazan');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);
        $planned = $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($planned->is($entry->nextRelevantOccurrence()));
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_finished_occurrence_is_ignored(): void
    {
        $entry = $this->makePublishedEntry('Završen');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-11',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertNull($entry->nextRelevantOccurrence());
        $this->assertSame(0, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_entry_without_relevant_occurrence_returns_null_and_zero(): void
    {
        $entry = $this->makePublishedEntry('Prazan');

        $this->assertNull($entry->nextRelevantOccurrence());
        $this->assertSame(0, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(0, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_sort_two_entries_by_next_occurrence(): void
    {
        $later = $this->makePublishedEntry('Kasnije');
        $this->makeOccurrence($later, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $earlier = $this->makePublishedEntry('Ranije');
        $this->makeOccurrence($earlier, [
            'datum' => '2026-08-12',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $ids = $this->publicQuery->orderedByNextRelevantOccurrence()->pluck('id')->all();

        $this->assertSame([$earlier->id, $later->id], $ids);
    }

    public function test_sort_uses_future_when_entry_has_old_and_future_occurrence(): void
    {
        $entry = $this->makePublishedEntry('Stari+novi');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-18',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $other = $this->makePublishedEntry('Srednji');
        $this->makeOccurrence($other, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $ids = $this->publicQuery->orderedByNextRelevantOccurrence()->pluck('id')->all();

        $this->assertSame([$other->id, $entry->id], $ids);
        $this->assertSame('2026-08-18', $entry->nextRelevantOccurrence()?->datum?->format('Y-m-d'));
    }

    public function test_null_next_occurrence_sorts_after_valid(): void
    {
        $withNext = $this->makePublishedEntry('Ima next');
        $this->makeOccurrence($withNext, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $without = $this->makePublishedEntry('Bez next');

        $ids = $this->publicQuery->orderedByNextRelevantOccurrence()->pluck('id')->all();

        $this->assertSame([$withNext->id, $without->id], $ids);
    }

    public function test_tie_breaker_is_stable_by_entry_id(): void
    {
        $a = $this->makePublishedEntry('A');
        $this->makeOccurrence($a, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $b = $this->makePublishedEntry('B');
        $this->makeOccurrence($b, [
            'datum' => '2026-08-15',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $ids = $this->publicQuery->orderedByNextRelevantOccurrence()->pluck('id')->all();

        $this->assertSame([min($a->id, $b->id), max($a->id, $b->id)], $ids);
    }

    public function test_expires_at_semantics_with_end_time(): void
    {
        $entry = $this->makePublishedEntry('Vrijeme do');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-10',
            'vrijeme_od' => '10:00',
            'vrijeme_do' => '14:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 13:59:59', config('app.timezone')));
        $this->assertFalse($occ->isExpiredAt(now()));
        $this->assertTrue($occ->is($entry->fresh()->nextRelevantOccurrence()));

        Carbon::setTestNow(Carbon::parse('2026-08-10 14:00:01', config('app.timezone')));
        $this->assertTrue($occ->fresh()->isExpiredAt(now()));
        $this->assertNull($entry->fresh()->nextRelevantOccurrence());
    }

    public function test_all_day_uses_end_of_day_expiry(): void
    {
        $entry = $this->makePublishedEntry('Cjelodnevno');
        $occ = $this->makeOccurrence($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 23:59:59', config('app.timezone')));
        $this->assertFalse($occ->isExpiredAt(now()));
        $this->assertTrue($occ->is($entry->fresh()->nextRelevantOccurrence()));

        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:01', config('app.timezone')));
        $this->assertTrue($occ->fresh()->isExpiredAt(now()));
        $this->assertNull($entry->fresh()->nextRelevantOccurrence());
    }

    public function test_query_and_helper_agree_for_same_entry(): void
    {
        $entry = $this->makePublishedEntry('Paritet');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-09',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $next = $this->makeOccurrence($entry, [
            'datum' => '2026-08-16',
            'vrijeme_od' => '18:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-16',
            'vrijeme_od' => '20:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $fromHelper = $entry->nextRelevantOccurrence();
        $fromScope = CulturalPublicCardOccurrenceCriteria::orderForNext(
            CulturalPublicCardOccurrenceCriteria::constrain(
                $entry->occurrences()->getQuery()
            )
        )->first();
        $fromLoaded = $entry->load('occurrences')->nextRelevantOccurrence();

        $this->assertTrue($next->is($fromHelper));
        $this->assertTrue($next->is($fromScope));
        $this->assertTrue($next->is($fromLoaded));
        $this->assertSame(2, $entry->cardRelevantOccurrencesCount());
        $this->assertSame(1, $entry->additionalRelevantOccurrencesCount());
    }

    public function test_same_day_orders_by_start_time(): void
    {
        $entry = $this->makePublishedEntry('Isti dan');
        $evening = $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'vrijeme_od' => '20:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);
        $morning = $this->makeOccurrence($entry, [
            'datum' => '2026-08-12',
            'vrijeme_od' => '10:00',
            'cjelodnevno' => false,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertTrue($morning->is($entry->nextRelevantOccurrence()));
        $this->assertFalse($evening->is($entry->nextRelevantOccurrence()));
    }

    private function makePublishedEntry(string $naslov): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => CulturalEventEntry::STATUS_PUBLISHED,
            'created_by' => $this->creator->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOccurrence(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
        ], $attributes));
    }
}
