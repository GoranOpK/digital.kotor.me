<?php

namespace App\Console\Commands;

use App\Services\DocumentProcessor;
use Illuminate\Console\Command;

class RecalculateStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:recalculate {--user-id= : ID korisnika (opciono, ako se ne navede, ažurira sve)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ažurira stvarno iskorišćen prostor za korisnike na osnovu fajlova na disku';

    /**
     * Execute the console command.
     */
    public function handle(DocumentProcessor $documentProcessor): int
    {
        $userId = $this->option('user-id');

        if ($userId) {
            // Ažuriraj samo određenog korisnika
            $this->info("Ažuriranje prostora za korisnika ID: {$userId}");
            $result = $documentProcessor->recalculateUserStorage((int) $userId);
            
            if ($result['updated']) {
                $this->info("✓ Prostor ažuriran: {$result['stored_size']} → {$result['actual_size']} bajtova");
                $this->info("  ({$this->formatBytes($result['stored_size'])} → {$this->formatBytes($result['actual_size'])})");
            } else {
                $this->info("✓ Prostor je već tačan: {$this->formatBytes($result['actual_size'])}");
            }
        } else {
            // Ažuriraj sve korisnike
            $this->info("Ažuriranje prostora za sve korisnike...");
            $users = \App\Models\User::all();
            $updated = 0;
            $total = $users->count();
            
            foreach ($users as $user) {
                $result = $documentProcessor->recalculateUserStorage($user->id);
                if ($result['updated']) {
                    $updated++;
                    $this->line("  Korisnik {$user->id} ({$user->email}): {$this->formatBytes($result['stored_size'])} → {$this->formatBytes($result['actual_size'])}");
                }
            }
            
            $this->info("✓ Ažurirano {$updated} od {$total} korisnika");
        }

        return Command::SUCCESS;
    }

    /**
     * Formatiraj bajte u čitljiv format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
