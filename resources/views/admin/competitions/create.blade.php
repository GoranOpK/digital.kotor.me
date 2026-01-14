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
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Kreiraj novi konkurs</h1>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('admin.competitions.store') }}">
                @csrf
                
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tip konkursa *</label>
                        <select name="type" class="form-control" required>
                            <option value="zensko" {{ old('type') === 'zensko' ? 'selected' : '' }}>Žensko preduzetništvo</option>
                            <option value="omladinsko" {{ old('type') === 'omladinsko' ? 'selected' : '' }}>Omladinsko preduzetništvo</option>
                            <option value="ostalo" {{ old('type') === 'ostalo' ? 'selected' : '' }}>Ostalo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Godina *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2020" max="2100" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Broj konkursa</label>
                        <input type="number" name="competition_number" class="form-control" value="{{ old('competition_number') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Budžet (€) *</label>
                        <input type="number" name="budget" class="form-control" value="{{ old('budget') }}" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Maksimalna podrška (%) *</label>
                        <input type="number" name="max_support_percentage" class="form-control" value="{{ old('max_support_percentage', 30) }}" step="0.01" min="0" max="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Datum početka</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}">
                    </div>
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

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">Sačuvaj konkurs</button>
                    <a href="{{ route('admin.competitions.index') }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

