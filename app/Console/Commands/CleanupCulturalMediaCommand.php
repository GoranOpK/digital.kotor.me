<?php

namespace App\Console\Commands;

use App\Services\CulturalMedia\CulturalMediaCleanup;
use App\Services\CulturalMedia\CulturalMediaCleanupReport;
use App\Services\CulturalMedia\CulturalMediaStorage;
use Illuminate\Console\Command;
use Throwable;

class CleanupCulturalMediaCommand extends Command
{
    protected $signature = 'cultural-media:cleanup
                            {--delete : Permanently delete confirmed filesystem orphans under cultural-media/}';

    protected $description = 'Preview (default) or delete orphan files under public/cultural-media/. Manual only.';

    public function handle(CulturalMediaCleanup $cleanup): int
    {
        $apply = (bool) $this->option('delete');

        try {
            $report = $apply ? $cleanup->apply() : $cleanup->inspect();
        } catch (Throwable $e) {
            $this->error('Cleanup scan failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->printReport($report, $apply);

        if ($apply && $report->deleteFailures !== []) {
            $this->error('Neki orphan fajlovi nijesu obrisani. Uspješna brisanja nisu vraćena.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printReport(CulturalMediaCleanupReport $report, bool $apply): void
    {
        $this->line('');
        if ($apply) {
            $this->warn('DELETE MODE: brišu se samo potvrđeni filesystem orphan fajlovi.');
        } else {
            $this->info('DRY RUN / PREVIEW: nijedan fajl nije obrisan.');
        }

        $this->line('Folder: '.CulturalMediaStorage::DISK.':'.CulturalMediaStorage::DIRECTORY.'/');
        $this->line(sprintf('DB references: %d', $report->dbReferences));
        $this->line(sprintf('Physical files: %d', $report->physicalFiles));
        $this->line(sprintf('Filesystem orphans: %d', count($report->filesystemOrphans)));
        $this->line(sprintf('DB missing-file references: %d', count($report->missingFileRows)));
        $this->line(sprintf('Suspicious DB paths: %d', count($report->suspiciousDbPaths)));
        $this->line(sprintf('Deleted: %d', count($report->deleted)));
        $this->line(sprintf('Delete failures: %d', count($report->deleteFailures)));

        $this->printList('Filesystem orphans', $report->filesystemOrphans);
        $this->printList('DB missing-file references (not deleted)', $report->missingFileRows);
        $this->printList('Suspicious DB paths (not used as delete targets)', $report->suspiciousDbPaths);
        $this->printList('Deleted', $report->deleted);
        $this->printList('Delete failures', $report->deleteFailures);
        $this->newLine();
    }

    /**
     * @param  list<string>  $items
     */
    private function printList(string $label, array $items): void
    {
        if ($items === []) {
            return;
        }

        $this->newLine();
        $this->line($label.':');
        foreach ($items as $item) {
            $this->line('  - '.$item);
        }
    }
}
