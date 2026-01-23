<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class DeleteExpiredDocuments extends Command
{
    protected $signature = 'documents:delete-expired
                            {--dry-run : Prikaži šta bi bilo obrisano, bez brisanja}';

    protected $description = 'Briše dokumente čiji je datum isteka (expires_at) prošao. Jednom dnevno (cron).';

    public function __construct(
        protected DocumentProcessor $documentProcessor
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Brisanje isteklih dokumenata...');
        if ($dryRun) {
            $this->warn('DRY RUN – ništa neće biti obrisano.');
        }

        $query = UserDocument::whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        $docs = $query->get();
        $count = $docs->count();

        if ($count === 0) {
            $this->info('Nema isteklih dokumenata.');
            return Command::SUCCESS;
        }

        $this->info("Pronađeno isteklih dokumenata: {$count}");

        $deleted = 0;
        $localFreed = 0;
        $cloudOnly = 0;

        foreach ($docs as $doc) {
            $id = $doc->id;
            $userId = $doc->user_id;
            $name = $doc->name;
            $hasCloud = $doc->cloud_path && str_contains((string) $doc->cloud_path, 'mega.nz');
            $hasLocal = (bool) $doc->file_path;

            if ($dryRun) {
                $this->line("  [dry-run] ID {$id}: {$name}" . ($hasCloud ? ' (MEGA)' : '') . ($hasLocal ? ' (lokalno)' : ''));
                $deleted++;
                if ($hasCloud && !$hasLocal) {
                    $cloudOnly++;
                }
                continue;
            }

            $sizeFreed = 0;

            if ($doc->file_path && Storage::disk('local')->exists($doc->file_path)) {
                $sizeFreed += Storage::disk('local')->size($doc->file_path);
                Storage::disk('local')->delete($doc->file_path);
            }
            if ($doc->original_file_path && Storage::disk('local')->exists($doc->original_file_path)) {
                $sizeFreed += Storage::disk('local')->size($doc->original_file_path);
                Storage::disk('local')->delete($doc->original_file_path);
            }

            if ($sizeFreed > 0 && Schema::hasColumn('users', 'used_storage_bytes')) {
                $this->documentProcessor->updateUserStorage($userId, -$sizeFreed);
            }

            $doc->delete();
            $deleted++;
            $localFreed += $sizeFreed;
            if ($hasCloud && !$hasLocal) {
                $cloudOnly++;
            }

            Log::info('Expired document deleted', [
                'document_id' => $id,
                'user_id' => $userId,
                'name' => $name,
                'had_cloud' => $hasCloud,
                'local_freed' => $sizeFreed,
            ]);
        }

        $this->info("Obrisano: {$deleted} dokumenata.");
        if ($localFreed > 0) {
            $this->info('Oslobođeno lokalnog prostora: ' . $this->formatBytes($localFreed));
        }
        if ($cloudOnly > 0) {
            $this->warn("{$cloudOnly} dokumenata bilo je samo na MEGA. Fajlovi na MEGA se ne brišu iz cron-a (koristi se megajs u browser-u). Obrisani su samo zapisi iz baze.");
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
