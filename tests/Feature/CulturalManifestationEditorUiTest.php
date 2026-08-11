<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalManifestationEditorUiTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $ordinary;

    private EventWriter $eventWriter;

    private EventLifecycle $eventLifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private ManifestationWriter $mfWriter;

    private ManifestationLifecycle $mfLifecycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->ordinary = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->eventWriter = app(EventWriter::class);
        $this->eventLifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);
        $this->mfWriter = app(ManifestationWriter::class);
        $this->mfLifecycle = app(ManifestationLifecycle::class);
    }

    public function test_editor_index_and_create_accessible_for_kk_admin(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.index'))
            ->assertOk()
            ->assertSee('Manifestacije');

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.create'))
            ->assertOk()
            ->assertSee('Nova Manifestacija')
            ->assertSee('Sačuvaj nacrt')
            ->assertSee('Odustani')
            ->assertSee('background:#b91c1c', false)
            ->assertSee('action="'.route('cultural-manifestations.store').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('type="submit"', false)
            ->assertSee('name="naziv"', false);
    }

    public function test_ordinary_user_forbidden_and_guest_redirected(): void
    {
        $this->get(route('cultural-manifestations.index'))->assertRedirect();

        $this->actingAs($this->ordinary)
            ->get(route('cultural-manifestations.index'))
            ->assertForbidden();
    }

    public function test_store_minimal_draft_and_optional_fields(): void
    {
        $organizer = $this->makeOrganizer();
        $media = $this->makeCoverMedia();

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), [
                'naziv' => 'Kotor Art',
                'opis' => 'Opis',
                'organizer_id' => $organizer->id,
                'cover_media_id' => $media->id,
                'web_stranica' => 'https://example.com',
            ])
            ->assertRedirect();

        $mf = CulturalManifestation::query()->firstOrFail();
        $this->assertSame(CulturalManifestation::STATUS_DRAFT, $mf->status);
        $this->assertSame('Kotor Art', $mf->naziv);
        $this->assertSame($organizer->id, $mf->organizer_id);
        $this->assertNull($mf->first_submitted_at);
        $this->assertNull($mf->published_at);
        $this->assertNull($mf->cancelled_at);
        $this->assertNull($mf->archived_at);
    }

    public function test_store_platform_mf_without_organizer(): void
    {
        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), ['naziv' => 'Platform MF'])
            ->assertRedirect();

        $this->assertDatabaseHas('cultural_manifestations', [
            'naziv' => 'Platform MF',
            'organizer_id' => null,
            'status' => CulturalManifestation::STATUS_DRAFT,
        ]);
    }

    public function test_link_unlink_move_and_eligibility_gates(): void
    {
        $draft = $this->makeDraftEvent('Draft OK');
        $cancelled = $this->makePublishedEvent('Cancel me');
        $this->eventLifecycle->cancel($cancelled->fresh(), $this->editor, 'x');

        $mf = $this->mfWriter->createDraft($this->editor, ['naziv' => 'MF']);
        $other = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Other',
            'event_entry_ids' => [$this->makeDraftEvent('In other')->id],
        ]);
        $moveEvent = $other->events()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.events.link', $mf), ['event_entry_id' => $draft->id])
            ->assertRedirect();
        $this->assertSame($mf->id, $draft->fresh()->manifestation_id);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.events.link', $mf), ['event_entry_id' => $cancelled->id])
            ->assertRedirect()
            ->assertSessionHasErrors('domain');

        $archived = $this->makePublishedEvent('Arch', '2020-01-01');
        $archived->update(['status' => CulturalEventEntry::STATUS_ARCHIVED]);
        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.events.link', $mf), ['event_entry_id' => $archived->id])
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.events.move', $mf), ['event_entry_id' => $moveEvent->id])
            ->assertRedirect();
        $this->assertSame($mf->id, $moveEvent->fresh()->manifestation_id);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.events.unlink', $mf), ['event_entry_id' => $draft->id])
            ->assertRedirect();
        $this->assertNull($draft->fresh()->manifestation_id);
    }

    public function test_editorial_lifecycle_submit_return_publish_cancel_without_reason(): void
    {
        $publishedEvent = $this->makePublishedEvent('Pub');
        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Lifecycle MF',
            'event_entry_ids' => [$publishedEvent->id],
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.submit', $mf))
            ->assertRedirect();
        $this->assertSame(CulturalManifestation::STATUS_PENDING_APPROVAL, $mf->fresh()->status);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), ['naziv' => 'Hacked'])
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.return', $mf))
            ->assertRedirect();
        $this->assertSame(CulturalManifestation::STATUS_RETURNED_FOR_REVISION, $mf->fresh()->status);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.submit', $mf->fresh()))
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.publish', $mf->fresh()))
            ->assertRedirect();
        $this->assertSame(CulturalManifestation::STATUS_PUBLISHED, $mf->fresh()->status);
        $this->assertNotNull($mf->fresh()->published_at);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf->fresh()), [
                'naziv' => 'Published rename',
                'opis' => 'Updated',
            ])
            ->assertRedirect();
        $this->assertSame('Published rename', $mf->fresh()->naziv);

        $eventStatusBefore = $publishedEvent->fresh()->status;
        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.cancel', $mf->fresh()))
            ->assertRedirect();
        $this->assertSame(CulturalManifestation::STATUS_CANCELLED, $mf->fresh()->status);
        $this->assertSame($eventStatusBefore, $publishedEvent->fresh()->status);
    }

    public function test_publish_gate_and_no_delete_route(): void
    {
        $draftEvent = $this->makeDraftEvent('Only draft');
        $mf = $this->mfWriter->createDraft($this->editor, [
            'naziv' => 'Gate MF',
            'event_entry_ids' => [$draftEvent->id],
        ]);
        $this->mfLifecycle->submitForApproval($mf, $this->editor);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.publish', $mf->fresh()))
            ->assertSessionHasErrors('domain');

        $this->actingAs($this->editor)
            ->delete('/kalendar-kulture/kanonske-manifestacije/'.$mf->id)
            ->assertStatus(405);
    }

    public function test_navigation_shows_manifestacije_for_editor(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.index'))
            ->assertOk()
            ->assertSee('Manifestacije')
            ->assertSee(route('cultural-manifestations.index'), false);
    }

    public function test_wrong_purpose_media_rejected_on_store(): void
    {
        $media = $this->makeCoverMedia(['namjena' => CulturalMedia::PURPOSE_EVENT_COVER]);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), [
                'naziv' => 'X',
                'cover_media_id' => $media->id,
            ])
            ->assertSessionHasErrors();
    }

    private function makeDraftEvent(string $title): CulturalEventEntry
    {
        return $this->eventWriter->createDraft($this->editor, ['naslov' => $title]);
    }

    private function makePublishedEvent(string $title, string $date = '2026-09-01'): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Kat '.uniqid(),
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = $this->eventWriter->createDraft($this->editor, [
            'naslov' => $title,
            'category_id' => $category->id,
        ]);
        $this->occurrenceWriter->create($entry, ['datum' => $date, 'cjelodnevno' => true]);
        $this->eventLifecycle->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh();
    }

    private function makeOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org '.uniqid(),
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org '.uniqid(),
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCoverMedia(array $overrides = []): CulturalMedia
    {
        return CulturalMedia::create(array_merge([
            'naziv' => 'Cover '.uniqid(),
            'namjena' => CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'm.jpg',
            'interni_naziv' => 'm.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 10,
            'storage_path' => 'cultural-media/m.jpg',
            'creator_id' => $this->editor->id,
        ], $overrides));
    }
}
