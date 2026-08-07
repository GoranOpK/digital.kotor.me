<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalMedia;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalMedia\CulturalMediaLinkInspector;
use App\Services\CulturalMedia\CulturalMediaStorage;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

/**
 * TS-008 Korak 1 — katalog Medija (Urednik = kk_admin).
 */
class CulturalMediaCatalogTest extends TestCase
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
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
    }

    public function test_editor_can_access_index(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-media.index'))
            ->assertOk()
            ->assertSee('Katalog medija', false);
    }

    public function test_editor_can_upload_jpeg(): void
    {
        $response = $this->actingAs($this->editor)->post(
            route('cultural-media.store'),
            $this->validPayload($this->uploadJpeg('naslovna.jpg'))
        );

        $response->assertRedirect(route('cultural-media.index'));
        $media = CulturalMedia::query()->first();
        $this->assertNotNull($media);
        $this->assertSame('image/jpeg', $media->mime);
        $this->assertSame('jpeg', $media->format);
        Storage::disk('public')->assertExists($media->storage_path);
        $this->assertStringStartsWith(CulturalMediaStorage::DIRECTORY.'/', $media->storage_path);
    }

    public function test_editor_can_upload_png(): void
    {
        $this->actingAs($this->editor)->post(
            route('cultural-media.store'),
            $this->validPayload($this->uploadPng('naslovna.png'))
        )->assertRedirect(route('cultural-media.index'));

        $media = CulturalMedia::query()->first();
        $this->assertSame('image/png', $media->mime);
        Storage::disk('public')->assertExists($media->storage_path);
    }

    public function test_editor_can_upload_webp(): void
    {
        $this->actingAs($this->editor)->post(
            route('cultural-media.store'),
            $this->validPayload($this->uploadWebp('naslovna.webp'))
        )->assertRedirect(route('cultural-media.index'));

        $media = CulturalMedia::query()->first();
        $this->assertSame('image/webp', $media->mime);
        Storage::disk('public')->assertExists($media->storage_path);
    }

    public function test_file_larger_than_5mb_is_rejected(): void
    {
        $response = $this->actingAs($this->editor)
            ->from(route('cultural-media.create'))
            ->post(route('cultural-media.store'), $this->validPayload(
                UploadedFile::fake()->create('huge.jpg', 5121, 'image/jpeg')
            ));

        $response->assertRedirect(route('cultural-media.create'));
        $response->assertSessionHasErrors('fajl');
        $this->assertSame(0, CulturalMedia::count());
    }

    public function test_disallowed_format_is_rejected(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);

        $response = $this->actingAs($this->editor)
            ->from(route('cultural-media.create'))
            ->post(route('cultural-media.store'), $this->validPayload(
                UploadedFile::fake()->createWithContent('x.gif', $gif)
            ));

        $response->assertRedirect(route('cultural-media.create'));
        $response->assertSessionHasErrors('fajl');
        $this->assertSame(0, CulturalMedia::count());
    }

    public function test_non_image_content_is_rejected(): void
    {
        $response = $this->actingAs($this->editor)
            ->from(route('cultural-media.create'))
            ->post(route('cultural-media.store'), $this->validPayload(
                UploadedFile::fake()->createWithContent('fake.jpg', 'not-a-real-image')
            ));

        $response->assertRedirect(route('cultural-media.create'));
        $response->assertSessionHasErrors('fajl');
        $this->assertSame(0, CulturalMedia::count());
    }

    public function test_editor_can_create_media_record(): void
    {
        $this->actingAs($this->editor)->post(route('cultural-media.store'), $this->validPayload(
            $this->uploadJpeg('a.jpg'),
            [
                'naziv' => '  Ljetnji festival  ',
                'alt_tekst' => 'Alt tekst',
                'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
                'opis' => 'Opis',
                'autor' => 'Autor',
            ]
        ))->assertRedirect(route('cultural-media.index'));

        $media = CulturalMedia::query()->first();
        $this->assertSame('Ljetnji festival', $media->naziv);
        $this->assertSame('Alt tekst', $media->alt_tekst);
        $this->assertSame(CulturalMedia::PURPOSE_EVENT_COVER, $media->namjena);
        $this->assertTrue($media->isActive());
        $this->assertSame($this->editor->id, $media->creator_id);
    }

    public function test_editor_can_update_metadata(): void
    {
        $media = $this->createStoredMedia();

        $this->actingAs($this->editor)->put(route('cultural-media.update', $media), [
            'naziv' => 'Ažurirani naziv',
            'alt_tekst' => 'Novi ALT',
            'namjena' => CulturalMedia::PURPOSE_CATEGORY_DEFAULT,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'opis' => 'Novi opis',
            'autor' => null,
            'izvor' => 'Arhiv',
            'licenca' => 'CC BY',
        ])->assertRedirect(route('cultural-media.index'));

        $media->refresh();
        $this->assertSame('Ažurirani naziv', $media->naziv);
        $this->assertSame(CulturalMedia::PURPOSE_CATEGORY_DEFAULT, $media->namjena);
        $this->assertSame('Arhiv', $media->izvor);
        Storage::disk('public')->assertExists($media->storage_path);
    }

    public function test_editor_can_deactivate_and_reactivate(): void
    {
        $media = $this->createStoredMedia();
        $path = $media->storage_path;

        $this->actingAs($this->editor)
            ->post(route('cultural-media.deactivate', $media))
            ->assertRedirect(route('cultural-media.index'));
        $this->assertTrue($media->fresh()->isInactive());
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->editor)
            ->post(route('cultural-media.activate', $media))
            ->assertRedirect(route('cultural-media.index'));
        $this->assertTrue($media->fresh()->isActive());
        Storage::disk('public')->assertExists($path);
    }

    public function test_deactivation_does_not_delete_file(): void
    {
        $media = $this->createStoredMedia();
        $path = $media->storage_path;

        $this->actingAs($this->editor)->post(route('cultural-media.deactivate', $media));

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('cultural_media', ['id' => $media->id]);
    }

    public function test_hard_delete_removes_record_and_file(): void
    {
        $media = $this->createStoredMedia();
        $path = $media->storage_path;

        $this->actingAs($this->editor)
            ->delete(route('cultural-media.destroy', $media))
            ->assertRedirect(route('cultural-media.index'));

        $this->assertDatabaseMissing('cultural_media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_hard_delete_blocked_when_business_links_exist(): void
    {
        $media = $this->createStoredMedia();
        $path = $media->storage_path;

        $this->mock(CulturalMediaLinkInspector::class, function ($mock) {
            $mock->shouldReceive('hasLinks')->once()->andReturn(true);
        });

        $this->actingAs($this->editor)
            ->from(route('cultural-media.index'))
            ->delete(route('cultural-media.destroy', $media))
            ->assertRedirect(route('cultural-media.index'))
            ->assertSessionHasErrors('medij');

        $this->assertDatabaseHas('cultural_media', ['id' => $media->id]);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * End-to-end: stvarni CulturalEventEntry.cover_media_id blokira hard-delete
     * bez mockovanja CulturalMediaLinkInspector (TS-003 / TS-008).
     */
    public function test_cover_media_used_by_canonical_event_cannot_be_hard_deleted(): void
    {
        $media = $this->createStoredMedia();
        $path = $media->storage_path;
        $this->assertTrue($media->isActive());
        $this->assertSame(CulturalMedia::PURPOSE_EVENT_COVER, $media->namjena);
        Storage::disk('public')->assertExists($path);

        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->editor->id,
            'proposed_moderator_user_id' => $this->editor->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'Org za medij',
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->editor->id,
            'decision_at' => now(),
        ]);

        $organizer = CulturalOrganizer::create([
            'naziv' => 'Org za medij',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);

        $category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);

        $entry = CulturalEventEntry::create([
            'naslov' => 'Kanonski događaj sa naslovnom',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'organizer_id' => $organizer->id,
            'category_id' => $category->id,
            'cover_media_id' => $media->id,
            'created_by' => $this->editor->id,
            'last_modified_by' => $this->editor->id,
        ]);

        $this->assertSame(1, $media->fresh()->businessLinkCount());
        $this->assertSame($media->id, $entry->cover_media_id);

        $this->actingAs($this->editor)
            ->from(route('cultural-media.index'))
            ->delete(route('cultural-media.destroy', $media))
            ->assertRedirect(route('cultural-media.index'))
            ->assertSessionHasErrors('medij');

        $this->assertDatabaseHas('cultural_media', ['id' => $media->id]);
        $this->assertDatabaseHas('cultural_event_entries', [
            'id' => $entry->id,
            'cover_media_id' => $media->id,
        ]);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(1, CulturalMedia::query()->whereKey($media->id)->count());
        $this->assertSame(1, $media->fresh()->businessLinkCount());
    }

    public function test_regular_user_cannot_access_catalog(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('cultural-media.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(UploadedFile $file, array $overrides = []): array
    {
        return array_merge([
            'naziv' => 'Naslovna fotografija',
            'alt_tekst' => 'Opis slike',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'opis' => null,
            'autor' => null,
            'izvor' => null,
            'licenca' => null,
            'fajl' => $file,
        ], $overrides);
    }

    private function createStoredMedia(): CulturalMedia
    {
        $this->actingAs($this->editor)->post(
            route('cultural-media.store'),
            $this->validPayload($this->uploadJpeg('seed.jpg'))
        );

        return CulturalMedia::query()->firstOrFail();
    }
}
