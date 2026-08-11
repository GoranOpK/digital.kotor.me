<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationPeriodCalculator;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalManifestationDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private ManifestationWriter $manifestationWriter;

    private ManifestationPeriodCalculator $periodCalculator;

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
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->manifestationWriter = app(ManifestationWriter::class);
        $this->periodCalculator = app(ManifestationPeriodCalculator::class);
    }

    public function test_create_minimal_manifestation_defaults_to_draft(): void
    {
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Kotor Art',
        ]);

        $this->assertSame(CulturalManifestation::STATUS_DRAFT, $manifestation->status);
        $this->assertSame('Kotor Art', $manifestation->naziv);
        $this->assertNull($manifestation->opis);
        $this->assertNull($manifestation->organizer_id);
        $this->assertNull($manifestation->cover_media_id);
        $this->assertNull($manifestation->web_stranica);
        $this->assertSame($this->editor->id, $manifestation->created_by);
        $this->assertSame($this->editor->id, $manifestation->last_modified_by);
        $this->assertDatabaseCount('cultural_manifestations', 1);
    }

    public function test_optional_fields_and_relations_can_be_set_on_create(): void
    {
        $organizer = $this->makeActiveOrganizer();
        $media = $this->makeActiveManifestationCoverMedia();

        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Festival',
            'opis' => 'Opis manifestacije',
            'organizer_id' => $organizer->id,
            'cover_media_id' => $media->id,
            'web_stranica' => 'https://example.com/festival',
        ]);

        $this->assertTrue($manifestation->organizer->is($organizer));
        $this->assertTrue($manifestation->coverMedia->is($media));
        $this->assertSame('https://example.com/festival', $manifestation->web_stranica);
    }

    public function test_media_and_organizer_guards_reject_invalid_links(): void
    {
        $inactiveOrganizer = $this->makeActiveOrganizer();
        $inactiveOrganizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'X',
            'organizer_id' => $inactiveOrganizer->id,
        ]);
    }

    public function test_wrong_cover_media_purpose_is_rejected(): void
    {
        $media = $this->makeActiveManifestationCoverMedia([
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'X',
            'cover_media_id' => $media->id,
        ]);
    }

    public function test_event_can_be_linked_and_unlinked_without_lifecycle_change(): void
    {
        $event = $this->makeDraftEvent('DG 1');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF']);

        $this->manifestationWriter->linkEvent($manifestation, $event->id, $this->editor);
        $event->refresh();
        $this->assertSame($manifestation->id, $event->manifestation_id);
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $event->status);

        $this->manifestationWriter->unlinkEvent($manifestation->fresh(), $event->id, $this->editor);
        $event->refresh();
        $this->assertNull($event->manifestation_id);
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $event->status);
    }

    public function test_one_event_cannot_belong_to_two_manifestations(): void
    {
        $event = $this->makeDraftEvent('DG 2');
        $mf1 = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF1']);
        $mf2 = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF2']);

        $this->manifestationWriter->linkEvent($mf1, $event->id, $this->editor);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->linkEvent($mf2, $event->id, $this->editor);
    }

    public function test_published_manifestation_cannot_unlink_last_published_event(): void
    {
        $publishedEvent = $this->makePublishedEvent('Published DG');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$publishedEvent->id],
        ]);

        $lifecycle = app(\App\Services\CulturalManifestationDomain\ManifestationLifecycle::class);
        $lifecycle->submitForApproval($manifestation, $this->editor);
        $lifecycle->publish($manifestation->fresh(), $this->editor);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->unlinkEvent($manifestation->fresh(), $publishedEvent->id, $this->editor);
    }

    public function test_cancelled_event_remains_linked_and_in_period_logic_ignored_if_occ_cancelled(): void
    {
        $event = $this->makePublishedEvent('DG for cancel');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$event->id],
        ]);

        $this->eventLifecycle->cancel($event->fresh(), $this->editor, 'Razlog');
        $event->refresh();
        $this->assertSame($manifestation->id, $event->manifestation_id);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $event->status);

        $this->assertNull($this->periodCalculator->calculate($manifestation->fresh()));
    }

    public function test_period_single_and_multiple_events(): void
    {
        $eventA = $this->makePublishedEvent('DG A', '2026-10-10');
        $eventB = $this->makePublishedEvent('DG B', '2026-10-20');

        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$eventA->id, $eventB->id],
        ]);

        $period = $this->periodCalculator->calculate($manifestation->fresh());
        $this->assertNotNull($period);
        $this->assertSame('2026-10-10', $period['start']->format('Y-m-d'));
        $this->assertSame('2026-10-20', $period['end']->format('Y-m-d'));
    }

    public function test_cancelled_and_postponed_without_new_term_are_excluded_from_period(): void
    {
        $event = $this->makePublishedEvent('DG P', '2026-11-05');
        $occ = $event->occurrences()->firstOrFail();
        $this->occurrenceLifecycle->postpone($occ, 'odgođeno');

        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$event->id],
        ]);

        $this->assertNull($this->periodCalculator->calculate($manifestation->fresh()));
    }

    public function test_resumed_new_planned_occurrence_enters_period(): void
    {
        $event = $this->makePublishedEvent('DG R', '2026-12-01');
        $occ = $event->occurrences()->firstOrFail();
        $this->occurrenceLifecycle->postpone($occ, 'odgođeno');
        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => '2026-12-15',
            'cjelodnevno' => true,
        ]);

        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$event->id],
        ]);

        $period = $this->periodCalculator->calculate($manifestation->fresh());
        $this->assertNotNull($period);
        $this->assertSame('2026-12-15', $period['start']->format('Y-m-d'));
        $this->assertSame('2026-12-15', $period['end']->format('Y-m-d'));
    }

    public function test_fk_set_null_on_manifestation_delete_and_mf_survives_event_delete(): void
    {
        $event = $this->makeDraftEvent('Delete DG');
        $manifestation = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Delete MF',
            'event_entry_ids' => [$event->id],
        ]);

        $manifestation->delete();
        $event->refresh();
        $this->assertNull($event->manifestation_id);

        $manifestation2 = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF2']);
        $event2 = $this->makeDraftEvent('E2');
        $this->manifestationWriter->linkEvent($manifestation2, $event2->id, $this->editor);

        $event2->delete();
        $this->assertNotNull($manifestation2->fresh());
    }

    public function test_move_event_between_manifestations_is_atomic(): void
    {
        $event = $this->makeDraftEvent('Move me');
        $source = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Source',
            'event_entry_ids' => [$event->id],
        ]);
        $target = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'Target']);

        $this->manifestationWriter->moveEvent($target, $event->id, $this->editor);

        $this->assertSame($target->id, $event->fresh()->manifestation_id);
        $this->assertSame(0, $source->fresh()->events()->count());
        $this->assertSame(1, $target->fresh()->events()->count());
    }

    public function test_move_blocked_when_source_published_would_lose_last_published_event(): void
    {
        $published = $this->makePublishedEvent('Only published');
        $source = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'Source published',
            'event_entry_ids' => [$published->id],
        ]);
        $lifecycle = app(\App\Services\CulturalManifestationDomain\ManifestationLifecycle::class);
        $lifecycle->submitForApproval($source, $this->editor);
        $lifecycle->publish($source->fresh(), $this->editor);

        $target = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'Target']);

        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->moveEvent($target, $published->id, $this->editor);
    }

    public function test_cancelled_and_archived_events_cannot_be_newly_linked(): void
    {
        $published = $this->makePublishedEvent('To cancel');
        $this->eventLifecycle->cancel($published->fresh(), $this->editor, 'Razlog');
        $mf = $this->manifestationWriter->createDraft($this->editor, ['naziv' => 'MF']);

        try {
            $this->manifestationWriter->linkEvent($mf, $published->id, $this->editor);
            $this->fail('Expected cancelled Event link to fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('ne može biti novo povezan', $e->getMessage());
        }

        $archived = $this->makePublishedEvent('To archive', '2020-01-01');
        $archived->update(['status' => CulturalEventEntry::STATUS_ARCHIVED]);
        $this->expectException(CulturalEventDomainException::class);
        $this->manifestationWriter->linkEvent($mf->fresh(), $archived->id, $this->editor);
    }

    public function test_cancelled_event_already_linked_remains_linked(): void
    {
        $published = $this->makePublishedEvent('Linked then cancel');
        $mf = $this->manifestationWriter->createDraft($this->editor, [
            'naziv' => 'MF',
            'event_entry_ids' => [$published->id],
        ]);
        $this->eventLifecycle->cancel($published->fresh(), $this->editor, 'Razlog');

        $this->assertSame($mf->id, $published->fresh()->manifestation_id);
    }

    private function makeDraftEvent(string $title): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($this->editor, [
            'naslov' => $title,
        ]);
    }

    private function makePublishedEvent(string $title, string $date = '2026-09-01'): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Kategorija '.uniqid(),
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

        return $entry->fresh(['occurrences']);
    }

    private function makeActiveOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org '.uniqid(),
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org '.uniqid(),
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeActiveManifestationCoverMedia(array $overrides = []): CulturalMedia
    {
        return CulturalMedia::create(array_merge([
            'naziv' => 'MF cover '.uniqid(),
            'namjena' => CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'm.jpg',
            'interni_naziv' => 'm.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 120,
            'storage_path' => 'cultural-media/m.jpg',
            'creator_id' => $this->editor->id,
        ], $overrides));
    }
}

