<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentProcessor
{
    /**
     * Maksimalna veličina prostora po korisniku (20 MB)
     */
    const MAX_STORAGE_PER_USER = 20 * 1024 * 1024; // 20 MB u bajtovima

    /**
     * Putanja do Python skripte
     */
    protected string $pythonScriptPath;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('scripts/process_document.py');
    }

    /**
     * Procesira upload-ovani dokument i kreira optimizovani PDF
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $userId
     * @return array ['success' => bool, 'file_path' => string|null, 'file_size' => int|null, 'error' => string|null]
     */
    public function processDocument($file, int $userId): array
    {
        try {
            // Validacija tipa fajla
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Tip fajla nije dozvoljen. Dozvoljeni su: JPEG, PNG, PDF.'
                ];
            }

            // Privremena putanja za originalni fajl
            $tempInputPath = $file->storeAs('temp', uniqid('doc_', true) . '.' . $file->getClientOriginalExtension(), 'local');
            $fullTempInputPath = Storage::disk('local')->path($tempInputPath);

            // Putanja za optimizovani PDF
            $outputFilename = uniqid('doc_', true) . '.pdf';
            $outputPath = "documents/user_{$userId}/{$outputFilename}";
            $fullOutputPath = Storage::disk('local')->path($outputPath);

            // Kreiraj direktorijum ako ne postoji
            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Pozovi Python skriptu
            $pythonCommand = $this->getPythonCommand();
            $result = Process::run([
                $pythonCommand,
                $this->pythonScriptPath,
                $fullTempInputPath,
                $fullOutputPath
            ]);

            // Obriši privremeni fajl
            Storage::disk('local')->delete($tempInputPath);

            if (!$result->successful()) {
                Log::error('Document processing failed', [
                    'error' => $result->errorOutput(),
                    'user_id' => $userId
                ]);

                return [
                    'success' => false,
                    'error' => 'Greška pri obradi dokumenta. Pokušajte ponovo.'
                ];
            }

            // Proveri da li je fajl kreiran
            if (!file_exists($fullOutputPath)) {
                return [
                    'success' => false,
                    'error' => 'Optimizovani fajl nije kreiran.'
                ];
            }

            $fileSize = filesize($fullOutputPath);

            return [
                'success' => true,
                'file_path' => $outputPath,
                'file_size' => $fileSize,
                'error' => null
            ];

        } catch (Exception $e) {
            Log::error('Document processing exception', [
                'message' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Greška pri obradi dokumenta: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Proverava da li korisnik ima dovoljno prostora
     *
     * @param int $userId
     * @param int $additionalBytes
     * @return bool
     */
    public function hasEnoughStorage(int $userId, int $additionalBytes): bool
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        $currentUsage = $user->used_storage_bytes ?? 0;
        $totalNeeded = $currentUsage + $additionalBytes;

        return $totalNeeded <= self::MAX_STORAGE_PER_USER;
    }

    /**
     * Ažurira korišćen prostor za korisnika
     *
     * @param int $userId
     * @param int $bytesToAdd (može biti negativno za brisanje)
     * @return void
     */
    public function updateUserStorage(int $userId, int $bytesToAdd): void
    {
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->used_storage_bytes = max(0, ($user->used_storage_bytes ?? 0) + $bytesToAdd);
            $user->save();
        }
    }

    /**
     * Vraća komandu za Python (proverava različite opcije)
     *
     * @return string
     */
    protected function getPythonCommand(): string
    {
        // Proveri različite Python komande
        $pythonCommands = ['python3', 'python', '/usr/bin/python3', '/usr/bin/python'];
        
        foreach ($pythonCommands as $cmd) {
            $result = Process::run([$cmd, '--version']);
            if ($result->successful()) {
                return $cmd;
            }
        }

        // Fallback na python3
        return 'python3';
    }

    /**
     * Briše fajl i ažurira storage
     *
     * @param string $filePath
     * @param int $userId
     * @return bool
     */
    public function deleteDocument(string $filePath, int $userId): bool
    {
        try {
            if (Storage::disk('local')->exists($filePath)) {
                $fileSize = Storage::disk('local')->size($filePath);
                Storage::disk('local')->delete($filePath);
                
                // Ažuriraj storage (oduzmi)
                $this->updateUserStorage($userId, -$fileSize);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('Document deletion failed', [
                'file_path' => $filePath,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}

