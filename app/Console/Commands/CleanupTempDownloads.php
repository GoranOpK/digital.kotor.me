<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTempDownloads extends Command
{
    protected $signature = 'documents:cleanup-temp-downloads
                            {--minutes=5 : Briši fajlove starije od ovog broja minuta}
                            {--dry-run : Prikaži šta bi bilo obrisano, bez brisanja}';

    protected $description = 'Briše privremene MEGA download fajlove (temp_downloads) starije od N minuta. Fallback ako deleteFileAfterSend ne uspe.';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        $cutoff = now()->subMinutes($minutes);
        $baseDir = 'temp_downloads';

        if (!Storage::disk('local')->exists($baseDir)) {
            $this->info('Folder temp_downloads ne postoji.');
            return Command::SUCCESS;
        }

        $deleted = 0;
        $freed = 0;

        $files = Storage::disk('local')->allFiles($baseDir);

        foreach ($files as $relPath) {
            $fullPath = Storage::disk('local')->path($relPath);
            if (!is_file($fullPath)) {
                continue;
            }
            $mtime = filemtime($fullPath);
            if ($mtime === false || $mtime >= $cutoff->timestamp) {
                continue;
            }

            $size = filesize($fullPath);
            if ($dryRun) {
                $this->line("  [dry-run] Obrisao bi: {$relPath} (" . $this->formatBytes($size) . ')');
            } else {
                Storage::disk('local')->delete($relPath);
            }
            $deleted++;
            $freed += $size;
        }

        $this->info("Obrisano fajlova: {$deleted}, oslobođeno: " . $this->formatBytes($freed));
        if ($dryRun && $deleted > 0) {
            $this->warn('DRY RUN – ništa nije obrisano.');
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
