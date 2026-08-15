<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
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
 * TS-004 Korak 1 — kanonsko Održavanje (PO-EV-01).
 */
class CulturalOccurrenceDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

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

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->writer = app(OccurrenceWriter::class);
        $this->lifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_event_has_one_to_many_occurrences(): void
    {
        $entry = $this->makeDraftEvent();

        $a = $this->writer->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);
        $b = $this->writer->create($entry, ['datum' => '2026-09-02', 'cjelodnevno' => true]);

        $this->assertCount(2, $entry->fresh()->occurrences);
        $this->assertSame($entry->id, $a->event_entry_id);
        $this->assertSame($entry->id, $b->event_entry_id);
    }

    public function test_occurrence_belongs_to_exactly_one_event(): void
    {
        $entry = $this->makeDraftEvent();
        $occurrence = $this->writer->create($entry, ['datum' => '2026-09-01']);

        $this->assertTrue($occurrence->eventEntry->is($entry));
        $this->assertDatabaseCount('cultural_occurrences', 1);
    }

    public function test_datum_is_required_and_validated(): void
    {
        $entry = $this->makeDraftEvent();

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->create($entry, ['datum' => '']);
    }

    public function test_time_from_to_validation(): void
    {
        $entry = $this->makeDraftEvent();

        $ok = $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'vrijeme_od' => '18:00',
            'vrijeme_do' => '20:00',
        ]);
        $this->assertSame('18:00:00', $ok->vrijeme_od);
        $this->assertSame('20:00:00', $ok->vrijeme_do);

        try {
            $this->writer->create($entry, [
                'datum' => '2026-09-02',
                'vrijeme_do' => '20:00',
            ]);
            $this->fail('Expected end-without-start failure');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('bez vremena početka', $e->getMessage());
        }

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->create($entry, [
            'datum' => '2026-09-03',
            'vrijeme_od' => '20:00',
            'vrijeme_do' => '18:00',
        ]);
    }

    public function test_all_day_behaviour(): void
    {
        $entry = $this->makeDraftEvent();

        $occurrence = $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'cjelodnevno' => true,
        ]);

        $this->assertTrue($occurrence->cjelodnevno);
        $this->assertNull($occurrence->vrijeme_od);
        $this->assertNull($occurrence->vrijeme_do);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->create($entry, [
            'datum' => '2026-09-02',
            'cjelodnevno' => true,
            'vrijeme_od' => '10:00',
        ]);
    }

    public function test_catalog_location(): void
    {
        $entry = $this->makeDraftEvent();
        $location = CulturalLocation::create([
            'naziv' => 'Trg',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $occurrence = $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'location_id' => $location->id,
        ]);

        $this->assertTrue($occurrence->hasCatalogLocation());
        $this->assertTrue($occurrence->location->is($location));
    }

    public function test_manual_location_name(): void
    {
        $entry = $this->makeDraftEvent();

        $occurrence = $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'location_manual_name' => 'Dvorište škole',
        ]);

        $this->assertTrue($occurrence->hasManualLocation());
        $this->assertNull($occurrence->location_id);
        $this->assertSame('Dvorište škole', $occurrence->location_manual_name);
    }

    public function test_without_location(): void
    {
        $entry = $this->makeDraftEvent();

        $occurrence = $this->writer->create($entry, [
            'datum' => '2026-09-01',
        ]);

        $this->assertTrue($occurrence->hasNoLocation());
    }

    public function test_deactivated_location_cannot_be_selected_for_new_link(): void
    {
        $entry = $this->makeDraftEvent();
        $location = CulturalLocation::create([
            'naziv' => 'Stara',
            'status' => CulturalLocation::STATUS_DEACTIVATED,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'location_id' => $location->id,
        ]);
    }

    public function test_historical_location_remains_after_deactivation(): void
    {
        $entry = $this->makeDraftEvent();
        $location = CulturalLocation::create([
            'naziv' => 'Park',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $occurrence = $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'location_id' => $location->id,
        ]);

        $location->update(['status' => CulturalLocation::STATUS_DEACTIVATED]);

        $this->writer->update($occurrence, ['datum' => '2026-09-02']);
        $this->assertSame($location->id, $occurrence->fresh()->location_id);
    }

    public function test_status_planned_postponed_cancelled_finished(): void
    {
        $entry = $this->makeDraftEvent();
        $occurrence = $this->writer->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
        $this->assertSame('Planiran', $occurrence->statusLabel());

        $this->lifecycle->postpone($occurrence);
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);

        $this->lifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => '2026-09-10',
            'cjelodnevno' => true,
        ]);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame('2026-09-10', $occurrence->fresh()->datum->toDateString());

        $this->lifecycle->cancel($occurrence->fresh());
        $this->assertSame(CulturalOccurrence::STATUS_CANCELLED, $occurrence->fresh()->status);

        $other = $this->writer->create($entry, ['datum' => '2026-09-03', 'cjelodnevno' => true]);
        $this->lifecycle->markFinished($other);
        $this->assertSame(CulturalOccurrence::STATUS_FINISHED, $other->fresh()->status);
    }

    public function test_cancelling_one_occurrence_does_not_cancel_event(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Program',
            'category_id' => $category->id,
        ]);
        $first = $this->writer->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);
        $second = $this->writer->create($entry, ['datum' => '2026-09-02', 'cjelodnevno' => true]);

        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);
        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);

        $result = $this->lifecycle->cancelWithoutAffectingEvent($first->fresh());

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $result['event_status_before']);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $result['event_status_after']);
        $this->assertTrue($result['occurrence']->isCancelled());
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $second->fresh()->status);
    }

    public function test_physical_delete_only_on_pristine_draft(): void
    {
        $entry = $this->makeDraftEvent();
        $occurrence = $this->writer->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);

        $this->writer->deletePhysically($occurrence);
        $this->assertDatabaseCount('cultural_occurrences', 0);

        $entry = $this->makeReadyPublishedPathEvent();
        $kept = $entry->occurrences()->first();

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->deletePhysically($kept);
    }

    public function test_catalog_and_manual_location_mutually_exclusive(): void
    {
        $entry = $this->makeDraftEvent();
        $location = CulturalLocation::create([
            'naziv' => 'Muzej',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->create($entry, [
            'datum' => '2026-09-01',
            'location_id' => $location->id,
            'location_manual_name' => 'Ručno',
        ]);
    }

    private function makeDraftEvent(): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Nacrt',
        ]);
    }

    private function makeReadyPublishedPathEvent(): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => 'Za objavu',
            'category_id' => $category->id,
            'organizer_id' => $this->makeOrganizer('Org OCC delete')->id,
        ]);
        $this->writer->create($entry, ['datum' => '2026-09-01', 'cjelodnevno' => true]);
        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);

        return $entry->fresh(['occurrences']);
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
