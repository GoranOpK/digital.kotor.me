<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalMedia;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

/**
 * Sprint 3A.2 — Draft UI za CulturalEventEntry (nije TS-010 / nije legacy CRUD).
 */
class CulturalEventEntryDraftUiTest extends TestCase
{
    use CreatesCulturalMediaFixtures;
    use RefreshDatabase;

    private User $editor;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_create_draft(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.create'))
            ->assertOk()
            ->assertSee('Novi događaj', false)
            ->assertSee('Sačuvaj i nastavi', false)
            ->assertSee('name="organizer_manual_name"', false)
            ->assertSee('Opciono — unesite naziv organizatora', false)
            ->assertDontSee('naziv ako nije u katalogu', false)
            ->assertSee('Isticanje događaja biće dostupno nakon objave.', false)
            ->assertDontSee('Featured: nije dostupno u pripremi', false)
            ->assertDontSee('name="organizer_id"', false)
            ->assertDontSee('Novi nacrt (kanonski)', false);

        $category = $this->makeActiveCategory();

        $response = $this->actingAs($this->editor)->post(route('cultural-event-entries.store'), [
            'naslov' => 'Kanonski nacrt',
            'opis' => 'Opis',
            'organizer_manual_name' => 'Lokalni ansambl',
            'category_id' => $category->id,
        ]);

        $entry = CulturalEventEntry::query()->firstOrFail();
        $response->assertRedirect(route('cultural-event-entries.edit', $entry));
        $response->assertSessionHas('status', 'Događaj je sačuvan.');
        $this->assertSame(CulturalEventEntry::STATUS_DRAFT, $entry->status);
        $this->assertNull($entry->organizer_id);
        $this->assertSame('Lokalni ansambl', $entry->organizer_manual_name);
        $this->assertSame('Kanonski nacrt', $entry->naslov);
        $this->assertSame($this->editor->id, $entry->created_by);
        $this->assertDatabaseCount('cultural_events', 0);
    }

    public function test_edit_draft(): void
    {
        $entry = $this->makeDraftEntry(['naslov' => 'Stari']);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Novi naslov',
                'opis' => 'Novi opis',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame('Novi naslov', $entry->fresh()->naslov);
        $this->assertSame('Novi opis', $entry->fresh()->opis);
    }

    public function test_draft_without_occurrence(): void
    {
        $entry = $this->makeDraftEntry();

        $this->assertSame(0, $entry->occurrences()->count());
        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.edit', $entry))
            ->assertOk()
            ->assertSee('Nema održavanja', false);
    }

    public function test_add_occurrence(): void
    {
        $entry = $this->makeDraftEntry();

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.occurrences.store', $entry), [
                'datum' => '2026-10-01',
                'cjelodnevno' => '1',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertDatabaseCount('cultural_occurrences', 1);
        $occurrence = CulturalOccurrence::query()->first();
        $this->assertSame($entry->id, $occurrence->event_entry_id);
        $this->assertTrue($occurrence->cjelodnevno);
        $this->assertSame(CulturalOccurrence::STATUS_PLANNED, $occurrence->status);
    }

