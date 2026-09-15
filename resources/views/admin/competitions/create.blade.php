@extends('layouts.app')

@section('content')
@php
    $isSecondCallForm = $isSecondCallForm ?? false;
    $selectedType = $isSecondCallForm
        ? 'omladinsko'
        : old('type', $presetType ?? 'zensko');
@endphp
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
    .form-control[readonly] {
        background: #f3f4f6;
        color: #374151;
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
    .input-uppercase {
        text-transform: uppercase !important;
    }
    .info-note {
        padding: 12px 14px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        font-size: 14px;
        color: #1e3a8a;
        margin-bottom: 20px;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $isSecondCallForm ? 'Kreiraj drugi Poziv' : 'Kreiraj novi konkurs' }}</h1>
        </div>

        <div class="form-card">
            @if($errors->any())
                <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $isSecondCallForm ? route('admin.competitions.second-call.store', $firstCall) : route('admin.competitions.store') }}">
                @csrf

                @if($isSecondCallForm)
                    <div class="info-note">
                        <p style="margin: 0;"><strong>Drugi Poziv</strong> profila mladih. Profil, godina, redni broj Poziva i godišnji budžet nasljeđuju se sa prvog Poziva i ne mogu se mijenjati.</p>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Profil</label>
                            <input type="text" class="form-control" value="{{ $typeLabel ?? 'Podrška preduzetništvu mladih' }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Godina</label>
                            <input type="text" class="form-control" value="{{ $firstCall->year }}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Oznaka Poziva</label>
                            <input type="text" class="form-control" value="Drugi Poziv" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Godišnji budžet (€)</label>
                            <input type="text" class="form-control" value="{{ number_format((float) $firstCall->annual_budget, 2, ',', '.') }}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Konačno potvrđena raspodjela prvog Poziva (€)</label>
                            <input type="text" class="form-control" value="{{ number_format((float) $confirmedAllocation, 2, ',', '.') }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Preostala sredstva nakon prvog Poziva (€)</label>
                            <input type="text" class="form-control" value="{{ number_format((float) $remainingAfterFirst, 2, ',', '.') }}" readonly>
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Naziv konkursa *</label>
                    <input type="text" name="title" class="form-control @error('title') error @enderror" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Opis</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    <p class="form-hint" style="margin-top: 8px; font-size: 13px; color: #6b7280;">
                        Za link na riječ koristite HTML, npr.:
                        Informacije o konkursu možete naći &lt;a href="https://www.kotor.me/..."&gt;OVDJE&lt;/a&gt;
                    </p>
                </div>

                @if(! $isSecondCallForm)
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tip konkursa *</label>
                        <select name="type" id="competition_type" class="form-control" required>
                            <option value="zensko" {{ $selectedType === 'zensko' ? 'selected' : '' }}>Žensko preduzetništvo</option>
                            <option value="omladinsko" {{ $selectedType === 'omladinsko' ? 'selected' : '' }}>Omladinsko preduzetništvo</option>
                            <option value="ostalo" {{ $selectedType === 'ostalo' ? 'selected' : '' }}>Ostalo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Godina *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2020" max="2100" required>
                    </div>
                </div>
                @endif

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ $isSecondCallForm ? 'Zavodni broj drugog Poziva *' : 'Broj konkursa' }}</label>
                        <input type="text" id="up_number" name="up_number" class="form-control input-uppercase @error('up_number') error @enderror" value="{{ old('up_number') }}" required autocomplete="off">
                        @error('up_number')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ $isSecondCallForm ? 'Budžet drugog Poziva (€) *' : 'Ukupan budžet (€) *' }}</label>
                        <input type="number" name="budget" class="form-control @error('budget') error @enderror" value="{{ old('budget') }}" step="0.01" min="{{ $isSecondCallForm || $selectedType === 'omladinsko' ? '0.01' : '0' }}" required>
                        @error('budget')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if(! $isSecondCallForm)
                <div id="omladinsko-first-call-fields" class="info-note" style="{{ $selectedType === 'omladinsko' ? '' : 'display:none;' }}">
                    <p style="margin: 0 0 12px 0;"><strong>Prvi Poziv</strong> profila mladih. Redni broj Poziva sistem postavlja na 1. Drugi Poziv se kreira posebno, nakon završetka prvog, ako ostanu sredstva.</p>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Godišnji budžet (€) *</label>
                        <input type="number" name="annual_budget" class="form-control @error('annual_budget') error @enderror" value="{{ old('annual_budget') }}" step="0.01" min="0.01">
                        @error('annual_budget')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Datum početka</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Komisija</label>
                    <select name="commission_id" class="form-control @error('commission_id') error @enderror">
                        <option value="">Izaberi komisiju...</option>
                        @foreach($commissions as $commission)
                            <option value="{{ $commission->id }}" {{ old('commission_id') == $commission->id ? 'selected' : '' }}>
                                {{ $commission->name }} ({{ $commission->year }})
                            </option>
                        @endforeach
                    </select>
                    @error('commission_id')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Izaberite komisiju koja će evaluirati prijave za ovaj konkurs
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Informacije o roku</label>
                    <div style="padding: 10px 14px; background: #f3f4f6; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; color: #374151; max-width: 400px;">
                        <p style="margin: 0;"><strong>Rok za prijave:</strong> 20 dana</p>
                        <p style="margin: 5px 0 0 0;"><strong>Datum završetka:</strong> <span id="display_end_date">Izaberite datum početka</span></p>
                    </div>
                </div>

                <script>
                    document.getElementById('start_date').addEventListener('change', function() {
                        const startDateVal = this.value;
                        if (startDateVal) {
                            const startDate = new Date(startDateVal);
                            const endDate = new Date(startDate);
                            endDate.setDate(startDate.getDate() + 20);

                            const day = String(endDate.getDate()).padStart(2, '0');
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const year = endDate.getFullYear();

                            document.getElementById('display_end_date').innerText = day + '.' + month + '.' + year;
                        } else {
                            document.getElementById('display_end_date').innerText = 'Izaberite datum početka';
                        }
                    });
                </script>
                <script>
                    (function() {
                        var el = document.getElementById('up_number');
                        if (!el) return;
                        function toUpper() {
                            el.value = el.value.toUpperCase();
                        }
                        el.addEventListener('input', toUpper);
                        el.addEventListener('keyup', toUpper);
                        el.addEventListener('paste', function() {
                            setTimeout(toUpper, 0);
                        });
                        toUpper();
                    })();
                </script>
                @if(! $isSecondCallForm)
                <script>
                    (function() {
                        var type = document.getElementById('competition_type');
                        var box = document.getElementById('omladinsko-first-call-fields');
                        if (!type || !box) return;
                        function sync() {
                            box.style.display = type.value === 'omladinsko' ? '' : 'none';
                        }
                        type.addEventListener('change', sync);
                        sync();
                    })();
                </script>
                @endif

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">{{ $isSecondCallForm ? 'Sačuvaj drugi Poziv' : 'Sačuvaj konkurs' }}</button>
                    <a href="{{ $isSecondCallForm ? route('admin.competitions.show', $firstCall) : route('admin.competitions.index') }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
