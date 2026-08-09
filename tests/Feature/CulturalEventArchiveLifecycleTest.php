<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BR-065 / G2 — automatsko arhiviranje Događaja.
 */
class CulturalEventArchiveLifecycleTest extends TestCase
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
            'naziv' => 'Izložbe',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_published_with_planned_is_not_archived(): void
    {
        $entry = $this->makePublished('Ima Planiran');

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->archiveIfEligible($entry);
    }

    public function test_published_with_postponed_is_not_archived(): void
    {
        $entry = $this->makePublished('Ima Odgođen');
        $this->occurrenceLifecycle->postpone($entry->occurrences()->firstOrFail());

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->archiveIfEligible($entry->fresh());
    }

    public function test_published_with_only_finished_and_cancelled_archives(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Finalna',
            'category_id' => $this->category->id,
        ]);
        $toFinish = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $toCancel = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(11)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->occurrenceLifecycle->markFinished($toFinish->fresh());
        $this->occurrenceLifecycle->cancel($toCancel->fresh());

        $archived = $this->lifecycle->archiveIfEligible($entry->fresh());
        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $archived->status);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $archived->archived_from_status);
        $this->assertFalse($archived->featured);
    }

    public function test_cancelled_after_cascade_can_be_archived(): void
    {
        $entry = $this->makePublished('Otkazan arhiv');
        $this->lifecycle->cancel($entry, $this->editor, 'Stop');

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
        $this->assertSame(
            CulturalOccurrence::STATUS_CANCELLED,
            $entry->occurrences()->firstOrFail()->fresh()->status
        );

        $archived = $this->lifecycle->archiveIfEligible($entry->fresh());
        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $archived->status);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $archived->archived_from_status);
    }

    public function test_already_archived_cannot_be_archived_again(): void
    {
        $entry = $this->makePublished('Već arhiv');
        $this->occurrenceLifecycle->markFinished($entry->occurrences()->firstOrFail());
        $this->lifecycle->archiveIfEligible($entry->fresh());

        $this->assertSame(
            CulturalEventEntry::STATUS_PUBLISHED,
            $entry->fresh()->archived_from_status
        );

        try {
            $this->lifecycle->archiveIfEligible($entry->fresh());
            $this->fail('Expected CulturalEventDomainException was not thrown.');
        } catch (CulturalEventDomainException) {
            // expected
        }

        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $entry->fresh()->status);
        $this->assertSame(
            CulturalEventEntry::STATUS_PUBLISHED,
            $entry->fresh()->archived_from_status
        );
    }

    public function test_stale_eligible_state_does_not_archive_when_open_reappears_under_lock(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Stale',
            'category_id' => $this->category->id,
        ]);
        $finished = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $stillOpen = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(30)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->occurrenceLifecycle->markFinished($finished->fresh());

        // Zastarjeli model: izgleda "finalno", ali pod lockom postoji Planiran.
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);

        $this->expectException(CulturalEventDomainException::class);
        $this->lifecycle->archiveIfEligible($stale);

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $stillOpen->fresh()->status);
    }

    private function makePublished(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }
}
