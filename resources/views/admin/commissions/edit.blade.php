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
            <h1>Izmijeni komisiju</h1>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('admin.commissions.update', $commission) }}">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label class="form-label">Naziv komisije *</label>
                    <input type="text" name="name" class="form-control @error('name') error @enderror" value="{{ old('name', $commission->name) }}" required>
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Godina *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', $commission->year) }}" min="2020" max="2100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', $commission->status) === 'active' ? 'selected' : '' }}>Aktivna</option>
                            <option value="inactive" {{ old('status', $commission->status) === 'inactive' ? 'selected' : '' }}>Neaktivna</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum početka mandata *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $commission->start_date->format('Y-m-d')) }}" required onchange="calculateEndDate()">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Mandat komisije traje tačno 2 godine od datuma početka
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum završetka mandata</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $commission->end_date->format('Y-m-d')) }}" readonly style="background: #f3f4f6;">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Automatski izračunato (2 godine od datuma početka)
                    </div>
                </div>

                <script>
                    function calculateEndDate() {
                        const startDateInput = document.getElementById('start_date');
                        const endDateInput = document.getElementById('end_date');
                        
                        if (startDateInput.value) {
                            const startDate = new Date(startDateInput.value);
                            const endDate = new Date(startDate);
                            endDate.setFullYear(endDate.getFullYear() + 2);
                            
                            // Formatiraj datum kao YYYY-MM-DD
                            const year = endDate.getFullYear();
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const day = String(endDate.getDate()).padStart(2, '0');
                            
                            endDateInput.value = `${year}-${month}-${day}`;
                        } else {
                            endDateInput.value = '';
                        }
                    }
                    
                    // Izračunaj na učitavanju stranice
                    document.addEventListener('DOMContentLoaded', function() {
                        if (document.getElementById('start_date').value) {
                            calculateEndDate();
                        }
                    });
                </script>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">Sačuvaj izmjene</button>
                    <a href="{{ route('admin.commissions.show', $commission) }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

