<?php

namespace Tests\Support;

use App\Contracts\MegaArchiveClient;
use App\Services\ExternalArchive\MegaUploadResult;

final class MegaArchiveFakeClient implements MegaArchiveClient
{
    public int $uploadCalls = 0;

    public ?string $lastUploadAbsolutePath = null;

    public ?int $lastUploadedBytes = null;

    public bool $uploadShouldFail = false;

    /**
     * When non-empty, each upload consumes the next result (for retry tests).
     *
     * @var list<MegaUploadResult>|null
     */
    public ?array $uploadResultsQueue = null;

    public function uploadLocalFile(string $absoluteLocalPath, string $generatedFileName): MegaUploadResult
    {
        if ($this->uploadResultsQueue !== null && $this->uploadResultsQueue !== []) {
            $this->uploadCalls++;
            $this->lastUploadAbsolutePath = $absoluteLocalPath;
            $this->lastUploadedBytes = is_file($absoluteLocalPath) ? (int) filesize($absoluteLocalPath) : null;
            /** @var MegaUploadResult */
            $next = array_shift($this->uploadResultsQueue);

            return $next;
        }

        $this->uploadCalls++;
        $this->lastUploadAbsolutePath = $absoluteLocalPath;
        $this->lastUploadedBytes = is_file($absoluteLocalPath) ? (int) filesize($absoluteLocalPath) : null;
        if ($this->uploadShouldFail) {
            return new MegaUploadResult(false, null, null, 'fake_upload_failed');
        }

        return new MegaUploadResult(true, 'fake-node-'.$this->uploadCalls, 'digital.kotor/'.$generatedFileName, null);
    }
}
