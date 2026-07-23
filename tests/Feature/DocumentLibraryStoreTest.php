<?php

namespace Tests\Feature;

use App\Contracts\MegaArchiveClient;
use App\Jobs\ProcessDocumentJob;
use App\Models\ExternalFileArchive;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use App\Services\ExternalArchive\MegaUploadResult;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MegaArchiveFakeClient;
use Tests\TestCase;

class DocumentLibraryStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $role = Role::where('name', 'korisnik')->firstOrFail();
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'used_storage_bytes' => 0,
        ]);

        Storage::fake('local');
    }

    public function test_flag_off_does_not_dispatch_archive_and_keeps_legacy_sync_path(): void
    {
        config(['external_archive.library_upload' => false]);
        Bus::fake();

        $outPath = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($outPath, 'pdf');

        $this->mock(DocumentProcessor::class, function ($mock) use ($outPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('processDocument')->andReturn([
                'success' => true,
                'file_path' => $outPath,
                'file_size' => 100,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Test doc',
        ]);

        $response->assertRedirect(route('documents.index'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('external_file_archives', 0);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'status' => 'processed',
        ]);
    }

    public function test_flag_on_always_dispatches_process_document_job(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Queued doc',
        ]);

        $response->assertRedirect(route('documents.index'));
        Bus::assertDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_flag_on_json_request_returns_document_id(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'JSON doc',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', true)
            ->assertJsonStructure(['document_id', 'status', 'message']);

        Bus::assertDispatched(ProcessDocumentJob::class);
    }

    public function test_flag_on_html_redirect_uses_same_flash_message_as_legacy_queue(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'HTML queued',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success', 'Dokument je uspješno otpremljen i poslat na obradu. Status možete pratiti u listi dokumenata.');
    }

    public function test_multi_file_merge_with_flag_on_dispatches_archive_only_job_not_sync_mega(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $files = [
            UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\ncontent-a"),
            UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\ncontent-b"),
        ];

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => $files,
            'category' => 'Ostali dokumenti',
            'name' => 'Merged',
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertSame(0, $fake->uploadCalls);
        Bus::assertDispatched(function (ProcessDocumentJob $job) {
            return $job->archiveOnly === true;
        });
        Bus::assertDispatchedTimes(ProcessDocumentJob::class, 1);
        $this->assertDatabaseCount('user_documents', 1);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged',
            'status' => 'processed',
        ]);
    }

    public function test_multi_file_json_flag_on_returns_queued_contract_not_redirect(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $files = [
            UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\ncontent-a-json"),
            UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\ncontent-b-json"),
        ];

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => $files,
            'category' => 'Ostali dokumenti',
            'name' => 'Merged JSON',
        ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', true)
            ->assertJsonStructure(['document_id', 'status', 'message'])
            ->assertJsonMissing(['redirect']);

        $this->assertFalse($response->isRedirect());
        Bus::assertDispatched(function (ProcessDocumentJob $job) {
            return $job->archiveOnly === true;
        });
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged JSON',
            'status' => 'processed',
        ]);
        $this->assertSame(
            UserDocument::where('user_id', $this->user->id)->value('id'),
            $response->json('document_id')
        );
        $this->assertSame('processed', $response->json('status'));
    }

    public function test_multi_file_html_keeps_existing_success_flash(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nhtml-a"),
                UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nhtml-b"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Merged HTML',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas(
            'success',
            'Fajlovi su uspješno spojeni u jedan PDF dokument i obrađeni.'
        );
    }

    public function test_multi_file_json_storage_error_returns_422_not_redirect(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(false);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nspace-a"),
                UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nspace-b"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'No space JSON',
        ]);

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['message', 'errors']);

        $this->assertFalse($response->isRedirect());
        $this->assertStringContainsString('Nemate dovoljno prostora', (string) $response->json('message'));
        $this->assertStringContainsString('Nemate dovoljno prostora', (string) data_get($response->json(), 'errors.files.0'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
    }

    public function test_multi_file_html_storage_error_keeps_session_errors(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(false);
        });

        $response = $this->actingAs($this->user)->from(route('documents.index'))->post(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nspace-html-a"),
                UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nspace-html-b"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'No space HTML',
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHasErrors('files');
        Bus::assertNotDispatched(ProcessDocumentJob::class);
    }

    public function test_multi_file_json_flag_off_returns_processed_contract(): void
    {
        config(['external_archive.library_upload' => false]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nflag-off-a"),
                UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nflag-off-b"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Merged flag off',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'processed')
            ->assertJsonStructure(['document_id', 'status', 'message'])
            ->assertJsonMissing(['queued']);

        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Merged flag off',
            'status' => 'processed',
        ]);
    }

    public function test_duplicate_same_filename_and_content_is_rejected(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
            $mock->shouldNotReceive('processDocument');
        });

        $body = "%PDF-1.4\nsame-bytes";
        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('dup.pdf', $body),
                UploadedFile::fake()->createWithContent('dup.pdf', $body),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Dup same name',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Isti fajl je dodat više puta. Uklonite duplikate i pokušajte ponovo.',
            (string) $response->json('message')
        );
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->user->refresh();
        $this->assertSame(0, (int) $this->user->used_storage_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles('documents/user_'.$this->user->id));
    }

    public function test_duplicate_same_content_different_filenames_is_rejected(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
        });

        $body = "%PDF-1.4\nidentical-payload";
        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('one.pdf', $body),
                UploadedFile::fake()->createWithContent('two.pdf', $body),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Dup different names',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Isti fajl je dodat više puta',
            (string) $response->json('message')
        );
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->user->refresh();
        $this->assertSame(0, (int) $this->user->used_storage_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles('documents/user_'.$this->user->id));
    }

    public function test_same_filename_different_content_is_allowed(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->once()->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('same-name.pdf', "%PDF-1.4\nversion-one"),
                UploadedFile::fake()->createWithContent('same-name.pdf', "%PDF-1.4\nversion-two"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Same name ok',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        Bus::assertDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 1);
    }

    public function test_distinct_files_multi_upload_still_works(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->once()->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 300,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('page1.pdf', "%PDF-1.4\npage-1"),
                UploadedFile::fake()->createWithContent('page2.pdf', "%PDF-1.4\npage-2-unique"),
                UploadedFile::fake()->createWithContent('page3.pdf', "%PDF-1.4\npage-3"),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Three distinct',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $this->user->id,
            'name' => 'Three distinct',
            'status' => 'processed',
        ]);
    }

    public function test_image_same_pixels_different_metadata_is_rejected(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required for normalized image duplicate tests');
        }

        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
        });

        $pngA = $this->fixturePngRed1x1();
        $pngB = $this->pngWithTextChunk($pngA, 'Comment', 'capture-metadata-b');
        $this->assertNotSame($pngA, $pngB);
        $this->assertNotSame(hash('sha256', $pngA), hash('sha256', $pngB));

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('capture01.png', $pngA),
                UploadedFile::fake()->createWithContent('capture05.png', $pngB),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Same pixels meta',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Isti fajl je dodat više puta. Uklonite duplikate i pokušajte ponovo.',
            (string) $response->json('message')
        );
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->user->refresh();
        $this->assertSame(0, (int) $this->user->used_storage_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles('documents/user_'.$this->user->id));
    }

    public function test_image_same_dimensions_different_pixels_is_allowed(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required for normalized image duplicate tests');
        }

        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, 'merged');

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('mergeDocuments')->once()->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 200,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('a.png', $this->fixturePngRed1x1()),
                UploadedFile::fake()->createWithContent('b.png', $this->fixturePngBlue1x1()),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Different pixels',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('user_documents', 1);
    }

    public function test_identical_binary_images_still_rejected_by_sha256(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
        });

        $png = $this->fixturePngRed1x1();

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('same.png', $png),
                UploadedFile::fake()->createWithContent('same-copy.png', $png),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Binary identical images',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Isti fajl je dodat više puta', (string) $response->json('message'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
    }

    public function test_corrupt_image_duplicate_check_returns_controlled_validation_error(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required');
        }

        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
        });

        $valid = $this->fixturePngRed1x1();
        $corrupt = "\x89PNG\r\n\x1a\n".str_repeat('x', 64);

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('ok.png', $valid),
                UploadedFile::fake()->createWithContent('bad.png', $corrupt),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Corrupt image',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Jedna od slika nije validna ili se ne može pročitati.',
            (string) $response->json('message')
        );
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->user->refresh();
        $this->assertSame(0, (int) $this->user->used_storage_bytes);
    }

    public function test_oversized_image_dimensions_are_rejected_without_side_effects(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required');
        }

        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldNotReceive('hasEnoughStorage');
            $mock->shouldNotReceive('updateUserStorage');
            $mock->shouldNotReceive('mergeDocuments');
        });

        $img = new \Imagick();
        $img->newImage(\App\Services\DocumentImageFingerprint::MAX_SIDE + 1, 8, new \ImagickPixel('red'));
        $img->setImageFormat('png');
        $blob = $img->getImageBlob();
        $img->clear();
        $img->destroy();

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('small.png', $this->fixturePngRed1x1()),
                UploadedFile::fake()->createWithContent('huge.png', $blob),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Huge image',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('nedozvoljene dimenzije', (string) $response->json('message'));
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->user->refresh();
        $this->assertSame(0, (int) $this->user->used_storage_bytes);
    }

    /** Deterministic 1×1 red PNG (no random metadata). */
    private function fixturePngRed1x1(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
    }

    /** Deterministic 1×1 blue PNG — same size, different pixels. */
    private function fixturePngBlue1x1(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==',
            true
        );
    }

    /**
     * Insert a PNG tEXt chunk before IEND — changes binary hash, not pixels.
     */
    private function pngWithTextChunk(string $pngBytes, string $keyword, string $text): string
    {
        $iend = strpos($pngBytes, 'IEND');
        $this->assertNotFalse($iend);
        $insertAt = $iend - 4;
        $data = $keyword."\0".$text;
        $chunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

        return substr($pngBytes, 0, $insertAt).$chunk.substr($pngBytes, $insertAt);
    }
}
