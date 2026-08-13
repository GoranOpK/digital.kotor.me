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
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-063 Phase 2 — Urednik create / „U pripremi“ / fail-closed organizer.
 */
class CulturalPatch063Phase2EditorialCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private CulturalOrganizer $organizer;

    private CulturalCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->organizer = $this->makeOrganizer('Org A');
        CulturalModeratorAuthorization::query()->updateOrCreate(
            [
                'user_id' => $this->moderator->id,
                'organizer_id' => $this->organizer->id,
            ],
            [
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]
        );

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_urednik_create_form_shows_manual_organizer_not_registered_dropdown(): void
    {
        $html = $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.create'))
            ->assertOk()
            ->assertSee('Sačuvaj i nastavi', false)
            ->assertSee('Organizator', false)
            ->assertSee('Opciono — unesite naziv organizatora', false)
            ->assertDontSee('naziv ako nije u katalogu', false)
            ->assertSee('Isticanje događaja biće dostupno nakon objave.', false)
            ->assertDontSee('Featured: nije dostupno u pripremi', false)
            ->assertDontSee('— bez organizatora —', false)
            ->getContent();

        $this->assertStringContainsString('name="organizer_manual_name"', $html);
        $this->assertStringNotContainsString('name="organizer_id"', $html);
    }

    public function test_urednik_can_save_optional_manual_organizer_and_update_before_publish(): void
    {
        $response = $this->actingAs($this->editor)->post(route('cultural-event-entries.store'), [
            'naslov' => 'Priprema',
            'organizer_manual_name' => '  Ansambl Kotor  ',
            'category_id' => $this->category->id,
        ]);

        $entry = CulturalEventEntry::query()->firstOrFail();
        $response->assertRedirect(route('cultural-event-entries.edit', $entry));
        $response->assertSessionHas('status', 'Događaj je sačuvan.');
        $this->assertNull($entry->organizer_id);
        $this->assertSame('Ansambl Kotor', $entry->organizer_manual_name);
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Priprema 2',
                'organizer_manual_name' => 'Ansambl Kotor 2',
                'category_id' => $this->category->id,
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $entry->refresh();
        $this->assertSame('Priprema 2', $entry->naslov);
        $this->assertSame('Ansambl Kotor 2', $entry->organizer_manual_name);
        $this->assertNull($entry->organizer_id);
    }

    public function test_urednik_manual_organizer_nullable_and_rejects_organizer_id_tamper(): void
    {
        $this->actingAs($this->editor)->post(route('cultural-event-entries.store'), [
            'naslov' => 'Bez org',
        ])->assertRedirect();

        $entry = CulturalEventEntry::query()->firstOrFail();
        $this->assertNull($entry->organizer_id);
        $this->assertNull($entry->organizer_manual_name);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.create'))
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'Tamper',
                'organizer_id' => $this->organizer->id,
                'organizer_manual_name' => 'I oba',
            ])
            ->assertRedirect(route('cultural-event-entries.create'))
            ->assertSessionHasErrors('organizer_id');

        $this->assertSame(1, CulturalEventEntry::query()->count());
    }

    public function test_index_and_edit_show_u_pripremi_and_objavi_for_direct_flow(): void
    {
        $withManual = CulturalEventEntry::create([
            'naslov' => 'Sa ručnim',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_manual_name' => 'Ručni',
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);
        $without = CulturalEventEntry::create([
            'naslov' => 'Bez org',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);
        $modDraft = CulturalEventEntry::create([
            'naslov' => 'Mod nacrt',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_id' => $this->organizer->id,
            'created_by' => $this->moderator->id,
            'last_modified_by' => $this->moderator->id,
        ]);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee('U pripremi', false)
            ->assertSee('Nacrt', false)
            ->assertSee('Ručni', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $withManual))
            ->assertOk()
            ->assertSee('Uredi događaj', false)
            ->assertSee('Status: U pripremi', false)
            ->assertSee('Objavi', false)
            ->assertDontSee('Pošalji na odobrenje', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $without))
            ->assertOk()
            ->assertSee('Objavi', false)
            ->assertDontSee('Pošalji na odobrenje', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $modDraft))
            ->assertOk()
            ->assertSee('Pošalji na odobrenje', false)
            ->assertDontSee('Objavi', false);
    }

    public function test_save_is_not_public(): void
    {

        $response = $this->actingAs($this->editor)->post(route('cultural-event-entries.store'), [
            'naslov' => 'Ne javno',
            'organizer_manual_name' => 'X',
        ]);

        $entry = CulturalEventEntry::query()->firstOrFail();
        $response->assertRedirect(route('cultural-event-entries.edit', $entry));
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertNull($entry->first_submitted_at);

        $this->get(route('cultural-calendar.show', $entry->id))
            ->assertNotFound();
    }

    public function test_moderator_rejects_manual_organizer_and_keeps_registered_flow(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.create'))
            ->assertOk()
            ->assertSee('Sačuvaj nacrt', false)
            ->assertDontSee('name="organizer_manual_name"', false);

        $this->actingAs($this->moderator)
            ->from(route('cultural-moderator-events.create'))
            ->post(route('cultural-moderator-events.store'), [
                'naslov' => 'Mod hak',
                'organizer_manual_name' => 'Ne smije',
            ])
            ->assertRedirect(route('cultural-moderator-events.create'))
            ->assertSessionHasErrors('organizer_manual_name');

        $this->assertDatabaseCount('cultural_event_entries', 0);

        $ok = $this->actingAs($this->moderator)->post(route('cultural-moderator-events.store'), [
            'naslov' => 'Mod OK',
            'category_id' => $this->category->id,
        ]);

        $entry = CulturalEventEntry::query()->firstOrFail();
        $ok->assertRedirect(route('cultural-moderator-events.edit', $entry));
        $this->assertSame($this->organizer->id, $entry->organizer_id);
        $this->assertNull($entry->organizer_manual_name);
        $this->assertSame('Nacrt', $entry->statusLabel());
        $this->assertSame('Nacrt', $entry->editorialStatusLabel());

        CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => now()->addDays(3)->toDateString(),
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'cjelodnevno' => true,
        ]);
        $entry->update(['category_id' => $this->category->id]);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('Pošalji na odobrenje', false)
            ->assertDontSee('Objavi', false);
    }

    public function test_editorial_status_label_helper(): void
    {
        $direct = CulturalEventEntry::create([
            'naslov' => 'D',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'created_by' => $this->editor->id,
        ]);
        $mod = CulturalEventEntry::create([
            'naslov' => 'M',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_id' => $this->organizer->id,
            'created_by' => $this->moderator->id,
        ]);

        $this->assertSame('Nacrt', $direct->statusLabel());
        $this->assertSame('U pripremi', $direct->editorialStatusLabel());
        $this->assertSame('Nacrt', $mod->editorialStatusLabel());
    }

    private function makeOrganizer(string $name): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => $name,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => $name,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
