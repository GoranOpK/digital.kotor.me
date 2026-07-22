<?php

namespace App\Jobs;

use App\Models\ExternalFileArchive;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use App\Services\ExternalArchive\ExternalFileArchiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minuta timeout (za velike fajlove)

    /**
     * Create a new job instance.
     *
     * @param  bool  $archiveOnly  When true, skip PDF processing and only run MEGA archive
     *                             (e.g. after sync multi-file merge). Requires document.file_path.
     */
    public function __construct(
        public UserDocument $document,
        public string $originalFilePath,
        public bool $archiveOnly = false,
    ) {
        //
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        // Lock per UserDocument; expireAfter > $timeout so a long MEGA upload is not double-run.
        return [
            (new WithoutOverlapping('process-document:'.$this->document->id))
                ->expireAfter(max(900, (int) $this->timeout + 120))
                ->dontRelease(),
        ];
    }

    /**
     * Execute the job.
     *
     * "processed" = local PDF ready for download (unchanged).
     * When external_archive.library_upload is true, archive runs after PDF success.
     * Archive failure keeps UserDocument as processed if the local PDF exists;
     * failure is recorded on external_file_archives only.
     */
    public function handle(DocumentProcessor $documentProcessor, ExternalFileArchiveService $archiveService): void
    {
        try {
            $this->document->refresh();
            if (! $this->document->exists) {
                return;
            }

            $libraryUpload = (bool) config('external_archive.library_upload', false);

            if ($libraryUpload && $this->hasUploadedArchive()) {
                Log::info('ProcessDocumentJob skipped: archive already uploaded', [
                    'document_id' => $this->document->id,
                ]);
                return;
            }

            // Archive-only (merge path) or retry when PDF already exists.
            if ($libraryUpload && ($this->archiveOnly || $this->hasLocalProcessedPdf())) {
                $this->archiveProcessedDocument($archiveService);
                return;
            }

            if (! $libraryUpload
                && $this->document->status === 'processed'
                && $this->hasLocalProcessedPdf()
            ) {
                return;
            }

            Log::info('Starting document processing job', [
                'document_id' => $this->document->id,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'original_file_path' => $this->originalFilePath
            ]);
            
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
            
            // Ažuriraj status na 'processing' i osveži model da bi se promjena vidjela
            $this->document->refresh();
            $this->document->update(['status' => 'processing']);
            $this->document->refresh(); // Osveži da bi se promjena vidjela u sljedećim provjerama
            
            // Mala pauza da bi JavaScript stigao da pročita "processing" status
            // (obrada je vrlo brza - 0.39 sekundi, pa treba da status bude vidljiv)
            if (! app()->environment('testing')) {
                usleep(500000); // 0.5 sekunde pauza
            }
            
            Log::info('Status postavljen na processing', [
                'document_id' => $this->document->id,
                'current_status' => $this->document->status
            ]);
            
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
            
            Log::info('Calling processDocument', [
                'document_id' => $this->document->id,
                'base_filename' => $baseFilename,
                'mime_type' => $mimeType,
                'temp_file_size' => filesize($tempFilePath)
            ]);
            
            // Procesiraj dokument sa istim base filename-om kao izvorni fajl
            $startTime = microtime(true);
            $result = $documentProcessor->processDocument($uploadedFile, $this->document->user_id, $baseFilename);
            $processingTime = microtime(true) - $startTime;
            
            Log::info('processDocument completed', [
                'document_id' => $this->document->id,
                'success' => $result['success'] ?? false,
                'processing_time' => round($processingTime, 2) . ' seconds',
                'error' => $result['error'] ?? null
            ]);

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

            // Ažuriraj dokument sa putanjom do obrađenog fajla i cloud_path-om ako postoji
            $updateData = [
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'processed',
                'processed_at' => now(),
            ];
            
            // Dodaj cloud_path samo ako kolona postoji u bazi
            if (Schema::hasColumn('user_documents', 'cloud_path')) {
                $updateData['cloud_path'] = $result['cloud_path'] ?? null;
            }
            
            $this->document->update($updateData);

            // Ažuriraj korišćen prostor
            // Oduzmi originalni fajl i dodaj obrađeni PDF
            $originalFileSize = Storage::disk('local')->exists($this->originalFilePath) 
                ? Storage::disk('local')->size($this->originalFilePath) 
                : 0;
            
            $documentProcessor->updateUserStorage($this->document->user_id, -$originalFileSize);
            $documentProcessor->updateUserStorage($this->document->user_id, $result['file_size']);

            // Obriši originalni fajl nakon uspešne obrade
            if (Storage::disk('local')->exists($this->originalFilePath)) {
                Storage::disk('local')->delete($this->originalFilePath);
                Log::info('Original file deleted after processing', [
                    'document_id' => $this->document->id,
                    'original_file_path' => $this->originalFilePath
                ]);
            }

            Log::info('Document processed successfully', [
                'document_id' => $this->document->id,
                'processed_file_path' => $result['file_path']
            ]);

            if ($libraryUpload) {
                $this->document->refresh();
                $this->archiveProcessedDocument($archiveService);
            }

        } catch (\Exception $e) {
            Log::error('Document processing job exception', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempts' => $this->attempts(),
                'max_tries' => $this->tries
            ]);
            
            // Do not demote a usable local PDF (e.g. archive-only / post-PDF failures).
            $this->document->refresh();
            if (! ($this->hasLocalProcessedPdf() && in_array($this->document->status, ['processed', 'active'], true))) {
                $this->document->update(['status' => 'failed']);
            }
            
            // Ne bacaj exception ako smo već probali maksimalan broj puta
            // ili ako je greška vezana za fajl koji ne postoji
            if ($this->attempts() >= $this->tries) {
                Log::error('Max attempts reached, not retrying', [
                    'document_id' => $this->document->id
                ]);
                return;
            }
            
            // Za druge greške, baci exception da bi Laravel ponovo pokušao
            throw $e;
        }
    }

    /**
     * Archive processed PDF via Paket 1 API. Does not demote UserDocument from
     * processed→failed when a local PDF remains usable.
     */
    private function archiveProcessedDocument(ExternalFileArchiveService $archiveService): void
    {
        $this->document->refresh();
        if (! $this->document->exists) {
            return;
        }

        if ($this->hasUploadedArchive()) {
            return;
        }

        $localPath = (string) ($this->document->file_path ?? '');
        if ($localPath === '' || ! Storage::disk('local')->exists($localPath)) {
            Log::error('ProcessDocumentJob archive skipped: processed PDF missing', [
                'document_id' => $this->document->id,
                'file_path' => $localPath,
                'archive_only' => $this->archiveOnly,
            ]);
            // archiveOnly: leave document status untouched (merge already produced processed PDF or nothing usable).
            if ($this->archiveOnly) {
                return;
            }
            $this->document->update(['status' => 'failed']);

            return;
        }

        try {
            $archive = $archiveService->archiveLocalPrivateFile(
                'user_documents',
                (int) $this->document->id,
                'file_path',
                $localPath,
                'document_library',
            );
        } catch (Throwable $e) {
            Log::error('ProcessDocumentJob archive exception', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            // Keep processed: local PDF is still downloadable.
            return;
        }

        if ($archive->status === ExternalFileArchive::STATUS_UPLOADED) {
            Log::info('ProcessDocumentJob archive succeeded', [
                'document_id' => $this->document->id,
                'external_file_archive_id' => $archive->id,
            ]);

            return;
        }

        Log::warning('ProcessDocumentJob archive failed; keeping UserDocument processed', [
            'document_id' => $this->document->id,
            'external_file_archive_id' => $archive->id,
            'archive_status' => $archive->status,
        ]);
    }

    private function hasUploadedArchive(): bool
    {
        return ExternalFileArchive::query()
            ->where('source_table', 'user_documents')
            ->where('source_id', $this->document->id)
            ->where('source_column', 'file_path')
            ->where('archive_provider', ExternalFileArchive::PROVIDER_MEGA)
            ->where('status', ExternalFileArchive::STATUS_UPLOADED)
            ->exists();
    }

    private function hasLocalProcessedPdf(): bool
    {
        $path = (string) ($this->document->file_path ?? '');

        return $path !== '' && Storage::disk('local')->exists($path);
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

        // Do not overwrite a usable local PDF with failed.
        $this->document->refresh();
        if ($this->hasLocalProcessedPdf() && in_array($this->document->status, ['processed', 'active'], true)) {
            return;
        }

        $this->document->update(['status' => 'failed']);
    }
}

