<?php

namespace App\Http\Controllers;

use App\Models\UserDocument;
use App\Services\DocumentProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected DocumentProcessor $documentProcessor;

    public function __construct(DocumentProcessor $documentProcessor)
    {
        $this->documentProcessor = $documentProcessor;
        $this->middleware('auth');
    }

    /**
     * Prikaz liste dokumenata korisnika
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Grupiši dokumente po kategorijama
        $documents = UserDocument::where('user_id', $user->id)
            ->orderBy('category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('category');

        $categories = [
            'Lični dokumenti' => 'Lični dokumenti',
            'Finansijski dokumenti' => 'Finansijski dokumenti',
            'Tehnički dokumenti' => 'Tehnički dokumenti',
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
            'category' => 'required|in:Lični dokumenti,Finansijski dokumenti,Tehnički dokumenti,Poslovni dokumenti,Ostali dokumenti',
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $user = Auth::user();
        $file = $request->file('file');

        // Procesiraj dokument (konvertuj u optimizovani PDF)
        $result = $this->documentProcessor->processDocument($file, $user->id);

        if (!$result['success']) {
            return back()->withErrors(['file' => $result['error']])->withInput();
        }

        // Proveri da li korisnik ima dovoljno prostora
        if (!$this->documentProcessor->hasEnoughStorage($user->id, $result['file_size'])) {
            // Obriši kreirani fajl
            Storage::disk('local')->delete($result['file_path']);
            
            return back()->withErrors([
                'file' => 'Nemate dovoljno prostora. Maksimalno dozvoljeno: 20 MB. Trenutno korišćeno: ' . 
                         round(($user->used_storage_bytes ?? 0) / 1024 / 1024, 2) . ' MB'
            ])->withInput();
        }

        // Kreiraj zapis u bazi
        $document = UserDocument::create([
            'user_id' => $user->id,
            'category' => $request->category,
            'name' => $request->name,
            'file_path' => $result['file_path'],
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $result['file_size'],
            'expires_at' => $request->expires_at,
            'status' => 'active',
        ]);

        // Ažuriraj korišćen prostor
        $this->documentProcessor->updateUserStorage($user->id, $result['file_size']);

        return redirect()->route('documents.index')
            ->with('success', 'Dokument je uspešno upload-ovan i optimizovan.');
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

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Dokument nije pronađen.');
        }

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'dat';

        return Storage::disk('local')->download(
            $document->file_path,
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

        // Obriši fajl i ažuriraj storage
        if ($this->documentProcessor->deleteDocument($document->file_path, $user->id)) {
            $document->delete();
            
            return redirect()->route('documents.index')
                ->with('success', 'Dokument je uspešno obrisan.');
        }

        return back()->withErrors(['error' => 'Greška pri brisanju dokumenta.']);
    }
}

