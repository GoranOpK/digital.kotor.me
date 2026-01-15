<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUploadSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:check-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proverava PHP postavke za upload fajlova';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PHP Upload Settings ===');
        $this->newLine();
        
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxFileUploads = ini_get('max_file_uploads');
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        
        $this->line("upload_max_filesize:  <fg=cyan>{$uploadMaxFilesize}</>");
        $this->line("post_max_size:         <fg=cyan>{$postMaxSize}</>");
        $this->line("max_file_uploads:     <fg=cyan>{$maxFileUploads}</>");
        $this->line("max_execution_time:   <fg=cyan>{$maxExecutionTime}</> sekundi");
        $this->line("memory_limit:         <fg=cyan>{$memoryLimit}</>");
        
        $this->newLine();
        $this->info('=== Laravel Validacija ===');
        $this->line("Maksimalna veličina po fajlu: <fg=cyan>10 MB</> (10240 KB)");
        
        $this->newLine();
        $this->info('=== Preporuke ===');
        
        // Konvertujemo u bajtove za poređenje
        $uploadMaxBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxBytes = $this->convertToBytes($postMaxSize);
        $laravelMaxBytes = 10 * 1024 * 1024; // 10 MB
        
        if ($uploadMaxBytes < $laravelMaxBytes) {
            $this->warn("⚠️  upload_max_filesize ({$uploadMaxFilesize}) je manji od Laravel limita (10 MB)!");
            $this->line("   Preporuka: Povećajte upload_max_filesize na najmanje 10M");
        } else {
            $this->line("✓ upload_max_filesize je dovoljno velik");
        }
        
        // Proveri post_max_size - treba da bude dovoljno za više fajlova
        $recommendedPostMax = $maxFileUploads * $laravelMaxBytes;
        if ($postMaxBytes < $recommendedPostMax) {
            $this->warn("⚠️  post_max_size ({$postMaxSize}) može biti ograničavajući za više fajlova!");
            $this->line("   Preporuka: Povećajte post_max_size na najmanje " . $this->formatBytes($recommendedPostMax));
        } else {
            $this->line("✓ post_max_size je dovoljno velik");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Konvertuje string veličine (npr. "10M", "2G") u bajtove
     */
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Formatira bajtove u čitljiv format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . 'G';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . 'M';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'K';
        }
        return $bytes . 'B';
    }
}
