<?php

namespace App\Contracts;

use App\Services\ExternalArchive\MegaUploadResult;

interface MegaArchiveClient
{
    /**
     * Upload a single local file into the configured MEGA base folder (no subfolders).
     * Server-side only; credentials never leave the server process environment.
     */
    public function uploadLocalFile(string $absoluteLocalPath, string $generatedFileName): MegaUploadResult;
}
