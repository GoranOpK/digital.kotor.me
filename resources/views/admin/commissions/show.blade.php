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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-left: 8px;
    }
    .btn-primary {
        background: #fff;
        color: var(--primary);
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-active {
        background: #d1fae5;
        color: #065f46;
    }
    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    .members-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .member-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .member-info {
        flex: 1;
    }
    .member-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .member-details {
        font-size: 12px;
        color: #6b7280;
    }
    .member-actions {
        display: flex;
        gap: 8px;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
    }
    .btn-success {
        background: #10b981;
        color: #fff;
    }
    .btn-warning {
        background: #f59e0b;
        color: #fff;
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
    }
    .form-card {
        background: #f9fafb;
        padding: 20px;
        border-radius: 8px;
        margin-top: 16px;
    }
    .form-group {
        margin-bottom: 16px;
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
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
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
    .alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
        align-items: stretch;
    }
    @media (min-width: 1024px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .info-grid .info-card {
        margin-bottom: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $commission->name }}</h1>
            <div>
                <a href="{{ route('admin.commissions.edit', $commission) }}" class="btn btn-primary">Izmijeni</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="info-grid">
            <!-- Osnovne informacije -->
            <div class="info-card">
                <h2>Osnovne informacije</h2>
                <p><strong>Naziv:</strong> {{ $commission->name }}</p>
                <p><strong>Godina:</strong> {{ $commission->year }}</p>
                <p><strong>Mandat:</strong> {{ $commission->start_date->format('d.m.Y') }} - {{ $commission->end_date->format('d.m.Y') }}</p>
                <p><strong>Status:</strong> <span class="status-badge status-{{ $commission->status }}">{{ $commission->status === 'active' ? 'Aktivna' : 'Neaktivna' }}</span></p>
                <p><strong>Broj članova:</strong> {{ $commission->members->count() }} / 5</p>
            </div>

            <!-- Članovi komisije -->
            <div class="info-card">
            <h2>Članovi komisije</h2>
            
            @if($commission->members->count() > 0)
                <ul class="members-list">
                    @foreach($commission->members as $member)
                        <li class="member-item">
                            <div class="member-info">
                                <div class="member-name">
                                    {{ $member->name }}
                                    @if($member->position === 'predsjednik')
                                        <span style="color: var(--primary); font-size: 12px;">(Predsjednik)</span>
                                    @endif
                                </div>
                                <div class="member-details">
                                    <div>Pozicija: {{ $member->position }}</div>
                                    <div>Tip: 
                                        @if($member->member_type === 'opstina') Opština
                                        @elseif($member->member_type === 'udruzenje') Udruženje
                                        @elseif($member->member_type === 'zene_mreza') Žene mreža
                                        @endif
                                    </div>
                                    @if($member->organization)
                                        <div>Organizacija: {{ $member->organization }}</div>
                                    @endif
                                    <div>Status: 
                                        <span style="color: {{ $member->status === 'active' ? '#10b981' : '#ef4444' }};">
                                            {{ $member->status === 'active' ? 'Aktivan' : ($member->status === 'resigned' ? 'Podneo ostavku' : 'Razriješen') }}
                                        </span>
                                    </div>
                                    @if($member->hasSignedDeclarations())
                                        <div style="color: #10b981; margin-top: 4px;">
                                            ✓ Izjave potpisane: {{ $member->declarations_signed_at->format('d.m.Y') }}
                                        </div>
                                    @else
                                        <div style="color: #f59e0b; margin-top: 4px;">
                                            ⚠ Izjave nisu potpisane
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="member-actions">
                                <form method="POST" action="{{ route('admin.commissions.members.delete', $member) }}" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovog člana?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-danger">Obriši</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: #6b7280; text-align: center; padding: 40px;">
                    Nema članova u komisiji.
                </p>
            @endif

            <!-- Forma za dodavanje člana -->
            @if($commission->members->count() < 5)
            <div class="form-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Dodaj novog člana</h3>
                <form method="POST" action="{{ route('admin.commissions.members.add', $commission) }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">Korisnik iz sistema (opciono)</label>
                        <select name="user_id" class="form-control">
                            <option value="">Izaberi korisnika...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Ako član već postoji u sistemu, izaberi ga. Inače unesi podatke ispod.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ime i prezime *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Pozicija *</label>
                            <select name="position" class="form-control" required>
                                <option value="predsjednik">Predsjednik</option>
                                <option value="clan">Član</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tip člana *</label>
                            <select name="member_type" class="form-control" required>
                                <option value="opstina">Opština</option>
                                <option value="udruzenje">Udruženje</option>
                                <option value="zene_mreza">Žene mreža</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organizacija (opciono)</label>
                        <input type="text" name="organization" class="form-control" value="{{ old('organization') }}" placeholder="Naziv organizacije...">
                    </div>

                    <button type="submit" class="btn-sm btn-success">Dodaj člana</button>
                </form>
            </div>
            @else
                <div class="alert alert-warning">
                    Komisija je popunjena (5/5 članova). Za dodavanje novog člana, prvo uklonite postojećeg.
                </div>
            @endif
            </div>
        </div>
    </div>
</div>
@endsection

