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
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
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
                    <label for="file" class="form-label">Fajl <span style="color: #ef4444;">*</span></label>
                    <div class="file-input-wrapper">
                        <input type="file" name="file" id="file" required 
                               accept="image/jpeg,image/png,image/jpg,application/pdf"
                               onchange="if(this.files[0]) { document.getElementById('file-label').textContent = this.files[0].name; document.getElementById('file-name').textContent = this.files[0].name; document.getElementById('file-name').style.display = 'block'; } else { document.getElementById('file-label').textContent = 'Izaberi fajl'; document.getElementById('file-name').style.display = 'none'; }">
                        <label for="file" class="file-input-label" id="file-label">Izaberi fajl</label>
                        <div id="file-name" class="file-name-display" style="display: none;"></div>
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 4px;">
                        Dozvoljeni formati: JPEG, PNG, PDF (max 10MB). Dokument će biti automatski optimizovan u PDF format.
                    </small>
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
                            <div class="document-item">
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
                                </div>
                                <div class="document-actions">
                                    <a href="{{ route('documents.download', $document) }}" class="btn-sm btn-download">
                                        Preuzmi
                                    </a>
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
@endsection

