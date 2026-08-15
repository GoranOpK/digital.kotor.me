<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventChangeProposal;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventChangeProposalApplicator;
use App\Services\CulturalEventDomain\EventChangeProposalLifecycle;
use App\Services\CulturalEventDomain\EventChangeProposalWriter;
use App\Services\CulturalEventDomain\EventCoverBinder;
use App\Services\CulturalEventDomain\EventCoverService;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
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

class CulturalEventCoverLifecycleTest extends TestCase
{
    use CreatesCulturalMediaFixtures;
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
        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_editor_adds_replaces_and_removes_cover_via_upload(): void
    {
        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'Sa coverom',
                'cover_file' => $this->uploadJpeg('prva.jpg'),
            ])
            ->assertRedirect();

        $entry = CulturalEventEntry::query()->firstOrFail();
        $firstId = (int) $entry->cover_media_id;
        $this->assertNotNull($firstId);
        $first = CulturalMedia::query()->findOrFail($firstId);
        $this->assertSame(CulturalMedia::PURPOSE_EVENT_COVER, $first->namjena);
        Storage::disk('public')->assertExists($first->storage_path);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Sa coverom',
                'cover_file' => $this->uploadPng('druga.png'),
            ])
            ->assertRedirect();

        $entry->refresh();
        $secondId = (int) $entry->cover_media_id;
        $this->assertNotSame($firstId, $secondId);
        $this->assertDatabaseMissing('cultural_media', ['id' => $firstId]);
        Storage::disk('public')->assertMissing($first->storage_path);
        Storage::disk('public')->assertExists(CulturalMedia::query()->findOrFail($secondId)->storage_path);

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Sa coverom',
                'remove_cover' => '1',
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertNull($entry->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $secondId]);
    }

    public function test_failed_upload_keeps_existing_cover(): void
    {
        $entry = $this->createEditorDraftWithCover();
        $oldId = (int) $entry->cover_media_id;
        $oldPath = CulturalMedia::query()->findOrFail($oldId)->storage_path;

        $this->actingAs($this->editor)
            ->from(route('cultural-event-entries.edit', $entry))
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => 'Sa coverom',
                'cover_file' => UploadedFile::fake()->createWithContent('x.txt', 'not-an-image'),
            ])
            ->assertSessionHasErrors('cover_file');

        $entry->refresh();
        $this->assertSame($oldId, (int) $entry->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $oldId]);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_event_persist_failure_discards_ingested_cover(): void
    {
        $binder = app(EventCoverBinder::class);

        try {
            $binder->persistDirectEvent(
                ['naslov' => 'Fail'],
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
        $entry = $this->createEditorDraftWithCover();
        $oldId = (int) $entry->cover_media_id;

        $storage = Mockery::mock(CulturalMediaStorage::class)->makePartial();
        $storage->shouldReceive('deletePath')->andThrow(new RuntimeException('disk down'));
        $this->app->instance(CulturalMediaStorage::class, $storage);

        Log::spy();

        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => $entry->naslov,
                'cover_file' => $this->uploadPng('nova.png'),
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertNotNull($entry->cover_media_id);
        $this->assertNotSame($oldId, (int) $entry->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $oldId]);
        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return $message === 'cultural_media_cleanup_file_failed';
        })->atLeast()->once();
    }

    public function test_arbitrary_existing_media_id_cannot_be_linked_from_http(): void
    {
        $foreign = CulturalMedia::create([
            'naziv' => 'Tudja',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => 'a.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => 'cultural-media/foreign.jpg',
            'creator_id' => $this->editor->id,
        ]);

        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'Hijack',
                'cover_media_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('cover_media_id');

        $this->assertDatabaseCount('cultural_event_entries', 0);

        $entry = $this->createEditorDraftWithCover();
        $this->actingAs($this->editor)
            ->put(route('cultural-event-entries.update', $entry), [
                'naslov' => $entry->naslov,
                'cover_media_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('cover_media_id');

        $this->assertNotSame($foreign->id, (int) $entry->fresh()->cover_media_id);
    }

    public function test_moderator_draft_and_returned_cover_is_editable_pending_is_locked(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.create'))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertDontSee('name="cover_media_id"', false)
            ->assertSee('data-kk-cover-dropzone', false);

        $this->actingAs($this->moderator)
            ->post(route('cultural-moderator-events.store'), [
                'naslov' => 'Mod cover',
                'category_id' => $this->category->id,
                'cover_file' => $this->uploadJpeg(),
            ])
            ->assertRedirect();

        $entry = CulturalEventEntry::query()->firstOrFail();
        $coverId = (int) $entry->cover_media_id;
        $this->assertGreaterThan(0, $coverId);

        app(OccurrenceWriter::class)->create($entry, [
            'datum' => now()->addDays(8)->toDateString(),
            'cjelodnevno' => true,
        ]);

        app(EventLifecycle::class)->submitForApproval($entry->fresh(), $this->moderator);
        $entry->refresh();
        $this->assertTrue($entry->isPendingApproval());

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertDontSee('name="cover_file"', false)
            ->assertSee('sadržaj je zaključan', false);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-events.update', $entry), [
                'naslov' => 'Locked',
                'cover_file' => $this->uploadPng(),
            ])
            ->assertForbidden();

        $this->assertSame($coverId, (int) $entry->fresh()->cover_media_id);

        app(EventLifecycle::class)->returnToDraft($entry->fresh(), $this->editor, 'Dopunite');
        $entry->refresh();
        $this->assertTrue($entry->isDraft());

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-events.edit', $entry))
            ->assertOk()
            ->assertSee('name="cover_file"', false);
    }

    public function test_proposal_upload_does_not_change_live_cover_until_approve(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $entry = $this->makePublishedWithCover();
        $liveId = (int) $entry->cover_media_id;
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->moderator);
        $this->assertSame($liveId, (int) $proposal->proposed_cover_media_id);

        $this->actingAs($this->moderator)
            ->get(route('cultural-moderator-proposals.edit', $proposal))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertDontSee('name="proposed_cover_media_id"', false);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => $proposal->proposed_naslov,
                'proposed_category_id' => $this->category->id,
                'cover_file' => $this->uploadPng('pending.png'),
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $proposal->refresh();
        $pendingId = (int) $proposal->proposed_cover_media_id;
        $this->assertNotSame($liveId, $pendingId);
        $this->assertSame($liveId, (int) $entry->fresh()->cover_media_id);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => $proposal->proposed_naslov,
                'proposed_category_id' => $this->category->id,
                'proposed_cover_media_id' => $liveId,
                'tag_ids' => [],
            ])
            ->assertSessionHasErrors('proposed_cover_media_id');
        $this->assertSame($pendingId, (int) $proposal->fresh()->proposed_cover_media_id);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => $proposal->proposed_naslov,
                'proposed_category_id' => $this->category->id,
                'cover_file' => $this->uploadJpeg('pending2.jpg'),
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $proposal->refresh();
        $secondPending = (int) $proposal->proposed_cover_media_id;
        $this->assertNotSame($pendingId, $secondPending);
        $this->assertDatabaseMissing('cultural_media', ['id' => $pendingId]);
        $this->assertSame($liveId, (int) $entry->fresh()->cover_media_id);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => $proposal->proposed_naslov,
                'proposed_category_id' => $this->category->id,
                'remove_cover' => '1',
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $proposal->refresh();
        $this->assertNull($proposal->proposed_cover_media_id);
        $this->assertSame($liveId, (int) $entry->fresh()->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $secondPending]);

        $this->actingAs($this->moderator)
            ->put(route('cultural-moderator-proposals.update', $proposal), [
                'proposed_naslov' => $proposal->proposed_naslov,
                'proposed_category_id' => $this->category->id,
                'cover_file' => $this->uploadJpeg('final.jpg'),
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $pendingFinal = (int) $proposal->fresh()->proposed_cover_media_id;
        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalLifecycle::class)->startReview($proposal->fresh(), $this->editor);
        app(EventChangeProposalApplicator::class)->approve($proposal->fresh(), $this->editor);

        $entry->refresh();
        $this->assertSame($pendingFinal, (int) $entry->cover_media_id);
        $this->assertDatabaseMissing('cultural_media', ['id' => $liveId]);
        $this->assertSame(CulturalEventChangeProposal::STATUS_APPROVED, $proposal->fresh()->status);
    }

    public function test_approve_failure_leaves_live_cover_and_pending_media(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $entry = $this->makePublishedWithCover();
        $liveId = (int) $entry->cover_media_id;
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->moderator);
        $pending = app(EventCoverService::class)->ingestEventCover($this->uploadJpeg(), $this->moderator);
        app(EventChangeProposalWriter::class)->updateDraftContent($proposal, $this->moderator, [
            'proposed_cover_media_id' => $pending->id,
        ]);

        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalLifecycle::class)->startReview($proposal->fresh(), $this->editor);
        $this->category->update(['status' => CulturalCategory::STATUS_INACTIVE]);

        try {
            app(EventChangeProposalApplicator::class)->approve($proposal->fresh(), $this->editor);
            $this->fail('Approve should fail');
        } catch (CulturalEventDomainException) {
        }

        $this->assertSame($liveId, (int) $entry->fresh()->cover_media_id);
        $this->assertSame($pending->id, (int) $proposal->fresh()->proposed_cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $pending->id]);
    }

    public function test_withdraw_and_return_keep_pending_cover_cancel_cleans_it(): void
    {
        CulturalOrganizerContext::set($this->moderator, $this->organizer->id);
        $entry = $this->makePublishedWithCover();
        $liveId = (int) $entry->cover_media_id;
        $proposal = app(EventChangeProposalWriter::class)->createFromPublished($entry, $this->moderator);
        $pending = app(EventCoverService::class)->ingestEventCover($this->uploadJpeg(), $this->moderator);
        app(EventChangeProposalWriter::class)->updateDraftContent($proposal, $this->moderator, [
            'proposed_cover_media_id' => $pending->id,
        ]);

        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalLifecycle::class)->withdraw($proposal->fresh(), $this->moderator);
        $this->assertDatabaseHas('cultural_media', ['id' => $pending->id]);
        $this->assertSame($pending->id, (int) $proposal->fresh()->proposed_cover_media_id);

        app(EventChangeProposalLifecycle::class)->submit($proposal->fresh(), $this->moderator);
        app(EventChangeProposalLifecycle::class)->startReview($proposal->fresh(), $this->editor);
        app(EventChangeProposalLifecycle::class)->returnToDraft(
            $proposal->fresh(),
            $this->editor,
            'Dorada covera'
        );
        $this->assertDatabaseHas('cultural_media', ['id' => $pending->id]);

        app(EventLifecycle::class)->cancel($entry->fresh(), $this->editor, 'Otkaz');
        $this->assertDatabaseMissing('cultural_media', ['id' => $pending->id]);
        $this->assertSame($liveId, (int) $entry->fresh()->cover_media_id);
        $this->assertTrue($entry->fresh()->isCancelled());
    }

    public function test_cancel_and_archive_keep_live_cover(): void
    {
        $entry = $this->makePublishedWithCover();
        $coverId = (int) $entry->cover_media_id;

        app(EventLifecycle::class)->cancel($entry->fresh(), $this->editor, 'Kiša');
        $entry->refresh();
        $this->assertTrue($entry->isCancelled());
        $this->assertSame($coverId, (int) $entry->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $coverId]);

        $archived = app(EventLifecycle::class)->archiveIfEligible($entry->fresh());
        $this->assertSame(CulturalEventEntry::STATUS_ARCHIVED, $archived->status);
        $this->assertSame($coverId, (int) $archived->cover_media_id);
        $this->assertDatabaseHas('cultural_media', ['id' => $coverId]);
    }

    public function test_never_published_delete_cleans_cover(): void
    {
        $entry = $this->createEditorDraftWithCover();
        $coverId = (int) $entry->cover_media_id;
        $path = CulturalMedia::query()->findOrFail($coverId)->storage_path;

        $this->actingAs($this->editor)
            ->delete(route('cultural-event-entries.destroy', $entry))
            ->assertRedirect(route('cultural-event-entries.index'));

        $this->assertDatabaseMissing('cultural_event_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('cultural_media', ['id' => $coverId]);
        Storage::disk('public')->assertMissing($path);
    }

    private function createEditorDraftWithCover(): CulturalEventEntry
    {
        $this->actingAs($this->editor)
            ->post(route('cultural-event-entries.store'), [
                'naslov' => 'Sa coverom',
                'cover_file' => $this->uploadJpeg(),
            ]);

        return CulturalEventEntry::query()->firstOrFail();
    }

    private function makePublishedWithCover(): CulturalEventEntry
    {
        $entry = app(EventWriter::class)->createDraft($this->editor, [
            'naslov' => 'Objavljen cover',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);
        $media = app(EventCoverService::class)->ingestEventCover($this->uploadJpeg(), $this->editor);
        app(EventWriter::class)->updateContent($entry, $this->editor, [
            'cover_media_id' => $media->id,
        ]);
        app(OccurrenceWriter::class)->create($entry->fresh(), [
            'datum' => now()->addDays(12)->toDateString(),
            'cjelodnevno' => true,
        ]);
        app(EventLifecycle::class)->submitForApproval($entry->fresh(), $this->editor);
        app(EventLifecycle::class)->approve($entry->fresh(), $this->editor);

        return $entry->fresh();
    }

    private function makeOrganizer(): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org Cover',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::create([
            'naziv' => 'Org Cover',
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
