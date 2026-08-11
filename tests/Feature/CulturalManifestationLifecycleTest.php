<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationLifecycleMaintenance;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalManifestationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private ManifestationWriter $manifestationWriter;

    private ManifestationLifecycle $manifestationLifecycle;

    private ManifestationLifecycleMaintenance $maintenance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->manifestationWriter = app(ManifestationWriter::class);
        $this->manifestationLifecycle = app(ManifestationLifecycle::class);
        $this->maintenance = app(ManifestationLifecycleMaintenance::class);
    }

    public function test_submit_requires_at_least_one_linked_event_and_sets_first_submitted_only_once(): void
    {
        $manifestation = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF']);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationLifecycle->submitForApproval($manifestation, $this->editor);
    }

    public function test_submit_return_resubmit_flow_preserves_first_submitted_at(): void
    {
        $event = $this->makeDraftEvent();
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$event->id],
        ]);

        $submitted = $this->manifestationLifecycle->submitForApproval($manifestation, $this->editor);
        $this->assertSame(CulturalManifestation::STATUS_PENDING_APPROVAL, $submitted->status);
        $this->assertNotNull($submitted->first_submitted_at);
        $firstTs = $submitted->first_submitted_at;

        $returned = $this->manifestationLifecycle->returnToRevision($submitted, $this->editor, 'Doraditi');
        $this->assertSame(CulturalManifestation::STATUS_RETURNED_FOR_REVISION, $returned->status);

        $resubmitted = $this->manifestationLifecycle->submitForApproval($returned, $this->editor);
        $this->assertSame(CulturalManifestation::STATUS_PENDING_APPROVAL, $resubmitted->status);
        $this->assertTrue($firstTs->equalTo($resubmitted->first_submitted_at));
    }

    public function test_publish_requires_at_least_one_published_event_and_sets_published_at(): void
    {
        $draftEvent = $this->makeDraftEvent();
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$draftEvent->id],
        ]);
        $manifestation = $this->manifestationLifecycle->submitForApproval($manifestation, $this->editor);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationLifecycle->publish($manifestation, $this->editor);
    }

    public function test_publish_cancel_archive_and_terminal_rules(): void
    {
        $publishedEvent = $this->makePublishedEvent('2024-01-10');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$publishedEvent->id],
        ]);
        $manifestation = $this->manifestationLifecycle->submitForApproval($manifestation, $this->editor);
        $manifestation = $this->manifestationLifecycle->publish($manifestation, $this->editor);

        $this->assertSame(CulturalManifestation::STATUS_PUBLISHED, $manifestation->status);
        $this->assertNotNull($manifestation->published_at);

        $eventBeforeCancel = $publishedEvent->fresh()->status;
        $manifestation = $this->manifestationLifecycle->cancel($manifestation, $this->editor);
        $this->assertSame(CulturalManifestation::STATUS_CANCELLED, $manifestation->status);
        $this->assertNotNull($manifestation->cancelled_at);
        $this->assertSame($eventBeforeCancel, $publishedEvent->fresh()->status);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationLifecycle->publish($manifestation, $this->editor);
    }

    public function test_archived_is_terminal(): void
    {
        $publishedEvent = $this->makePublishedEvent('2024-01-10');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$publishedEvent->id],
        ]);
        $manifestation = $this->manifestationLifecycle->submitForApproval($manifestation, $this->editor);
        $manifestation = $this->manifestationLifecycle->publish($manifestation, $this->editor);
        $manifestation = $this->manifestationLifecycle->archiveIfEligible($manifestation);

        $this->assertSame(CulturalManifestation::STATUS_ARCHIVED, $manifestation->status);
        $this->assertNotNull($manifestation->archived_at);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationLifecycle->cancel($manifestation, $this->editor);
    }

    public function test_maintenance_auto_archives_only_published_and_cancelled_with_expired_period(): void
    {
        $expiredEvent = $this->makePublishedEvent('2020-01-01', 'Expired');
        $futureEvent = $this->makePublishedEvent(now()->addDays(5)->format('Y-m-d'), 'Future');
        $cancelExpiredEvent = $this->makePublishedEvent('2020-01-05', 'CancelExpired');

        $publishedExpired = $this->publishManifestationWithEvent($expiredEvent, 'MF expired');
        $publishedFuture = $this->publishManifestationWithEvent($futureEvent, 'MF future');
        $cancelledExpired = $this->publishManifestationWithEvent($cancelExpiredEvent, 'MF cancel exp');
        $cancelledExpired = $this->manifestationLifecycle->cancel($cancelledExpired, $this->editor);

        $draftMf = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'Draft']);
        $pendingMf = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Pending',
            'event_entry_ids' => [$this->makeDraftEvent('Pending event')->id],
        ]);
        $pendingMf = $this->manifestationLifecycle->submitForApproval($pendingMf, $this->editor);
        $reworkMf = $this->manifestationLifecycle->returnToRevision($pendingMf, $this->editor, 'Dorada');

        $result = $this->maintenance->archiveEligibleManifestations();

        $this->assertSame(2, $result['archived']);
        $this->assertGreaterThanOrEqual(1, $result['skipped']);

        $this->assertSame(CulturalManifestation::STATUS_ARCHIVED, $publishedExpired->fresh()->status);
        $this->assertSame(CulturalManifestation::STATUS_ARCHIVED, $cancelledExpired->fresh()->status);
        $this->assertSame(CulturalManifestation::STATUS_PUBLISHED, $publishedFuture->fresh()->status);
        $this->assertSame(CulturalManifestation::STATUS_DRAFT, $draftMf->fresh()->status);
        $this->assertSame(CulturalManifestation::STATUS_RETURNED_FOR_REVISION, $reworkMf->fresh()->status);
    }

    public function test_event_without_manifestation_is_valid(): void
    {
        $event = $this->makeDraftEvent('Independent event');

        $this->assertNull($event->manifestation_id);
        $this->assertDatabaseHas('cultural_event_entries', [
            'id' => $event->id,
            'manifestation_id' => null,
        ]);
    }

    private function publishManifestationWithEvent(CulturalEventEntry $event, string $name): CulturalManifestation
    {
        $mf = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => $name,
            'event_entry_ids' => [$event->id],
        ]);
        $mf = $this->manifestationLifecycle->submitForApproval($mf, $this->editor);

        return $this->manifestationLifecycle->publish($mf, $this->editor);
    }

    private function makeDraftEvent(string $title = 'DG'): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($this->editor, ['naslov' => $title]);
    }

    private function makePublishedEvent(string $date, string $title = 'DG published'): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Kat '.uniqid(),
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $title,
            'category_id' => $category->id,
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => $date,
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh();
    }
}

