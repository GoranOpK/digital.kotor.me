<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class DeleteExpiredDocuments extends Command
{
    protected $signature = 'documents:delete-expired
                            {--dry-run : Prikaži šta bi bilo obrisano, bez brisanja}';

    protected $description = 'Briše dokumente čiji je datum isteka (expires_at) prošao. Lokalne fajlove briše direktno; MEGA fajlove briše preko Node + megajs. Jednom dnevno (cron).';

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
        $megaQueue = [];

        foreach ($docs as $doc) {
            $id = $doc->id;
            $userId = $doc->user_id;
            $name = $doc->name;
            $hasCloud = $doc->cloud_path && str_contains((string) $doc->cloud_path, 'mega.nz');
            $hasLocal = (bool) $doc->file_path;

            if ($dryRun) {
                $this->line("  [dry-run] ID {$id}: {$name}" . ($hasCloud ? ' (MEGA)' : '') . ($hasLocal ? ' (lokalno)' : ''));
                $deleted++;
                continue;
            }

            if ($hasCloud && !$hasLocal) {
                $megaFileName = $doc->mega_file_name ?? null;
                if (!$megaFileName) {
                    $this->warn("  ID {$id}: MEGA dokument bez mega_file_name, preskačem.");
                    Log::warning('Expired MEGA document skipped (no mega_file_name)', ['document_id' => $id, 'name' => $name]);
                    continue;
                }
                $megaQueue[] = ['id' => $id, 'mega_file_name' => $megaFileName];
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

            Log::info('Expired document deleted (local)', [
                'document_id' => $id,
                'user_id' => $userId,
                'name' => $name,
                'local_freed' => $sizeFreed,
            ]);
        }

        if ($megaQueue !== [] && !$dryRun) {
            $queuePath = Storage::disk('local')->path('expired_mega_queue.json');
            Storage::disk('local')->put('expired_mega_queue.json', json_encode($megaQueue, JSON_UNESCAPED_UNICODE));

            $nodeScript = base_path('scripts/delete-expired-mega.js');
            $nodeBinary = env('NODE_BINARY', 'node');
            $this->info('Pokretanje Node skripte za brisanje sa MEGA...');
            $result = Process::path(base_path())->run([$nodeBinary, $nodeScript, $queuePath]);

            if (!$result->successful()) {
                $this->error('Node skripta nije uspela: ' . $result->errorOutput());
                Log::error('delete-expired-mega.js failed', [
                    'output' => $result->output(),
                    'error' => $result->errorOutput(),
                ]);
            } else {
                $this->line($result->output());
            }

            $doneIds = [];
            if (Storage::disk('local')->exists('expired_mega_done.json')) {
                $raw = Storage::disk('local')->get('expired_mega_done.json');
                $done = json_decode($raw, true);
                if (is_array($done)) {
                    $doneIds = array_column($done, 'id');
                }
            }

            foreach ($doneIds as $doneId) {
                $d = UserDocument::find($doneId);
                if ($d) {
                    $d->delete();
                    $deleted++;
                    Log::info('Expired document deleted (MEGA)', [
                        'document_id' => $doneId,
                        'user_id' => $d->user_id,
                        'name' => $d->name,
                    ]);
                }
            }

            Storage::disk('local')->delete(['expired_mega_queue.json', 'expired_mega_done.json']);

            $this->info('MEGA: obrisano ' . count($doneIds) . ' fajlova.');
        }

        $this->info("Ukupno obrisano: {$deleted} dokumenata.");
        if ($localFreed > 0) {
            $this->info('Oslobođeno lokalnog prostora: ' . $this->formatBytes($localFreed));
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
