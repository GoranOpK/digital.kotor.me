<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Konflikt termina: statusni Odgođen→Planiran vs stariji Proposal update.
 */
class CulturalEventChangeProposalTerminConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private CulturalOrganizer $orgA;

    private CulturalCategory $category;

    private CulturalTag $tag;

    private CulturalLocation $location;

    private CulturalLocation $locationB;

    private EventChangeProposalWriter $writer;

    private EventChangeProposalLifecycle $lifecycle;

    private EventChangeProposalApplicator $applicator;

    private OccurrenceLifecycle $occurrenceLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private EventLifecycle $eventLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->modA = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org Conflict');
        $this->grantModerator($this->modA, $this->orgA);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti Conflict',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->tag = CulturalTag::create([
            'naziv' => 'Tag Conflict',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);
        $this->location = CulturalLocation::create([
            'naziv' => 'Lokacija A',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);
        $this->locationB = CulturalLocation::create([
            'naziv' => 'Lokacija B',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventChangeProposalWriter::class);
        $this->lifecycle = app(EventChangeProposalLifecycle::class);
        $this->applicator = app(EventChangeProposalApplicator::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
    }

    public function test_approve_rejected_when_termin_changed_via_resume(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $t1 = now()->addDays(10)->toDateString();
        $t2 = now()->addDays(45)->toDateString();

        $entry = $this->makePublished('Conflict termin', [
            'opis' => 'Original opis',
            'tag_ids' => [$this->tag->id],
            'occurrence_datum' => $t1,
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();
        $originalNaslov = $entry->naslov;

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Naslov iz prijedloga',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [$this->tag->id],
        ]);
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $t1,
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $op = $proposal->fresh()->occurrenceOps->first();
        $this->assertSame($t1, $op->baseline_datum->toDateString());

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->occurrenceLifecycle->postpone($occurrence->fresh());
        $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => $t2,
            'cjelodnevno' => true,
        ]);
        $this->assertSame($t2, $occurrence->fresh()->datum->toDateString());

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve should reject termin conflict');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('međuvremenu promijenjeno', $e->getMessage());
        }

        $entry->refresh();
        $proposal->refresh();
        $occurrence->refresh();

        $this->assertSame($originalNaslov, $entry->naslov);
        $this->assertSame('Original opis', $entry->opis);
        $this->assertSame($t2, $occurrence->datum->toDateString());
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->status);
        $this->assertSame($entry->id, $proposal->active_for_event_id);
        $this->assertFalse($proposal->isApproved());
    }

    public function test_postpone_alone_does_not_false_conflict_on_location_update(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $t1 = now()->addDays(12)->toDateString();

        $entry = $this->makePublished('Location only', [
            'occurrence_datum' => $t1,
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Location only',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $t1,
            'cjelodnevno' => true,
            'location_id' => $this->locationB->id,
        ]);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->occurrenceLifecycle->postpone($occurrence->fresh());
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->fresh()->status);
        $this->assertSame($t1, $occurrence->fresh()->datum->toDateString());

        $approved = $this->applicator->approve($proposal->fresh(), $this->editor);

        $this->assertTrue($approved->isApproved());
        $occurrence->refresh();
        $this->assertSame($this->locationB->id, $occurrence->location_id);
        $this->assertSame($t1, $occurrence->datum->toDateString());
        $this->assertSame(CulturalOccurrence::STATUS_POSTPONED, $occurrence->status);
    }

    public function test_approve_succeeds_when_termin_unchanged(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $t1 = now()->addDays(15)->toDateString();

        $entry = $this->makePublished('Same termin', [
            'occurrence_datum' => $t1,
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Same termin updated',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $t1,
            'cjelodnevno' => true,
            'location_id' => $this->locationB->id,
        ]);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);
        $this->applicator->approve($proposal->fresh(), $this->editor);

        $entry->refresh();
        $occurrence->refresh();
        $this->assertSame('Same termin updated', $entry->naslov);
        $this->assertSame($this->locationB->id, $occurrence->location_id);
        $this->assertSame($t1, $occurrence->datum->toDateString());
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
    }

    public function test_conflict_rolls_back_entire_approve_including_other_ops(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $t1 = now()->addDays(8)->toDateString();
        $t2 = now()->addDays(55)->toDateString();

        $entry = $this->makePublished('Partial rollback', [
            'opis' => 'Keep opis',
            'tag_ids' => [$this->tag->id],
            'occurrence_datum' => $t1,
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();
        $beforeCount = $entry->occurrences()->count();
        $originalTagIds = $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Should not apply',
            'proposed_opis' => 'Should not apply opis',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $t1,
            'cjelodnevno' => true,
            'location_id' => $this->locationB->id,
        ]);
        $this->writer->addOccurrenceOp($proposal->fresh(), $this->modA, [
            'datum' => now()->addDays(70)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->occurrenceLifecycle->postpone($occurrence->fresh());
        $this->occurrenceLifecycle->resumeWithNewTermin($occurrence->fresh(), [
            'datum' => $t2,
            'cjelodnevno' => true,
        ]);

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Expected conflict');
        } catch (CulturalEventDomainException) {
            // expected
        }

        $entry->refresh();
        $proposal->refresh();
        $occurrence->refresh();

        $this->assertSame('Partial rollback', $entry->naslov);
        $this->assertSame('Keep opis', $entry->opis);
        $this->assertEqualsCanonicalizing(
            $originalTagIds,
            $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame($beforeCount, $entry->occurrences()->count());
        $this->assertSame($t2, $occurrence->datum->toDateString());
        $this->assertSame($this->location->id, $occurrence->location_id);
        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->status);
        $this->assertSame(2, $proposal->occurrenceOps()->count());
    }

    public function test_baseline_not_overwritten_on_subsequent_edit(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $t1 = now()->addDays(9)->toDateString();
        $tProposed = now()->addDays(20)->toDateString();

        $entry = $this->makePublished('Baseline stable', [
            'occurrence_datum' => $t1,
            'occurrence_location_id' => $this->location->id,
        ]);
        $occurrence = $entry->occurrences->first();

        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $op = $this->writer->upsertOccurrenceUpdateOp($proposal, $this->modA, $occurrence, [
            'datum' => $tProposed,
            'cjelodnevno' => true,
            'location_id' => $this->location->id,
        ]);
        $this->assertSame($t1, $op->baseline_datum->toDateString());

        $op2 = $this->writer->upsertOccurrenceUpdateOp($proposal->fresh(), $this->modA, $occurrence, [
            'datum' => now()->addDays(25)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $this->locationB->id,
        ]);

        $this->assertSame($op->id, $op2->id);
        $this->assertSame($t1, $op2->fresh()->baseline_datum->toDateString());
        $this->assertSame(CulturalEventChangeProposalOccurrence::OPERATION_UPDATE, $op2->operation);
    }

    /**
     * @param  array{
     *     opis?: string,
     *     tag_ids?: list<int>,
     *     occurrence_datum?: string,
     *     occurrence_location_id?: int
     * }  $extra
     */
    private function makePublished(string $naslov, array $extra = []): CulturalEventEntry
    {
        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'opis' => $extra['opis'] ?? null,
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgA->id,
            'tag_ids' => $extra['tag_ids'] ?? [],
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => $extra['occurrence_datum'] ?? now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
            'location_id' => $extra['occurrence_location_id'] ?? null,
        ]);

        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh(['tags', 'occurrences']);
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

    private function grantModerator(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'organizer_id' => $organizer->id,
            ],
            [
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );
    }
}
