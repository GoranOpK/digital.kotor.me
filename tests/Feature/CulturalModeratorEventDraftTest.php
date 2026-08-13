<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TS-010.1 — Moderator Draft tok + aktivni Organizator kontekst.
 */
class CulturalModeratorEventDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $modA;

    private User $modB;

    private CulturalOrganizer $orgA;

    private CulturalOrganizer $orgB;

    private CulturalCategory $category;

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

        $this->orgA = $this->makeOrganizer('Org A');
        $this->orgB = $this->makeOrganizer('Org B');
        $this->grantModerator($this->modA, $this->orgA);
        $this->grantModerator($this->modB, $this->orgB);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_moderator_creates_draft_for_own_org_with_server_side_organizer_id(): void
    {
        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgA->id])
            ->assertRedirect(route('cultural-moderator-dashboard.index'));

        $response = $this->actingAs($this->modA)->post(route('cultural-moderator-events.store'), [
            'naslov' => 'Mod nacrt',
            'opis' => 'Opis',
            'category_id' => $this->category->id,
            'organizer_id' => $this->orgB->id, // payload manipulisanje — mora biti ignorisano
        ]);

        $entry = CulturalEventEntry::query()->firstOrFail();
        $response->assertRedirect(route('cultural-moderator-events.edit', $entry));
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertSame($this->orgA->id, $entry->organizer_id);
        $this->assertSame('Mod nacrt', $entry->naslov);
        $this->assertSame($this->modA->id, $entry->created_by);
        $this->assertDatabaseCount('cultural_events', 0);
    }

    public function test_moderator_cannot_create_for_other_org_via_payload(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $this->actingAs($this->modA)->post(route('cultural-moderator-events.store'), [
            'naslov' => 'Hak',
            'organizer_id' => $this->orgB->id,
        ]);

        $entry = CulturalEventEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame($this->orgA->id, $entry->organizer_id);
        $this->assertNotSame($this->orgB->id, $entry->organizer_id);
    }

    public function test_moderator_cannot_act_on_event_outside_active_organizer_context(): void
    {
        $this->grantModerator($this->modA, $this->orgB);

        $entryB = $this->makeDraftFor($this->orgB, $this->modA, 'Event Org B');
        $occurrence = CulturalOccurrence::create([
            'event_entry_id' => $entryB->id,
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $this->assertSame($this->orgA->id, CulturalOrganizerContext::get($this->modA)->id);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entryB))
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.update', $entryB), ['naslov' => 'Hak B'])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.submit', $entryB))
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.store', $entryB), [
                'datum' => '2026-11-01',
                'cjelodnevno' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.occurrences.update', [$entryB, $occurrence]), [
                'datum' => '2026-11-02',
                'cjelodnevno' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->delete(route('cultural-moderator-events.occurrences.destroy', [$entryB, $occurrence]))
            ->assertForbidden();

        $entryB->refresh();
        $this->assertSame('Event Org B', $entryB->naslov);
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entryB->status);
        $this->assertSame(1, $entryB->occurrences()->count());
        $this->assertSame(
            now()->addDays(5)->toDateString(),
            $occurrence->fresh()->datum->toDateString()
        );

        CulturalOrganizerContext::set($this->modA, $this->orgB->id);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entryB))
            ->assertOk()
            ->assertSee('Event Org B', false);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.update', $entryB), [
                'naslov' => 'Event Org B uredjen',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entryB));

        $this->assertSame('Event Org B uredjen', $entryB->fresh()->naslov);
    }

    public function test_moderator_cannot_access_foreign_event_or_occurrences(): void
    {
        $entryB = $this->makeDraftFor($this->orgB, $this->modB, 'Tuđi');
        $occurrence = CulturalOccurrence::create([
            'event_entry_id' => $entryB->id,
            'datum' => now()->addDays(5)->toDateString(),
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entryB))
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.update', $entryB), ['naslov' => 'Hak'])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.occurrences.store', $entryB), [
                'datum' => '2026-11-01',
                'cjelodnevno' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.occurrences.update', [$entryB, $occurrence]), [
                'datum' => '2026-11-02',
                'cjelodnevno' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->delete(route('cultural-moderator-events.occurrences.destroy', [$entryB, $occurrence]))
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-events.submit', $entryB))
            ->assertForbidden();

        $this->assertSame('Tuđi', $entryB->fresh()->naslov);
        $this->assertDatabaseCount('cultural_occurrences', 1);
    }

    public function test_lost_authorization_or_deactivated_org_blocks_access(): void
    {
        $entry = $this->makeDraftFor($this->orgA, $this->modA, 'Privatni');
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->modA->id)
            ->where('organizer_id', $this->orgA->id)
            ->update([
                'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertForbidden();

        $this->grantModerator($this->modA, $this->orgA);
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $this->orgA->update(['status' => CulturalOrganizer::STATUS_DEACTIVATED]);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertForbidden();
    }

    public function test_moderator_draft_edit_occurrences_and_submit_flow(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);

        $this->actingAs($this->modA)->post(route('cultural-moderator-events.store'), [
            'naslov' => 'Flow',
            'category_id' => $this->category->id,
        ]);
        $entry = CulturalEventEntry::query()->firstOrFail();

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('Nema održavanja', false);

        $this->actingAs($this->modA)->put(route('cultural-moderator-events.update', $entry), [
            'naslov' => 'Flow uredjen',
            'category_id' => $this->category->id,
        ])->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $this->assertSame('Flow uredjen', $entry->fresh()->naslov);

        $this->actingAs($this->modA)->post(route('cultural-moderator-events.occurrences.store', $entry), [
            'datum' => '2026-10-01',
            'cjelodnevno' => '1',
        ]);
        $this->actingAs($this->modA)->post(route('cultural-moderator-events.occurrences.store', $entry), [
            'datum' => '2026-10-02',
            'vrijeme_od' => '19:00',
        ]);
        $this->assertSame(2, $entry->fresh()->occurrences()->count());

        $occ = $entry->occurrences()->first();
        $this->actingAs($this->modA)->put(route('cultural-moderator-events.occurrences.update', [$entry, $occ]), [
            'datum' => '2026-10-03',
            'cjelodnevno' => '1',
        ]);
        $this->assertSame('2026-10-03', $occ->fresh()->datum->toDateString());

        $this->actingAs($this->modA)
            ->delete(route('cultural-moderator-events.occurrences.destroy', [$entry, $occ]))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));
        $this->assertSame(1, $entry->fresh()->occurrences()->count());

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $entry))
            ->post(route('cultural-moderator-events.submit', $entry))
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);
        $this->assertNotNull($entry->first_submitted_at);

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.update', $entry), ['naslov' => 'Hak pending'])
            ->assertForbidden();

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('Na odobrenju', false);
    }

    public function test_submit_without_occurrence_rejected_by_gate(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makeDraftFor($this->orgA, $this->modA, 'Bez termina');
        $entry->update(['category_id' => $this->category->id]);

        $this->actingAs($this->modA)
            ->from(route('cultural-moderator-events.edit', $entry))
            ->post(route('cultural-moderator-events.submit', $entry))
            ->assertSessionHasErrors('domain');

        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_returned_draft_editable_again_by_moderator(): void
    {
        CulturalOrganizerContext::set($this->modA, $this->orgA->id);
        $entry = $this->makeReadyDraftFor($this->orgA, $this->modA, 'Vrati');

        app(EventLifecycle::class)->submitForApproval($entry, $this->modA);
        app(EventLifecycle::class)->returnToDraft($entry->fresh(), $this->editor, 'Doradi opis');

        $this->actingAs($this->modA)
            ->put(route('cultural-moderator-events.update', $entry->fresh()), [
                'naslov' => 'Vracen uredjen',
                'opis' => 'Novi opis',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect(route('cultural-moderator-events.edit', $entry));

        $this->assertSame('Vracen uredjen', $entry->fresh()->naslov);
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->fresh()->status);
    }

    public function test_context_defaults_with_one_org_and_switches_with_many(): void
    {
        $this->grantModerator($this->modA, $this->orgB);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.index'))
            ->assertOk()
            ->assertSee('Izbor organizatora', false);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgB->id])
            ->assertRedirect(route('cultural-moderator-dashboard.index'));

        $this->assertSame($this->orgB->id, CulturalOrganizerContext::get($this->modA)->id);

        $this->actingAs($this->modA)
            ->post(route('cultural-moderator-context.update'), ['organizer_id' => $this->orgA->id]);

        $this->assertSame($this->orgA->id, CulturalOrganizerContext::get($this->modA)->id);

        // Jedan Org → automatski kontekst
        CulturalOrganizerContext::clear();
        CulturalModeratorAuthorization::query()
            ->where('user_id', $this->modA->id)
            ->where('organizer_id', $this->orgB->id)
            ->update(['status' => CulturalModeratorAuthorization::STATUS_REMOVED, 'removed_at' => now()]);

        $this->actingAs($this->modA)
            ->get(route('cultural-moderator-events.index'))
            ->assertOk()
            ->assertSee('Org A', false);
        $this->assertSame($this->orgA->id, CulturalOrganizerContext::get($this->modA)->id);
    }

    public function test_kk_admin_canonical_routes_remain_available(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk();

        $this->actingAs($this->editor)
            ->get(route('cultural-moderator-events.index'))
            ->assertForbidden();
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

    private function makeDraftFor(CulturalOrganizer $org, User $creator, string $naslov): CulturalEventEntry
    {
        return app(EventWriter::class)->createDraft($creator, [
            'naslov' => $naslov,
            'organizer_id' => $org->id,
            'category_id' => $this->category->id,
        ]);
    }

    private function makeReadyDraftFor(CulturalOrganizer $org, User $creator, string $naslov): CulturalEventEntry
    {
        $entry = $this->makeDraftFor($org, $creator, $naslov);
        app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(8)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }
}