    public function test_edit_occurrence(): void
    {
        $entry = $this->makeDraftEntry();
        $occurrence = CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-10-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.occurrences.update', [$entry, $occurrence]), [
                'datum' => '2026-10-15',
                'vrijeme_od' => '18:00',
                'vrijeme_do' => '20:00',
                'cjelodnevno' => '0',
            ])
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $occurrence->refresh();
        $this->assertSame('2026-10-15', $occurrence->datum->toDateString());
        $this->assertFalse($occurrence->cjelodnevno);
        $this->assertSame('18:00:00', $occurrence->vrijeme_od);
    }

    public function test_remove_occurrence(): void
    {
        $entry = $this->makeDraftEntry();
        $occurrence = CulturalOccurrence::create([
            'event_entry_id' => $entry->id,
            'datum' => '2026-10-01',
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ]);

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.occurrences.destroy', [$entry, $occurrence]))
            ->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertDatabaseCount('cultural_occurrences', 0);
    }

    public function test_multiple_occurrences(): void
    {
        $entry = $this->makeDraftEntry();

        $this->actingAs($this->editor)->post(route('cultural-event-entries.occurrences.store', $entry), [
            'datum' => '2026-10-01',
            'cjelodnevno' => '1',
        ]);
        $this->actingAs($this->editor)->post(route('cultural-event-entries.occurrences.store', $entry), [
            'datum' => '2026-10-02',
            'vrijeme_od' => '19:00',
        ]);

        $this->assertSame(2, $entry->fresh()->occurrences()->count());
    }

    public function test_catalog_location(): void
    {
        $entry = $this->makeDraftEntry();
        $location = CulturalLocation::create([
            'naziv' => 'Trg od oružja',
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->editor)->post(route('cultural-event-entries.occurrences.store', $entry), [
            'datum' => '2026-10-01',
            'location_id' => $location->id,
        ])->assertRedirect(route('cultural-event-entries.edit', $entry));

        $this->assertSame($location->id, CulturalOccurrence::query()->first()->location_id);
    }

    public function test_manual_location(): void
    {
        $entry = $this->makeDraftEntry();

        $this->actingAs($this->editor)->post(route('cultural-event-entries.occurrences.store', $entry), [
            'datum' => '2026-10-01',
            'location_manual_name' => 'Dvorište škole',
        ])->assertRedirect(route('cultural-event-entries.edit', $entry));

        $occurrence = CulturalOccurrence::query()->first();
        $this->assertNull($occurrence->location_id);
        $this->assertSame('Dvorište škole', $occurrence->location_manual_name);
    }

    public function test_without_location(): void
    {
        $entry = $this->makeDraftEntry();

        $this->actingAs($this->editor)->post(route('cultural-event-entries.occurrences.store', $entry), [
            'datum' => '2026-10-01',
            'cjelodnevno' => '1',
        ])->assertRedirect(route('cultural-event-entries.edit', $entry));

        $occurrence = CulturalOccurrence::query()->first();
        $this->assertNull($occurrence->location_id);
        $this->assertNull($occurrence->location_manual_name);
    }

    public function test_organizer_validation(): void
    {
        $organizer = $this->makeActiveOrganizer();

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.create'))
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'X',
                'organizer_id' => $organizer->id,
            ])
            ->assertRedirect(route('cultural-event-entries.create'))
            ->assertSessionHasErrors('organizer_id');

        $this->assertDatabaseCount('cultural_event_entries', 0);
    }

    public function test_category_validation(): void
    {
        $category = CulturalCategory::create([
            'naziv' => 'Neaktivna',
            'status' => CulturalCategory::STATUS_INACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.create'))
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'X',
                'category_id' => $category->id,
            ])
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseCount('cultural_event_entries', 0);
    }

    public function test_media_validation(): void
    {
        $media = $this->makeCoverMedia([
            'namjena' => CulturalMedia::PURPOSE_CATEGORY_DEFAULT,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.create'))
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'X',
                'cover_media_id' => $media->id,
            ])
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseCount('cultural_event_entries', 0);
    }

    public function test_tag_validation(): void
    {
        $inactive = CulturalTag::create([
            'naziv' => 'Stara',
            'status' => CulturalTag::STATUS_INACTIVE,
        ]);

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.create'))
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'X',
                'tag_ids' => [$inactive->id],
            ])
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseCount('cultural_event_entries', 0);
    }

    public function test_index_lists_entries_and_regular_user_forbidden(): void
    {
        $this->makeDraftEntry(['naslov' => 'Vidljiv']);

        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->assertSee('Događaji', false)
            ->assertSee('Vidljiv', false)
            ->assertDontSee('Kanonski događaji', false);

        $this->actingAs($this->regularUser)
            ->get(route('cultural-event-entries.index'))
            ->assertForbidden();
    }

    public function test_legacy_cultural_events_routes_untouched(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-events.index'))
            ->assertOk();

        $this->assertDatabaseCount('cultural_event_entries', 0);
    }

    public function test_kk_admin_day_click_opens_entry_create_not_legacy(): void
    {
        $date = '2026-08-15';

        $this->actingAs($this->editor)
            ->get(route('cultural-calendar.day', ['date' => $date]))
            ->assertRedirect(route('cultural-event-entries.create'));

        $this->actingAs($this->regularUser)
            ->get(route('cultural-calendar.day', ['date' => $date]))
            ->assertOk()
            ->assertViewIs('cultural-calendar.day');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeDraftEntry(array $overrides = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => 'Nacrt',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ], $overrides));
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

    private function makeActiveCategory(): CulturalCategory
    {
        return CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCoverMedia(array $overrides = []): CulturalMedia
    {
        return CulturalMedia::create(array_merge([
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
        ], $overrides));
    }
}
