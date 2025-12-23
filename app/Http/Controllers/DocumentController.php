<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        return view('documents.index', compact('documents', 'categories', 'usedStorageMB', 'maxStorageMB', 'storagePercentage'));
    }

    /**
     * Upload novog dokumenta
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240', // Max 10MB originalni fajl
            'category' => 'required|in:Lični dokumenti,Finansijski dokumenti,Poslovni dokumenti,Ostali dokumenti',
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $user = Auth::user();
        $file = $request->file('file');

        // Direktorijum za korisnika
        $directory = "documents/user_{$user->id}";
        
        // Generiši ime za izvorni fajl (isti format kao obrađeni, ali sa originalnom ekstenzijom)
        $date = now()->format('Ymd');
        $randomString = bin2hex(random_bytes(4));
        $baseFilename = "{$user->id}-{$date}-{$randomString}";
        $originalExtension = $file->getClientOriginalExtension();
        $originalFilename = "{$baseFilename}_original.{$originalExtension}";
        $originalFilePath = "{$directory}/{$originalFilename}";

        // Sačuvaj izvorni fajl
        $originalFileSize = $file->getSize();
        Storage::disk('local')->putFileAs($directory, $file, $originalFilename);

        // Proveri da li korisnik ima dovoljno prostora za izvorni fajl
        if (!$this->documentProcessor->hasEnoughStorage($user->id, $originalFileSize)) {
            Storage::disk('local')->delete($originalFilePath);
            
            return back()->withErrors([
                'file' => 'Nemate dovoljno prostora. Maksimalno dozvoljeno: 20 MB. Trenutno korišćeno: ' . 
                         round(($user->used_storage_bytes ?? 0) / 1024 / 1024, 2) . ' MB'
            ])->withInput();
        }

        // Kreiraj zapis u bazi sa statusom 'pending'
        $document = UserDocument::create([
            'user_id' => $user->id,
            'category' => $request->category,
            'name' => $request->name,
            'original_file_path' => $originalFilePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $originalFileSize, // Privremeno, ažuriraće se nakon obrade
            'expires_at' => $request->expires_at,
            'status' => 'pending',
        ]);

        // Ažuriraj korišćen prostor za izvorni fajl
        $this->documentProcessor->updateUserStorage($user->id, $originalFileSize);

        // Odluči da li da obrađujemo direktno ili preko queue-a
        // Za fajlove manje od 2MB, obrađujemo direktno (brže za korisnika)
        // Za veće fajlove, koristimo queue (ne blokira korisnika)
        $fileSizeMB = $originalFileSize / 1024 / 1024;
        $useQueue = $fileSizeMB > 2; // 2MB threshold

        if ($useQueue) {
            // Pokreni job za asinhronu obradu (veliki fajlovi)
            ProcessDocumentJob::dispatch($document, $originalFilePath);

            return redirect()->route('documents.index')
                ->with('success', 'Dokument je uspješno upload-ovan. Obrada je u toku i bićete obavešteni kada bude završena.');
        } else {
            // Direktna obrada za male fajlove (brže iskustvo)
            try {
                // Učitaj izvorni fajl
                $fileContent = Storage::disk('local')->get($originalFilePath);
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid('doc_process_', true) . '_' . basename($originalFilePath);
                
                // Sačuvaj privremeno za obradu
                file_put_contents($tempFilePath, $fileContent);
                
                // Ažuriraj status na 'processing'
                $document->update(['status' => 'processing']);
                
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

                // Proveri da li korisnik ima dovoljno prostora
                if (!$this->documentProcessor->hasEnoughStorage($user->id, $result['file_size'])) {
                    Storage::disk('local')->delete($result['file_path']);
                    $document->update(['status' => 'failed']);
                    
                    return back()->withErrors([
                        'file' => 'Nemate dovoljno prostora. Maksimalno dozvoljeno: 20 MB.'
                    ])->withInput();
                }

                // Ažuriraj dokument sa putanjom do obrađenog fajla
                $document->update([
                    'file_path' => $result['file_path'],
                    'file_size' => $result['file_size'],
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);

                // Ažuriraj korišćen prostor
                $this->documentProcessor->updateUserStorage($user->id, $result['file_size']);

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

        // Koristi obrađeni fajl ako postoji, inače izvorni
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

        // Obriši fajlove (obrađeni i izvorni) i ažuriraj storage
        $deleted = false;
        
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            $fileSize = Storage::disk('local')->size($document->file_path);
            Storage::disk('local')->delete($document->file_path);
            $this->documentProcessor->updateUserStorage($user->id, -$fileSize);
            $deleted = true;
        }
        
        if ($document->original_file_path && Storage::disk('local')->exists($document->original_file_path)) {
            $originalFileSize = Storage::disk('local')->size($document->original_file_path);
            Storage::disk('local')->delete($document->original_file_path);
            $this->documentProcessor->updateUserStorage($user->id, -$originalFileSize);
            $deleted = true;
        }
        
        if ($deleted) {
            $document->delete();
            
            return redirect()->route('documents.index')
                ->with('success', 'Dokument je uspješno obrisan.');
        }

        // Ako nema fajlova, samo obriši zapis
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
        
        // Vrati samo dokumente koji su u pending ili processing statusu
        $documents = UserDocument::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->get(['id', 'status', 'processed_at'])
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'status' => $document->status,
                    'processed_at' => $document->processed_at ? $document->processed_at->format('d.m.Y H:i') : null,
                ];
            });

        return response()->json([
            'documents' => $documents,
        ]);
    }
}

