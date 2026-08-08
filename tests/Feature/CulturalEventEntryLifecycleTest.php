<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 3A.3 — Lifecycle Urednika (TS-003/004; nije TS-010).
 */
class CulturalEventEntryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    private CulturalCategory $category;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
    }

    public function test_valid_draft_can_be_submitted(): void
    {
        $entry = $this->makeReadyDraft('Za odobrenje');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.submit', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);
        $this->assertNotNull($entry->first_submitted_at);
        $this->assertSame('Za odobrenje', $entry->naslov);
        $this->assertDatabaseCount('cultural_events', 0);
    }

    public function test_resubmit_after_return_keeps_first_submitted_at(): void
    {
        $entry = $this->makeReadyDraft('Ponovo');
        $this->lifecycle->submitForApproval($entry, $this->editor);
        $entry->refresh();
        $first = $entry->first_submitted_at->copy();

        $this->travel(2)->hours();

        $this->lifecycle->returnToDraft($entry->fresh(), $this->editor, 'Doradi opis');
        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.submit', $entry->fresh()))
            ->assertRedirect(route('cultural-event-entries.index'));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);
        $this->assertTrue($first->equalTo($entry->first_submitted_at));
    }

    public function test_submit_without_occurrence_is_rejected(): void
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => 'Bez održavanja',
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.submit', $entry))
            ->assertRedirect(route('cultural-event-entries.edit', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
        $this->assertNull($entry->fresh()->first_submitted_at);
    }

    public function test_submit_with_inactive_category_is_rejected(): void
    {
        $entry = $this->makeReadyDraft('Kat');
        $this->category->update(['status' => CulturalCategory::STATUS_INACTIVE]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.submit', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_submit_with_inactive_organizer_is_rejected(): void
    {
        $organizer = $this->makeActiveOrganizer();
        $entry = $this->makeReadyDraft('Org', ['organizer_id' => $organizer->id]);
        $organizer->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.submit', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_pending_entry_edit_is_rejected(): void
    {
        $entry = $this->makePending('Zaključan');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Hakovan',
            ])
            ->assertRedirect(route('cultural-event-entries.index'))
            ->assertSessionHasErrors('domain');

        $this->assertSame('Zaključan', $entry->fresh()->naslov);

        try {
            $this->writer->updateContent($entry->fresh(), $this->editor, ['naslov' => 'Hakovan']);
            $this->fail('Expected pending content lock');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('zaključan', mb_strtolower($e->getMessage()));
        }
    }

    public function test_pending_add_occurrence_is_rejected(): void
    {
        $entry = $this->makePending('Zaključan');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.store', $entry), [
                'datum' => '2026-12-01',
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-event-entries.index'))
            ->assertSessionHasErrors('domain');

        $this->assertSame(1, $entry->fresh()->occurrences()->count());

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceWriter->create($entry->fresh(), [
            'datum' => '2026-12-01',
            'cjelodnevno' => true,
        ]);
    }

    public function test_pending_edit_occurrence_is_rejected(): void
    {
        $entry = $this->makePending('Zaključan');
        $occurrence = $entry->occurrences()->firstOrFail();
        $originalDate = $occurrence->datum->toDateString();

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.occurrences.update', [$entry, $occurrence]), [
                'datum' => '2026-12-15',
                'cjelodnevno' => '1',
            ])
            ->assertNotFound();

        $this->assertSame($originalDate, $occurrence->fresh()->datum->toDateString());

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceWriter->update($occurrence->fresh(), [
            'datum' => '2026-12-15',
            'cjelodnevno' => true,
        ]);
    }

    public function test_pending_remove_occurrence_is_rejected(): void
    {
        $entry = $this->makePending('Zaključan');
        $occurrence = $entry->occurrences()->firstOrFail();

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.occurrences.destroy', [$entry, $occurrence]))
            ->assertNotFound();

        $this->assertDatabaseCount('cultural_occurrences', 1);

        $this->expectException(CulturalEventDomainException::class);
        $this->occurrenceWriter->deletePhysically($occurrence->fresh());
    }

    public function test_pending_can_be_approved(): void
    {
        $entry = $this->makePending('Odobri me');

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.approve', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $this->assertSame(CulturalEventEntry::STATUS_PUBLISHED, $entry->fresh()->status);
    }

    public function test_approve_rechecks_publish_gate(): void
    {
        $entry = $this->makePending('Gate');
        $this->category->update(['status' => CulturalCategory::STATUS_INACTIVE]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.approve', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->fresh()->status);
    }

    public function test_pending_can_be_returned_to_draft_with_required_reason(): void
    {
        $entry = $this->makePending('Vrati');

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->post(route('cultural-event-entries.return', $entry), [])
            ->assertSessionHasErrors('return_reason');

        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->fresh()->status);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.return', $entry), [
                'return_reason' => 'Nedostaje opis programa',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertSame('Nedostaje opis programa', $entry->return_reason);
    }

    public function test_editing_allowed_again_after_return(): void
    {
        $entry = $this->makePending('Dorada');
        $this->lifecycle->returnToDraft($entry, $this->editor, 'Popravi naslov');

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry->fresh()), [
                'naslov' => 'Ispravljen naslov',
                'opis' => 'Novi opis',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame('Ispravljen naslov', $entry->fresh()->naslov);
    }

    public function test_ordinary_user_cannot_lifecycle_actions(): void
    {
        $entry = $this->makeReadyDraft('Zabranjeno');

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.submit', $entry))
            ->assertForbidden();

        $this->lifecycle->submitForApproval($entry->fresh(), $this->editor);
        $pending = $entry->fresh();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.approve', $pending))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->post(route('cultural-event-entries.return', $pending), [
                'return_reason' => 'Ne smije',
            ])
            ->assertForbidden();

        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $pending->fresh()->status);
    }

    public function test_index_shows_status_actions_for_draft_and_pending(): void
    {
        $draft = $this->makeReadyDraft('Draft UI');
        $pending = $this->makePending('Pending UI');

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee('Objavi', false)
            ->assertSee(route('cultural-event-entries.publish', $draft), false)
            ->assertDontSee('Pošalji na odobrenje', false)
            ->assertSee('Odobri', false)
            ->assertSee('Vrati na doradu', false)
            ->assertSee($draft->naslov, false)
            ->assertSee($pending->naslov, false)
            ->assertSee('Na odobrenju', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeReadyDraft(string $naslov, array $overrides = []): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, array_merge([
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ], $overrides));

        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(10)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }

    private function makePending(string $naslov): CulturalEventEntry
    {
        $entry = $this->makeReadyDraft($naslov);
        $this->lifecycle->submitForApproval($entry, $this->editor);

        return $entry->fresh();
    }

    private function makeActiveOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
