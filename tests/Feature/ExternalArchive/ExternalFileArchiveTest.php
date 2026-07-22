<?php

namespace Tests\Feature\ExternalArchive;

use App\Contracts\MegaArchiveClient;
use App\Models\ExternalFileArchive;
use App\Services\ExternalArchive\ArchiveFilenameGenerator;
use App\Services\ExternalArchive\ExternalFileArchiveService;
use App\Services\ExternalArchive\MegaUploadResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MegaArchiveFakeClient;
use Tests\TestCase;

class ExternalFileArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-22 10:00:00', 'UTC'));
        config(['external_archive.delete_local_after_upload' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_success_creates_row_uploads_and_keeps_local_by_default(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/test.jpg';
        Storage::disk('local')->put($path, 'binary');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 1, 'file_path', $path, 'document_library');

        $this->assertSame(1, $fake->uploadCalls);
        $this->assertSame(ExternalFileArchive::STATUS_UPLOADED, $row->status);
        $this->assertNotNull($row->archived_at);
        $this->assertNull($row->local_deleted_at);
        $this->assertSame($path, $row->original_local_path);
        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertGreaterThanOrEqual(1, $row->attempts);
        $this->assertNotNull($row->last_attempt_at);
        $this->assertDatabaseHas('external_file_archives', [
            'id' => $row->id,
            'status' => ExternalFileArchive::STATUS_UPLOADED,
            'context_type' => 'document_library',
            'source_column' => 'file_path',
        ]);
    }

    public function test_success_deletes_local_when_config_enabled(): void
    {
        config(['external_archive.delete_local_after_upload' => true]);
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/delete-me.jpg';
        Storage::disk('local')->put($path, 'binary');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 2, 'file_path', $path, 'document_library');

        $this->assertSame(ExternalFileArchive::STATUS_UPLOADED, $row->status);
        $this->assertNotNull($row->local_deleted_at);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_upload_failure_keeps_local_and_marks_failed(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadShouldFail = true;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/fail.jpg';
        Storage::disk('local')->put($path, 'x');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 3, 'file_path', $path, 'document_library');

        $this->assertSame(ExternalFileArchive::STATUS_FAILED, $row->status);
        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertNull($row->local_deleted_at);
        $this->assertSame(1, $fake->uploadCalls);
    }

    public function test_transient_upload_failure_succeeds_on_retry(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadResultsQueue = [
            new MegaUploadResult(false, null, null, 'ETIMEDOUT: connection timed out'),
            new MegaUploadResult(true, 'node-recovered', 'digital.kotor/recovered.pdf', null),
        ];
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/retry-ok.jpg';
        Storage::disk('local')->put($path, 'binary');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 42, 'file_path', $path, 'document_library');

        $this->assertSame(2, $fake->uploadCalls);
        $this->assertSame(ExternalFileArchive::STATUS_UPLOADED, $row->status);
        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertNull($row->local_deleted_at);
        $this->assertSame(2, $row->attempts);
    }

    public function test_permanent_login_failure_does_not_retry_upload(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadResultsQueue = [
            new MegaUploadResult(false, null, null, 'Wrong password for account'),
        ];
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/bad-login.jpg';
        Storage::disk('local')->put($path, 'x');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 43, 'file_path', $path, 'document_library');

        $this->assertSame(1, $fake->uploadCalls);
        $this->assertSame(ExternalFileArchive::STATUS_FAILED, $row->status);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_transient_upload_exhausts_retries_then_failed(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadResultsQueue = [
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
            new MegaUploadResult(false, null, null, 'ETIMEDOUT'),
        ];
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/exhaust.jpg';
        Storage::disk('local')->put($path, 'x');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $row = $svc->archiveLocalPrivateFile('user_documents', 44, 'file_path', $path, 'document_library');

        $this->assertSame(3, $fake->uploadCalls);
        $this->assertSame(ExternalFileArchive::STATUS_FAILED, $row->status);
        $this->assertSame(3, $row->attempts);
    }

    public function test_second_call_returns_existing_uploaded_without_reupload(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/once.jpg';
        Storage::disk('local')->put($path, 'binary');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $first = $svc->archiveLocalPrivateFile('user_documents', 5, 'file_path', $path, 'document_library');
        $second = $svc->archiveLocalPrivateFile('user_documents', 5, 'file_path', $path, 'document_library');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $fake->uploadCalls);
    }

    public function test_generated_names_are_unique_and_safe(): void
    {
        $a = ArchiveFilenameGenerator::generate('document_library', 'user_documents', 1, 'file_path', 'a/b c.pdf');
        $b = ArchiveFilenameGenerator::generate('document_library', 'user_documents', 1, 'file_path', 'a/b c.pdf');

        $this->assertNotSame($a, $b);
        $this->assertDoesNotMatchRegularExpression('/\s/', $a);
        $this->assertStringStartsWith('document_library__user_documents_1__file_path__', $a);
        $this->assertStringEndsWith('.pdf', $a);
    }

    public function test_timestamps_on_row(): void
    {
        Storage::fake('local');
        $this->app->instance(MegaArchiveClient::class, new MegaArchiveFakeClient);

        $path = 'archives/ts.jpg';
        Storage::disk('local')->put($path, 'x');

        $row = $this->app->make(ExternalFileArchiveService::class)
            ->archiveLocalPrivateFile('user_documents', 6, 'file_path', $path, 'document_library');

        $this->assertNotNull($row->created_at);
        $this->assertNotNull($row->updated_at);
        $this->assertNotNull($row->archived_at);
        $this->assertNull($row->local_deleted_at);
    }

    public function test_retry_failed_archive_succeeds(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadShouldFail = true;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/retry-row.jpg';
        Storage::disk('local')->put($path, 'x');

        $svc = $this->app->make(ExternalFileArchiveService::class);
        $failed = $svc->archiveLocalPrivateFile('user_documents', 7, 'file_path', $path, 'document_library');
        $this->assertSame(ExternalFileArchive::STATUS_FAILED, $failed->status);

        $fake->uploadShouldFail = false;
        $retried = $svc->retryFailedArchive($failed->fresh());

        $this->assertSame(ExternalFileArchive::STATUS_UPLOADED, $retried->status);
        $this->assertSame($failed->id, $retried->id);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_db_update_failure_keeps_local_file(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/db-fail.jpg';
        Storage::disk('local')->put($path, 'binary');

        ExternalFileArchive::saving(function (ExternalFileArchive $model): void {
            if ($model->status === ExternalFileArchive::STATUS_UPLOADED) {
                throw new \RuntimeException('forced db failure password=SecretLeak123');
            }
        });

        try {
            $svc = $this->app->make(ExternalFileArchiveService::class);
            $row = $svc->archiveLocalPrivateFile('user_documents', 8, 'file_path', $path, 'document_library');

            $this->assertSame(ExternalFileArchive::STATUS_FAILED, $row->status);
            $this->assertTrue(Storage::disk('local')->exists($path));
            $this->assertNull($row->local_deleted_at);
            $this->assertStringNotContainsString('SecretLeak123', (string) $row->error_message);
            $this->assertStringContainsString('db_update_failed', (string) $row->error_message);
        } finally {
            ExternalFileArchive::flushEventListeners();
        }
    }

    public function test_missing_local_file_does_not_call_mega(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);

        $svc = $this->app->make(ExternalFileArchiveService::class);

        try {
            $svc->archiveLocalPrivateFile('user_documents', 9, 'file_path', 'archives/missing.jpg', 'document_library');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }

        $this->assertSame(0, $fake->uploadCalls);
        $this->assertDatabaseCount('external_file_archives', 0);
    }

    public function test_unsafe_absolute_and_traversal_paths_are_rejected(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $this->app->instance(MegaArchiveClient::class, $fake);
        $svc = $this->app->make(ExternalFileArchiveService::class);

        foreach (['../secret.txt', '/etc/passwd', 'C:/Windows/system32/x', '~/.env'] as $bad) {
            try {
                $svc->archiveLocalPrivateFile('user_documents', 10, 'file_path', $bad, 'document_library');
                $this->fail('Expected rejection for path: '.$bad);
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Unsafe local path', $e->getMessage());
            }
        }

        $this->assertSame(0, $fake->uploadCalls);
    }

    public function test_error_message_is_sanitized_and_truncated(): void
    {
        Storage::fake('local');
        $fake = new MegaArchiveFakeClient;
        $fake->uploadResultsQueue = [
            new MegaUploadResult(
                false,
                null,
                null,
                'Wrong password=SuperSecretMegaPassword-xyz for MEGA_PASSWORD=leak '.str_repeat('z', 800),
            ),
        ];
        $this->app->instance(MegaArchiveClient::class, $fake);

        $path = 'archives/sanitize.jpg';
        Storage::disk('local')->put($path, 'x');

        $row = $this->app->make(ExternalFileArchiveService::class)
            ->archiveLocalPrivateFile('user_documents', 11, 'file_path', $path, 'document_library');

        $this->assertSame(ExternalFileArchive::STATUS_FAILED, $row->status);
        $this->assertStringNotContainsString('SuperSecretMegaPassword-xyz', (string) $row->error_message);
        $this->assertStringNotContainsString('leak', (string) $row->error_message);
        $this->assertLessThanOrEqual(500, mb_strlen((string) $row->error_message));
    }
}
