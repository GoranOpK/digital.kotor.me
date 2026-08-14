<?php

namespace Tests\Feature;

use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceLifecycle;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalOrganizer\OrganizerCreationDecisionService;
use App\Services\CulturalOrganizer\OrganizerCreationRequestSubmissionService;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * F8-03 — TS12-EV emitters.
 */
class CulturalActivityEmitterEventTest extends TestCase
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
            'email' => 'f803.ev.mod@example.com',
        ]);
        $this->category = CulturalCategory::create([
            'naziv' => 'F803 Cat',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->occurrenceLifecycle = app(OccurrenceLifecycle::class);
    }

    public function test_event_create_submit_return_resubmit_approve(): void
    {
        $organizer = $this->makeOrganizer();
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Tok',
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);
        $this->assertActivity('event.create', [
            'actor_user_id' => $this->editor->id,
            'target_id' => $entry->id,
            'source_module' => 'TS-003',
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(3)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->assertActivity('event.submit', ['target_id' => $entry->id]);

        $this->lifecycle->returnToDraft($entry->fresh(), $this->editor, 'Dorada');
        $this->assertActivity('event.return', ['target_id' => $entry->id]);

        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->assertActivity('event.resubmit', ['target_id' => $entry->id]);

        $this->lifecycle->approve($entry->fresh(), $this->editor);
        $this->assertActivity('event.approve', ['target_id' => $entry->id]);
    }

    public function test_direct_publish_cancel_reason_delete_and_occ_user_actions(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Direct',
            'category_id' => $this->category->id,
        ]);
        $occ = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->assertActivity('event.direct_publish', ['target_id' => $entry->id]);

        $this->occurrenceLifecycle->postpone($occ->fresh(), 'Kiša', $this->editor);
        $this->assertActivity('occ.postpone', [
            'source_module' => 'TS-004',
            'actor_user_id' => $this->editor->id,
            'target_id' => $occ->id,
        ]);

        $this->occurrenceLifecycle->resumeWithNewTermin($occ->fresh(), [
            'datum' => now()->addDays(8)->toDateString(),
            'cjelodnevno' => true,
        ], $this->editor);
        $this->assertActivity('occ.reschedule', ['target_id' => $occ->id]);

        $this->occurrenceLifecycle->cancel($occ->fresh(), 'Stop', $this->editor);
        $this->assertActivity('occ.cancel', ['target_id' => $occ->id]);

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Event stop');
        $this->assertActivity('event.cancel', ['target_id' => $entry->id]);
        $this->assertSame(1, CulturalActivityRecord::query()->where('event_type', 'occ.cancel')->count());

        $this->writer->updateContent($entry->fresh(), $this->editor, ['cancellation_reason' => 'Dopuna']);
        $this->assertActivity('event.cancellation_reason', ['target_id' => $entry->id]);
    }

    public function test_cascade_event_cancel_does_not_emit_occ_cancel(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Cascade',
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(2)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $occCancelBefore = CulturalActivityRecord::query()->where('event_type', 'occ.cancel')->count();

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Sve');
        $this->assertSame($occCancelBefore, CulturalActivityRecord::query()->where('event_type', 'occ.cancel')->count());
        $this->assertActivity('event.cancel', ['target_id' => $entry->id]);
    }

    public function test_published_direct_edit_unpublished_delete_archive_autofinish(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Edit me',
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(4)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'Edited']);
        $this->assertActivity('event.published_direct_edit', ['target_id' => $entry->id]);

        $draft = $this->writer->createDraft($this->editor, [
            'naslov' => 'Delete me',
            'category_id' => $this->category->id,
        ]);
        $this->writer->destroyNeverPublishedDraft($draft, $this->editor);
        $this->assertActivity('event.unpublished_delete', ['target_id' => $draft->id]);

        $expired = $this->writer->createDraft($this->editor, [
            'naslov' => 'Finish',
            'category_id' => $this->category->id,
        ]);
        $occ = $this->occurrenceWriter->create($expired, [
            'datum' => now()->subDays(3)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($expired->fresh(), $this->editor);
        $finished = $this->occurrenceLifecycle->finishIfExpiredAt($occ->fresh(), now());
        $this->assertNotNull($finished);
        $auto = $this->assertActivity('occ.auto_finish', ['target_id' => $occ->id]);
        $this->assertSame('system', $auto->actor_type);
        $this->assertNull($auto->actor_user_id);

        $this->lifecycle->archiveIfEligible($expired->fresh());
        $archive = $this->assertActivity('event.auto_archive', ['target_id' => $expired->id]);
        $this->assertSame('system', $archive->actor_type);
    }

    public function test_proposal_submit_approve_return_and_org_link(): void
    {
        $organizer = $this->makeOrganizer();
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Prop',
            'category_id' => $this->category->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(6)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $this->lifecycle->publishDirectly($entry->fresh(), $this->editor);
        $this->writer->linkOrganizer($entry->fresh(), $this->editor, $organizer->id);
        $this->assertActivity('org.event.link', [
            'source_module' => 'TS-003',
            'target_id' => $entry->id,
        ]);

        CulturalOrganizerContext::set($this->moderator, $organizer->id);
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry->fresh(), $this->moderator);
        $this->assertSame(0, CulturalActivityRecord::query()->where('event_type', 'event.proposal.submit')->count());

        app(EventChangeProposalLifecycle::class)->submit($proposal, $this->moderator);
        $this->assertActivity('event.proposal.submit', [
            'actor_user_id' => $this->moderator->id,
            'target_id' => $proposal->id,
        ]);

        app(EventChangeProposalLifecycle::class)->returnToDraft($proposal->fresh(), $this->editor, 'Ispravka');
        $this->assertActivity('event.proposal.return', ['target_id' => $proposal->id]);

        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalApplicator::class)->approve($proposal->fresh(), $this->editor);
        $this->assertActivity('event.proposal.approve', ['actor_user_id' => $this->editor->id]);
    }

    public function test_draft_content_save_is_not_published_direct_edit(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Draft',
            'category_id' => $this->category->id,
        ]);
        $before = CulturalActivityRecord::query()->where('event_type', 'event.published_direct_edit')->count();
        $this->writer->updateContent($entry, $this->editor, ['naslov' => 'Draft 2']);
        $this->assertSame($before, CulturalActivityRecord::query()->where('event_type', 'event.published_direct_edit')->count());
    }

    private function makeOrganizer(): CulturalOrganizer
    {
        $request = app(OrganizerCreationRequestSubmissionService::class)->submit($this->editor, [
            'naziv' => 'EV Org',
            'proposed_moderator_name' => $this->moderator->name,
            'proposed_moderator_email' => $this->moderator->email,
        ]);

        return app(OrganizerCreationDecisionService::class)->approve($request, $this->editor);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function assertActivity(string $eventType, array $attrs = []): CulturalActivityRecord
    {
        $query = CulturalActivityRecord::query()->where('event_type', $eventType);
        foreach ($attrs as $key => $value) {
            $query->where($key, $value);
        }
        $record = $query->latest('id')->first();
        $this->assertNotNull($record, 'Expected activity '.$eventType);

        return $record;
    }
}
