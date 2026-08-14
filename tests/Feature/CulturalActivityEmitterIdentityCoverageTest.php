<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityActor;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use App\Services\CulturalActivity\CulturalActivityRecordInput;
use App\Services\CulturalActivity\CulturalActivityRecorder;
use App\Services\CulturalActivity\CulturalActivityStore;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * F8-03 identity + remaining catalog coverage.
 *
 * Coverage map (executable unless noted):
 * MOD-01..07 — CulturalActivityEmitterModOrgTest
 * ORG-01..07 — ModOrgTest + EventTest (ORG-05)
 * EV-01..06, 09..13, 15..21 — EmitterEventTest
 * EV-07, EV-08, EV-14 — this class
 * MF-01..05, 07..12 — EmitterMfNlTest
 * MF-06 — this class
 * NL-01..06 — EmitterMfNlTest (NL-06 also here via MfNl send test)
 */
class CulturalActivityEmitterIdentityCoverageTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private OccurrenceLifecycle $occurrenceLifecycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email' => 'f803.id.mod@example.com',
        ]);
        $this->category = CulturalCategory::create([
            'naziv' => 'ID Cat',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_retry_same_published_edit_keeps_one_audit_row(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        $entry = $this->publishDirect('Retry');
        $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'Retry 1']);
        $row = CulturalActivityRecord::query()->where('event_type', 'event.published_direct_edit')->sole();

        app(CulturalActivityRecorder::class)->record(new CulturalActivityRecordInput(
            sourceModule: $row->source_module,
            eventId: $row->event_id,
            eventType: $row->event_type,
            occurredAt: $row->occurred_at,
            actor: CulturalActivityActor::user($this->editor),
            targetType: $row->target_type,
            targetId: $row->target_id,
            organizerContextId: $row->organizer_context_id,
            context: $row->context ?? [],
        ));

        $this->assertSame(1, CulturalActivityRecord::query()->where('event_type', 'event.published_direct_edit')->count());
    }

    public function test_ev20_event_id_cannot_be_rebuilt_from_persisted_row_after_audit_failure(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        $capturedId = null;
        $store = $this->createMock(CulturalActivityStore::class);
        $store->method('write')->willReturnCallback(function ($input) use (&$capturedId) {
            if ($input->eventType === 'event.published_direct_edit') {
                $capturedId = $input->eventId;
            }
            throw new \RuntimeException('forced audit');
        });
        $recorder = new CulturalActivityRecorder($store);
        $this->app->instance(CulturalActivityRecorder::class, $recorder);
        $this->app->instance(CulturalActivityEmitter::class, new CulturalActivityEmitter($recorder));
        $this->writer = $this->app->make(EventWriter::class);

        $entry = $this->publishDirect('Before title');
        $this->assertDatabaseHas('cultural_event_entries', ['id' => $entry->id, 'naslov' => 'Before title']);

        $updated = $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'After title']);
        $this->assertSame('After title', $updated->fresh()->naslov);
        $this->assertNotNull($capturedId);
        $this->assertSame(0, CulturalActivityRecord::query()->where('event_type', 'event.published_direct_edit')->count());

        $persisted = $updated->fresh(['tags']);
        $current = $this->contentIdentity($persisted);
        $rebuilt = CulturalActivityEventId::repeatable(
            CulturalActivityCatalog::EV_20,
            (int) $persisted->id,
            ['from' => $current, 'to' => $current],
            $persisted->updated_at ?? now()
        );

        $this->assertNotSame(
            $capturedId,
            $rebuilt,
            'Post-persist reconstruction has only current state; original event_id hashed pre-change from-state.'
        );
    }

    public function test_identical_payload_feature_actions_share_repeatable_formula_on_frozen_clock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-14 12:00:00.000000', 'Europe/Belgrade'));
        $first = CulturalActivityEventId::repeatable(
            CulturalActivityCatalog::EV_07,
            42,
            ['featured' => 1],
            now()
        );
        $second = CulturalActivityEventId::repeatable(
            CulturalActivityCatalog::EV_07,
            42,
            ['featured' => 1],
            now()
        );
        $this->assertSame($first, $second);
    }

    public function test_two_frozen_clock_published_edits_are_not_deduplicated(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        $entry = $this->publishDirect('Two edits');
        $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'Edit A']);
        $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'Edit B']);

        $rows = CulturalActivityRecord::query()
            ->where('event_type', 'event.published_direct_edit')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $rows);
        $this->assertNotSame($rows[0]->event_id, $rows[1]->event_id);
        $this->assertSame($this->editor->id, $rows[0]->actor_user_id);
        $this->assertSame($entry->id, $rows[0]->target_id);
        $this->assertSame(['entry_id' => $entry->id], $rows[0]->context);
    }

    public function test_two_frozen_clock_resumes_emit_distinct_ev13(): void
    {
        Carbon::setTestNow('2026-08-14 12:00:00');
        $entry = $this->publishDirect('Resume');
        $occ = $entry->occurrences()->firstOrFail();
        $this->occurrenceLifecycle->postpone($occ->fresh(), null, $this->editor);
        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => '2026-09-01',
            'cjelodnevno' => true,
        ], $this->editor);
        $this->occurrenceLifecycle->postpone($occ->fresh(), null, $this->editor);
        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => '2026-10-01',
            'cjelodnevno' => true,
        ], $this->editor);

        $rows = CulturalActivityRecord::query()->where('event_type', 'occ.reschedule')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertNotSame($rows[0]->event_id, $rows[1]->event_id);
        $this->assertSame('TS-004', $rows[0]->source_module);
        $this->assertSame($occ->id, $rows[0]->target_id);
    }

    public function test_ev07_feature_and_ev08_unfeature(): void
    {
        $entry = $this->publishDirect('Featured');
        $this->assertTrue($entry->fresh()->isAktuelan());

        $featured = $this->writer->updateContent($entry->fresh(), $this->editor, ['featured' => true]);
        $ev07 = CulturalActivityRecord::query()->where('event_type', 'event.feature')->sole();
        $this->assertSame('TS-003', $ev07->source_module);
        $this->assertSame('user', $ev07->actor_type);
        $this->assertSame($this->editor->id, $ev07->actor_user_id);
        $this->assertSame('event', $ev07->target_type);
        $this->assertSame($featured->id, $ev07->target_id);
        $this->assertStringStartsWith('TS12-EV-07:', $ev07->event_id);
        $this->assertSame(['entry_id' => $featured->id], $ev07->context);

        $this->writer->updateContent($featured->fresh(), $this->editor, ['featured' => false]);
        $ev08 = CulturalActivityRecord::query()->where('event_type', 'event.unfeature')->sole();
        $this->assertSame('TS-003', $ev08->source_module);
        $this->assertSame($this->editor->id, $ev08->actor_user_id);
        $this->assertSame($featured->id, $ev08->target_id);
        $this->assertStringStartsWith('TS12-EV-08:', $ev08->event_id);
        $this->assertNotSame($ev07->event_id, $ev08->event_id);
        $this->assertSame(['entry_id' => $featured->id], $ev08->context);
    }

    public function test_ev14_location_change_via_proposal_apply(): void
    {
        $organizer = $this->makeOrganizer();
        $location = CulturalLocation::create([
            'naziv' => 'Sala ID',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Loc change',
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);
        $occ = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(6)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->lifecycle->approve($entry->fresh(), $this->editor);

        CulturalOrganizerContext::set($this->moderator, $organizer->id);
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry->fresh(), $this->moderator);
        app(EventChangeProposalWriter::class)->upsertOccurrenceUpdateOp($proposal, $this->moderator, $occ->fresh(), [
            'datum' => $occ->datum->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $location->id,
        ]);
        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalApplicator::class)->approve($proposal->fresh(), $this->editor);

        $ev14 = CulturalActivityRecord::query()->where('event_type', 'occ.location_change')->sole();
        $this->assertSame('TS-004', $ev14->source_module);
        $this->assertSame($this->editor->id, $ev14->actor_user_id);
        $this->assertSame('occurrence', $ev14->target_type);
        $this->assertSame($occ->id, $ev14->target_id);
        $this->assertStringStartsWith('TS12-EV-14:', $ev14->event_id);
        $this->assertStringContainsString('proposal', $ev14->event_id);
        $this->assertSame($occ->id, $ev14->context['occurrence_id']);
        $this->assertSame($entry->id, $ev14->context['entry_id']);
        $this->assertArrayNotHasKey('location_name', $ev14->context);
    }

    public function test_mf06_system_archive(): void
    {
        $expired = $this->writer->createDraft($this->editor, [
            'naslov' => 'Expired MF event',
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($expired, [
            'datum' => '2020-01-01',
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($expired->fresh(), $this->editor);

        $mf = app(ManifestationWriter::class)->createDraft($this->editor, [
            'naziv' => 'Archive MF',
            'event_entry_ids' => [$expired->id],
        ]);
        app(ManifestationLifecycle::class)->publishDirectly($mf->fresh(), $this->editor);
        $archived = app(ManifestationLifecycle::class)->archiveIfEligible($mf->fresh());

        $row = CulturalActivityRecord::query()->where('event_type', 'mf.auto_archive')->sole();
        $this->assertSame('system', $row->actor_type);
        $this->assertNull($row->actor_user_id);
        $this->assertSame($archived->id, $row->target_id);
        $this->assertSame('TS12-MF-06:'.$archived->id, $row->event_id);
    }

    private function publishDirect(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(7)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
    }

    /**
     * Mirrors EventWriter content identity used in EV-20 event_id.
     *
     * @return array<string, scalar|null|list<int>>
     */
    private function contentIdentity(CulturalEventEntry $entry): array
    {
        $tagIds = $entry->relationLoaded('tags')
            ? $entry->tags->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all()
            : $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        return [
            'naslov' => (string) $entry->naslov,
            'opis' => (string) ($entry->opis ?? ''),
            'category_id' => $entry->category_id !== null ? (int) $entry->category_id : null,
            'cover_media_id' => $entry->cover_media_id !== null ? (int) $entry->cover_media_id : null,
            'featured' => (bool) $entry->featured ? 1 : 0,
            'organizer_id' => $entry->organizer_id !== null ? (int) $entry->organizer_id : null,
            'tag_ids' => $tagIds,
        ];
    }

    private function makeOrganizer(): \App\Models\CulturalOrganizer
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->editor, [
            'naziv' => 'ID Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);

        return app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
    }
}
