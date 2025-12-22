<?php

namespace App\Jobs;

use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minuta timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserDocument $document,
        public string $originalFilePath
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentProcessor $documentProcessor): void
    {
        try {
            // Proveri da li izvorni fajl još postoji
            if (!Storage::disk('local')->exists($this->originalFilePath)) {
                Log::error('Original file not found for processing', [
                    'document_id' => $this->document->id,
                    'file_path' => $this->originalFilePath
                ]);
                $this->document->update(['status' => 'failed']);
                return;
            }

            // Učitaj izvorni fajl
            $fileContent = Storage::disk('local')->get($this->originalFilePath);
            $tempFilePath = sys_get_temp_dir() . '/' . uniqid('doc_process_', true) . '_' . basename($this->originalFilePath);
            
            // Sačuvaj privremeno za obradu
            file_put_contents($tempFilePath, $fileContent);
            
            // Ažuriraj status na 'processing'
            $this->document->update(['status' => 'processing']);
            
            // Kreiraj UploadedFile objekat za procesiranje
            $mimeType = mime_content_type($tempFilePath) ?: 'application/octet-stream';
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempFilePath,
                basename($this->originalFilePath),
                $mimeType,
                null,
                true // test mode
            );

            // Izvuci base filename iz original_file_path (bez ekstenzije i _original dela)
            // Primer: documents/user_8/8-20251220-abc123_original.jpg -> 8-20251220-abc123
            $originalBasename = basename($this->originalFilePath);
            $baseFilename = pathinfo($originalBasename, PATHINFO_FILENAME); // Uklanja ekstenziju
            
            // Procesiraj dokument sa istim base filename-om kao izvorni fajl
            $result = $documentProcessor->processDocument($uploadedFile, $this->document->user_id, $baseFilename);

            // Obriši privremeni fajl
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            if (!$result['success']) {
                Log::error('Document processing failed in job', [
                    'document_id' => $this->document->id,
                    'error' => $result['error']
                ]);
                $this->document->update(['status' => 'failed']);
                return;
            }

            // Proveri da li korisnik ima dovoljno prostora
            if (!$documentProcessor->hasEnoughStorage($this->document->user_id, $result['file_size'])) {
                // Obriši kreirani fajl
                Storage::disk('local')->delete($result['file_path']);
                
                Log::error('Insufficient storage for processed document', [
                    'document_id' => $this->document->id,
                    'user_id' => $this->document->user_id
                ]);
                $this->document->update(['status' => 'failed']);
                return;
            }

            // Ažuriraj dokument sa putanjom do obrađenog fajla
            $this->document->update([
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Ažuriraj korišćen prostor
            $documentProcessor->updateUserStorage($this->document->user_id, $result['file_size']);

            Log::info('Document processed successfully', [
                'document_id' => $this->document->id,
                'processed_file_path' => $result['file_path']
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing job exception', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->document->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Document processing job failed permanently', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage()
        ]);

        $this->document->update(['status' => 'failed']);
    }
}

