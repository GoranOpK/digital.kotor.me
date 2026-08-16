<?php

namespace App\Services\CulturalMedia;

use App\Models\CulturalMedia;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Ručna reconciliation cultural-media/ (MED-23/24/25).
 * Default inspect je non-destructive. Brisanje samo filesystem orphan-a uz eksplicitni apply.
 */
class CulturalMediaCleanup
{
    public function inspect(): CulturalMediaCleanupReport
    {
        return $this->buildReport();
    }

    public function apply(): CulturalMediaCleanupReport
    {
        $report = $this->buildReport();
        $report->applied = true;

        foreach ($report->filesystemOrphans as $relative) {
            if (! $this->storage()->isManagedPath($relative)) {
                $report->deleteFailures[] = $relative;
                continue;
            }

            try {
                if (! $this->deletePublicFile($relative)) {
                    $report->deleteFailures[] = $relative;
                    continue;
                }
                $report->deleted[] = $relative;
            } catch (Throwable) {
                $report->deleteFailures[] = $relative;
            }
        }

        return $report;
    }

    /**
     * @internal Overridable in tests to simulate a single delete failure.
     */
    protected function deletePublicFile(string $relative): bool
    {
        $disk = Storage::disk(CulturalMediaStorage::DISK);
        if (! $disk->exists($relative)) {
            return false;
        }

        return $disk->delete($relative) === true;
    }

    private function buildReport(): CulturalMediaCleanupReport
    {
        $report = new CulturalMediaCleanupReport;
        $storage = $this->storage();
        $disk = Storage::disk(CulturalMediaStorage::DISK);

        $physical = [];
        foreach ($disk->allFiles(CulturalMediaStorage::DIRECTORY) as $relative) {
            $normalized = $this->normalize($relative);
            if (! $storage->isManagedPath($normalized)) {
                continue;
            }
            $physical[$normalized] = true;
        }
        $report->physicalFiles = count($physical);

        $owned = [];
        foreach (CulturalMedia::query()->pluck('storage_path') as $rawPath) {
            $report->dbReferences++;
            $normalized = $this->normalize((string) $rawPath);

            if (! $storage->isManagedPath($normalized)) {
                $report->suspiciousDbPaths[] = (string) $rawPath;
                continue;
            }

            $owned[$normalized] = true;
            if (! isset($physical[$normalized])) {
                $report->missingFileRows[] = $normalized;
            }
        }

        foreach (array_keys($physical) as $relative) {
            if (! isset($owned[$relative])) {
                $report->filesystemOrphans[] = $relative;
            }
        }

        sort($report->filesystemOrphans);
        sort($report->missingFileRows);
        sort($report->suspiciousDbPaths);

        return $report;
    }

    private function normalize(string $path): string
    {
        return str_replace('\\', '/', trim($path));
    }

    private function storage(): CulturalMediaStorage
    {
        return app(CulturalMediaStorage::class);
    }
}
