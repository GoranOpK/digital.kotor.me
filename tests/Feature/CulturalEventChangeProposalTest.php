<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
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
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

/**
 * TS-010.3a — Prijedlog izmjene objavljenog Događaja (sadržaj + workflow).
 * Occurrence ops: CulturalEventChangeProposalOccurrenceTest (TS-010.3b).
 */
class CulturalEventChangeProposalTest extends TestCase
{
    use CreatesCulturalMediaFixtures;
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modA2;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

    private CulturalTag $tag;

    private CulturalMedia $cover;

    private EventChangeProposalWriter $writer;

    private EventChangeProposalLifecycle $lifecycle;

    private EventChangeProposalApplicator $applicator;

    private EventLifecycle $eventLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->modA = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modA2 = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modB = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org A Proposal');
        $this->orgB = $this->makeOrganizer('Org B Proposal');
        $this->grantModerator($this->modA, $this->orgA);
        $this->grantModerator($this->modA2, $this->orgA);
        $this->grantModerator($this->modB, $this->orgB);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $this->tag = CulturalTag::create([
            'naziv' => 'Jazz',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);
        $this->cover = $this->makeActiveCoverMedia(['naziv' => 'Cover A']);

        $this->writer = app(EventChangeProposalWriter::class);
        $this->lifecycle = app(EventChangeProposalLifecycle::class);
        $this->applicator = app(EventChangeProposalApplicator::class);
        $this->eventLifecycle = app(EventLifecycle::class);
    }

