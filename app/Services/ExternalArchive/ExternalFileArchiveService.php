<?php

namespace App\Services\ExternalArchive;

use App\Contracts\MegaArchiveClient;
use App\Models\ExternalFileArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ExternalFileArchiveService
{
    private const UPLOAD_MAX_ATTEMPTS = 3;

    /**
     * Milliseconds to wait after failed attempt N before attempt N+1.
     *
     * @var list<int>
     */
    private const UPLOAD_RETRY_BACKOFF_MS = [1000, 3000];

    public function __construct(
        private readonly MegaArchiveClient $megaClient,
        private readonly MegaArchiveFailureClassifier $failureClassifier,
    ) {}

    /**
     * Archive one private disk file to MEGA.
     * Local file is deleted only after successful upload + DB update to uploaded + config flag.
     *
     * @throws \InvalidArgumentException
     */
    public function archiveLocalPrivateFile(
        string $sourceTable,
        int $sourceId,
        string $sourceColumn,
        string $localPath,
        string $contextType,
    ): ExternalFileArchive {
        $this->assertSafeRelativePath($localPath);

        if ($sourceColumn === '' || $contextType === '') {
            throw new \InvalidArgumentException('source_column and context_type are required.');
        }

        $disk = Storage::disk('local');

        $existing = ExternalFileArchive::query()
            ->where('source_table', $sourceTable)
            ->where('source_id', $sourceId)
            ->where('source_column', $sourceColumn)
            ->where('archive_provider', ExternalFileArchive::PROVIDER_MEGA)
            ->first();

        if ($existing instanceof ExternalFileArchive) {
            if ($existing->status === ExternalFileArchive::STATUS_UPLOADED) {
                return $existing;
            }

            if (! $disk->exists($localPath)) {
                throw new \InvalidArgumentException('Local file does not exist on private disk.');
            }

            $existing->update([
                'status' => ExternalFileArchive::STATUS_PENDING,
                'error_message' => null,
                'mega_node_id' => null,
                'mega_path' => null,
                'archived_at' => null,
                'original_local_path' => $localPath,
                'context_type' => $contextType,
            ]);
            $existing->refresh();

            return $this->runMegaUploadForPendingRow(
                $existing,
                $sourceTable,
                $sourceId,
                $sourceColumn,
                $contextType,
                $localPath,
                $disk->path($localPath),
                (string) $existing->generated_file_name,
                false,
            );
        }

        if (! $disk->exists($localPath)) {
            throw new \InvalidArgumentException('Local file does not exist on private disk.');
        }

        $generated = ArchiveFilenameGenerator::generate(
            $contextType,
            $sourceTable,
            $sourceId,
            $sourceColumn,
            $localPath,
        );

        /** @var ExternalFileArchive $row */
        $row = ExternalFileArchive::query()->create([
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'source_column' => $sourceColumn,
            'context_type' => $contextType,
            'archive_provider' => ExternalFileArchive::PROVIDER_MEGA,
            'generated_file_name' => $generated,
            'mega_node_id' => null,
            'mega_path' => null,
            'original_local_path' => $localPath,
            'local_deleted_at' => null,
            'archived_at' => null,
            'attempts' => 0,
            'last_attempt_at' => null,
            'status' => ExternalFileArchive::STATUS_PENDING,
            'error_message' => null,
        ]);

        return $this->runMegaUploadForPendingRow(
            $row,
            $sourceTable,
            $sourceId,
            $sourceColumn,
            $contextType,
            $localPath,
            $disk->path($localPath),
            $generated,
            false,
        );
    }

    /**
     * Re-run MEGA upload for an existing row in {@see ExternalFileArchive::STATUS_FAILED}.
     *
     * @throws \InvalidArgumentException
     */
    public function retryFailedArchive(ExternalFileArchive $row): ExternalFileArchive
    {
        if ($row->status !== ExternalFileArchive::STATUS_FAILED) {
            throw new \InvalidArgumentException('Retry is only for failed archive rows.');
        }
        if ($row->archive_provider !== ExternalFileArchive::PROVIDER_MEGA) {
            throw new \InvalidArgumentException('Unsupported archive provider.');
        }

        $localPath = (string) $row->original_local_path;
        $this->assertSafeRelativePath($localPath);

        $generated = (string) $row->generated_file_name;
        if ($generated === '') {
            throw new \InvalidArgumentException('Missing generated_file_name.');
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($localPath)) {
            throw new \InvalidArgumentException('Local file does not exist on private disk.');
        }

        $sourceTable = (string) $row->source_table;
        $sourceId = (int) $row->source_id;
        $sourceColumn = (string) $row->source_column;
        $contextType = (string) $row->context_type;

        if ($sourceColumn === '' || $contextType === '') {
            throw new \InvalidArgumentException('source_column and context_type are required on archive row.');
        }

        $row->update([
            'status' => ExternalFileArchive::STATUS_PENDING,
            'error_message' => null,
            'mega_node_id' => null,
            'mega_path' => null,
            'archived_at' => null,
        ]);
        $row->refresh();

        Log::info('external_archive', [
            'event' => 'external_archive_retry_started',
            'external_file_archive_id' => $row->id,
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
        ]);

        return $this->runMegaUploadForPendingRow(
            $row,
            $sourceTable,
            $sourceId,
            $sourceColumn,
            $contextType,
            $localPath,
            $disk->path($localPath),
            $generated,
            true,
        );
    }

    /**
     * @return ExternalFileArchive Fresh row after upload attempt (uploaded or failed).
     */
    private function runMegaUploadForPendingRow(
        ExternalFileArchive $row,
        string $sourceTable,
        int $sourceId,
        string $sourceColumn,
        string $contextType,
        string $localPath,
        string $absolute,
        string $generatedFileName,
        bool $isRetry,
    ): ExternalFileArchive {
        $disk = Storage::disk('local');

        Log::info('external_archive', [
            'event' => 'external_archive_upload_started',
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'source_column' => $sourceColumn,
            'context_type' => $contextType,
            'generated_file_name' => $generatedFileName,
            'original_local_path' => $localPath,
            'external_file_archive_id' => $row->id,
            'retry' => $isRetry,
        ]);

        /** @var MegaUploadResult|null $upload */
        $upload = null;

        for ($attempt = 1; $attempt <= self::UPLOAD_MAX_ATTEMPTS; $attempt++) {
            $row->update([
                'attempts' => (int) $row->attempts + 1,
                'last_attempt_at' => now(),
            ]);
            $row->refresh();

            $upload = $this->megaClient->uploadLocalFile($absolute, $generatedFileName);

            if ($upload->ok) {
                if ($attempt > 1) {
                    Log::info('external_archive', [
                        'event' => 'external_archive_upload_recovered_after_retry',
                        'external_file_archive_id' => $row->id,
                        'attempt' => $attempt,
                    ]);
                }
                break;
            }

            $errorText = $this->failureClassifier->sanitize($upload->error ?? 'upload_failed');
            $isTransient = $this->failureClassifier->isTransient($errorText);
            $isLastAttempt = $attempt >= self::UPLOAD_MAX_ATTEMPTS;

            if ($isLastAttempt || ! $isTransient) {
                $row->update([
                    'status' => ExternalFileArchive::STATUS_FAILED,
                    'error_message' => $errorText,
                ]);
                Log::warning('external_archive', [
                    'event' => 'external_archive_upload_failed',
                    'external_file_archive_id' => $row->id,
                    'source_table' => $sourceTable,
                    'source_id' => $sourceId,
                    'attempts' => $attempt,
                    'max_attempts' => self::UPLOAD_MAX_ATTEMPTS,
                    'reason' => $this->failureClassifier->shortReason($errorText),
                    'transient' => $isTransient,
                ]);

                return $row->refresh();
            }

            Log::info('external_archive', [
                'event' => 'external_archive_upload_retry',
                'external_file_archive_id' => $row->id,
                'attempt' => $attempt,
                'max_attempts' => self::UPLOAD_MAX_ATTEMPTS,
                'reason' => $this->failureClassifier->shortReason($errorText),
            ]);

            $sleepMs = self::UPLOAD_RETRY_BACKOFF_MS[$attempt - 1] ?? 0;
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        if ($upload === null || ! $upload->ok) {
            return $row->refresh();
        }

        try {
            DB::transaction(function () use ($row, $upload): void {
                $row->update([
                    'status' => ExternalFileArchive::STATUS_UPLOADED,
                    'mega_node_id' => $upload->megaNodeId,
                    'mega_path' => $upload->megaPath,
                    'archived_at' => now(),
                    'error_message' => null,
                ]);
            });
        } catch (Throwable $e) {
            $row->update([
                'status' => ExternalFileArchive::STATUS_FAILED,
                'error_message' => $this->failureClassifier->sanitize('db_update_failed: '.$e->getMessage()),
            ]);
            Log::error('external_archive', [
                'event' => 'external_archive_upload_db_failed',
                'external_file_archive_id' => $row->id,
                'error' => $this->failureClassifier->sanitize($e->getMessage()),
            ]);

            return $row->refresh();
        }

        $shouldDelete = (bool) config('external_archive.delete_local_after_upload', false);
        if ($shouldDelete) {
            $deleted = $disk->delete($localPath);
            if ($deleted) {
                $row->update(['local_deleted_at' => now()]);
                Log::info('external_archive', [
                    'event' => 'external_archive_local_deleted',
                    'external_file_archive_id' => $row->id,
                    'original_local_path' => $localPath,
                ]);
            } else {
                Log::warning('external_archive', [
                    'event' => 'external_archive_local_delete_failed',
                    'external_file_archive_id' => $row->id,
                    'original_local_path' => $localPath,
                ]);
            }
        }

        Log::info('external_archive', [
            'event' => 'external_archive_upload_succeeded',
            'external_file_archive_id' => $row->id,
            'mega_path' => $upload->megaPath,
            'local_deleted' => $shouldDelete && $row->local_deleted_at !== null,
            'retry' => $isRetry,
        ]);

        return $row->refresh();
    }

    /**
     * Reject absolute paths, traversal, and null bytes. Relative paths only under the private disk.
     */
    private function assertSafeRelativePath(string $localPath): void
    {
        $path = str_replace('\\', '/', $localPath);

        if ($path === '' || str_contains($path, "\0") || str_contains($path, '..')) {
            throw new \InvalidArgumentException('Unsafe local path rejected.');
        }

        if (str_starts_with($path, '/') || str_starts_with($path, '~')) {
            throw new \InvalidArgumentException('Unsafe local path rejected.');
        }

        if (preg_match('#^[a-zA-Z]:/#', $path) === 1) {
            throw new \InvalidArgumentException('Unsafe local path rejected.');
        }
    }
}
