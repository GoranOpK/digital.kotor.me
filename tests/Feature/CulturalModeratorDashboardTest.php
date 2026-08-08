<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-010.6 — Moderator Dashboard / Radna tabla (DM-01–DM-03).
 */
class CulturalModeratorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private User $regularUser;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

    private EventLifecycle $eventLifecycle;

    private EventChangeProposalWriter $proposalWriter;

    private EventChangeProposalLifecycle $proposalLifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org A DM');
        $this->orgB = $this->makeOrganizer('Org B DM');
        $this->grantModerator($this->moderator, $this->orgA);

        $this->category = CulturalCategory::create([
            'naziv' => 'DM Kategorija',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventLifecycle = app(EventLifecycle::class);
        $this->proposalWriter = app(EventChangeProposalWriter::class);
        $this->proposalLifecycle = app(EventChangeProposalLifecycle::class);
    }

    public function test_moderator_with_active_grant_can_view_dashboard(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Radna tabla', false)
            ->assertSee('DM-01', false)
            ->assertSee('DM-02', false)
            ->assertSee('DM-03', false)
            ->assertSee('Nacrti', false)
            ->assertSee('Na odobrenju', false)
            ->assertSee('Aktivni prijedlozi izmjena', false);
    }

    public function test_regular_user_cannot_view_dashboard(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertForbidden();
    }

    public function test_editor_without_moderator_grant_cannot_view_dashboard(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertForbidden();
    }

    public function test_moderator_without_active_grant_cannot_view_dashboard(): void
    {
        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->moderator->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertForbidden();
    }

    public function test_moderator_with_multiple_orgs_and_no_context_sees_select_context(): void
    {
        $this->grantModerator($this->moderator, $this->orgB);
        CulturalOrganizerContext::clear();

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('Izaberite Organizator', false)
            ->assertDontSee('DM-01', false);
    }

    public function test_dm01_counts_drafts_of_active_organizer_only(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->makeEntry('Draft A', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $this->makeEntry('Draft B other', CulturalEventEntry::STATUS_DRAFT, $this->orgB->id);
        $this->makeEntry('Pending A', CulturalEventEntry::STATUS_PENDING_APPROVAL, $this->orgA->id);
        $this->makeEntry('Published A', CulturalEventEntry::STATUS_PUBLISHED, $this->orgA->id);
        $this->makeEntry('Draft no org', CulturalEventEntry::STATUS_DRAFT, null);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DM-01') === 1
                    && $this->cardCount($cards, 'DM-02') === 1
                    && $this->cardCount($cards, 'DM-03') === 0;
            });
    }

    public function test_dm01_includes_event_returned_to_draft(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $entry = $this->makePublishableDraft('Za povrat', $this->orgA);
        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->fresh()->status);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', fn (array $cards): bool => $this->cardCount($cards, 'DM-01') === 0
                && $this->cardCount($cards, 'DM-02') === 1);

        $this->eventLifecycle->returnToDraft($entry->fresh(), $this->editor, 'Dorada naslova');

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', fn (array $cards): bool => $this->cardCount($cards, 'DM-01') === 1
                && $this->cardCount($cards, 'DM-02') === 0);
    }

    public function test_dm02_counts_pending_of_active_organizer_only(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->makeEntry('Pending A', CulturalEventEntry::STATUS_PENDING_APPROVAL, $this->orgA->id);
        $this->makeEntry('Pending B', CulturalEventEntry::STATUS_PENDING_APPROVAL, $this->orgB->id);
        $this->makeEntry('Draft A', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $this->makeEntry('Published A', CulturalEventEntry::STATUS_PUBLISHED, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DM-02') === 1;
            });
    }

    public function test_dm03_counts_published_with_active_proposals_only(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $draftProposalEntry = $this->makePublished('Pub Draft Prop', $this->orgA);
        $this->proposalWriter->createFromPublished($draftProposalEntry, $this->moderator);

        $pendingEntry = $this->makePublished('Pub Pending Prop', $this->orgA);
        $pendingProposal = $this->proposalWriter->createFromPublished($pendingEntry, $this->moderator);
        $this->proposalLifecycle->submit($pendingProposal->fresh(), $this->moderator);

        $returnedEntry = $this->makePublished('Pub Returned Prop', $this->orgA);
        $returnedProposal = $this->proposalWriter->createFromPublished($returnedEntry, $this->moderator);
        $this->proposalLifecycle->submit($returnedProposal->fresh(), $this->moderator);
        $this->proposalLifecycle->returnToDraft($returnedProposal->fresh(), $this->editor, 'Dorada');

        $approvedEntry = $this->makePublished('Pub Approved Prop', $this->orgA);
        $approvedProposal = $this->proposalWriter->createFromPublished($approvedEntry, $this->moderator);
        $this->proposalLifecycle->submit($approvedProposal->fresh(), $this->moderator);
        $this->proposalLifecycle->startReview($approvedProposal->fresh(), $this->editor);
        app(\App\Services\CulturalEventDomain\EventChangeProposalApplicator::class)
            ->approve($approvedProposal->fresh(), $this->editor);

        $plainPublished = $this->makePublished('Pub No Prop', $this->orgA);

        $this->grantModerator($this->moderator, $this->orgB);
        $otherOrgEntry = $this->makePublished('Pub Other Org', $this->orgB);
        CulturalOrganizerContext::set($this->moderator, $this->orgB->id);
        $this->proposalWriter->createFromPublished($otherOrgEntry, $this->moderator);
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards) use ($plainPublished): bool {
                return $this->cardCount($cards, 'DM-03') === 3;
            });

        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $approvedProposal->fresh()->status);
        $this->assertNull($approvedProposal->fresh()->active_for_event_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $plainPublished->fresh()->status);
    }

    public function test_dm03_excludes_inoperable_after_g_w02_cancel(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $entry = $this->makePublished('Pub Cancel Prop', $this->orgA);
        $proposal = $this->proposalWriter->createFromPublished($entry, $this->moderator);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', fn (array $cards): bool => $this->cardCount($cards, 'DM-03') === 1);

        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Otkazano zbog vremena');

        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->active_for_event_id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', fn (array $cards): bool => $this->cardCount($cards, 'DM-03') === 0);
    }

    public function test_dm03_includes_withdrawn_proposal_still_in_draft_slot(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $entry = $this->makePublished('Pub Withdraw Prop', $this->orgA);
        $proposal = $this->proposalWriter->createFromPublished($entry, $this->moderator);
        $this->proposalLifecycle->submit($proposal->fresh(), $this->moderator);
        $this->proposalLifecycle->withdraw($proposal->fresh(), $this->moderator);

        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->fresh()->status);
        $this->assertSame($entry->id, $proposal->fresh()->active_for_event_id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', fn (array $cards): bool => $this->cardCount($cards, 'DM-03') === 1);
    }

    public function test_context_switch_changes_dm_counts(): void
    {
        $this->grantModerator($this->moderator, $this->orgB);

        $this->makeEntry('Draft A only', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $this->makeEntry('Draft A2', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $this->makeEntry('Pending B only', CulturalEventEntry::STATUS_PENDING_APPROVAL, $this->orgB->id);

        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DM-01') === 2
                    && $this->cardCount($cards, 'DM-02') === 0;
            });

        CulturalOrganizerContext::set($this->moderator, $this->orgB->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DM-01') === 0
                    && $this->cardCount($cards, 'DM-02') === 1;
            });
    }

    public function test_dashboard_card_links_use_filtered_moderator_index(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                $byId = collect($cards)->keyBy('id');

                return $byId['DM-01']['url'] === route('cultural-moderator-events.index', [
                    'status' => CulturalEventEntry::STATUS_DRAFT,
                ])
                    && $byId['DM-02']['url'] === route('cultural-moderator-events.index', [
                        'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
                    ])
                    && $byId['DM-03']['url'] === route('cultural-moderator-events.index', [
                        'has_active_proposal' => '1',
                    ]);
            });
    }

    public function test_zero_counts_still_render_cards(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertSee('DM-01', false)
            ->assertSee('DM-02', false)
            ->assertSee('DM-03', false)
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DM-01') === 0
                    && $this->cardCount($cards, 'DM-02') === 0
                    && $this->cardCount($cards, 'DM-03') === 0;
            });
    }

    public function test_event_index_status_filter_and_scope_isolation(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $draftA = $this->makeEntry('Draft Filter A', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $pendingA = $this->makeEntry('Pending Filter A', CulturalEventEntry::STATUS_PENDING_APPROVAL, $this->orgA->id);
        $draftB = $this->makeEntry('Draft Filter B', CulturalEventEntry::STATUS_DRAFT, $this->orgB->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.index', [
                'status' => CulturalEventEntry::STATUS_DRAFT,
            ]))
            ->assertOk()
            ->assertSee($draftA->naslov, false)
            ->assertDontSee($pendingA->naslov, false)
            ->assertDontSee($draftB->naslov, false);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.index', [
                'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
            ]))
            ->assertOk()
            ->assertSee($pendingA->naslov, false)
            ->assertDontSee($draftA->naslov, false)
            ->assertDontSee($draftB->naslov, false);
    }

    public function test_event_index_active_proposal_filter_and_published_link(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $withProp = $this->makePublished('Pub With Active Prop', $this->orgA);
        $this->proposalWriter->createFromPublished($withProp, $this->moderator);

        $withoutProp = $this->makePublished('Pub Without Prop Unique', $this->orgA);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.index', [
                'has_active_proposal' => '1',
            ]))
            ->assertOk()
            ->assertSee($withProp->naslov, false)
            ->assertDontSee($withoutProp->naslov, false)
            ->assertSee(route('cultural-moderator-events.edit', $withProp), false);
    }

    public function test_manual_query_cannot_show_other_organizer_events(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->orgA->id);

        $draftA = $this->makeEntry('Scope Draft A', CulturalEventEntry::STATUS_DRAFT, $this->orgA->id);
        $draftB = $this->makeEntry('Scope Draft B Secret', CulturalEventEntry::STATUS_DRAFT, $this->orgB->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.index', [
                'status' => CulturalEventEntry::STATUS_DRAFT,
                'organizer_id' => $this->orgB->id,
            ]))
            ->assertOk()
            ->assertSee($draftA->naslov, false)
            ->assertDontSee($draftB->naslov, false);
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     */
    private function cardCount(array $cards, string $id): int
    {
        foreach ($cards as $card) {
            if (($card['id'] ?? null) === $id) {
                return (int) $card['count'];
            }
        }

        $this->fail("Card {$id} missing from dashboard.");
    }

    private function makeEntry(string $naslov, string $status, ?int $organizerId): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => $status,
            'organizer_id' => $organizerId,
            'category_id' => $this->category->id,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);
    }

    private function makePublishableDraft(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
    {
        $entry = app(EventWriter::class)->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
            'tag_ids' => [],
        ]);

        app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makePublished(string $naslov, CulturalOrganizer $organizer): CulturalEventEntry
    {
        $entry = $this->makePublishableDraft($naslov, $organizer);
        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return $entry->fresh();
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