    public function test_create_only_on_published_rejects_other_statuses(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        foreach ([
            CulturalEventEntry::STATUS_DRAFT,
            CulturalEventEntry::STATUS_PENDING_APPROVAL,
            CulturalEventEntry::STATUS_CANCELLED,
            CulturalEventEntry::STATUS_ARCHIVED,
        ] as $status) {
            $entry = $this->makeEntryWithStatus($status, 'Status '.$status);
            try {
                $this->writer->createFromPublished($entry, $this->modA);
                $this->fail("Expected rejection for status {$status}");
            } catch (CulturalEventDomainException $e) {
                $this->assertStringContainsString('objavljeni', mb_strtolower($e->getMessage()));
            }
        }

        $published = $this->makePublished('Ok published');
        $proposal = $this->writer->createFromPublished($published, $this->modA);
        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->status);
        $this->assertSame($published->id, $proposal->active_for_event_id);
    }

    public function test_create_snapshots_event_content_and_tags(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Snapshot naslov', [
            'opis' => 'Snapshot opis',
            'cover_media_id' => $this->cover->id,
            'tag_ids' => [$this->tag->id],
        ]);

        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $this->assertSame('Snapshot naslov', $proposal->proposed_naslov);
        $this->assertSame('Snapshot opis', $proposal->proposed_opis);
        $this->assertSame($this->category->id, $proposal->proposed_category_id);
        $this->assertSame($this->cover->id, $proposal->proposed_cover_media_id);
        $this->assertEqualsCanonicalizing([$this->tag->id], $proposal->tags->pluck('id')->all());
        $this->assertSame($this->orgA->id, $proposal->organizer_id);
        $this->assertFalse((bool) $entry->fresh()->featured);
    }

    public function test_br012_only_one_active_proposal_second_moderator_rejected(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Jedan aktivan');
        $this->writer->createFromPublished($entry, $this->modA);

        CulturalOrganizerContext::set($this->modA2, $this->orgA->id);
        try {
            $this->writer->createFromPublished($entry->fresh(), $this->modA2);
            $this->fail('Second active proposal should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('aktivan prijedlog', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(1, CulturalEventChangeProposal::query()->where('event_entry_id', $entry->id)->count());
    }

    public function test_br012_unique_violation_maps_to_domain_error(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Unique race');
        $this->writer->createFromPublished($entry, $this->modA);

        try {
            DB::table('cultural_event_change_proposals')->insert([
                'event_entry_id' => $entry->id,
                'organizer_id' => $this->orgA->id,
                'created_by' => $this->modA2->id,
                'last_modified_by' => $this->modA2->id,
                'status' => CulturalEventChangeProposal::STATUS_DRAFT,
                'proposed_naslov' => 'Race',
                'proposed_category_id' => $this->category->id,
                'active_for_event_id' => $entry->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Expected unique violation');
        } catch (QueryException $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'cecp_active_for_event_unique')
                || str_contains($e->getMessage(), 'active_for_event_id')
            );
        }

        // Writer path maps the same class of error:
        CulturalOrganizerContext::set($this->modA2, $this->orgA->id);
        $this->expectException(CulturalEventDomainException::class);
        $this->writer->createFromPublished($entry->fresh(), $this->modA2);
    }

    public function test_auth_cross_org_and_cross_context_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entryA = $this->makePublished('Org A event');
        $proposal = $this->writer->createFromPublished($entryA, $this->modA);

        CulturalOrganizerContext::set($this->modB, $this->orgB->id);
        $this->actingAs($this->modB)
            ->post(route('cultural-moderator-proposals.store', $entryA))
            ->assertForbidden();

        $this->actingAs($this->modB)
            ->get(route('cultural-moderator-proposals.edit', $proposal))
            ->assertForbidden();

        $this->grantModerator($this->modA, $this->orgB);
        CulturalOrganizerContext::set($this->modA, $this->orgB->id);
        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-proposals.edit', $proposal))
            ->assertForbidden();
        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Hak',
            ])
            ->assertForbidden();
    }

    public function test_inactive_authorization_and_inactive_organizer_rejected(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Auth gate');

        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->modA->id)
            ->where('organizer_id', $this->orgA->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.store', $entry))
            ->assertForbidden();

        $this->grantModerator($this->modA, $this->orgA);
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $this->orgA->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.store', $entry->fresh()))
            ->assertForbidden();
    }

    public function test_moderator_edit_draft_leaves_published_event_untouched(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Original', [
            'opis' => 'Original opis',
            'tag_ids' => [$this->tag->id],
        ]);
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $newTag = CulturalTag::create(['naziv' => 'Blues', 'status' => CulturalTag::STATUS_ACTIVE]);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Predloženo',
                'proposed_opis' => 'Novi opis',
                'proposed_category_id' => $this->category->id,
                'cover_file' => $this->uploadJpeg('pending.jpg'),
                'tag_ids' => [$newTag->id],
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal));

        $proposal->refresh();
        $this->assertSame('Predloženo', $proposal->proposed_naslov);
        $this->assertSame('Novi opis', $proposal->proposed_opis);
        $this->assertNotNull($proposal->proposed_cover_media_id);
        $this->assertSame(
            CulturalMedia::PURPOSE_EVENT_COVER,
            CulturalMedia::query()->findOrFail($proposal->proposed_cover_media_id)->namjena
        );
        $this->assertEqualsCanonicalizing([$newTag->id], $proposal->tags()->pluck('cultural_tags.id')->all());

        $entry->refresh();
        $this->assertSame('Original', $entry->naslov);
        $this->assertSame('Original opis', $entry->opis);
        $this->assertNull($entry->cover_media_id);
        $this->assertEqualsCanonicalizing([$this->tag->id], $entry->tags()->pluck('cultural_tags.id')->all());
        $this->assertSame(1, CulturalOccurrence::query()->where('event_entry_id', $entry->id)->count());
    }

    public function test_submit_pending_lock_and_timestamps(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Za submit');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $this->lifecycle->submit($proposal, $this->modA);
        $proposal->refresh();

        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->status);
        $this->assertNotNull($proposal->first_submitted_at);
        $this->assertNotNull($proposal->last_submitted_at);
        $this->assertSame(
            $proposal->first_submitted_at->toDateTimeString(),
            $proposal->last_submitted_at->toDateTimeString()
        );
        $this->assertNull($proposal->review_started_at);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Ne smije',
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal))
            ->assertSessionHasErrors('domain');

        $this->assertSame('Za submit', $proposal->fresh()->proposed_naslov);
    }

    public function test_withdraw_before_review_allowed_after_start_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Withdraw');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->lifecycle->submit($proposal, $this->modA);

        $this->lifecycle->withdraw($proposal->fresh(), $this->modA);
        $proposal->refresh();
        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->status);
        $this->assertNotNull($proposal->withdrawn_at);
        $this->assertSame($entry->id, $proposal->active_for_event_id);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $firstSubmitted = $proposal->fresh()->first_submitted_at;
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        try {
            $this->lifecycle->withdraw($proposal->fresh(), $this->modA);
            $this->fail('Withdraw after startReview should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('prije početka', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->fresh()->status);
        $this->assertEquals(
            $firstSubmitted?->toDateTimeString(),
            $proposal->fresh()->first_submitted_at?->toDateTimeString()
        );
    }

    public function test_editor_start_review_blocks_moderator_edit_and_allows_editor_edit(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Review edit');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->lifecycle->submit($proposal, $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->assertTrue($proposal->fresh()->isUnderEditorialReview());

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Mod ne smije',
            ])
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-change-proposals.update', $proposal), [
                'proposed_naslov' => 'Urednik izmijenio',
                'proposed_opis' => 'Opis urednik',
                'proposed_category_id' => $this->category->id,
                'tag_ids' => [$this->tag->id],
            ])
            ->assertRedirect(route('cultural-event-change-proposals.show', $proposal));

        $this->assertSame('Urednik izmijenio', $proposal->fresh()->proposed_naslov);
        $this->assertSame('Review edit', $entry->fresh()->naslov);
    }

    public function test_return_requires_reason_and_restores_moderator_edit(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Return flow');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->lifecycle->submit($proposal, $this->modA);
        $firstSubmitted = $proposal->fresh()->first_submitted_at;
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-change-proposals.show', $proposal))
            ->post(route('cultural-event-change-proposals.return', $proposal), [])
            ->assertSessionHasErrors('return_reason');

        $this->lifecycle->returnToDraft($proposal->fresh(), $this->editor, 'Dopunite opis');
        $proposal->refresh();

        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->status);
        $this->assertSame('Dopunite opis', $proposal->return_reason);
        $this->assertNull($proposal->review_started_at);
        $this->assertNull($proposal->review_started_by);
        $this->assertEquals(
            $firstSubmitted?->toDateTimeString(),
            $proposal->first_submitted_at?->toDateTimeString()
        );
        $this->assertSame($entry->id, $proposal->active_for_event_id);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Nakon return',
                'proposed_category_id' => $this->category->id,
                'tag_ids' => [],
            ])
            ->assertRedirect(route('cultural-moderator-proposals.edit', $proposal));

        $this->assertSame('Nakon return', $proposal->fresh()->proposed_naslov);
    }

    public function test_approve_applies_snapshot_and_frees_active_slot(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Prije approve', [
            'opis' => 'Stari opis',
            'tag_ids' => [$this->tag->id],
        ]);
        $occurrenceCount = $entry->occurrences()->count();
        $proposal = $this->writer->createFromPublished($entry, $this->modA);

        $newTag = CulturalTag::create(['naziv' => 'Novi tag', 'status' => CulturalTag::STATUS_ACTIVE]);
        $newCover = $this->makeActiveCoverMedia(['naziv' => 'Cover Approve', 'storage_path' => 'cultural-media/c.jpg']);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Poslije approve',
            'proposed_opis' => 'Novi opis',
            'proposed_category_id' => $this->category->id,
            'proposed_cover_media_id' => $newCover->id,
            'tag_ids' => [$newTag->id],
        ]);

        $this->assertSame('Prije approve', $entry->fresh()->naslov);

        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);
        $this->applicator->approve($proposal->fresh(), $this->editor);

        $proposal->refresh();
        $entry->refresh();

        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame('Poslije approve', $entry->naslov);
        $this->assertSame('Novi opis', $entry->opis);
        $this->assertSame($newCover->id, $entry->cover_media_id);
        $this->assertEqualsCanonicalizing([$newTag->id], $entry->tags()->pluck('cultural_tags.id')->all());
        $this->assertSame($occurrenceCount, $entry->occurrences()->count());

        $newProposal = $this->writer->createFromPublished($entry->fresh(), $this->modA);
        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $newProposal->status);
        $this->assertSame($entry->id, $newProposal->active_for_event_id);
    }

    public function test_approve_rejects_inactive_catalogs(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Catalog gate', [
            'cover_media_id' => $this->cover->id,
            'tag_ids' => [$this->tag->id],
        ]);
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->lifecycle->submit($proposal, $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->category->update(['status' => CulturalCategory::STATUS_INACTIVE]);
        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Inactive category should block approve');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('kategorij', mb_strtolower($e->getMessage()));
        }
        $this->assertSame('Catalog gate', $entry->fresh()->naslov);
        $this->category->update(['status' => CulturalCategory::STATUS_ACTIVE]);

        $this->tag->update(['status' => CulturalTag::STATUS_INACTIVE]);
        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Inactive tag should block approve');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('oznak', mb_strtolower($e->getMessage()));
        }
        $this->tag->update(['status' => CulturalTag::STATUS_ACTIVE]);

        $this->cover->update(['status' => CulturalMedia::STATUS_INACTIVE]);
        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Inactive media should block approve');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('medij', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $proposal->fresh()->status);
        $this->assertSame('Catalog gate', $entry->fresh()->naslov);
    }

    public function test_g_w02_cancel_makes_proposal_inoperable(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Za cancel');
        $originalNaslov = $entry->naslov;
        $originalOpis = $entry->opis;
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Ne smije se primijeniti',
            'proposed_opis' => 'Opis iz prijedloga',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [],
        ]);
        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkazano zbog vremena');
        $proposal->refresh();
        $entry->refresh();

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame('Otkazano zbog vremena', $entry->cancellation_reason);
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->status);
        $this->assertSame(CulturalEventChangeProposal::INOPERABLE_REASON_EVENT_CANCELLED, $proposal->inoperable_reason);
        $this->assertNotNull($proposal->inoperable_at);
        $this->assertNull($proposal->active_for_event_id);

        try {
            $this->applicator->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve after cancel should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertTrue(true);
        }

        $entry->refresh();
        $this->assertSame($originalNaslov, $entry->naslov);
        $this->assertSame($originalOpis, $entry->opis);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);

        try {
            $this->lifecycle->returnToDraft($proposal->fresh(), $this->editor, 'Ne');
            $this->fail('Return after cancel should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertTrue(true);
        }

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => 'Ne',
            ])
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.submit', $proposal))
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-proposals.withdraw', $proposal))
            ->assertSessionHasErrors('domain');
    }

    public function test_approve_first_then_cancel_event_normally(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Approve first', [
            'opis' => 'Stari opis',
        ]);
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->writer->updateDraftContent($proposal, $this->modA, [
            'proposed_naslov' => 'Nakon approve',
            'proposed_opis' => 'Novi opis',
            'proposed_category_id' => $this->category->id,
            'tag_ids' => [$this->tag->id],
        ]);
        $this->lifecycle->submit($proposal->fresh(), $this->modA);
        $this->lifecycle->startReview($proposal->fresh(), $this->editor);
        $this->applicator->approve($proposal->fresh(), $this->editor);

        $proposal->refresh();
        $entry->refresh();
        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame('Nakon approve', $entry->naslov);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkaz poslije approve');
        $entry->refresh();
        $proposal->refresh();

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame('Otkaz poslije approve', $entry->cancellation_reason);
        $this->assertSame('Nakon approve', $entry->naslov);
        $this->assertSame('Novi opis', $entry->opis);
        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertNull($proposal->inoperable_at);
    }

    public function test_create_rejected_after_event_cancelled_and_no_active_slot_remains(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makePublished('Cancel then create');
        $proposal = $this->writer->createFromPublished($entry, $this->modA);
        $this->assertSame($entry->id, $proposal->active_for_event_id);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkazano');
        $entry->refresh();
        $proposal->refresh();

        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame(
            0,
            CulturalEventChangeProposal::query()->where('active_for_event_id', $entry->id)->count()
        );

        try {
            $this->writer->createFromPublished($entry->fresh(), $this->modA);
            $this->fail('Create after cancel should fail');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('objavljeni', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(
            0,
            CulturalEventChangeProposal::query()->where('active_for_event_id', $entry->id)->count()
        );
        $this->assertSame(
            0,
            CulturalEventChangeProposal::query()
                ->where('event_entry_id', $entry->id)
                ->whereIn('status', CulturalEventChangeProposal::ACTIVE_STATUSES)
                ->count()
        );
    }

    public function test_approved_and_inoperable_free_active_slot_for_new_proposal_only_when_published(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $entryApprove = $this->makePublished('Slot after approve');
        $first = $this->writer->createFromPublished($entryApprove, $this->modA);
        $this->lifecycle->submit($first, $this->modA);
        $this->lifecycle->startReview($first->fresh(), $this->editor);
        $this->applicator->approve($first->fresh(), $this->editor);
        $this->assertNull($first->fresh()->active_for_event_id);

        $second = $this->writer->createFromPublished($entryApprove->fresh(), $this->modA);
        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $second->status);
        $this->assertSame($entryApprove->id, $second->active_for_event_id);

        $entryInop = $this->makePublished('Slot after inop');
        $inop = $this->writer->createFromPublished($entryInop, $this->modA);
        $this->eventLifecycle->cancel($entryInop->fresh(), $this->editor, 'Stop');
        $this->assertNull($inop->fresh()->active_for_event_id);
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $inop->fresh()->status);

        try {
            $this->writer->createFromPublished($entryInop->fresh(), $this->modA);
            $this->fail('Create on cancelled event should fail even with free slot');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('objavljeni', mb_strtolower($e->getMessage()));
        }
    }

    public function test_du02_counts_pending_review_on_published_only(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $published = $this->makePublished('DU02 published');
        $draftProposal = $this->writer->createFromPublished($published, $this->modA);

        $pendingEntry = $this->makePublished('DU02 pending');
        $pendingProposal = $this->writer->createFromPublished($pendingEntry, $this->modA);
        $this->lifecycle->submit($pendingProposal, $this->modA);

        $approvedEntry = $this->makePublished('DU02 approved');
        $approvedProposal = $this->writer->createFromPublished($approvedEntry, $this->modA);
        $this->lifecycle->submit($approvedProposal, $this->modA);
        $this->lifecycle->startReview($approvedProposal->fresh(), $this->editor);
        $this->applicator->approve($approvedProposal->fresh(), $this->editor);

        $inopEntry = $this->makePublished('DU02 inop');
        $inopProposal = $this->writer->createFromPublished($inopEntry, $this->modA);
        $this->lifecycle->submit($inopProposal, $this->modA);
        $this->eventLifecycle->cancel($inopEntry->fresh(), $this->editor, 'Stop');

        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $draftProposal->fresh()->status);
        $this->assertSame(CulturalEventChangeProposal::STATUS_PENDING_REVIEW, $pendingProposal->fresh()->status);
        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $approvedProposal->fresh()->status);
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $inopProposal->fresh()->status);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                foreach ($cards as $card) {
                    if (($card['id'] ?? null) === 'DU-02') {
                        return (int) $card['count'] === 1
                            && $card['url'] === route('cultural-event-change-proposals.index', [
                                'proposal_status' => CulturalEventChangeProposal::STATUS_PENDING_REVIEW,
                            ]);
                    }
                }

                return false;
            });

        $this->actingAs($this->editor)
            ->get(route('cultural-event-change-proposals.index', [
                'proposal_status' => CulturalEventChangeProposal::STATUS_PENDING_REVIEW,
            ]))
            ->assertOk()
            ->assertSee($pendingEntry->naslov, false)
            ->assertDontSee($approvedEntry->naslov, false)
            ->assertDontSee($inopEntry->naslov, false);
    }

    /**
     * @param  array{opis?: string, cover_media_id?: int, tag_ids?: list<int>}  $extra
     */
    private function makePublished(string $naslov, array $extra = []): CulturalEventEntry
    {
        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'opis' => $extra['opis'] ?? null,
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgA->id,
            'cover_media_id' => $extra['cover_media_id'] ?? null,
            'tag_ids' => $extra['tag_ids'] ?? [],
        ]);

        app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh(['tags', 'occurrences']);
    }

    private function makeEntryWithStatus(string $status, string $naslov): CulturalEventEntry
    {
        $entry = CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => $status,
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgA->id,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
            'cancellation_reason' => $status === CulturalEventEntry::STATUS_CANCELLED ? 'X' : null,
        ]);

        CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        return $entry;
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeActiveCoverMedia(array $overrides = []): CulturalMedia
    {
        $name = $overrides['naziv'] ?? 'Naslovna';
        $path = $overrides['storage_path'] ?? ('cultural-media/'.uniqid('m_', true).'.jpg');

        return CulturalMedia::create(array_merge([
            'naziv' => $name,
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => basename($path),
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => $path,
            'creator_id' => $this->editor->id,
        ], $overrides));
    }
}
