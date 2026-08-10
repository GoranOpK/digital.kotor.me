<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
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
 * BR-052 / PO-DG-08 / PO-DG-09 — naknadno povezivanje Objavljenog Događaja sa Organizatorom.
 */
class CulturalEventEntryLinkOrganizerTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $editorB;

    private User $regularUser;

    private User $moderator;

    private User $moderatorOther;

    private CulturalCategory $category;

    private CulturalOrganizer $organizer;

    private CulturalOrganizer $organizerB;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $korisnikId = Role::where('name', 'korisnik')->firstOrFail()->id;
        $adminId = Role::where('name', 'kk_admin')->firstOrFail()->id;

        $this->editor = User::factory()->create([
            'role_id' => $adminId,
            'activation_status' => 'active',
        ]);

        $this->editorB = User::factory()->create([
            'role_id' => $adminId,
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

        $this->moderatorOther = User::factory()->create([
            'role_id' => $korisnikId,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti LinkOrg',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->organizer = $this->makeOrganizer('Org A Link');
        $this->organizerB = $this->makeOrganizer('Org B Link');
        $this->grantModerator($this->moderator, $this->organizer);
        $this->grantModerator($this->moderatorOther, $this->organizerB);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
    }

    public function test_editor_links_published_without_organizer_to_active_organizer(): void
    {
        [$entry, $occurrence] = $this->makePublishedWithoutOrganizer('Link OK');
        $fixedSubmitted = $entry->first_submitted_at;
        $this->assertNotNull($fixedSubmitted);

        $linked = $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id);

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $linked->status);
        $this->assertSame($this->organizer->id, $linked->organizer_id);
        $this->assertSame($this->editor->id, $linked->last_modified_by);
        $this->assertSame('Link OK', $linked->naslov);
        $this->assertSame($this->category->id, $linked->category_id);
        $this->assertFalse((bool) $linked->featured);
        $this->assertTrue($linked->first_submitted_at->equalTo($fixedSubmitted));
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->fresh()->status);
        $this->assertSame($occurrence->id, $linked->occurrences->first()->id);
    }

    public function test_rejects_draft_pending_cancelled_archived_and_already_linked(): void
    {
        $draft = $this->writer->createDraft($this->editor, [
            'naslov' => 'Nacrt',
            'category_id' => $this->category->id,
        ]);
        $this->assertDomainRejects(fn () => $this->writer->linkOrganizer($draft, $this->editor, $this->organizer->id));

        [$ready] = $this->makeReadyDraftWithoutOrganizer('Pending');
        $pending = $this->lifecycle->submitForApproval($ready, $this->editor);
        $this->assertDomainRejects(fn () => $this->writer->linkOrganizer($pending, $this->editor, $this->organizer->id));

        [$published] = $this->makePublishedWithoutOrganizer('Za otkaz');
        $cancelled = $this->lifecycle->cancel($published, $this->editor, 'Otkaz za BR-052');
        $this->assertDomainRejects(fn () => $this->writer->linkOrganizer($cancelled, $this->editor, $this->organizer->id));

        [$toArchive] = $this->makePublishedWithoutOrganizer('Za arhivu');
        CulturalEventEntry::query()->whereKey($toArchive->id)->update([
            'status' => CulturalEventEntry::STATUS_ARCHIVED,
        ]);
        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($toArchive->fresh(), $this->editor, $this->organizer->id)
        );

        [$withOrg] = $this->makePublishedWithOrganizer('Već povezan', $this->organizer);
        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($withOrg, $this->editor, $this->organizerB->id),
            'već povezan'
        );
    }

    public function test_unidirectional_repeat_and_reassign_rejected(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Jednosmjerno');
        $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id);

        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($entry->fresh(), $this->editor, $this->organizer->id),
            'već povezan'
        );
        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($entry->fresh(), $this->editor, $this->organizerB->id),
            'već povezan'
        );

        $this->assertSame($this->organizer->id, $entry->fresh()->organizer_id);
    }

    public function test_inactive_and_missing_organizer_rejected(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Org status');

        $this->organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);
        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id),
            'Deaktivirani'
        );

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->linkOrganizer($entry->fresh(), $this->editor, 999999);
    }

    public function test_stale_model_rejects_when_already_linked_in_db(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Stale Org');
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);
        $this->assertNull($stale->organizer_id);

        CulturalEventEntry::query()->whereKey($entry->id)->update([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($stale, $this->editor, $this->organizerB->id),
            'već povezan'
        );

        $this->assertSame($this->organizer->id, $entry->fresh()->organizer_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_stale_model_rejects_when_status_changed_in_db(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Stale status');
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);

        CulturalEventEntry::query()->whereKey($entry->id)->update([
            'status' => CulturalEventEntry::STATUS_CANCELLED,
            'cancellation_reason' => 'U međuvremenu',
        ]);

        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($stale, $this->editor, $this->organizer->id)
        );
        $this->assertNull($entry->fresh()->organizer_id);
    }

    public function test_stale_model_rejects_when_organizer_deactivated(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Stale deakt');
        $stale = CulturalEventEntry::query()->findOrFail($entry->id);

        $this->organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($stale, $this->editor, $this->organizer->id),
            'Deaktivirani'
        );
        $this->assertNull($entry->fresh()->organizer_id);
    }

    public function test_two_editors_second_link_does_not_overwrite(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Dva urednika');

        $first = $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id);
        $this->assertSame($this->organizer->id, $first->organizer_id);

        $staleForB = CulturalEventEntry::query()->findOrFail($entry->id);
        // Simulacija zastarjelog modela Urednika B (u memoriji još bez Org).
        $staleForB->organizer_id = null;
        $staleForB->syncOriginal();

        $this->assertDomainRejects(
            fn () => $this->writer->linkOrganizer($staleForB, $this->editorB, $this->organizerB->id),
            'već povezan'
        );

        $fresh = $entry->fresh();
        $this->assertSame($this->organizer->id, $fresh->organizer_id);
        $this->assertSame($this->editor->id, $fresh->last_modified_by);
    }

    public function test_http_link_succeeds_and_ui_hides_form_after(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('HTTP link');

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->assertSee('Poveži sa Organizatorom', false)
            ->assertSee(route('cultural-event-entries.link-organizer', $entry), false);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.link-organizer', $entry), [
                'organizer_id' => $this->organizer->id,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHas('status');

        $entry->refresh();
        $this->assertSame($this->organizer->id, $entry->organizer_id);
        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->status);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->assertSee($this->organizer->naziv, false)
            ->assertDontSee(route('cultural-event-entries.link-organizer', $entry), false);
    }

    public function test_http_auth_blocks_regular_user_and_moderator(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Auth link');

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.link-organizer', $entry), [
                'organizer_id' => $this->organizer->id,
            ])
            ->assertForbidden();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-event-entries.link-organizer', $entry), [
                'organizer_id' => $this->organizer->id,
            ])
            ->assertForbidden();

        $this->assertNull($entry->fresh()->organizer_id);
    }

    public function test_http_validation_rejects_missing_and_unknown_organizer(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Validacija');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.link-organizer', $entry), [])
            ->assertSessionHasErrors('organizer_id');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.link-organizer', $entry), [
                'organizer_id' => 999999,
            ])
            ->assertSessionHasErrors('organizer_id');

        $this->assertNull($entry->fresh()->organizer_id);
    }

    public function test_http_rejects_reassign_on_already_linked_event(): void
    {
        [$entry] = $this->makePublishedWithOrganizer('HTTP reassign', $this->organizer);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.link-organizer', $entry), [
                'organizer_id' => $this->organizerB->id,
            ])
            ->assertSessionHasErrors('domain');

        $this->assertSame($this->organizer->id, $entry->fresh()->organizer_id);
    }

    public function test_draft_crud_still_allows_organizer_assignment_outside_br052(): void
    {
        $draft = $this->writer->createDraft($this->editor, [
            'naslov' => 'CRUD Nacrt',
            'category_id' => $this->category->id,
        ]);

        $updated = $this->writer->updateContent($draft, $this->editor, [
            'organizer_id' => $this->organizer->id,
        ]);

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $updated->status);
        $this->assertSame($this->organizer->id, $updated->organizer_id);
    }

    public function test_after_link_moderator_of_organizer_can_access_and_create_proposal(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Mod posljedice');
        $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id);
        $entry = $entry->fresh();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk();

        CulturalOrganizerContext::set($this->moderatorOther, $this->organizerB->id);
        $this->actingAs($this->moderatorOther)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertForbidden();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->moderator);
        $this->assertSame(CulturalEventChangeProposal::STATUS_DRAFT, $proposal->status);

        $this->expectException(CulturalEventDomainException::class);
        app(EventChangeProposalWriter::class)->createFromPublished($entry->fresh(), $this->moderator);
    }

    public function test_after_link_cancel_makes_proposal_inoperable_g_w02(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('G-W02 nakon link');
        $this->writer->linkOrganizer($entry, $this->editor, $this->organizer->id);
        $entry = $entry->fresh();

        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->moderator);
        $this->assertNotNull($proposal->active_for_event_id);

        $this->lifecycle->cancel($entry, $this->editor, 'Otkaz nakon povezivanja');

        $proposal->refresh();
        $this->assertNull($proposal->active_for_event_id);
        $this->assertSame(CulturalEventEntry::STATUS_CANCELLED, $entry->fresh()->status);
    }

    public function test_update_content_still_blocks_organizer_change_on_published(): void
    {
        [$entry] = $this->makePublishedWithoutOrganizer('Content lock');

        $this->expectException(CulturalEventDomainException::class);
        $this->expectExceptionMessage('Registrovani Organizator se ne može postaviti kroz uređivanje sadržaja');
        $this->writer->updateContent($entry, $this->editor, [
            'organizer_id' => $this->organizer->id,
        ]);
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function assertDomainRejects(callable $callback, ?string $messageContains = null): void
    {
        try {
            $callback();
            $this->fail('Expected CulturalEventDomainException');
        } catch (CulturalEventDomainException $e) {
            if ($messageContains !== null) {
                $this->assertStringContainsString($messageContains, $e->getMessage());
            }
        }
    }

    /**
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makePublishedWithoutOrganizer(string $naslov): array
    {
        [$entry, $occurrence] = $this->makeReadyDraftWithoutOrganizer($naslov);
        $published = $this->lifecycle->publishDirectly($entry, $this->editor);

        return [$published, $occurrence->fresh()];
    }

    /**
     * @return array{0: CulturalEventEntry}
     */
    private function makePublishedWithOrganizer(string $naslov, CulturalOrganizer $organizer): array
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
            'organizer_id' => $organizer->id,
        ]);
        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);
        $published = $this->lifecycle->approve(
            $this->lifecycle->submitForApproval($entry->fresh(), $this->editor),
            $this->editor
        );

        return [$published];
    }

    /**
     * @return array{0: CulturalEventEntry, 1: CulturalOccurrence}
     */
    private function makeReadyDraftWithoutOrganizer(string $naslov): array
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $occurrence = $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return [$entry->fresh(), $occurrence->fresh()];
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
