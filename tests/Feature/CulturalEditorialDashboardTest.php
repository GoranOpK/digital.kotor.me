<?php

namespace Tests\Feature;

use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-010.2 — Urednik Dashboard / Inbox (DU-01, DU-03, DU-04, DU-05).
 */
class CulturalEditorialDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private User $moderator;

    private CulturalOrganizer $organizer;

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

        $this->regularUser = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->organizer = $this->makeOrganizer('Org Dashboard');
        $this->grantModerator($this->moderator, $this->organizer);
    }

    public function test_kk_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertSee('Kontrolna tabla', false)
            ->assertSee('Čeka pregled', false)
            ->assertSee('Prijedlozi izmjena na pregledu', false)
            ->assertSee('Događaji u pripremi', false)
            ->assertSee('Zahtjevi za Organizatora', false)
            ->assertSee('Zahtjevi za Moderatore', false)
            ->assertSee('DU-02', false);
    }

    public function test_moderator_cannot_view_dashboard(): void
    {
        $this->actingAs($this->moderator)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_view_dashboard(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertForbidden();
    }

    public function test_du01_counts_pending_approval_only(): void
    {
        $this->makeEntry('Pending A', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->makeEntry('Pending B', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->makeEntry('Draft X', CulturalEventEntry::STATUS_DRAFT);
        $this->makeEntry('Published Y', CulturalEventEntry::STATUS_PUBLISHED);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DU-01') === 2;
            });
    }

    public function test_du03_counts_drafts_without_organizer_only(): void
    {
        $this->makeEntry('Urednik nacrt', CulturalEventEntry::STATUS_DRAFT, null);
        $this->makeEntry('Drugi urednik nacrt', CulturalEventEntry::STATUS_DRAFT, null);
        $this->makeEntry('Moderator nacrt', CulturalEventEntry::STATUS_DRAFT, $this->organizer->id);
        $this->makeEntry('Pending bez org', CulturalEventEntry::STATUS_PENDING_APPROVAL, null);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DU-03') === 2;
            });
    }

    public function test_du04_counts_submitted_organizer_requests_only(): void
    {
        $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_SUBMITTED);
        $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_SUBMITTED);
        $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_APPROVED);
        $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_REJECTED);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DU-04') === 2;
            });
    }

    public function test_du05_counts_submitted_add_and_remove_moderator_requests(): void
    {
        $target = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->makeModeratorRequest(CulturalModeratorRequest::TYPE_ADD, CulturalModeratorRequest::STATUS_SUBMITTED, $target);
        $this->makeModeratorRequest(CulturalModeratorRequest::TYPE_REMOVE, CulturalModeratorRequest::STATUS_SUBMITTED, $this->moderator);
        $this->makeModeratorRequest(CulturalModeratorRequest::TYPE_ADD, CulturalModeratorRequest::STATUS_APPROVED, $target);
        $this->makeModeratorRequest(CulturalModeratorRequest::TYPE_REMOVE, CulturalModeratorRequest::STATUS_REJECTED, $this->moderator);

        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                return $this->cardCount($cards, 'DU-05') === 2;
            });
    }

    public function test_dashboard_card_links_use_filtered_canonical_indexes(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-editorial-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                $byId = collect($cards)->keyBy('id');

                return $byId['DU-01']['url'] === route('cultural-event-entries.index', [
                    'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
                ])
                    && $byId['DU-02']['url'] === route('cultural-event-change-proposals.index', [
                        'proposal_status' => CulturalEventChangeProposal::STATUS_PENDING_REVIEW,
                    ])
                    && $byId['DU-03']['url'] === route('cultural-event-entries.index', [
                        'status' => CulturalEventEntry::STATUS_DRAFT,
                        'organizer' => 'none',
                    ])
                    && $byId['DU-04']['url'] === route('cultural-organizer-creation-requests.index', [
                        'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
                    ])
                    && $byId['DU-05']['url'] === route('cultural-moderator-requests.index', [
                        'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
                    ]);
            });
    }

    public function test_event_index_accepts_status_and_organizer_filters(): void
    {
        $pending = $this->makeEntry('Pending filter', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $draftNoOrg = $this->makeEntry('Draft bez org', CulturalEventEntry::STATUS_DRAFT, null);
        $draftWithOrg = $this->makeEntry('Draft sa org', CulturalEventEntry::STATUS_DRAFT, $this->organizer->id);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index', [
                'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
            ]))
            ->assertOk()
            ->assertSee($pending->naslov, false)
            ->assertDontSee($draftNoOrg->naslov, false)
            ->assertDontSee($draftWithOrg->naslov, false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index', [
                'status' => CulturalEventEntry::STATUS_DRAFT,
                'organizer' => 'none',
            ]))
            ->assertOk()
            ->assertSee($draftNoOrg->naslov, false)
            ->assertDontSee($draftWithOrg->naslov, false)
            ->assertDontSee($pending->naslov, false);
    }

    public function test_organizer_and_moderator_request_indexes_accept_submitted_filter(): void
    {
        $submittedOrg = $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_SUBMITTED, 'Submitted Org');
        $approvedOrg = $this->makeOrgCreationRequest(CulturalOrganizerCreationRequest::STATUS_APPROVED, 'Approved Org');

        $submittedTarget = User::factory()->create([
            'name' => 'Submitted Target Unique',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $approvedTarget = User::factory()->create([
            'name' => 'Approved Target Unique',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->makeModeratorRequest(
            CulturalModeratorRequest::TYPE_ADD,
            CulturalModeratorRequest::STATUS_SUBMITTED,
            $submittedTarget
        );
        $this->makeModeratorRequest(
            CulturalModeratorRequest::TYPE_ADD,
            CulturalModeratorRequest::STATUS_APPROVED,
            $approvedTarget
        );

        $this->actingAs($this->editor)
            ->get(route('cultural-organizer-creation-requests.index', [
                'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
            ]))
            ->assertOk()
            ->assertSee($submittedOrg->proposed_naziv, false)
            ->assertDontSee($approvedOrg->proposed_naziv, false);

        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-requests.index', [
                'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            ]))
            ->assertOk()
            ->assertSee('Submitted Target Unique', false)
            ->assertDontSee('Approved Target Unique', false);
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

    private function makeEntry(string $naslov, string $status, ?int $organizerId = null): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => $status,
            'organizer_id' => $organizerId,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);
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

    private function makeOrgCreationRequest(string $status, ?string $naziv = null): CulturalOrganizerCreationRequest
    {
        return CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->regularUser->id,
            'proposed_moderator_user_id' => $this->regularUser->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $naziv ?? ('Org Req '.uniqid()),
            'status' => $status,
            'decision_user_id' => $status === CulturalOrganizerCreationRequest::STATUS_SUBMITTED ? null : $this->editor->id,
            'decision_at' => $status === CulturalOrganizerCreationRequest::STATUS_SUBMITTED ? null : now(),
        ]);
    }

    private function makeModeratorRequest(string $type, string $status, User $target): CulturalModeratorRequest
    {
        return CulturalModeratorRequest::create([
            'organizer_id' => $this->organizer->id,
            'submitter_user_id' => $this->moderator->id,
            'target_user_id' => $target->id,
            'type' => $type,
            'status' => $status,
            'decision_user_id' => $status === CulturalModeratorRequest::STATUS_SUBMITTED ? null : $this->editor->id,
            'decision_at' => $status === CulturalModeratorRequest::STATUS_SUBMITTED ? null : now(),
        ]);
    }
}
