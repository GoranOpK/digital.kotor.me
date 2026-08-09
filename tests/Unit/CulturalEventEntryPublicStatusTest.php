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
 * 6A-11 / PO-6A11-01 — kanonski javni status Entry (multi-OCC).
 */
class CulturalEventEntryPublicStatusTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancelled_entry_is_otkazan_regardless_of_occurrences(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry(CulturalEventEntry::STATUS_CANCELLED);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertSame([
            'key' => 'cancelled',
            'label' => 'Otkazan',
            'class' => 'kk-status-cancelled',
        ], $entry->fresh()->load('occurrences')->publicStatus());
    }

    public function test_published_single_future_planned_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
        $this->assertSame('Predstoji', $this->statusLabel($entry));
    }

    public function test_published_single_active_planned_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
        $this->assertSame('U toku', $this->statusLabel($entry));
    }

    public function test_published_temporally_expired_planned_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 20:00:01', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->assertSame('finished', $this->statusKey($entry));
    }

    public function test_finished_occ_only_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertSame('finished', $this->statusKey($entry));
    }

    public function test_cancelled_occ_only_is_finished_not_otkazan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $status = $entry->fresh()->load('occurrences')->publicStatus();
        $this->assertSame('finished', $status['key']);
        $this->assertNotSame('Otkazan', $status['label']);
    }

    public function test_past_plus_future_planned_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_ongoing_plus_future_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
    }

    public function test_past_ongoing_future_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
    }

    public function test_postponed_only_returns_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $this->assertNull($entry->fresh()->load('occurrences')->publicStatus());
    }

    public function test_zero_occurrences_returns_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();

        $this->assertNull($entry->fresh()->load('occurrences')->publicStatus());
    }

    public function test_postponed_plus_future_planned_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-11',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_postponed_plus_active_planned_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-09',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
    }

    public function test_cancelled_occ_plus_future_planned_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-05',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_finished_occ_plus_future_planned_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_all_day_future_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => true,
            'vrijeme_od' => null,
            'vrijeme_do' => null,
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_all_day_today_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => true,
            'vrijeme_od' => null,
            'vrijeme_do' => null,
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
    }

    public function test_all_day_past_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => true,
            'vrijeme_od' => null,
            'vrijeme_do' => null,
        ]);

        $this->assertSame('finished', $this->statusKey($entry));
    }

    public function test_timed_before_start_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 17:59:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $this->statusKey($entry));
    }

    public function test_timed_between_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));
    }

    public function test_timed_after_end_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 20:00:01', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('finished', $this->statusKey($entry));
    }

    public function test_without_vrijeme_do_uses_end_of_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 23:30:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => null,
        ]);

        $this->assertSame('ongoing', $this->statusKey($entry));

        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', config('app.timezone')));
        $this->assertSame('finished', $this->statusKey($entry));
    }

    public function test_does_not_expose_odgodjen_as_entry_badge(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $status = $entry->fresh()->load('occurrences')->publicStatus();
        $this->assertNull($status);
    }

    private function entry(string $status = CulturalEventEntry::STATUS_PUBLISHED): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => 'Status test',
            'status' => $status,
            'created_by' => $this->creator->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function occ(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }

    private function statusKey(CulturalEventEntry $entry): string
    {
        return $entry->fresh()->load('occurrences')->publicStatus()['key'];
    }

    private function statusLabel(CulturalEventEntry $entry): string
    {
        return $entry->fresh()->load('occurrences')->publicStatus()['label'];
    }
}
