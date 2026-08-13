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
 * CulturalEventEntry::publicStatus — canonical public badge SSOT.
 */
class CulturalEventPublicStatusTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $extra
     */
    private function entry(string $status = CulturalEventEntry::STATUS_PUBLISHED, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => 'Test',
            'opis' => null,
            'status' => $status,
            'created_by' => $this->creator->id,
            'featured' => false,
        ], $extra));
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

    public function test_cancelled_has_absolute_priority_over_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $entry = $this->entry(CulturalEventEntry::STATUS_CANCELLED);
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);

        $this->assertSame([
            'key' => 'cancelled',
            'label' => 'Otkazan',
            'class' => 'kk-status-cancelled',
        ], $entry->fresh()->publicStatus());
    }

    public function test_all_day_future_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 12:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, ['datum' => '2026-08-10']);

        $status = $entry->fresh()->load('occurrences')->publicStatus();
        $this->assertSame('upcoming', $status['key']);
        $this->assertSame('Predstoji', $status['label']);
    }

    public function test_all_day_today_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, ['datum' => '2026-08-10']);

        $status = $entry->fresh()->load('occurrences')->publicStatus();
        $this->assertSame('ongoing', $status['key']);
        $this->assertSame('U toku', $status['label']);
    }

    public function test_all_day_past_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $status = $entry->fresh()->load('occurrences')->publicStatus();
        $this->assertSame('finished', $status['key']);
        $this->assertSame('Završen', $status['label']);
    }

    public function test_timed_before_start_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 17:59:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('upcoming', $entry->fresh()->load('occurrences')->publicStatus()['key']);
    }

    public function test_timed_between_start_and_end_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('ongoing', $entry->fresh()->load('occurrences')->publicStatus()['key']);
    }

    public function test_timed_after_end_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 20:00:01', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-10',
            'cjelodnevno' => false,
            'vrijeme_od' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ]);

        $this->assertSame('finished', $entry->fresh()->load('occurrences')->publicStatus()['key']);
    }

    public function test_postponed_only_returns_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();
        $this->occ($entry, [
            'datum' => '2026-08-20',
            'status' => CulturalOccurrence::STATUS_POSTPONED,
        ]);

        $this->assertNull($entry->fresh()->load('occurrences')->publicStatus());
    }

    public function test_zero_occurrences_returns_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $entry = $this->entry();

        $this->assertNull($entry->fresh()->publicStatus());
    }

    public function test_archived_from_published_is_finished(): void
    {
        $entry = $this->entry(CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $this->assertSame([
            'key' => 'finished',
            'label' => 'Završen',
            'class' => 'kk-status-finished',
        ], $entry->publicStatus());
    }

    public function test_archived_from_cancelled_is_cancelled(): void
    {
        $entry = $this->entry(CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_CANCELLED,
        ]);

        $this->assertSame([
            'key' => 'cancelled',
            'label' => 'Otkazan',
            'class' => 'kk-status-cancelled',
        ], $entry->publicStatus());
    }

    public function test_draft_returns_null(): void
    {
        $entry = $this->entry(CulturalEventEntry::STATUS_DRAFT);
        $this->occ($entry, ['datum' => '2026-08-20']);

        $this->assertNull($entry->fresh()->load('occurrences')->publicStatus());
    }
}
