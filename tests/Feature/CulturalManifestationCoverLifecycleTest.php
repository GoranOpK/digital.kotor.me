<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalActivityRecord;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Services\CulturalManifestationDomain\ManifestationCoverBinder;
use App\Services\CulturalManifestationDomain\ManifestationLifecycle;
use App\Services\CulturalManifestationDomain\ManifestationWriter;
use App\Services\CulturalMedia\CulturalMediaStorage;
use App\Support\CulturalOrganizerContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

class CulturalManifestationCoverLifecycleTest extends TestCase
{
    use CreatesCulturalMediaFixtures;
    use RefreshDatabase;

    private User $editor;

    private User $moderator;

    private CulturalOrganizer $organizer;

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
        $this->moderator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->organizer = $this->makeOrganizer();
        $this->grantModerator($this->moderator, $this->organizer);
    }

    public function test_editor_adds_replaces_and_removes_cover_via_upload(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.create'))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertSee('data-kk-cover-dropzone', false)
            ->assertSee('data-kk-cover-preview', false)
            ->assertSee('data-kk-cover-remove', false)
            ->assertSee('Duža strana fotografije je manja od 800 px', false)
            ->assertDontSee('name="cover_media_id"', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.create'))
            ->assertOk();
        $this->assertDatabaseCount('cultural_manifestations', 0);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), [
                'naziv' => 'MF cover',
                'cover_file' => $this->uploadJpeg('prva.jpg'),
            ])
            ->assertRedirect();

        $mf = CulturalManifestation::query()->firstOrFail();
        $firstId = (int) $mf->cover_media_id;
        $this->assertNotNull($firstId);
        $first = CulturalMedia::query()->findOrFail($firstId);
        $this->assertSame(CulturalMedia::PURPOSE_MANIFESTATION_COVER, $first->namjena);
        Storage::disk('public')->assertExists($first->storage_path);
        $this->assertSame(
            0,
            CulturalActivityRecord::query()->where('event_type', 'mf.cover.change')->count()
        );

        $this->actingAs($this->editor)
            ->from(route('cultural-manifestations.edit', $mf))
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => 'MF cover',
                'cover_file' => $this->uploadPng('druga.png'),
            ])
            ->assertRedirect();

        $mf->refresh();
        $secondId = (int) $mf->cover_media_id;
        $this->assertNotSame($firstId, $secondId);
        $this->assertDatabaseMissing('cultural_media', ['id' => $firstId]);
        Storage::disk('public')->assertMissing($first->storage_path);
        $this->assertCoverAudit($mf);


        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => 'MF cover',
                'remove_cover' => '1',
            ])
            ->assertRedirect();

        $mf->refresh();
        $this->assertNull($mf->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $secondId]);
        $this->assertCoverAudit($mf);

        $this->assertSame(
            0,
            CulturalActivityRecord::query()->where('event_type', 'like', 'media.%')->count()
        );
        $this->assertSame(
            'mf.cover.change',
            CulturalActivityCatalog::row(CulturalActivityCatalog::MF_11)['type']
        );
    }

    public function test_failed_upload_keeps_existing_cover(): void
    {
        $mf = $this->createEditorDraftWithCover();
        $oldId = (int) $mf->cover_media_id;
        $oldPath = CulturalMedia::query()->findOrFail($oldId)->storage_path;

        $this->actingAs($this->editor)
            ->from(route('cultural-manifestations.edit', $mf))
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => 'MF cover',
                'cover_file' => UploadedFile::fake()->createWithContent('x.txt', 'not-an-image'),
            ])
            ->assertSessionHasErrors('cover_file');

        $mf->refresh();
        $this->assertSame($oldId, (int) $mf->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $oldId]);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_manifestation_persist_failure_discards_ingested_cover(): void
    {
        $binder = app(ManifestationCoverBinder::class);

        try {
            $binder->persist(
                ['naziv' => 'Fail'],
                $this->editor,
                $this->uploadJpeg(),
                false,
                null,
                function (array $payload) {
                    $this->assertNotNull($payload['cover_media_id'] ?? null);
                    throw new CulturalEventDomainException('persist fail');
                },
            );
            $this->fail('Expected persist failure');
        } catch (CulturalEventDomainException $e) {
            $this->assertSame('persist fail', $e->getMessage());
        }

        $this->assertDatabaseCount('cultural_media', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('cultural-media'));
    }

    public function test_physical_cleanup_failure_keeps_new_cover_and_logs(): void
    {
        $mf = $this->createEditorDraftWithCover();
        $oldId = (int) $mf->cover_media_id;

        $storage = Mockery::mock(CulturalMediaStorage::class)->makePartial();
        $storage->shouldReceive('deletePath')->andThrow(new RuntimeException('disk down'));
        $this->app->instance(CulturalMediaStorage::class, $storage);

        Log::spy();

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => $mf->naziv,
                'cover_file' => $this->uploadPng('nova.png'),
            ])
            ->assertRedirect();

        $mf->refresh();
        $this->assertNotNull($mf->cover_media_id);
        $this->assertNotSame($oldId, (int) $mf->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $oldId]);
        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return $message === 'cultural_media_cleanup_file_failed';
        })->atLeast()->once();
    }

    public function test_arbitrary_existing_media_id_cannot_be_linked_from_http(): void
    {
        $foreign = CulturalMedia::create([
            'naziv' => 'Tudja',
            'namjena' => CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => 'a.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => 'cultural-media/foreign-mf.jpg',
            'creator_id' => $this->editor->id,
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), [
                'naziv' => 'Hijack',
                'cover_media_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('cover_media_id');

        $this->assertDatabaseCount('cultural_manifestations', 0);

        $mf = $this->createEditorDraftWithCover();
        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => $mf->naziv,
                'cover_media_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('cover_media_id');

        $this->assertNotSame($foreign->id, (int) $mf->fresh()->cover_media_id);
    }

    public function test_moderator_draft_cover_editable_pending_and_published_locked(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-manifestations.create'))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertDontSee('name="cover_media_id"', false)
            ->assertSee('data-kk-cover-dropzone', false);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-manifestations.store'), [
                'naziv' => 'Mod MF cover',
                'cover_file' => $this->uploadJpeg(),
            ])
            ->assertRedirect();

        $mf = CulturalManifestation::query()->firstOrFail();
        $coverId = (int) $mf->cover_media_id;
        $this->assertGreaterThan(0, $coverId);

        $event = $this->makePublishedEvent();
        app(ManifestationWriter::class)->linkEvent($mf->fresh(), $event->id, $this->moderator);
        app(ManifestationLifecycle::class)->submitForApproval($mf->fresh(), $this->moderator);
        $mf->refresh();
        $this->assertTrue($mf->isPendingApproval());

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-manifestations.edit', $mf))
            ->assertOk()
            ->assertDontSee('name="cover_file"', false);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-manifestations.update', $mf), [
                'naziv' => 'Locked',
                'cover_file' => $this->uploadPng(),
            ])
            ->assertForbidden();

        $this->assertSame($coverId, (int) $mf->fresh()->cover_media_id);

        app(ManifestationLifecycle::class)->returnToRevision($mf->fresh(), $this->editor);
        $mf->refresh();
        $this->assertTrue($mf->isReturnedForRevision());

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-manifestations.edit', $mf))
            ->assertOk()
            ->assertSee('name="cover_file"', false);

        app(ManifestationLifecycle::class)->submitForApproval($mf->fresh(), $this->moderator);
        app(ManifestationLifecycle::class)->publish($mf->fresh(), $this->editor);
        $mf->refresh();
        $this->assertTrue($mf->isPublished());

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-manifestations.edit', $mf))
            ->assertOk()
            ->assertDontSee('name="cover_file"', false);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-manifestations.update', $mf), [
                'naziv' => 'Published locked',
                'cover_file' => $this->uploadPng(),
            ])
            ->assertForbidden();
        $this->assertSame($coverId, (int) $mf->fresh()->cover_media_id);
    }

    public function test_editor_published_direct_edit_can_change_cover(): void
    {
        $event = $this->makePublishedEvent();
        $mf = app(ManifestationWriter::class)->createDraft($this->editor, [
            'naziv' => 'Published MF',
            'event_entry_ids' => [$event->id],
        ]);
        $mf = app(ManifestationLifecycle::class)->publishDirectly($mf, $this->editor);

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.edit', $mf))
            ->assertOk()
            ->assertSee('name="cover_file"', false);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => $mf->naziv,
                'cover_file' => $this->uploadJpeg('pub.jpg'),
            ])
            ->assertRedirect();

        $mf->refresh();
        $this->assertNotNull($mf->cover_media_id);
        $this->assertSame(
            CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            CulturalMedia::query()->findOrFail($mf->cover_media_id)->namjena
        );
        $this->assertCoverAudit($mf);

    }

    public function test_pending_editor_cannot_change_cover(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $mf = $this->createModeratorPendingWithCover();
        $coverId = (int) $mf->cover_media_id;

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.edit', $mf))
            ->assertOk()
            ->assertDontSee('name="cover_file"', false);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $mf), [
                'naziv' => $mf->naziv,
                'cover_file' => $this->uploadPng(),
            ])
            ->assertSessionHasErrors('domain');

        $this->assertSame($coverId, (int) $mf->fresh()->cover_media_id);
    }

    public function test_cancel_and_archive_keep_cover(): void
    {
        $event = $this->makePublishedEvent('2024-01-10');
        $mf = app(ManifestationWriter::class)->createDraft($this->editor, [
            'naziv' => 'Keep cover MF',
            'event_entry_ids' => [$event->id],
        ]);
        $media = app(\App\Services\CulturalEventDomain\EventCoverService::class)
            ->ingestCover($this->uploadJpeg(), $this->editor, CulturalMedia::PURPOSE_MANIFESTATION_COVER);
        $mf = app(ManifestationWriter::class)->updateContent($mf, $this->editor, [
            'cover_media_id' => $media->id,
        ]);
        $mf = app(ManifestationLifecycle::class)->publishDirectly($mf, $this->editor);
        $coverId = (int) $mf->cover_media_id;

        $cancelled = app(ManifestationLifecycle::class)->cancel($mf, $this->editor);
        $this->assertTrue($cancelled->isCancelled());
        $this->assertSame($coverId, (int) $cancelled->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $coverId]);

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.edit', $cancelled))
            ->assertOk()
            ->assertDontSee('name="cover_file"', false);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $cancelled), [
                'naziv' => $cancelled->naziv,
                'remove_cover' => '1',
            ])
            ->assertSessionHasErrors('domain');
        $this->assertSame($coverId, (int) $cancelled->fresh()->cover_media_id);

        $archived = app(ManifestationLifecycle::class)->archiveIfEligible($cancelled->fresh());
        $this->assertTrue($archived->isArchived());
        $this->assertSame($coverId, (int) $archived->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $coverId]);

        $this->actingAs($this->editor)
            ->put(route('cultural-manifestations.update', $archived), [
                'naziv' => $archived->naziv,
                'remove_cover' => '1',
            ])
            ->assertSessionHasErrors('domain');
        $this->assertSame($coverId, (int) $archived->fresh()->cover_media_id);
    }

    public function test_no_manifestation_hard_delete_route(): void
    {
        $mf = $this->createEditorDraftWithCover();
        $coverId = (int) $mf->cover_media_id;

        $this->actingAs($this->editor)
            ->delete('/kalendar-kulture/kanonske-manifestacije/'.$mf->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('cultural_manifestations', ['id' => $mf->id]);
        $this->assertDatabaseHas('cultural_media', ['id' => $coverId]);
    }

    private function assertCoverAudit(CulturalManifestation $mf): void
    {
        $record = CulturalActivityRecord::query()
            ->where('event_type', 'mf.cover.change')
            ->where('target_id', $mf->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('mf.cover.change', $record->event_type);
        $this->assertStringStartsWith(CulturalActivityCatalog::MF_11.':', (string) $record->event_id);
        $this->assertSame(['manifestation_id' => (int) $mf->id], $record->context);
        $this->assertArrayNotHasKey('storage_path', $record->context ?? []);
        $this->assertArrayNotHasKey('filename', $record->context ?? []);
    }

    private function createEditorDraftWithCover(): CulturalManifestation
    {
        $this->actingAs($this->editor)
            ->post(route('cultural-manifestations.store'), [
                'naziv' => 'MF cover',
                'cover_file' => $this->uploadJpeg(),
            ]);

        return CulturalManifestation::query()->firstOrFail();
    }

    private function createModeratorPendingWithCover(): CulturalManifestation
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-manifestations.store'), [
                'naziv' => 'Pending MF',
                'cover_file' => $this->uploadJpeg(),
            ]);
        $mf = CulturalManifestation::query()->firstOrFail();
        $event = $this->makePublishedEvent();
        app(ManifestationWriter::class)->linkEvent($mf, $event->id, $this->moderator);

        return app(ManifestationLifecycle::class)->submitForApproval($mf->fresh(), $this->moderator);
    }

    private function makePublishedEvent(string $date = '2026-09-01'): CulturalEventEntry
    {
        $category = CulturalCategory::create([
            'naziv' => 'Kat '.uniqid(),
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
        $entry = app(EventWriter::class)->createDraft($this->editor, [
            'naslov' => 'DG '.uniqid(),
            'category_id' => $category->id,
        ]);
        app(OccurrenceWriter::class)->create($entry, [
            'datum' => $date,
            'cjelodnevno' => true,
        ]);
        app(EventLifecycle::class)->publishDirectly($entry->fresh(), $this->editor);

        return $entry->fresh();
    }

    private function makeOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org MF Cover',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org MF Cover',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }

    private function grantModerator(User $user, CulturalOrganizer $organizer): void
    {
        CulturalModeratorAuthorization::create([
            'user_id' => $user->id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
            'activated_at' => now(),
            'removed_at' => null,
        ]);
    }
}
