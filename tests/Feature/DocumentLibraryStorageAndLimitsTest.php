<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentJob;
use App\Models\ExternalFileArchive;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLibraryStorageAndLimitsTest extends TestCase
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

    public function test_recalculate_sums_file_size_of_active_documents(): void
    {
        UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'A',
            'file_path' => null,
            'cloud_path' => 'https://mega.nz/file/a',
            'file_size' => 1000,
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'B',
            'file_path' => null,
            'cloud_path' => 'https://mega.nz/file/b',
            'file_size' => 2500,
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Failed',
            'file_size' => 9999,
            'status' => 'failed',
        ]);

        $result = app(DocumentProcessor::class)->recalculateUserStorage($this->user->id);

        $this->assertSame(3500, $result['actual_size']);
        $this->assertTrue($result['updated']);
        $this->user->refresh();
        $this->assertSame(3500, (int) $this->user->used_storage_bytes);
    }

    public function test_external_archive_row_does_not_add_to_quota(): void
    {
        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Local',
            'file_path' => 'documents/user_'.$this->user->id.'/a.pdf',
            'file_size' => 4000,
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        Storage::disk('local')->put($document->file_path, str_repeat('a', 4000));

        ExternalFileArchive::create([
            'source_table' => 'user_documents',
            'source_id' => $document->id,
            'source_column' => 'file_path',
            'context_type' => 'document_library',
            'archive_provider' => ExternalFileArchive::PROVIDER_MEGA,
            'generated_file_name' => 'archive.pdf',
            'original_local_path' => $document->file_path,
            'status' => ExternalFileArchive::STATUS_UPLOADED,
        ]);

        $result = app(DocumentProcessor::class)->recalculateUserStorage($this->user->id);

        $this->assertSame(4000, $result['actual_size']);
    }

    public function test_flag_on_single_file_records_original_bytes_then_job_can_replace(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage')->once();
            $mock->shouldReceive('recalculateUserStorage')->andReturn([
                'actual_size' => 0,
                'stored_size' => 0,
                'updated' => false,
            ]);
        });

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Sized',
        ])->assertOk();

        $document = UserDocument::where('name', 'Sized')->firstOrFail();
        $this->assertSame(100 * 1024, (int) $document->file_size);
        Bus::assertDispatched(ProcessDocumentJob::class);
    }

    public function test_multi_file_stores_final_pdf_size_not_sum_of_inputs(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, str_repeat('x', 3500));

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath) {
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
            $mock->shouldReceive('recalculateUserStorage')->andReturn([
                'actual_size' => 0,
                'stored_size' => 0,
                'updated' => false,
            ]);
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 3500,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $this->actingAs($this->user)->post(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Merged size',
        ])->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('user_documents', [
            'name' => 'Merged size',
            'file_size' => 3500,
            'status' => 'processed',
        ]);
    }

    public function test_single_image_over_2mb_is_rejected_with_clear_message(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $file = UploadedFile::fake()->create('big.jpg', 2049, 'image/jpeg');

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Too big image',
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $flat = collect($errors)->flatten()->implode(' ');
        $this->assertStringContainsString('Svaka pojedinačna slika može imati najviše 2 MB.', $flat);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
    }

    public function test_pdf_over_2mb_under_20mb_is_accepted(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $file = UploadedFile::fake()->create('mid.pdf', 3000, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Mid PDF',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        Bus::assertDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 1);
    }

    public function test_pdf_over_20mb_is_rejected(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        $file = UploadedFile::fake()->create('huge.pdf', 20481, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [$file],
            'category' => 'Ostali dokumenti',
            'name' => 'Huge PDF',
        ]);

        $response->assertStatus(422);
        $flat = collect($response->json('errors'))->flatten()->implode(' ');
        $this->assertStringContainsString('PDF dokument može imati najviše 20 MB.', $flat);
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
    }

    public function test_frontend_help_text_distinguishes_image_and_pdf_limits(): void
    {
        $response = $this->actingAs($this->user)->get(route('documents.index'));
        $response->assertOk();
        $response->assertSee('max 2 MB po slici', false);
        $response->assertSee('max 20 MB po dokumentu', false);
        $response->assertSee('PDF dokument može imati najviše 20 MB.', false);
        $response->assertSee('Svaka pojedinačna slika može imati najviše 2 MB.', false);
    }

    public function test_multi_file_quota_overflow_cleans_up_without_archive_job(): void
    {
        config(['external_archive.library_upload' => true]);
        Bus::fake();

        // Dovoljno prostora za originale (+20% estimate), ali ne za veći finalni PDF.
        $this->user->update([
            'used_storage_bytes' => DocumentProcessor::MAX_STORAGE_PER_USER - 130000,
        ]);

        $mergedPath = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($mergedPath, str_repeat('y', 140000));

        $real = app(DocumentProcessor::class);

        $this->mock(DocumentProcessor::class, function ($mock) use ($mergedPath, $real) {
            $mock->shouldReceive('hasEnoughStorage')->andReturnUsing(function (int $userId, int $bytes) use ($real) {
                return $real->hasEnoughStorage($userId, $bytes);
            });
            $mock->shouldReceive('updateUserStorage')->andReturnUsing(function (int $userId, int $bytes) use ($real) {
                $real->updateUserStorage($userId, $bytes);
            });
            $mock->shouldReceive('recalculateUserStorage')->andReturnUsing(function (int $userId) use ($real) {
                return $real->recalculateUserStorage($userId);
            });
            $mock->shouldReceive('mergeDocuments')->andReturn([
                'success' => true,
                'file_path' => $mergedPath,
                'file_size' => 140000,
                'cloud_path' => null,
                'error' => null,
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('documents.store'), [
            'files' => [
                UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'),
                UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'),
            ],
            'category' => 'Ostali dokumenti',
            'name' => 'Over quota',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'prekoračena ukupna kvota od 20 MB',
            (string) $response->json('message')
        );
        Bus::assertNotDispatched(ProcessDocumentJob::class);
        $this->assertDatabaseCount('user_documents', 0);
        $this->assertFalse(Storage::disk('local')->exists($mergedPath));
    }

    public function test_processing_banner_message_after_reload(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('documents.index', [
                'library_upload_success' => 1,
                'processing' => 1,
            ])
        );

        $response->assertOk();
        $response->assertSee('Dokument je uspješno otpremljen. Obrada je u toku.', false);
        $response->assertDontSee('Dokument je poslat na obradu.', false);
    }

    public function test_processed_banner_message_after_reload(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('documents.index', [
                'library_upload_success' => 1,
                'processed' => 1,
            ])
        );

        $response->assertOk();
        $response->assertSee('Dokument je uspješno sačuvan.', false);
    }
}
