<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-010.4 — Moderator Otkaži Objavljeni Događaj (HTTP → EventLifecycle::cancel).
 */
class CulturalModeratorEventCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

    private EventLifecycle $eventLifecycle;

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
        $this->modA = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);
        $this->modB = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->orgA = $this->makeOrganizer('Org A Cancel');
        $this->orgB = $this->makeOrganizer('Org B Cancel');
        $this->grantModerator($this->modA, $this->orgA);
        $this->grantModerator($this->modB, $this->orgB);

        $this->category = CulturalCategory::create([
            'naziv' => 'Cancel Kategorija',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->eventLifecycle = app(EventLifecycle::class);
    }

    public function test_moderator_cancels_published_event_of_active_organizer(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence('Za otkaz Mod');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Loše vrijeme',
            ])
            ->assertRedirect(route('cultural-moderator-events.index'));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->status);
        $this->assertSame('Loše vrijeme', $entry->cancellation_reason);
        $this->assertSame($this->modA->id, $entry->last_modified_by);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_cancel_requires_reason_and_rejects_whitespace(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Bez razloga');

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $entry))
            ->post(route('cultural-moderator-events.cancel', $entry), [])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $entry))
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => '   ',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_cancel_clears_featured(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Istaknut za otkaz');
        $entry->update(['featured' => true]);
        $this->assertTrue((bool) $entry->fresh()->featured);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Otkaz istaknutog',
            ])
            ->assertRedirect(route('cultural-moderator-events.index'));

        $this->assertFalse((bool) $entry->fresh()->featured);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_other_organizer_moderator_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Org A event');

        CulturalOrganizerContext::set($this->modB, $this->orgB->id);

        $this->actingAs($this->modB)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Nedozvoljeno',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_wrong_active_context_forbidden(): void
    {
        $this->grantModerator($this->modA, $this->orgB);
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Context A event');

        CulturalOrganizerContext::set($this->modA, $this->orgB->id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Pogrešan kontekst',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_removed_moderator_grant_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Bez grant-a');

        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->modA->id)
            ->where('organizer_id', $this->orgA->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Bez ovlašćenja',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_deactivated_organizer_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Deaktiviran Org');

        $this->orgA->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Org deaktiviran',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_regular_user_direct_url_forbidden(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Direct URL');

        $regular = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->actingAs($regular)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Običan korisnik',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_draft_and_pending_cannot_be_cancelled_via_moderator_route(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $draft = CulturalEventEntry::create([
            'naslov' => 'Nacrt cancel',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_id' => $this->orgA->id,
            'category_id' => $this->category->id,
            'created_by' => $this->modA->id,
            'last_modified_by' => $this->modA->id,
        ]);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $draft))
            ->post(route('cultural-moderator-events.cancel', $draft), [
                'cancellation_reason' => 'Pokušaj nacrta',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $draft->fresh()->status);

        $pending = CulturalEventEntry::create([
            'naslov' => 'Pending cancel',
            'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
            'organizer_id' => $this->orgA->id,
            'category_id' => $this->category->id,
            'created_by' => $this->modA->id,
            'last_modified_by' => $this->modA->id,
        ]);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $pending))
            ->post(route('cultural-moderator-events.cancel', $pending), [
                'cancellation_reason' => 'Pokušaj pending',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $pending->fresh()->status);
    }

    public function test_already_cancelled_and_archived_cannot_be_cancelled_again(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('Već otkazan');
        $this->eventLifecycle->cancel($entry->fresh(), $this->editor, 'Prvi otkaz');

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.index'))
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Drugi otkaz',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame('Prvi otkaz', $entry->fresh()->cancellation_reason);

        $archived = CulturalEventEntry::create([
            'naslov' => 'Arhiviran cancel',
            'status' => CulturalEventEntry::STATUS_ARCHIVED,
            'organizer_id' => $this->orgA->id,
            'category_id' => $this->category->id,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.index'))
            ->post(route('cultural-moderator-events.cancel', $archived), [
                'cancellation_reason' => 'Arhiva otkaz',
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $archived->fresh()->status);
    }

    public function test_g_w02_via_moderator_http_makes_proposal_inoperable(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('G-W02 Mod HTTP');
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->modA);

        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->status);
        $this->assertSame($entry->id, $proposal->active_for_event_id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Otkaz sa prijedlogom',
            ])
            ->assertRedirect(route('cultural-moderator-events.index'));

        $proposal->refresh();
        $this->assertSame(CulturalEventChangeProposal::STATUS_INOPERABLE, $proposal->status);
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame(
            CulturalEventChangeProposal::INOPERABLE_REASON_EVENT_CANCELLED,
            $proposal->inoperable_reason
        );
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_dm03_no_longer_counts_after_moderator_cancel(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('DM-03 cancel');
        app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->modA);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                foreach ($cards as $card) {
                    if (($card['id'] ?? null) === 'DM-03') {
                        return (int) $card['count'] === 1;
                    }
                }

                return false;
            });

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'DM-03 otkaz',
            ])
            ->assertRedirect(route('cultural-moderator-events.index'));

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-dashboard.index'))
            ->assertOk()
            ->assertViewHas('cards', function (array $cards): bool {
                foreach ($cards as $card) {
                    if (($card['id'] ?? null) === 'DM-03') {
                        return (int) $card['count'] === 0;
                    }
                }

                return false;
            });
    }

    public function test_occurrence_status_actions_blocked_after_event_cancel(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry, $occurrence] = $this->makePublishedWithOccurrence('Occ block after cancel');

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.cancel', $entry), [
                'cancellation_reason' => 'Blokira održavanja',
            ])
            ->assertRedirect(route('cultural-moderator-events.index'));

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence]))
            ->assertSessionHasErrors('occurrence');

        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
    }

    public function test_published_show_includes_cancel_form(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        [$entry] = $this->makePublishedWithOccurrence('UI cancel form');

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('Otkaži Događaj', false)
            ->assertSee(route('cultural-moderator-events.cancel', $entry), false);
    }

    /**
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makePublishedWithOccurrence(string $naslov): array
    {
        $writer = app(EventWriter::class);
        $entry = $writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgA->id,
        ]);

        $occurrence = app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        $this->eventLifecycle->submitForApproval($entry->fresh(), $this->editor);
        $this->eventLifecycle->approve($entry->fresh(), $this->editor);

        return [$entry->fresh(['occurrences']), $occurrence->fresh()];
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
