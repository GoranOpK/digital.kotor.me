<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use App\Services\MegaStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    protected DocumentProcessor $documentProcessor;

    public function __construct(DocumentProcessor $documentProcessor)
    {
        $this->documentProcessor = $documentProcessor;
    }

    /**
     * Prikaz liste dokumenata korisnika
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Proveri i ažuriraj stvarno iskorišćen prostor
        $storageCheck = $this->documentProcessor->recalculateUserStorage($user->id);
        
        // Osveži korisnika da dobijemo ažurirane podatke
        $user->refresh();
        
        // Grupiši dokumente po kategorijama (najnoviji na vrhu unutar svake kategorije)
        $documents = UserDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc') // Prvo sortiraj po datumu (najnoviji na vrhu)
            ->orderBy('category') // Zatim po kategoriji
            ->get()
            ->groupBy('category');

        $categories = [
            'Lični dokumenti' => 'Lični dokumenti',
            'Finansijski dokumenti' => 'Finansijski dokumenti',
            'Poslovni dokumenti' => 'Poslovni dokumenti',
            'Ostali dokumenti' => 'Ostali dokumenti',
        ];

        $usedStorage = $user->used_storage_bytes ?? 0;
        $maxStorage = DocumentProcessor::MAX_STORAGE_PER_USER;
        $usedStorageMB = round($usedStorage / 1024 / 1024, 2);
        $maxStorageMB = round($maxStorage / 1024 / 1024, 2);
        $storagePercentage = $maxStorage > 0 ? round(($usedStorage / $maxStorage) * 100, 1) : 0;
        
        // Proveri da li je uspešan MEGA upload (iz query parametra)
        $megaUploadSuccess = request()->get('mega_upload_success');

        return view('documents.index', compact('documents', 'categories', 'usedStorageMB', 'maxStorageMB', 'storagePercentage', 'megaUploadSuccess'));
    }

    /**
     * Upload novog dokumenta (podržava više fajlova odjednom)
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpeg,jpg,png,pdf|max:2048', // Max 2MB po fajlu (ograničeno PHP upload_max_filesize)
            'category' => 'required|in:Lični dokumenti,Finansijski dokumenti,Poslovni dokumenti,Ostali dokumenti',
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ], [
            'files.*.max' => 'Fajl ne može biti veći od 2 MB.',
        ]);
        
        // Proveri ukupnu veličinu svih fajlova (max 7MB zbog post_max_size = 8M)
        $maxTotalSize = 7 * 1024 * 1024; // 7MB u bajtovima (ostavljamo marginu od 1MB)
        $totalSize = 0;
        $fileCount = 0;
        foreach ($request->file('files') as $file) {
            $totalSize += $file->getSize();
            $fileCount++;
        }
        
        if ($totalSize > $maxTotalSize) {
            $totalSizeMB = round($totalSize / 1024 / 1024, 2);
            return back()->withErrors([
                'files' => "Ukupna veličina svih fajlova ({$totalSizeMB} MB) prelazi dozvoljeno ograničenje. Maksimalna ukupna veličina je 7 MB. Molimo smanjite broj ili veličinu fajlova."
            ])->withInput();
        }
        
        // Proveri broj fajlova - previše fajlova može uzrokovati probleme sa memorijom
        if ($fileCount > 10) {
            return back()->withErrors([
                'files' => "Previše fajlova odabrano ({$fileCount}). Maksimalno dozvoljeno je 10 fajlova odjednom. Molimo smanjite broj fajlova."
            ])->withInput();
        }

        $user = Auth::user();
        $files = $request->file('files');
        
        if (empty($files)) {
            return back()->withErrors(['files' => 'Molimo izaberite barem jedan fajl.'])->withInput();
        }

        // Direktorijum za korisnika
        $directory = "documents/user_{$user->id}";
        $date = now()->format('Ymd');
        
        // Ako ima više fajlova, spoji ih u jedan PDF
        if (count($files) > 1) {
            return $this->handleMultipleFilesMerge($files, $user, $request, $directory, $date);
        }
        
        // Ako ima samo jedan fajl, koristi postojeću logiku
        $file = $files[0];
        $uploadedCount = 0;
        $queuedCount = 0;
        $failedCount = 0;
        $errors = [];

        try {
            // Generiši jedinstveno ime za izvorni fajl
            $randomString = bin2hex(random_bytes(4));
            $baseFilename = "{$user->id}-{$date}-{$randomString}";
            $originalExtension = $file->getClientOriginalExtension();
            $originalFilename = "{$baseFilename}_original.{$originalExtension}";
            $originalFilePath = "{$directory}/{$originalFilename}";

            // Sačuvaj izvorni fajl
            $originalFileSize = $file->getSize();
            
            // Proveri da li korisnik ima dovoljno prostora za izvorni fajl
            if (!$this->documentProcessor->hasEnoughStorage($user->id, $originalFileSize)) {
                return back()->withErrors([
                    'file' => 'Nemate dovoljno prostora. Maksimalno dozvoljeno: 20 MB. Trenutno korišćeno: ' . 
                             round(($user->used_storage_bytes ?? 0) / 1024 / 1024, 2) . ' MB'
                ])->withInput();
            }

                Storage::disk('local')->putFileAs($directory, $file, $originalFilename);

                // Kreiraj zapis u bazi sa statusom 'pending'
                $document = UserDocument::create([
                    'user_id' => $user->id,
                    'category' => $request->category,
                    'name' => $request->name . (count($files) > 1 ? ' (' . $file->getClientOriginalName() . ')' : ''),
                    'original_file_path' => $originalFilePath,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $originalFileSize, // Privremeno, ažuriraće se nakon obrade
                    'expires_at' => $request->expires_at,
                    'status' => 'pending',
                ]);

                // Ažuriraj korišćen prostor za izvorni fajl
                $this->documentProcessor->updateUserStorage($user->id, $originalFileSize);

                // Odluči da li da obrađujemo direktno ili preko queue-a
                $fileSizeMB = $originalFileSize / 1024 / 1024;
                $useQueue = $fileSizeMB > 5; // 5MB threshold

                if ($useQueue) {
                    // Pokreni job za asinhronu obradu (veliki fajlovi)
                    ProcessDocumentJob::dispatch($document, $originalFilePath);
                    $queuedCount++;
                } else {
                    // Direktna obrada za male fajlove (brže iskustvo)
                    try {
                        // Učitaj izvorni fajl
                        $fileContent = Storage::disk('local')->get($originalFilePath);
                        $tempFilePath = sys_get_temp_dir() . '/' . uniqid('doc_process_', true) . '_' . basename($originalFilePath);
                        
                        // Sačuvaj privremeno za obradu
                        file_put_contents($tempFilePath, $fileContent);
                        
                        // Ažuriraj status na 'processing' i osveži model
                        $document->refresh();
                        $document->update(['status' => 'processing']);
                        $document->refresh();
                        
                        // Mala pauza da bi JavaScript stigao da pročita "processing" status
                        usleep(500000); // 0.5 sekunde pauza
                        
                        // Kreiraj UploadedFile objekat
                        $mimeType = mime_content_type($tempFilePath) ?: 'application/octet-stream';
                        $uploadedFile = new \Illuminate\Http\UploadedFile(
                            $tempFilePath,
                            basename($originalFilePath),
                            $mimeType,
                            null,
                            true // test mode
                        );

                        // Izvuci base filename
                        $originalBasename = basename($originalFilePath);
                        $baseFilename = pathinfo($originalBasename, PATHINFO_FILENAME);
                        
                        // Procesiraj dokument direktno
                        $result = $this->documentProcessor->processDocument($uploadedFile, $user->id, $baseFilename);
                        
                        // Obriši privremeni fajl
                        if (file_exists($tempFilePath)) {
                            unlink($tempFilePath);
                        }

                        if (!$result['success']) {
                            $document->update(['status' => 'failed']);
                            return back()->withErrors(['file' => $result['error'] ?? 'Greška pri obradi dokumenta.'])->withInput();
                        }

                        // Proveri da li korisnik ima dovoljno prostora (samo ako fajl nije na cloud-u)
                        if (!$result['cloud_path'] && !$this->documentProcessor->hasEnoughStorage($user->id, $result['file_size'])) {
                            if ($result['file_path']) {
                                Storage::disk('local')->delete($result['file_path']);
                            }
                            $document->update(['status' => 'failed']);
                            
                            return back()->withErrors([
                                'file' => 'Nemate dovoljno prostora. Maksimalno dozvoljeno: 20 MB.'
                            ])->withInput();
                        }

                        // Ažuriraj dokument sa putanjom do obrađenog fajla i cloud_path-om ako postoji
                        $updateData = [
                            'file_path' => $result['file_path'],
                            'file_size' => $result['file_size'],
                            'status' => 'processed',
                            'processed_at' => now(),
                        ];
                        
                        // Dodaj cloud_path samo ako kolona postoji u bazi
                        if (\Schema::hasColumn('user_documents', 'cloud_path')) {
                            $updateData['cloud_path'] = $result['cloud_path'] ?? null;
                        }
                        
                        $document->update($updateData);

                        // Ažuriraj korišćen prostor (samo za lokalne fajlove, cloud fajlovi ne računaju se u lokalni prostor)
                        if (!$result['cloud_path']) {
                            $this->documentProcessor->updateUserStorage($user->id, -$originalFileSize);
                            $this->documentProcessor->updateUserStorage($user->id, $result['file_size']);
                        } else {
                            // Ako je fajl na cloud-u, samo oduzmi originalni fajl iz lokalnog prostora
                            $this->documentProcessor->updateUserStorage($user->id, -$originalFileSize);
                        }
                        
                        // Obriši originalni fajl (sada imamo obrađeni PDF)
                        if (Storage::disk('local')->exists($originalFilePath)) {
                            Storage::disk('local')->delete($originalFilePath);
                            Log::info('Original file deleted after processing', [
                                'document_id' => $document->id,
                                'original_file_path' => $originalFilePath
                            ]);
                        }

                        return redirect()->route('documents.index')
                            ->with('success', 'Dokument je uspješno upload-ovan i obrađen.');
                            
                    } catch (\Exception $e) {
                        // Ako direktna obrada ne uspe, prebaci na queue
                        Log::error('Direct processing failed, falling back to queue', [
                            'document_id' => $document->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        $document->update(['status' => 'pending']);
                        ProcessDocumentJob::dispatch($document, $originalFilePath);
                        
                        return redirect()->route('documents.index')
                            ->with('success', 'Dokument je uspješno upload-ovan. Obrada je u toku.');
                    }
                }
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors(['file' => 'Greška pri upload-u dokumenta: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Obrađuje više fajlova i spaja ih u jedan PDF
     */
    private function handleMultipleFilesMerge(array $files, $user, Request $request, string $directory, string $date): RedirectResponse
    {
        try {
            // Proveri ukupnu veličinu svih fajlova
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += $file->getSize();
            }
            
            // Proveri da li korisnik ima dovoljno prostora
            // Dodajemo buffer od 20% jer finalni PDF može biti veći od originalnih fajlova
            $estimatedFinalSize = $totalSize * 1.2; // 20% buffer za finalni PDF
            if (!$this->documentProcessor->hasEnoughStorage($user->id, $estimatedFinalSize)) {
                return back()->withErrors([
                    'files' => 'Nemate dovoljno prostora za sve fajlove. Maksimalno dozvoljeno: 20 MB. Trenutno korišćeno: ' . 
                             round(($user->used_storage_bytes ?? 0) / 1024 / 1024, 2) . ' MB'
                ])->withInput();
            }

            // Sačuvaj sve originalne fajlove privremeno
            $originalFilePaths = [];
            $totalOriginalSize = 0;
            $tempFiles = [];
            
            foreach ($files as $index => $file) {
                $randomString = bin2hex(random_bytes(4));
                $baseFilename = "{$user->id}-{$date}-{$randomString}";
                $originalExtension = $file->getClientOriginalExtension();
                $originalFilename = "{$baseFilename}_original.{$originalExtension}";
                $originalFilePath = "{$directory}/{$originalFilename}";
                
                Storage::disk('local')->putFileAs($directory, $file, $originalFilename);
                $originalFilePaths[] = $originalFilePath;
                $totalOriginalSize += $file->getSize();
                
                // Kreiraj privremeni UploadedFile za merge
                $fileContent = Storage::disk('local')->get($originalFilePath);
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid('merge_', true) . '_' . basename($originalFilePath);
                file_put_contents($tempFilePath, $fileContent);
                
                $mimeType = mime_content_type($tempFilePath) ?: 'application/octet-stream';
                $tempFiles[] = new \Illuminate\Http\UploadedFile(
                    $tempFilePath,
                    basename($originalFilePath),
                    $mimeType,
                    null,
                    true
                );
            }

            // Ažuriraj korišćen prostor za originalne fajlove
            $this->documentProcessor->updateUserStorage($user->id, $totalOriginalSize);

            // Generiši ime za spojeni PDF
            $randomString = bin2hex(random_bytes(4));
            $baseFilename = "{$user->id}-{$date}-{$randomString}";

            // Kreiraj zapis u bazi sa statusom 'processing'
            $document = UserDocument::create([
                'user_id' => $user->id,
                'category' => $request->category,
                'name' => $request->name,
                'original_file_path' => implode('|', $originalFilePaths), // Sačuvaj sve putanje odvojeno sa |
                'original_filename' => count($files) . ' fajlova',
                'file_size' => $totalOriginalSize,
                'expires_at' => $request->expires_at,
                'status' => 'processing',
            ]);

            // Direktna obrada (za sada, queue može biti dodato kasnije)
            return $this->processMergeDirectly($document, $tempFiles, $user, $baseFilename, $originalFilePaths);

        } catch (\Exception $e) {
            Log::error('Multiple files merge failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['files' => 'Greška pri spajanju fajlova: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Direktno procesira spajanje fajlova
     */
    private function processMergeDirectly($document, array $tempFiles, $user, string $baseFilename, array $originalFilePaths): RedirectResponse
    {
        try {
            // Mala pauza da bi JavaScript stigao da pročita "processing" status
            usleep(500000); // 0.5 sekunde pauza

            // Spoji fajlove u jedan PDF
            $result = $this->documentProcessor->mergeDocuments($tempFiles, $user->id, $baseFilename);

            // Obriši privremene fajlove
            foreach ($tempFiles as $tempFile) {
                if ($tempFile->getRealPath() && file_exists($tempFile->getRealPath())) {
                    @unlink($tempFile->getRealPath());
                }
            }

            if (!$result['success']) {
                $document->update(['status' => 'failed']);
                return back()->withErrors(['files' => $result['error'] ?? 'Greška pri spajanju fajlova.'])->withInput();
            }

            // Proveri da li korisnik ima dovoljno prostora za spojeni PDF (samo ako nije na cloud-u)
            if (!$result['cloud_path'] && !$this->documentProcessor->hasEnoughStorage($user->id, $result['file_size'])) {
                if ($result['file_path']) {
                    Storage::disk('local')->delete($result['file_path']);
                }
                $document->update(['status' => 'failed']);
                
                return back()->withErrors([
                    'files' => 'Nemate dovoljno prostora za spojeni PDF. Maksimalno dozvoljeno: 20 MB.'
                ])->withInput();
            }

            // Ažuriraj dokument sa putanjom do spojenog PDF-a i cloud_path-om ako postoji
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
            
            $document->update($updateData);

            // Ažuriraj korišćen prostor
            // Prvo izračunaj ukupnu veličinu originalnih fajlova PRE brisanja
            $originalTotalSize = 0;
            foreach ($originalFilePaths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    $originalTotalSize += Storage::disk('local')->size($path);
                }
            }
            
            Log::info('Updating storage for merged PDF', [
                'original_total_size' => $originalTotalSize,
                'merged_pdf_size' => $result['file_size'],
                'cloud_path' => $result['cloud_path'] ?? null,
                'difference' => $result['cloud_path'] ? -$originalTotalSize : ($result['file_size'] - $originalTotalSize)
            ]);
            
            // Ako je fajl na cloud-u, samo oduzmi originalne fajlove iz lokalnog prostora
            // Ako nije na cloud-u, oduzmi originalne i dodaj spojeni PDF
            if ($result['cloud_path']) {
                $this->documentProcessor->updateUserStorage($user->id, -$originalTotalSize);
            } else {
                $sizeDifference = $result['file_size'] - $originalTotalSize;
                $this->documentProcessor->updateUserStorage($user->id, $sizeDifference);
            }

            // Obriši originalne fajlove (sada imamo spojeni PDF)
            foreach ($originalFilePaths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                    Log::info('Original file deleted after merge processing', [
                        'document_id' => $document->id,
                        'original_file_path' => $path
                    ]);
                }
            }

            return redirect()->route('documents.index')
                ->with('success', 'Fajlovi su uspješno spojeni u jedan PDF dokument i obrađeni.');

        } catch (\Exception $e) {
            Log::error('Direct merge processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            $document->update(['status' => 'failed']);
            return back()->withErrors(['files' => 'Greška pri spajanju fajlova: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Preuzimanje dokumenta
     */
    public function download(UserDocument $document)
    {
        $user = Auth::user();

        // Proveri da li dokument pripada korisniku
        if ($document->user_id !== $user->id) {
            abort(403, 'Nemate pristup ovom dokumentu.');
        }

        // Proveri da li je dokument obrađen
        if ($document->status !== 'processed' && $document->status !== 'active') {
            abort(404, 'Dokument još nije obrađen.');
        }

        // Ako dokument ima cloud_path, preuzmi sa Mega.nz
        if ($document->cloud_path) {
            $megaService = new MegaStorageService();
            $downloadResult = $megaService->download($document->cloud_path);
            
            if ($downloadResult['success'] && !empty($downloadResult['content'])) {
                $extension = 'pdf'; // Obrađeni dokumenti su uvek PDF
                $filename = $document->name . '.' . $extension;
                
                return response($downloadResult['content'])
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            } else {
                Log::error('MEGA download failed', [
                    'document_id' => $document->id,
                    'cloud_path' => $document->cloud_path,
                    'error' => $downloadResult['error'] ?? 'Unknown error'
                ]);
                abort(404, 'Dokument nije pronađen na cloud storage-u.');
            }
        }

        // Ako nema cloud_path, preuzmi lokalno
        $filePath = $document->file_path ?? $document->original_file_path;
        
        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'Dokument nije pronađen.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download(
            $filePath,
            $document->name . '.' . $extension
        );
    }

    /**
     * Brisanje dokumenta
     */
    public function destroy(UserDocument $document): RedirectResponse
    {
        $user = Auth::user();

        // Proveri da li dokument pripada korisniku
        if ($document->user_id !== $user->id) {
            abort(403, 'Nemate pristup ovom dokumentu.');
        }

        $deleted = false;
        $megaService = new MegaStorageService();
        
        // Ako dokument ima cloud_path, obriši sa Mega.nz
        if ($document->cloud_path) {
            $deleted = $megaService->delete($document->cloud_path);
            
            if ($deleted) {
                Log::info('Document deleted from MEGA', [
                    'document_id' => $document->id,
                    'cloud_path' => $document->cloud_path
                ]);
            } else {
                Log::warning('MEGA delete failed, but continuing with local cleanup', [
                    'document_id' => $document->id,
                    'cloud_path' => $document->cloud_path
                ]);
            }
        }

        // Obriši lokalne fajlove ako postoje
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            $fileSize = Storage::disk('local')->size($document->file_path);
            Storage::disk('local')->delete($document->file_path);
            $this->documentProcessor->updateUserStorage($user->id, -$fileSize);
            $deleted = true;
            
            Log::info('Processed PDF file deleted', [
                'document_id' => $document->id,
                'file_path' => $document->file_path,
                'file_size' => $fileSize
            ]);
        }
        
        if ($document->original_file_path && Storage::disk('local')->exists($document->original_file_path)) {
            $originalFileSize = Storage::disk('local')->size($document->original_file_path);
            Storage::disk('local')->delete($document->original_file_path);
            $this->documentProcessor->updateUserStorage($user->id, -$originalFileSize);
            $deleted = true;
            
            Log::info('Original file deleted', [
                'document_id' => $document->id,
                'original_file_path' => $document->original_file_path,
                'file_size' => $originalFileSize
            ]);
        }
        
        // Obriši zapis iz baze
        $document->delete();
        
        return redirect()->route('documents.index')
            ->with('success', 'Dokument je uspješno obrisan.');
    }

    /**
     * Vraća status dokumenata koji su u obradi (API endpoint)
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();
        
        // Vrati dokumente koji su u pending, processing statusu
        // ILI su nedavno obrađeni (u poslednjih 5 minuta) - da bi se ažurirao status na stranici
        $documents = UserDocument::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'processing'])
                    ->orWhere(function ($q) {
                        // Dokumenti obrađeni u poslednjih 5 minuta
                        $q->where('status', 'processed')
                          ->where('processed_at', '>=', now()->subMinutes(5));
                    });
            })
            ->get(['id', 'status', 'processed_at'])
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'status' => $document->status,
                    'processed_at' => $document->processed_at ? $document->processed_at->format('d.m.Y H:i') : null,
                ];
            });
        
        // Log za debug
        Log::info('Status API pozvan', [
            'user_id' => $user->id,
            'documents_count' => $documents->count(),
            'statuses' => $documents->pluck('status')->toArray()
        ]);

        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Vraća MEGA session token za browser upload
     * 
     * @return JsonResponse
     */
    public function getMegaSession(): JsonResponse
    {
        try {
            $megaService = new MegaStorageService();
            
            if (!$megaService->isConfigured()) {
                return response()->json([
                    'error' => 'MEGA credentials not configured'
                ], 500);
            }

            // Za sada vraćamo email/password (frontend će se ulogovati sa njima)
            // TODO: Implementirati session token caching u budućnosti
            $email = config('services.mega.email');
            $password = config('services.mega.password');
            
            return response()->json([
                'email' => $email,
                'password' => $password
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get MEGA session', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get MEGA session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Čuva MEGA metadata (fajlovi su već upload-ovani na MEGA iz browser-a)
     * 
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function storeMegaMetadata(Request $request)
    {
        try {
            $user = Auth::user();

            // Validacija
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|in:Lični dokumenti,Finansijski dokumenti,Poslovni dokumenti,Ostali dokumenti',
                'expires_at' => 'nullable|date|after:today',
                'files' => 'required|array|min:1',
                'files.*.mega_node_id' => 'required|string',
                'files.*.mega_link' => 'required|url',
                'files.*.name' => 'required|string',
                'files.*.size' => 'required|integer|min:1',
            ]);

            // Ako ima više fajlova, spoji ih u jedan dokument
            if (count($validated['files']) > 1) {
                $firstFile = $validated['files'][0];
                
                $document = UserDocument::create([
                    'user_id' => $user->id,
                    'category' => $validated['category'],
                    'name' => $validated['name'],
                    'file_path' => null, // Nema lokalni fajl
                    'cloud_path' => $firstFile['mega_link'], // Čuvamo MEGA link
                    'file_size' => array_sum(array_column($validated['files'], 'size')),
                    'status' => 'processed',
                    'processed_at' => now(),
                    'expires_at' => $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null,
                ]);

                Log::info('MEGA document created (merged)', [
                    'document_id' => $document->id,
                    'files_count' => count($validated['files']),
                    'total_size' => $document->file_size
                ]);

            } else {
                $file = $validated['files'][0];
                
                $document = UserDocument::create([
                    'user_id' => $user->id,
                    'category' => $validated['category'],
                    'name' => $validated['name'],
                    'file_path' => null,
                    'cloud_path' => $file['mega_link'],
                    'file_size' => $file['size'],
                    'status' => 'processed',
                    'processed_at' => now(),
                    'expires_at' => $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null,
                ]);

                Log::info('MEGA document created', [
                    'document_id' => $document->id,
                    'mega_link' => $file['mega_link']
                ]);
            }

            // Storage se ne računa jer su fajlovi na cloud-u

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'document_id' => $document->id,
                    'message' => 'Dokument uspešno upload-ovan na MEGA'
                ]);
            }

            return redirect()->route('documents.index')
                ->with('success', 'Dokument uspešno upload-ovan na MEGA');

        } catch (\Exception $e) {
            Log::error('Failed to save MEGA metadata', [
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Greška pri čuvanju metadata: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Greška pri čuvanju metadata: ' . $e->getMessage()
            ])->withInput();
        }
    }
}

