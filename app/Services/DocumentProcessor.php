<?php

namespace App\Services;

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
     * Procesira upload-ovani dokument i kreira optimizovanu verziju fajla.
     * Za slike (JPEG/PNG) radi se resize + JPEG kompresija.
     * Za PDF fajlove, fajl se snima bez izmjene.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $userId
     * @return array ['success' => bool, 'file_path' => string|null, 'file_size' => int|null, 'error' => string|null]
     */
    public function processDocument($file, int $userId): array
    {
        try {
            // Dozvoljeni tipovi – slike (JPEG/PNG) i PDF
            $mime = $file->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($mime, $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Tip fajla nije dozvoljen. Dozvoljeni su: JPEG, PNG, PDF.'
                ];
            }

            // Direktorijum za korisnika
            $directory = "documents/user_{$userId}";

            // Ako je PDF – ne diramo fajl, samo ga snimimo
            if ($mime === 'application/pdf') {
                $outputFilename = uniqid('doc_', true) . '.pdf';
                $path = $file->storeAs($directory, $outputFilename, 'local');

                $fullPath = Storage::disk('local')->path($path);
                $fileSize = filesize($fullPath);

                return [
                    'success' => true,
                    'file_path' => $path,
                    'file_size' => $fileSize,
                    'error' => null,
                ];
            }

            // Za slike – kompresija u JPEG sa smanjenom rezolucijom
            $outputFilename = uniqid('doc_', true) . '.jpg';
            $outputPath = "{$directory}/{$outputFilename}";

            // Maksimalna širina/visina (u pikselima)
            $maxWidth = 2000;
            $maxHeight = 2000;

            $imageResource = $this->createImageResource($file->getRealPath(), $mime);
            if (!$imageResource) {
                return [
                    'success' => false,
                    'error' => 'Greška pri otvaranju slike. Pokušajte sa drugim fajlom.',
                ];
            }

            $width = imagesx($imageResource);
            $height = imagesy($imageResource);

            // Izračunaj novu veličinu uz očuvanje proporcija
            $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
            $newWidth = (int) floor($width * $ratio);
            $newHeight = (int) floor($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Sačuvaj u buffer kao JPEG sa kvalitetom 75
            ob_start();
            imagejpeg($resized, null, 75);
            $imageData = ob_get_clean();

            imagedestroy($imageResource);
            imagedestroy($resized);

            if ($imageData === false) {
                return [
                    'success' => false,
                    'error' => 'Greška pri kompresiji slike.',
                ];
            }

            // Snimi kompresovani fajl u storage
            Storage::disk('local')->put($outputPath, $imageData);

            $fileSize = strlen($imageData);

            return [
                'success' => true,
                'file_path' => $outputPath,
                'file_size' => $fileSize,
                'error' => null,
            ];

        } catch (Exception $e) {
            Log::error('Document processing exception', [
                'message' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'error' => 'Greška pri obradi dokumenta: ' . $e->getMessage(),
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

    /**
     * Kreira GD image resource iz fajla na osnovu MIME tipa.
     *
     * @param string $path
     * @param string $mime
     * @return resource|false
     */
    protected function createImageResource(string $path, string $mime)
    {
        try {
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                return imagecreatefromjpeg($path);
            }

            if ($mime === 'image/png') {
                return imagecreatefrompng($path);
            }
        } catch (Exception $e) {
            Log::error('Image resource creation failed', [
                'path' => $path,
                'mime' => $mime,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}

