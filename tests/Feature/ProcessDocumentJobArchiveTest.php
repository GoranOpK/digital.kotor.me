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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MegaArchiveFakeClient;
use Tests\TestCase;

class ProcessDocumentJobArchiveTest extends TestCase
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

    public function test_flag_off_does_not_call_mega_archive(): void
    {
        config(['external_archive.library_upload' => false]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $original = 'documents/user_'.$this->user->id.'/orig.pdf';
        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($original, 'original');
        Storage::disk('local')->put($processed, 'processed-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'No archive',
            'original_file_path' => $original,
            'original_filename' => 'orig.pdf',
            'file_size' => 10,
            'status' => 'pending',
        ]);

        $this->mockProcessorSuccess($processed, 20);

        (new ProcessDocumentJob($document, $original))->handle(
            $this->app->make(DocumentProcessor::class),
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame(0, $fake->uploadCalls);
        $this->assertDatabaseCount('external_file_archives', 0);
        $this->assertSame('processed', $document->fresh()->status);
    }

    public function test_archive_success_keeps_processed_and_local_file(): void
    {
        config([
            'external_archive.library_upload' => true,
            'external_archive.delete_local_after_upload' => false,
        ]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $original = 'documents/user_'.$this->user->id.'/orig.pdf';
        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($original, 'original');
        Storage::disk('local')->put($processed, 'processed-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Archive ok',
            'original_file_path' => $original,
            'original_filename' => 'orig.pdf',
            'file_size' => 10,
            'status' => 'pending',
        ]);

        $this->mockProcessorSuccess($processed, 20);

        (new ProcessDocumentJob($document, $original))->handle(
            $this->app->make(DocumentProcessor::class),
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $document->refresh();
        $this->assertSame('processed', $document->status);
        $this->assertTrue(Storage::disk('local')->exists($processed));
        $this->assertSame(1, $fake->uploadCalls);
        $this->assertDatabaseHas('external_file_archives', [
            'source_table' => 'user_documents',
            'source_id' => $document->id,
            'status' => ExternalFileArchive::STATUS_UPLOADED,
        ]);
    }

    public function test_archive_fail_keeps_user_document_processed_when_local_pdf_exists(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $fake->uploadShouldFail = true;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $original = 'documents/user_'.$this->user->id.'/orig.pdf';
        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($original, 'original');
        Storage::disk('local')->put($processed, 'processed-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Archive soft-fail',
            'original_file_path' => $original,
            'original_filename' => 'orig.pdf',
            'file_size' => 10,
            'status' => 'pending',
        ]);

        $this->mockProcessorSuccess($processed, 20);

        (new ProcessDocumentJob($document, $original))->handle(
            $this->app->make(DocumentProcessor::class),
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame('processed', $document->fresh()->status);
        $this->assertTrue(Storage::disk('local')->exists($processed));
        $this->assertDatabaseHas('external_file_archives', [
            'source_id' => $document->id,
            'status' => ExternalFileArchive::STATUS_FAILED,
        ]);
    }

    public function test_already_uploaded_archive_skips_mega_upload_on_rerun(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($processed, 'processed-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Already archived',
            'file_path' => $processed,
            'file_size' => 20,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        ExternalFileArchive::query()->create([
            'source_table' => 'user_documents',
            'source_id' => $document->id,
            'source_column' => 'file_path',
            'context_type' => 'document_library',
            'archive_provider' => ExternalFileArchive::PROVIDER_MEGA,
            'generated_file_name' => 'document_library__user_documents_'.$document->id.'__file_path__abc.pdf',
            'mega_node_id' => 'node-1',
            'mega_path' => 'digital.kotor/already.pdf',
            'original_local_path' => $processed,
            'status' => ExternalFileArchive::STATUS_UPLOADED,
            'archived_at' => now(),
            'attempts' => 1,
        ]);

        $processor = $this->createMock(DocumentProcessor::class);
        $processor->expects($this->never())->method('processDocument');

        (new ProcessDocumentJob($document, 'documents/missing.pdf'))->handle(
            $processor,
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame(0, $fake->uploadCalls);
        $this->assertSame('processed', $document->fresh()->status);
    }

    public function test_missing_original_file_marks_failed_without_mega(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Missing',
            'original_file_path' => 'documents/user_'.$this->user->id.'/gone.pdf',
            'original_filename' => 'gone.pdf',
            'file_size' => 10,
            'status' => 'pending',
        ]);

        (new ProcessDocumentJob($document, 'documents/user_'.$this->user->id.'/gone.pdf'))->handle(
            $this->app->make(DocumentProcessor::class),
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame('failed', $document->fresh()->status);
        $this->assertSame(0, $fake->uploadCalls);
    }

    public function test_retry_after_archive_failure_reuses_processed_pdf(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $fake->uploadResultsQueue = [
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
            new MegaUploadResult(true, 'node-ok', 'digital.kotor/retry.pdf', null),
        ];
        $this->app->instance(MegaArchiveClient::class, $fake);

        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($processed, 'processed-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Retry',
            'file_path' => $processed,
            'file_size' => 20,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        ExternalFileArchive::query()->create([
            'source_table' => 'user_documents',
            'source_id' => $document->id,
            'source_column' => 'file_path',
            'context_type' => 'document_library',
            'archive_provider' => ExternalFileArchive::PROVIDER_MEGA,
            'generated_file_name' => 'document_library__user_documents_'.$document->id.'__file_path__retry.pdf',
            'original_local_path' => $processed,
            'status' => ExternalFileArchive::STATUS_FAILED,
            'error_message' => 'previous',
            'attempts' => 3,
        ]);

        $processor = $this->createMock(DocumentProcessor::class);
        $processor->expects($this->never())->method('processDocument');

        (new ProcessDocumentJob($document, 'unused.pdf'))->handle(
            $processor,
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );
        $this->assertSame('processed', $document->fresh()->status);
        $this->assertSame(ExternalFileArchive::STATUS_FAILED, ExternalFileArchive::query()
            ->where('source_id', $document->id)->value('status'));

        (new ProcessDocumentJob($document->fresh(), 'unused.pdf'))->handle(
            $processor,
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame('processed', $document->fresh()->status);
        $this->assertSame(ExternalFileArchive::STATUS_UPLOADED, ExternalFileArchive::query()
            ->where('source_id', $document->id)->value('status'));
    }

    public function test_job_registers_without_overlapping_for_document_id(): void
    {
        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Lock',
            'file_size' => 1,
            'status' => 'pending',
        ]);

        $job = new ProcessDocumentJob($document, 'documents/x.pdf');
        $middleware = $job->middleware();

        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);

        $other = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Lock other',
            'file_size' => 1,
            'status' => 'pending',
        ]);
        $otherMiddleware = (new ProcessDocumentJob($other, 'documents/y.pdf'))->middleware();
        $this->assertNotSame(
            spl_object_id($middleware[0]),
            spl_object_id($otherMiddleware[0]),
        );
    }

    public function test_pdf_processing_fail_marks_document_failed(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $original = 'documents/user_'.$this->user->id.'/orig.pdf';
        Storage::disk('local')->put($original, 'original');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'PDF fail',
            'original_file_path' => $original,
            'original_filename' => 'orig.pdf',
            'file_size' => 10,
            'status' => 'pending',
        ]);

        $this->mock(DocumentProcessor::class, function ($mock) {
            $mock->shouldReceive('processDocument')->once()->andReturn([
                'success' => false,
                'file_path' => null,
                'file_size' => null,
                'cloud_path' => null,
                'error' => 'bad pdf',
            ]);
        });

        (new ProcessDocumentJob($document, $original))->handle(
            $this->app->make(DocumentProcessor::class),
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame('failed', $document->fresh()->status);
        $this->assertSame(0, $fake->uploadCalls);
    }

    public function test_deleted_document_exits_quietly(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Gone',
            'file_size' => 1,
            'status' => 'pending',
        ]);
        $id = $document->id;
        $document->delete();

        $processor = $this->createMock(DocumentProcessor::class);
        $processor->expects($this->never())->method('processDocument');

        (new ProcessDocumentJob($document, 'documents/x.pdf'))->handle(
            $processor,
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $this->assertSame(0, $fake->uploadCalls);
        $this->assertDatabaseMissing('user_documents', ['id' => $id]);
    }

    public function test_archive_only_does_not_set_processing_or_reprocess_pdf(): void
    {
        config(['external_archive.library_upload' => true]);

        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $processed = 'documents/user_'.$this->user->id.'/merged.pdf';
        Storage::disk('local')->put($processed, 'merged-pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Archive only',
            'file_path' => $processed,
            'file_size' => 20,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $processor = $this->createMock(DocumentProcessor::class);
        $processor->expects($this->never())->method('processDocument');

        (new ProcessDocumentJob($document, $processed, true))->handle(
            $processor,
            $this->app->make(\App\Services\ExternalArchive\ExternalFileArchiveService::class),
        );

        $document->refresh();
        $this->assertSame('processed', $document->status);
        $this->assertSame(1, $fake->uploadCalls);
        $this->assertTrue(Storage::disk('local')->exists($processed));
        $this->assertSame($processed, $document->file_path);
    }

    public function test_failed_callback_does_not_overwrite_processed_after_archive_only(): void
    {
        $processed = 'documents/user_'.$this->user->id.'/out.pdf';
        Storage::disk('local')->put($processed, 'pdf');

        $document = UserDocument::create([
            'user_id' => $this->user->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Keep processed',
            'file_path' => $processed,
            'file_size' => 20,
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        (new ProcessDocumentJob($document, $processed, true))
            ->failed(new \RuntimeException('queue worker died'));

        $this->assertSame('processed', $document->fresh()->status);
    }

    private function mockProcessorSuccess(string $processedPath, int $size): void
    {
        $this->mock(DocumentProcessor::class, function ($mock) use ($processedPath, $size) {
            $mock->shouldReceive('processDocument')->once()->andReturn([
                'success' => true,
                'file_path' => $processedPath,
                'file_size' => $size,
                'cloud_path' => null,
                'error' => null,
            ]);
            $mock->shouldReceive('hasEnoughStorage')->andReturn(true);
            $mock->shouldReceive('updateUserStorage');
        });
    }
}
