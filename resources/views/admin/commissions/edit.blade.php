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
                        Mandat komisije traje tačno 1 godinu od datuma početka
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum završetka mandata</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $commission->end_date->format('Y-m-d')) }}" readonly style="background: #f3f4f6;">
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

                <!-- Dodjela konkursa komisiji -->
                @if($competitions->count() > 0)
                <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Dodjela konkursa</h2>
                    <div class="form-group">
                        <label class="form-label">Izaberi konkurs(e) za ovu komisiju</label>
                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb;">
                            @php
                                $assignedCompetitionIds = $commission->competitions->pluck('id')->toArray();
                            @endphp
                            @foreach($competitions as $competition)
                                <div style="margin-bottom: 12px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="competition_ids[]" value="{{ $competition->id }}" 
                                               {{ in_array($competition->id, old('competition_ids', $assignedCompetitionIds)) ? 'checked' : '' }}
                                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                                        <span style="font-size: 14px; color: #374151;">
                                            {{ $competition->title }} ({{ $competition->year }})
                                            @if($competition->commission && $competition->commission->id != $commission->id)
                                                <span style="color: #6b7280; font-size: 12px;">- Već dodijeljeno: {{ $competition->commission->name }}</span>
                                            @endif
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                            Možete izabrati više konkursa. Ista komisija može biti dodijeljena na više različitih konkursa.
                        </div>
                    </div>
                </div>
                @endif

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">Sačuvaj izmjene</button>
                    <a href="{{ route('admin.commissions.show', $commission) }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>

            <div style="margin-top: 36px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 16px;">Dodaj zamjenskog člana</h2>
                <p style="font-size: 13px; color: #6b7280; margin: 0 0 16px;">
                    Zamjenski član se imenuje ukoliko je neki član opravdano odsutan i preuzima ista prava i obaveze člana/predsjednika kojeg mijenja.
                </p>

                <form method="POST" action="{{ route('admin.commissions.members.add', $commission) }}" autocomplete="on">
                    @csrf
                    <input type="hidden" name="position" value="clan">
                    <input type="hidden" name="member_type" value="zamjenski">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ime i prezime *</label>
                            <input type="text" name="name" class="form-control @error('name') error @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control @error('email') error @enderror" value="{{ old('email') }}" autocapitalize="off" spellcheck="false" autocomplete="section-commission-substitute username">
                            @error('email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @error('password') error @enderror" minlength="8" autocomplete="section-commission-substitute current-password">
                            @error('password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mijenja člana/predsjednika *</label>
                            <select name="replaces_member_number" class="form-control @error('replaces_member_number') error @enderror">
                                <option value="">-- Izaberite --</option>
                                <option value="1" {{ old('replaces_member_number') == '1' ? 'selected' : '' }}>Predsjednik komisije</option>
                                <option value="2" {{ old('replaces_member_number') == '2' ? 'selected' : '' }}>Član 2 - Predstavnik Opštine</option>
                                <option value="3" {{ old('replaces_member_number') == '3' ? 'selected' : '' }}>Član 3 - Predstavnik Opštine</option>
                                <option value="4" {{ old('replaces_member_number') == '4' ? 'selected' : '' }}>Član 4 - Predstavnica Udruženja/Biznisa/Akademske zajednice</option>
                                <option value="5" {{ old('replaces_member_number') == '5' ? 'selected' : '' }}>Član 5 - Predstavnica Ženske političke mreže</option>
                            </select>
                            @error('replaces_member_number')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organizacija</label>
                        <input type="text" name="organization" class="form-control @error('organization') error @enderror" value="{{ old('organization') }}" placeholder="Obavezno ako mijenja člana iz udruženja">
                        @error('organization')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-top: 16px;">
                        <button type="submit" class="btn-primary">Dodaj zamjenskog člana</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

