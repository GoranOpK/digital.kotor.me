<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalMedia;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PATCH-063 Phase 3 — trajno brisanje Urednik direct-flow Događaja u pripremi.
 */
class CulturalPatch063Phase3DeleteDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private User $regularUser;

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

        $this->regularUser = User::factory()->create([
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

    public function test_kk_admin_can_delete_direct_flow_draft_without_occurrence(): void
    {
        $entry = $this->makeDirectDraft(['naslov' => 'Bez OCC']);

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect(route('cultural-event-entries.index'))
            ->assertSessionHas('status', 'Događaj je obrisan.');

        $this->assertDatabaseMissing('cultural_event_entries', ['id' => $entry->id]);
    }

    public function test_kk_admin_can_delete_direct_flow_draft_with_manual_organizer(): void
    {
        $entry = $this->makeDirectDraft([
            'naslov' => 'Sa ručnim',
            'organizer_manual_name' => 'Ansambl',
        ]);

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $this->assertDatabaseMissing('cultural_event_entries', ['id' => $entry->id]);
    }

    public function test_kk_admin_can_delete_draft_with_occurrences_and_clears_them(): void
    {
        $entry = $this->makeDirectDraft(['naslov' => 'Sa OCC']);
        $occ1 = CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-10-01',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'cjelodnevno' => true,
        ]);
        $occ2 = CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-10-02',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'cjelodnevno' => true,
        ]);

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $this->assertDatabaseMissing('cultural_event_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('cultural_occurrences', ['id' => $occ1->id]);
        $this->assertDatabaseMissing('cultural_occurrences', ['id' => $occ2->id]);
    }

    public function test_delete_clears_tag_pivot_but_keeps_shared_tag_and_catalog(): void
    {
        $tag = CulturalTag::create([
            'naziv' => 'Ljeto',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);
        $media = CulturalMedia::create([
            'naziv' => 'Naslovna',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => 'a.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => 'cultural-media/a.jpg',
            'creator_id' => $this->editor->id,
        ]);
        $location = CulturalLocation::create([
            'naziv' => 'Trg',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $entry = $this->makeDirectDraft([
            'naslov' => 'Sa tagom',
            'category_id' => $this->category->id,
            'cover_media_id' => $media->id,
        ]);
        $entry->tags()->sync([$tag->id]);

        CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-10-01',
            'status' => CulturalOccurrence::STATUS_PLANNED,
            'cjelodnevno' => true,
            'location_id' => $location->id,
        ]);

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $this->assertDatabaseMissing('cultural_event_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('cultural_event_entry_tag', [
            'cultural_event_entry_id' => $entry->id,
            'cultural_tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('cultural_tags', ['id' => $tag->id]);
        $this->assertDatabaseHas('cultural_categories', ['id' => $this->category->id]);
        $this->assertDatabaseHas('cultural_media', ['id' => $media->id]);
        $this->assertDatabaseHas('cultural_locations', ['id' => $location->id]);
        $this->assertDatabaseHas('cultural_organizers', ['id' => $this->organizer->id]);
    }

    public function test_delete_blocked_for_non_direct_flow_statuses_and_registered_organizer(): void
    {
        $cases = [
            ['status' => CulturalEventEntry::STATUS_PUBLISHED, 'organizer_id' => null],
            ['status' => CulturalEventEntry::STATUS_CANCELLED, 'organizer_id' => null],
            ['status' => CulturalEventEntry::STATUS_ARCHIVED, 'organizer_id' => null],
            ['status' => CulturalEventEntry::STATUS_PENDING_APPROVAL, 'organizer_id' => $this->organizer->id],
            ['status' => CulturalEventEntry::STATUS_DRAFT, 'organizer_id' => $this->organizer->id],
        ];

        foreach ($cases as $i => $overrides) {
            $entry = $this->makeDirectDraft(array_merge([
                'naslov' => 'Blocked '.$i,
            ], $overrides));

            $this->actingAs($this->editor)
                ->from(route('cultural-event-entries.index'))
                ->delete(route('cultural-event-entries.destroy', $entry))
                ->assertRedirect(route('cultural-event-entries.index'))
                ->assertSessionHasErrors('domain');

            $this->assertDatabaseHas('cultural_event_entries', ['id' => $entry->id]);
        }
    }

    public function test_moderator_and_ordinary_user_and_guest_cannot_delete(): void
    {
        $entry = $this->makeDirectDraft(['naslov' => 'Auth']);

        $this->actingAs($this->moderator)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertForbidden();

        $this->actingAs($this->regularUser)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertForbidden();

        $this->assertDatabaseHas('cultural_event_entries', ['id' => $entry->id]);

        auth()->logout();

        $this->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect();

        $this->assertDatabaseHas('cultural_event_entries', ['id' => $entry->id]);
    }

    public function test_index_and_edit_show_delete_only_for_u_pripremi_with_confirmation(): void
    {
        $direct = $this->makeDirectDraft(['naslov' => 'Direct UI']);
        $modDraft = $this->makeDirectDraft([
            'naslov' => 'Mod UI',
            'organizer_id' => $this->organizer->id,
        ]);
        $published = $this->makeDirectDraft([
            'naslov' => 'Pub UI',
            'status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);

        $confirm = 'Da li ste sigurni da želite trajno obrisati ovaj događaj? Ova radnja se ne može poništiti.';

        $index = $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee($confirm, false)
            ->getContent();

        $this->assertStringContainsString(
            'action="'.route('cultural-event-entries.destroy', $direct).'"',
            $index
        );
        $this->assertStringNotContainsString(
            'action="'.route('cultural-event-entries.destroy', $modDraft).'"',
            $index
        );
        $this->assertStringNotContainsString(
            'action="'.route('cultural-event-entries.destroy', $published).'"',
            $index
        );

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $direct))
            ->assertOk()
            ->assertSee('Brisanje događaja', false)
            ->assertSee('Obriši', false)
            ->assertSee($confirm, false);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $modDraft))
            ->assertOk()
            ->assertDontSee('Brisanje događaja', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeDirectDraft(array $overrides = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => 'Priprema',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_id' => null,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ], $overrides));
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
