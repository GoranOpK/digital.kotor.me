<?php

namespace App\Services\ExternalArchive;

use App\Contracts\MegaArchiveClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

final class MegaArchiveService implements MegaArchiveClient
{
    public function __construct(
        private readonly MegaArchiveFailureClassifier $failureClassifier,
    ) {}

    public function uploadLocalFile(string $absoluteLocalPath, string $generatedFileName): MegaUploadResult
    {
        return $this->runNode('upload', [
            'localPath' => $absoluteLocalPath,
            'targetName' => $generatedFileName,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function runNode(string $action, array $payload): MegaUploadResult
    {
        $email = trim((string) config('services.mega.email', ''));
        $password = (string) config('services.mega.password', '');
        if ($email === '' || $password === '') {
            return new MegaUploadResult(false, null, null, 'MEGA_EMAIL / MEGA_PASSWORD not configured.');
        }

        $nodeBinary = trim((string) config('services.mega.node_binary', ''));
        $binary = $nodeBinary !== '' ? $nodeBinary : 'node';
        $userAgent = trim((string) config('services.mega.user_agent', 'DigitalKotorArchive/1.0'));
        if ($userAgent === '') {
            $userAgent = 'DigitalKotorArchive/1.0';
        }

        $script = base_path('scripts/mega-archive.js');
        if (! is_file($script)) {
            return new MegaUploadResult(false, null, null, 'mega-archive.js missing.');
        }

        $timeout = max(1, (int) config('external_archive.upload_timeout_seconds', 900));

        try {
            // Credentials only via process env — never CLI arguments.
            $result = Process::path(base_path())
                ->timeout($timeout)
                ->env([
                    'MEGA_EMAIL' => $email,
                    'MEGA_PASSWORD' => $password,
                    'MEGA_BASE_FOLDER' => (string) config('services.mega.base_folder', 'digital.kotor'),
                    'MEGA_USER_AGENT' => $userAgent,
                ])
                ->input(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
                ->run([$binary, $script, $action]);
        } catch (Throwable $e) {
            Log::warning('mega_archive', [
                'event' => 'mega_node_exception',
                'action' => $action,
                'error' => $this->failureClassifier->sanitize($e->getMessage()),
            ]);

            return new MegaUploadResult(false, null, null, $this->failureClassifier->sanitize($e->getMessage()));
        }

        if (! $result->successful()) {
            $stderr = $result->errorOutput();
            $stdout = $result->output();
            $preview = $this->failureClassifier->sanitize(mb_substr($stderr !== '' ? $stderr : $stdout, 0, 400));
            Log::warning('mega_archive', [
                'event' => 'mega_node_process_failed',
                'action' => $action,
                'exit_code' => $result->exitCode(),
                'error' => $preview,
            ]);

            return new MegaUploadResult(false, null, null, 'MEGA process failed: '.$preview);
        }

        $out = trim($result->output());
        if ($out === '') {
            return new MegaUploadResult(false, null, null, 'Empty MEGA script output.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return new MegaUploadResult(false, null, null, 'Invalid JSON from MEGA script.');
        }

        if (! empty($decoded['ok'])) {
            return new MegaUploadResult(
                true,
                isset($decoded['mega_node_id']) ? (string) $decoded['mega_node_id'] : null,
                isset($decoded['mega_path']) ? (string) $decoded['mega_path'] : null,
                null,
            );
        }

        $error = isset($decoded['error']) ? (string) $decoded['error'] : 'Unknown MEGA error';

        return new MegaUploadResult(
            false,
            null,
            null,
            $this->failureClassifier->sanitize($error),
        );
    }
}
