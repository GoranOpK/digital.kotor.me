<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PO-AUTO-02 — automatski Planiran → Završen.
 */
class CulturalOccurrenceAutoFinishTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private CulturalCategory $category;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $writer;

    private OccurrenceLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Film',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->writer = app(OccurrenceWriter::class);
        $this->lifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_with_vrijeme_do_finishes_only_after_end(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-08',
            'vrijeme_od' => '10:00',
            'vrijeme_do' => '12:00',
            'cjelodnevno' => false,
        ]);

        $before = Carbon::parse('2026-08-08 11:59:59', config('app.timezone'));
        $after = Carbon::parse('2026-08-08 12:00:01', config('app.timezone'));

        $this->assertFalse($occurrence->isExpiredAt($before));
        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence, $before));
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);

        $this->assertTrue($occurrence->fresh()->isExpiredAt($after));
        $finished = $this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $after);
        $this->assertNotNull($finished);
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $finished->status);
    }

    public function test_only_vrijeme_od_waits_until_end_of_day(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-08',
            'vrijeme_od' => '10:00',
            'cjelodnevno' => false,
        ]);

        $afterStart = Carbon::parse('2026-08-08 10:30:00', config('app.timezone'));
        $afterDay = Carbon::parse('2026-08-09 00:00:01', config('app.timezone'));

        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence, $afterStart));
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);

        $this->assertNotNull($this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $afterDay));
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $occurrence->fresh()->status);
    }

    public function test_date_only_finishes_after_calendar_day(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-08',
            'cjelodnevno' => false,
        ]);

        $during = Carbon::parse('2026-08-08 23:59:59', config('app.timezone'));
        $after = Carbon::parse('2026-08-09 00:00:00', config('app.timezone'))->addSecond();

        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence, $during));
        $this->assertNotNull($this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $after));
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $occurrence->fresh()->status);
    }

    public function test_all_day_finishes_after_calendar_day(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-08',
            'cjelodnevno' => true,
        ]);

        $during = Carbon::parse('2026-08-08 18:00:00', config('app.timezone'));
        $after = Carbon::parse('2026-08-09 00:00:01', config('app.timezone'));

        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence, $during));
        $this->assertNotNull($this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $after));
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $occurrence->fresh()->status);
    }

    public function test_postponed_cancelled_finished_are_ignored(): void
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Ignore statuses',
            'category_id' => $this->category->id,
        ]);

        $postponed = $this->writer->create($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);
        $cancelled = $this->writer->create($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);
        $finished = $this->writer->create($entry, [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->lifecycle->postpone($postponed->fresh());
        $this->lifecycle->cancel($cancelled->fresh());
        $this->lifecycle->markFinished($finished->fresh());

        $now = Carbon::parse('2026-08-10 12:00:00', config('app.timezone'));

        $this->assertNull($this->lifecycle->finishIfExpiredAt($postponed->fresh(), $now));
        $this->assertNull($this->lifecycle->finishIfExpiredAt($cancelled->fresh(), $now));
        $this->assertNull($this->lifecycle->finishIfExpiredAt($finished->fresh(), $now));

        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $postponed->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $cancelled->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $finished->fresh()->status);
    }

    public function test_expiry_uses_application_timezone_not_utc(): void
    {
        config(['app.timezone' => 'Europe/Belgrade']);

        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-08',
            'vrijeme_od' => '22:00',
            'vrijeme_do' => '23:30',
            'cjelodnevno' => false,
        ]);

        // 23:00 UTC = 01:00 Europe/Belgrade next day — istekao u app zoni.
        $utcLate = Carbon::parse('2026-08-08 23:00:00', 'UTC');
        $this->assertTrue($occurrence->isExpiredAt($utcLate->copy()->timezone('Europe/Belgrade')));

        // 21:00 Europe/Belgrade — još nije isteklo (vrijeme_do 23:30).
        $localBefore = Carbon::parse('2026-08-08 21:00:00', 'Europe/Belgrade');
        $this->assertFalse($occurrence->isExpiredAt($localBefore));

        $expires = $occurrence->expiresAt();
        $this->assertSame('Europe/Belgrade', $expires->timezoneName);
        $this->assertSame('2026-08-08 23:30:00', $expires->format('Y-m-d H:i:s'));
    }

    public function test_race_postponed_before_finish_is_not_overwritten(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);

        $this->lifecycle->postpone($occurrence->fresh());

        $now = Carbon::parse('2026-08-10 12:00:00', config('app.timezone'));
        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $now));
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
    }

    public function test_race_event_cancelled_before_finish_is_not_overwritten(): void
    {
        $occurrence = $this->makePublishedOccurrence([
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->cancel($occurrence->eventEntry, $this->editor, 'Race cancel');
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);

        $now = Carbon::parse('2026-08-10 12:00:00', config('app.timezone'));
        $this->assertNull($this->lifecycle->finishIfExpiredAt($occurrence->fresh(), $now));
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);
    }

    private function makePublishedEntry(string $naslov): CulturalEventEntry
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $this->writer->create($entry, [
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }

    /**
     * @param  array{
     *     datum: string,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool
     * }  $termin
     */
    private function makePublishedOccurrence(array $termin): CulturalOccurrence
    {
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Auto finish '.uniqid(),
            'category_id' => $this->category->id,
        ]);

        $occurrence = $this->writer->create($entry, $termin);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $occurrence->fresh();
    }
}
