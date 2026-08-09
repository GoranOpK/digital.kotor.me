<?php

namespace Tests\Unit;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-09 — lastHistoricalOccurrence / historicalSortAt.
 */
class CulturalHistoricalOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_finished_only_is_last_historical(): void
    {
        $entry = $this->entry();
        $occ = $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertSame($occ->id, $entry->fresh()->load('occurrences')->lastHistoricalOccurrence()?->id);
    }

    public function test_cancelled_only_is_last_historical(): void
    {
        $entry = $this->entry();
        $occ = $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $this->assertSame($occ->id, $entry->fresh()->load('occurrences')->lastHistoricalOccurrence()?->id);
    }

    public function test_multiple_finished_picks_latest(): void
    {
        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $later = $this->occ($entry, [
            'datum' => '2026-08-05',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertSame($later->id, $entry->fresh()->load('occurrences')->lastHistoricalOccurrence()?->id);
    }

    public function test_finished_and_cancelled_picks_temporal_last(): void
    {
        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '10:00:00',
            'vrijeme_do' => '12:00:00',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $cancelledLater = $this->occ($entry, [
            'datum' => '2026-08-03',
            'cjelodnevno' => false,
            'vrijeme_od' => '19:00:00',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $this->assertSame(
            $cancelledLater->id,
            $entry->fresh()->load('occurrences')->lastHistoricalOccurrence()?->id
        );
    }

    public function test_postponed_is_not_historical_candidate(): void
    {
        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);
        $finished = $this->occ($entry, [
            'datum' => '2026-07-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $last = $entry->fresh()->load('occurrences')->lastHistoricalOccurrence();
        $this->assertSame($finished->id, $last?->id);
    }

    public function test_all_day_finished_uses_end_of_day_for_sort(): void
    {
        $entry = $this->entry();
        $occ = $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $sortAt = $occ->fresh()->historicalSortAt();
        $this->assertNotNull($sortAt);
        $this->assertSame('2026-08-01 23:59:59', $sortAt->format('Y-m-d H:i:s'));
    }

    public function test_same_day_tie_breaker_uses_time_then_id(): void
    {
        $entry = $this->entry();
        $earlier = $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '10:00:00',
            'vrijeme_do' => '11:00:00',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $later = $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertGreaterThan($earlier->id, $later->id);
        $this->assertSame($later->id, $entry->fresh()->load('occurrences')->lastHistoricalOccurrence()?->id);
    }

    public function test_no_historical_occurrence_returns_null(): void
    {
        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertNull($entry->fresh()->load('occurrences')->lastHistoricalOccurrence());
    }

    private function entry(string $status = CulturalEventEntry::STATUS_PUBLISHED): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => 'Hist OCC',
            'status' => $status,
            'created_by' => $this->creator->id,
        ]);
    }

    private function occ(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }
}
