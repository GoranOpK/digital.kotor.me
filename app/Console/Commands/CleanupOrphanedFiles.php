<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:cleanup-orphaned
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--user= : Only cleanup files for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned files that are no longer referenced in database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user');

        $this->info('Starting orphaned files cleanup...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
        }

        $deletedCount = 0;
        $totalSize = 0;

        // Ako je naveden user_id, samo proveri taj user
        if ($userId) {
            $this->info("Checking files for user ID: {$userId}");
            $result = $this->cleanupUserFiles($userId, $dryRun);
            $deletedCount += $result['count'];
            $totalSize += $result['size'];
        } else {
            // Prolazi kroz sve foldere korisnika
            $documentsPath = 'documents';
            if (!Storage::disk('local')->exists($documentsPath)) {
                $this->info('No documents folder found.');
                return Command::SUCCESS;
            }

            $userFolders = Storage::disk('local')->directories($documentsPath);
            
            foreach ($userFolders as $userFolder) {
                // Izvuci user_id iz foldera: documents/user_7 -> 7
                if (preg_match('/user_(\d+)/', $userFolder, $matches)) {
                    $folderUserId = (int)$matches[1];
                    $this->info("Checking files for user ID: {$folderUserId}");
                    
                    $result = $this->cleanupUserFiles($folderUserId, $dryRun);
                    $deletedCount += $result['count'];
                    $totalSize += $result['size'];
                }
            }
        }

        $this->info("\nCleanup completed!");
        $this->info("Files deleted: {$deletedCount}");
        $this->info("Total size freed: " . $this->formatBytes($totalSize));

        if ($dryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to actually delete files.");
        }

        return Command::SUCCESS;
    }

    /**
     * Cleanup files for specific user
     */
    private function cleanupUserFiles(int $userId, bool $dryRun): array
    {
        $deletedCount = 0;
        $totalSize = 0;

        // Pronađi sve dokumente za ovog korisnika
        $documents = UserDocument::where('user_id', $userId)->get();
        
        // Napravi listu validnih putanja
        $validPaths = [];
        foreach ($documents as $doc) {
            // Dodaj obrađeni fajl (file_path) - uvek je validan ako postoji
            if ($doc->file_path) {
                $validPaths[] = $doc->file_path;
            }
            
            // Originalni fajl (original_file_path) je validan SAMO ako dokument NIJE obrađen
            // Ako je dokument obrađen (ima file_path), originalni fajl bi trebalo da se obriše
            // Prema tome, originalni fajl je validan samo ako dokument NEMA obrađeni fajl
            if ($doc->original_file_path && !$doc->file_path) {
                // Originalni fajl je validan samo ako dokument nije obrađen
                $validPaths[] = $doc->original_file_path;
            }
        }

        // Proveri sve fajlove u user folderu
        $userFolder = "documents/user_{$userId}";
        if (!Storage::disk('local')->exists($userFolder)) {
            return ['count' => 0, 'size' => 0];
        }

        $files = Storage::disk('local')->files($userFolder);
        
        foreach ($files as $filePath) {
            // Proveri da li je fajl u listi validnih putanja
            if (!in_array($filePath, $validPaths)) {
                $fileSize = Storage::disk('local')->size($filePath);
                $totalSize += $fileSize;
                
                if ($dryRun) {
                    $this->line("Would delete: {$filePath} (" . $this->formatBytes($fileSize) . ")");
                } else {
                    Storage::disk('local')->delete($filePath);
                    $this->line("Deleted: {$filePath} (" . $this->formatBytes($fileSize) . ")");
                    
                    Log::info('Orphaned file deleted', [
                        'user_id' => $userId,
                        'file_path' => $filePath,
                        'file_size' => $fileSize
                    ]);
                }
                
                $deletedCount++;
            }
        }

        return ['count' => $deletedCount, 'size' => $totalSize];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
