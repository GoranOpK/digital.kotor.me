@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
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
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
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
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .form-step {
        margin-bottom: 28px;
        padding-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    .form-step h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 16px;
    }
    .type-option {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        font-size: 15px;
        color: #374151;
        cursor: pointer;
    }
</style>

@php
    $selectedCompetitionType = $selectedCompetitionType ?? null;
    $commissionFormTypes = $commissionFormTypes ?? [];
    $statusLabels = ['draft' => 'Nacrt', 'published' => 'Objavljen', 'closed' => 'Zatvoren', 'completed' => 'Završen'];
    $oldCompetitionIds = old('competition_ids', isset($targetCompetition) && $targetCompetition ? [$targetCompetition->id] : []);
@endphp

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Kreiraj novu komisiju</h1>
        </div>

        <div class="form-card">
            <div class="form-step">
                <h2>1. Vrsta konkursa</h2>
                <form method="GET" action="{{ route('admin.commissions.create') }}" id="commission-type-form">
                    @foreach($commissionFormTypes as $type => $label)
                        <label class="type-option">
                            <input type="radio" name="competition_type" value="{{ $type }}"
                                   {{ $selectedCompetitionType === $type ? 'checked' : '' }}
                                   onchange="this.form.submit()">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <noscript>
                        <button type="submit" class="btn-primary" style="margin-top: 8px;">Prikaži Pozive</button>
                    </noscript>
                </form>
                @if($selectedCompetitionType === null)
                    <p style="font-size: 14px; color: #6b7280; margin: 12px 0 0;">Izaberite vrstu konkursa da biste nastavili sa izborom Poziva i sastavom Komisije.</p>
                @endif
            </div>

            @if($selectedCompetitionType)
            <form method="POST" action="{{ route('admin.commissions.store') }}" autocomplete="on">
                @csrf
                <input type="hidden" name="competition_type" value="{{ $selectedCompetitionType }}">
                @error('competition_type')
                    <div class="error-message" style="margin-bottom: 12px;">{{ $message }}</div>
                @enderror

                <div class="form-group">
                    <label class="form-label">Naziv komisije *</label>
                    <input type="text" name="name" class="form-control @error('name') error @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Godina *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2020" max="2100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 12px; color: #6b7280;">
                            Mandat komisije traje 1 godinu
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum početka mandata *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required onchange="calculateEndDate()">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Mandat komisije traje tačno 1 godinu od datuma početka
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum završetka mandata</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" readonly style="background: #f3f4f6;">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Automatski izračunato (1 godina od datuma početka)
                    </div>
                </div>

                <script>
                    function calculateEndDate() {
                        const startDateInput = document.getElementById('start_date');
                        const endDateInput = document.getElementById('end_date');
                        
                        if (startDateInput.value) {
                            const startDate = new Date(startDateInput.value);
                            const endDate = new Date(startDate);
                            endDate.setFullYear(endDate.getFullYear() + 1);
                            
                            const year = endDate.getFullYear();
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const day = String(endDate.getDate()).padStart(2, '0');
                            
                            endDateInput.value = `${year}-${month}-${day}`;
                        } else {
                            endDateInput.value = '';
                        }
                    }
                    
                    document.addEventListener('DOMContentLoaded', function() {
                        if (document.getElementById('start_date') && document.getElementById('start_date').value) {
                            calculateEndDate();
                        }
                    });
                </script>

                <div class="form-step">
                    <h2>2. Poziv</h2>
                    @error('competition_ids')
                        <div class="error-message" style="margin-bottom: 12px;">{{ $message }}</div>
                    @enderror
                    @if($competitions->count() > 0)
                        <div class="form-group">
                            <label class="form-label">
                                @if(!empty($isOmladinskoCommissionForm))
                                    Izaberite omladinski Poziv (opciono)
                                @else
                                    Izaberi konkurs(e) za ovu komisiju (opciono)
                                @endif
                            </label>
                            <div style="background: #f9fafb; padding: 16px; border-radius: 8px; max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb;">
                                @foreach($competitions as $competition)
                                    <div style="margin-bottom: 12px;">
                                        <label style="display: flex; align-items: flex-start; cursor: pointer;">
                                            <input type="checkbox" name="competition_ids[]" value="{{ $competition->id }}"
                                                   {{ in_array($competition->id, $oldCompetitionIds) ? 'checked' : '' }}
                                                   style="margin-right: 8px; margin-top: 3px; width: 18px; height: 18px; cursor: pointer;">
                                            <span style="font-size: 14px; color: #374151;">
                                                {{ $competition->title }} ({{ $competition->year }})
                                                — {{ $statusLabels[$competition->status] ?? $competition->status }}
                                                @if($competition->call_number)
                                                    — Poziv br. {{ $competition->call_number }}
                                                @endif
                                                @if($competition->commission)
                                                    <span style="color: #6b7280; font-size: 12px;">- Već dodijeljeno: {{ $competition->commission->name }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                Poziv nije obavezan pri kreiranju. Možete ostaviti prazno ili izabrati jedan ili više Poziva izabranog profila.
                                @if(!empty($isOmladinskoCommissionForm))
                                    Ponuđeni su samo omladinski Pozivi.
                                @endif
                            </div>
                        </div>
                    @else
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Nema dostupnih Poziva ovog tipa. Komisija se može sačuvati i bez Poziva.</p>
                    @endif
                </div>

                <div class="form-step" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">
                    <h2>3. Sastav Komisije</h2>
                    @error('members')
                        <div class="error-message" style="margin-bottom: 12px;">{{ $message }}</div>
                    @enderror
                    @if(!empty($isOmladinskoCommissionForm))
                        @include('admin.commissions.partials.create-omladinsko-members')
                    @else
                        @include('admin.commissions.partials.create-zensko-members')
                    @endif
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">Sačuvaj komisiju</button>
                    <a href="{{ route('admin.commissions.index') }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
