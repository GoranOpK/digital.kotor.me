<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventLifecycleMaintenance;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Command + maintenance: PO-AUTO-02 pa arhiviranje.
 */
class CulturalEventLifecycleMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Muzika',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_command_finishes_expired_then_archives_eligible(): void
    {
        $entry = $this->makePublished('Maintenance', [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);
        $postponedEntry = $this->makePublished('Keep postponed', [
            'datum' => '2026-08-01',
            'cjelodnevno' => true,
        ]);
        $this->occurrenceLifecycle->postpone($postponedEntry->occurrences()->firstOrFail());

        $openFuture = $this->makePublished('Still open', [
            'datum' => '2026-12-01',
            'cjelodnevno' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $this->artisan('cultural-calendar:process-event-lifecycle')
            ->assertSuccessful();

        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $entry->occurrences()->firstOrFail()->fresh()->status);
        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $entry->fresh()->status);

        $this->assertSame(
            CulturalOccurrence::STATUS_POSTPONED,
            $postponedEntry->occurrences()->firstOrFail()->fresh()->status
        );
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $postponedEntry->fresh()->status);

        $this->assertSame(
            CulturalOccurrence::STATUS_PLANNED,
            $openFuture->occurrences()->firstOrFail()->fresh()->status
        );
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $openFuture->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_command_is_idempotent_on_rerun(): void
    {
        $entry = $this->makePublished('Idempotent', [
            'datum' => '2026-07-01',
            'cjelodnevno' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));

        $this->artisan('cultural-calendar:process-event-lifecycle')->assertSuccessful();
        $this->artisan('cultural-calendar:process-event-lifecycle')->assertSuccessful();

        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $entry->fresh()->status);
        $this->assertSame(
            CulturalOccurrence::STATUS_FINISHED,
            $entry->occurrences()->firstOrFail()->fresh()->status
        );

        Carbon::setTestNow();
    }

    public function test_maintenance_service_order_finish_then_archive(): void
    {
        $entry = $this->makePublished('Order', [
            'datum' => '2026-07-01',
            'vrijeme_od' => '10:00',
            'vrijeme_do' => '11:00',
            'cjelodnevno' => false,
        ]);

        $now = Carbon::parse('2026-08-01 11:00:01', config('app.timezone'));
        $result = app(EventLifecycleMaintenance::class)->process($now);

        $this->assertSame(1, $result['finished']);
        $this->assertSame(1, $result['archived']);
        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $entry->fresh()->status);
    }

    public function test_schedule_registers_lifecycle_command(): void
    {
        $events = Artisan::call('schedule:list') === 0
            ? collect(\Illuminate\Support\Facades\Schedule::events())
            : collect();

        // Laravel 12: Schedule facade events after boot.
        $scheduled = collect(app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events());

        $match = $scheduled->first(function ($event): bool {
            return str_contains($event->command ?? '', 'cultural-calendar:process-event-lifecycle')
                || str_contains($event->description ?? '', 'process-event-lifecycle');
        });

        $this->assertNotNull(
            $match,
            'Schedule mora registrovati cultural-calendar:process-event-lifecycle'
        );
    }

    /**
     * @param  array{
     *     datum: string,
     *     vrijeme_od?: ?string,
     *     vrijeme_do?: ?string,
     *     cjelodnevno?: bool
     * }  $termin
     */
    private function makePublished(string $naslov, array $termin): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $this->occurrenceWriter->create($entry, $termin);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }
}
