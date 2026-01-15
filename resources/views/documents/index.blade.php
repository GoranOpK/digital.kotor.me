@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .documents-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .page-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .top-sections-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (min-width: 768px) {
        .top-sections-grid {
            grid-template-columns: 400px 1fr;
        }
    }
    .storage-info {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .storage-bar {
        width: 100%;
        height: 24px;
        background: #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 12px;
    }
    .storage-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        transition: width 0.3s ease;
    }
    .storage-fill.warning {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }
    .storage-fill.danger {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }
    .upload-section {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    .file-input-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    .file-input-label {
        display: block;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #6b7280;
        font-size: 14px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
    }
    .file-input-label:hover {
        border-color: var(--primary);
        background: #f9fafb;
    }
    .file-input-wrapper input[type="file"]:focus + .file-input-label {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .file-name-display {
        margin-top: 8px;
        font-size: 12px;
        color: var(--primary);
        font-weight: 600;
    }
    .upload-section h2 {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 20px;
        color: var(--primary);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .documents-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 768px) {
        .documents-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (min-width: 1024px) {
        .documents-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    .category-section {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .category-section h3 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 16px;
        color: var(--primary);
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .document-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }
    .document-item:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .document-info {
        flex: 1;
    }
    .document-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .document-meta {
        font-size: 12px;
        color: #6b7280;
    }
    .document-actions {
        display: flex;
        gap: 8px;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-download {
        background: var(--primary);
        color: #fff;
    }
    .btn-download:hover {
        background: var(--primary-dark);
    }
    .btn-delete {
        background: #ef4444;
        color: #fff;
    }
    .btn-delete:hover {
        background: #dc2626;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }
    .alert-error {
        background: #fee2e2;
        border-color: #ef4444;
        color: #991b1b;
    }
</style>

<div class="documents-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Moja biblioteka dokumenata</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Upravljajte svojim dokumentima i koristite ih pri prijavama na konkurse</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Informacije o prostoru i Upload sekcija u istom redu -->
        <div class="top-sections-grid">
            <!-- Informacije o prostoru -->
            <div class="storage-info">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600; color: #374151;">Iskorišćen prostor</span>
                    <span style="font-weight: 600; color: var(--primary);">{{ $usedStorageMB }} MB / {{ $maxStorageMB }} MB</span>
                </div>
                <div class="storage-bar">
                    <div class="storage-fill {{ $storagePercentage > 80 ? 'danger' : ($storagePercentage > 60 ? 'warning' : '') }}" 
                         style="width: {{ $storagePercentage }}%"></div>
                </div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                    {{ $storagePercentage }}% iskorišćeno
                </div>
            </div>

            <!-- Upload sekcija -->
            <div class="upload-section">
            <h2>Dodaj novi dokument</h2>
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="document-upload-form" onsubmit="return prepareFormSubmit(event)">
                @csrf
                <div class="form-group">
                    <label for="name" class="form-label">Naziv dokumenta <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="category" class="form-label">Kategorija <span style="color: #ef4444;">*</span></label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="">Izaberite kategoriju</option>
                        <option value="Lični dokumenti">Lični dokumenti</option>
                        <option value="Finansijski dokumenti">Finansijski dokumenti</option>
                        <option value="Poslovni dokumenti">Poslovni dokumenti</option>
                        <option value="Ostali dokumenti">Ostali dokumenti</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file" class="form-label">Fajlovi <span style="color: #ef4444;">*</span></label>
                    <div class="file-input-wrapper">
                        <input type="file" name="files[]" id="file" required multiple
                               accept="image/jpeg,image/png,image/jpg,application/pdf"
                               onchange="updateFileDisplay(this)">
                        <label for="file" class="file-input-label" id="file-label" onclick="event.stopPropagation();">Izaberi fajlove (možete izabrati više)</label>
                        <div id="file-names" class="file-name-display" style="display: none; margin-top: 8px;" onclick="event.stopPropagation();"></div>
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 4px;">
                        Dozvoljeni formati: JPEG, PNG, PDF (max 10MB po fajlu). Dokumenti će biti automatski konvertovani u greyscale PDF format sa 300 DPI rezolucijom.
                    </small>
                    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin-top: 12px; border-radius: 4px;">
                        <strong style="color: #1e40af; display: block; margin-bottom: 4px;">ℹ️ Važno:</strong>
                        <span style="color: #1e3a8a; font-size: 13px;">
                            Ako izaberete više fajlova, oni će biti spojeni u <strong>jedan PDF dokument</strong> tim redosledom kako su navedeni. 
                            Možete promeniti redosled fajlova pomoću dugmadi "Gore" i "Dole" pre upload-a.
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="expires_at" class="form-label">Datum isteka (opciono)</label>
                    <input type="date" name="expires_at" id="expires_at" class="form-control" 
                           min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                </div>
                <button type="submit" class="btn-primary">Upload dokumenta</button>
            </form>
            </div>
        </div>

        <!-- Lista dokumenata po kategorijama -->
        <div class="documents-grid">
            @foreach($categories as $categoryKey => $categoryName)
                <div class="category-section">
                    <h3>{{ $categoryName }}</h3>
                    @if(isset($documents[$categoryKey]) && $documents[$categoryKey]->count() > 0)
                        @foreach($documents[$categoryKey] as $document)
                            <div class="document-item" data-document-id="{{ $document->id }}">
                                <div class="document-info">
                                    <div class="document-name">{{ $document->name }}</div>
                                    <div class="document-meta">
                                        {{ $document->formatted_file_size }} • 
                                        Upload-ovano: {{ $document->created_at->format('d.m.Y H:i') }}
                                        @if($document->expires_at)
                                            • Ističe: {{ $document->expires_at->format('d.m.Y') }}
                                        @endif
                                        @if($document->isExpired())
                                            <span style="color: #ef4444; font-weight: 600;"> (ISTEKLO)</span>
                                        @endif
                                    </div>
                                    @if($document->status === 'pending')
                                        <div class="document-status" style="margin-top: 8px; color: #f59e0b; font-size: 13px; font-weight: 500;">
                                            ⏳ Čeka obradu...
                                        </div>
                                    @elseif($document->status === 'processing')
                                        <div class="document-status" style="margin-top: 8px; color: #3b82f6; font-size: 13px; font-weight: 500;">
                                            🔄 U obradi...
                                        </div>
                                    @elseif($document->status === 'failed')
                                        <div class="document-status" style="margin-top: 8px; color: #ef4444; font-size: 13px; font-weight: 500;">
                                            ❌ Greška pri obradi
                                        </div>
                                    @elseif($document->status === 'processed' && $document->processed_at)
                                        <div class="document-status" style="margin-top: 8px; color: #10b981; font-size: 13px; font-weight: 500;">
                                            ✅ Obrađeno: {{ $document->processed_at->format('d.m.Y H:i') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="document-actions" data-document-status="{{ $document->status }}">
                                    @if($document->status === 'processed' || $document->status === 'active')
                                        <a href="{{ route('documents.download', $document) }}" class="btn-sm btn-download">
                                            Preuzmi
                                        </a>
                                    @endif
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovaj dokument?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-delete">Obriši</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state" style="padding: 20px; text-align: center; color: #6b7280;">
                            <p style="margin: 0; font-size: 14px;">Nema upload-ovanih dokumenata u ovoj kategoriji.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
// Čuva prethodno izabrane fajlove
let selectedFiles = [];

// Funkcija za prikaz izabranih fajlova
function updateFileDisplay(input) {
    const fileNamesDiv = document.getElementById('file-names');
    const fileLabel = document.getElementById('file-label');
    
    // Dodaj nove fajlove u listu (izbegni duplikate)
    if (input.files && input.files.length > 0) {
        const newFiles = Array.from(input.files);
        
        // Proveri da li fajl već postoji (po imenu i veličini)
        newFiles.forEach(newFile => {
            const exists = selectedFiles.some(existingFile => 
                existingFile.name === newFile.name && existingFile.size === newFile.size
            );
            
            if (!exists) {
                selectedFiles.push(newFile);
            }
        });
        
        // Kreiraj novi DataTransfer objekat sa svim fajlovima
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        
        // Postavi novi FileList na input
        input.files = dataTransfer.files;
    }
    
    // Prikaži sve izabrane fajlove
    if (selectedFiles.length > 0) {
        let fileList = '<div style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 4px;">Izabrano fajlova: ' + selectedFiles.length + (selectedFiles.length > 1 ? ' (biće spojeni u jedan PDF)' : '') + '</div>';
        fileList += '<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #6b7280; list-style: none;">';
        
        selectedFiles.forEach((file, index) => {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            fileList += '<li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 6px; background: #f9fafb; border-radius: 4px;">';
            fileList += '<span style="flex: 1;"><strong style="color: var(--primary);">' + (index + 1) + '.</strong> ' + file.name + ' (' + fileSize + ' MB)</span>';
            fileList += '<div style="display: flex; gap: 4px; margin-left: 8px;">';
            
            // Dugme za pomeranje gore
            if (index > 0) {
                fileList += '<button type="button" class="file-action-btn" data-action="move-up" data-index="' + index + '" title="Pomeri gore" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">⬆️</button>';
            } else {
                fileList += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">⬆️</button>';
            }
            
            // Dugme za pomeranje dole
            if (index < selectedFiles.length - 1) {
                fileList += '<button type="button" class="file-action-btn" data-action="move-down" data-index="' + index + '" title="Pomeri dole" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">⬇️</button>';
            } else {
                fileList += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">⬇️</button>';
            }
            
            // Dugme za uklanjanje
            fileList += '<button type="button" class="file-action-btn" data-action="remove" data-index="' + index + '" title="Ukloni" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">✕</button>';
            fileList += '</div>';
            fileList += '</li>';
        });
        
        fileList += '</ul>';
        fileNamesDiv.innerHTML = fileList;
        fileNamesDiv.style.display = 'block';
        
        if (selectedFiles.length === 1) {
            fileLabel.textContent = selectedFiles[0].name;
        } else {
            fileLabel.textContent = 'Izabrano ' + selectedFiles.length + ' fajlova (biće spojeni u jedan PDF)';
        }
    } else {
        fileNamesDiv.style.display = 'none';
        fileLabel.textContent = 'Izaberi fajlove (možete izabrati više)';
    }
}

// Funkcija za uklanjanje fajla iz liste
function removeFile(index, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    selectedFiles.splice(index, 1);
    
    // Ažuriraj input sa preostalim fajlovima
    const input = document.getElementById('file');
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    // Ažuriraj prikaz
    updateFileDisplay(input);
    
    return false;
}

// Funkcija za pomeranje fajla gore
function moveFileUp(index, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (index > 0) {
        // Zameni pozicije
        const temp = selectedFiles[index];
        selectedFiles[index] = selectedFiles[index - 1];
        selectedFiles[index - 1] = temp;
        
        // Ažuriraj input sa novim redosledom
        const input = document.getElementById('file');
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        input.files = dataTransfer.files;
        
        // Ažuriraj prikaz
        updateFileDisplay(input);
    }
    
    return false;
}

// Funkcija za pomeranje fajla dole
function moveFileDown(index, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (index < selectedFiles.length - 1) {
        // Zameni pozicije
        const temp = selectedFiles[index];
        selectedFiles[index] = selectedFiles[index + 1];
        selectedFiles[index + 1] = temp;
        
        // Ažuriraj input sa novim redosledom
        const input = document.getElementById('file');
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        input.files = dataTransfer.files;
        
        // Ažuriraj prikaz
        updateFileDisplay(input);
    }
    
    return false;
}

// Funkcija za pripremu forme pre submit-a
function prepareFormSubmit(event) {
    const input = document.getElementById('file');
    
    // Proveri da li ima izabranih fajlova
    if (selectedFiles.length === 0) {
        event.preventDefault();
        alert('Molimo izaberite barem jedan fajl.');
        return false;
    }
    
    // Osiguraj da su fajlovi u input-u pre submit-a
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    // Resetuj listu nakon kratke pauze (da forma stigne da se pošalje)
    setTimeout(function() {
        selectedFiles = [];
        input.value = '';
        updateFileDisplay(input);
    }, 100);
    
    return true;
}

// Event listener za dugmad (umesto inline onclick) - koristi capture fazu da spreči propagaciju
document.addEventListener('click', function(event) {
    // Proveri da li je klik na dugme ili unutar dugmeta
    const btn = event.target.closest('.file-action-btn');
    if (!btn) return;
    
    // Zaustavi sve propagacije
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    
    // Spreči aktivaciju label-a
    const label = document.getElementById('file-label');
    if (label) {
        label.style.pointerEvents = 'none';
        setTimeout(() => {
            label.style.pointerEvents = 'auto';
        }, 100);
    }
    
    const action = btn.getAttribute('data-action');
    const index = parseInt(btn.getAttribute('data-index'));
    
    if (action === 'move-up') {
        moveFileUp(index, event);
    } else if (action === 'move-down') {
        moveFileDown(index, event);
    } else if (action === 'remove') {
        removeFile(index, event);
    }
    
    return false;
}, true); // useCapture = true da bi se event uhvatio pre propagacije

document.addEventListener('DOMContentLoaded', function() {
    
    // Funkcija za ažuriranje statusa dokumenta u DOM-u
    function updateDocumentStatus(documentId, status, processedAt) {
        console.log('updateDocumentStatus pozvan:', { documentId, status, processedAt });
        
        // Pronađi document-item sa odgovarajućim ID-jem
        const documentItem = document.querySelector(`.document-item[data-document-id="${documentId}"]`);
        if (!documentItem) {
            console.warn('Dokument nije pronađen u DOM-u:', documentId);
            return;
        }
        
        const documentInfo = documentItem.querySelector('.document-info');
        if (!documentInfo) {
            console.warn('Document info nije pronađen za dokument:', documentId);
            return;
        }
        
        // Pronađi ili kreiraj status element
        let statusElement = documentInfo.querySelector('.document-status');
        if (!statusElement) {
            console.log('Kreiram novi status element');
            statusElement = document.createElement('div');
            statusElement.className = 'document-status';
            statusElement.style.marginTop = '8px';
            statusElement.style.fontSize = '13px';
            statusElement.style.fontWeight = '500';
            documentInfo.appendChild(statusElement);
        }
        
        // Ažuriraj status
        if (status === 'pending') {
            statusElement.innerHTML = '⏳ Čeka obradu...';
            statusElement.style.color = '#f59e0b';
        } else if (status === 'processing') {
            statusElement.innerHTML = '🔄 U obradi...';
            statusElement.style.color = '#3b82f6';
        } else if (status === 'failed') {
            statusElement.innerHTML = '❌ Greška pri obradi';
            statusElement.style.color = '#ef4444';
        } else if (status === 'processed' && processedAt) {
            statusElement.innerHTML = '✅ Obrađeno: ' + processedAt;
            statusElement.style.color = '#10b981';
        } else if (status === 'processed') {
            statusElement.innerHTML = '✅ Obrađeno';
            statusElement.style.color = '#10b981';
        }
        
        console.log('Status ažuriran:', statusElement.innerHTML);
        
        // Ažuriraj actions sekciju (dodaj/ukloni download dugme)
        const actionsElement = documentItem.querySelector('.document-actions');
        if (actionsElement) {
            actionsElement.setAttribute('data-document-status', status);
            
            // Ako je status processed ili active, dodaj download dugme ako ne postoji
            if ((status === 'processed' || status === 'active') && !actionsElement.querySelector('.btn-download')) {
                const downloadLink = document.createElement('a');
                downloadLink.href = `/documents/${documentId}/download`;
                downloadLink.className = 'btn-sm btn-download';
                downloadLink.textContent = 'Preuzmi';
                actionsElement.insertBefore(downloadLink, actionsElement.firstChild);
                console.log('Download dugme dodato');
            } else if (status !== 'processed' && status !== 'active') {
                // Ukloni download dugme ako status nije processed ili active
                const downloadLink = actionsElement.querySelector('.btn-download');
                if (downloadLink) {
                    downloadLink.remove();
                    console.log('Download dugme uklonjeno');
                }
            }
        }
    }
    
    // Funkcija za proveru statusa
    let statusCheckInterval = null;
    
    function checkDocumentStatus() {
        console.log('Proveravam status dokumenata...');
        
        fetch('{{ route("documents.status") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Status data:', data);
            
            if (data.documents && data.documents.length > 0) {
                console.log('Pronađeno dokumenata u obradi:', data.documents.length);
                
                data.documents.forEach(doc => {
                    console.log('Ažuriram dokument ID:', doc.id, 'Status:', doc.status, 'Processed at:', doc.processed_at);
                    updateDocumentStatus(doc.id, doc.status, doc.processed_at);
                });
                
                // Proveri da li još ima dokumenata u obradi
                const hasProcessing = data.documents.some(doc => 
                    doc.status === 'pending' || doc.status === 'processing'
                );
                
                console.log('Ima dokumenata u obradi:', hasProcessing);
                
                // Ako nema više dokumenata u obradi, zaustavi proveru
                if (!hasProcessing && statusCheckInterval) {
                    console.log('Zaustavljam proveru statusa');
                    clearInterval(statusCheckInterval);
                    statusCheckInterval = null;
                }
            } else {
                console.log('Nema dokumenata u obradi');
                // Ako nema dokumenata u obradi, zaustavi proveru
                if (statusCheckInterval) {
                    clearInterval(statusCheckInterval);
                    statusCheckInterval = null;
                }
            }
        })
        .catch(error => {
            console.error('Greška pri proveri statusa:', error);
        });
    }
    
    // Proveri da li ima dokumenata u pending ili processing statusu
    const documentItems = document.querySelectorAll('.document-item[data-document-id]');
    let hasPendingOrProcessing = false;
    
    documentItems.forEach(item => {
        const statusElement = item.querySelector('.document-status');
        if (statusElement) {
            const statusText = statusElement.textContent;
            if (statusText.includes('Čeka obradu') || statusText.includes('U obradi')) {
                hasPendingOrProcessing = true;
            }
        }
    });
    
    // Pokreni proveru statusa ako ima dokumenata u obradi
    if (hasPendingOrProcessing) {
        // Prva provera nakon 1 sekunde (brže da uhvatimo processing status)
        setTimeout(function() {
            checkDocumentStatus();
            
            // Pokreni interval za proveru svakih 1 sekund (brže ažuriranje)
            if (!statusCheckInterval) {
                statusCheckInterval = setInterval(checkDocumentStatus, 1000);
            }
        }, 1000);
    } else {
        // Proveri jednom da vidimo da li ima dokumenata u obradi (možda su se promijenili)
        setTimeout(function() {
            checkDocumentStatus();
            
            // Ako nakon provere ima dokumenata u obradi, pokreni interval
            const stillProcessing = Array.from(document.querySelectorAll('.document-item[data-document-id]')).some(item => {
                const status = item.querySelector('.document-status');
                return status && (status.textContent.includes('Čeka obradu') || status.textContent.includes('U obradi'));
            });
            
            if (stillProcessing && !statusCheckInterval) {
                statusCheckInterval = setInterval(checkDocumentStatus, 1000);
            }
        }, 1000);
    }
});
</script>
@endsection

