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
    .btn-danger {
        background: #ef4444;
        color: #fff;
        border: none;
        cursor: pointer;
    }
    .btn-danger:hover {
        background: #dc2626;
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
    }
    .composition-slot {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        background: #fff;
    }
    .composition-slot.is-substituted {
        border-color: #f59e0b;
        background: #fffbeb;
    }
    .composition-slot.is-empty {
        border-style: dashed;
        background: #f9fafb;
    }
    .composition-slot-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
    }
    .composition-slot-name {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
    }
    .composition-slot-meta {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
        line-height: 1.5;
    }
    .composition-slot-actions {
        margin-top: 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $commission->name }}</h1>
            <div>
                <a href="{{ route('admin.commissions.edit', $commission) }}" class="btn btn-primary">Izmijeni</a>
                <form action="{{ route('admin.commissions.destroy', $commission) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovu komisiju? Ova akcija je nepovratna.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Obriši</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-warning">
                @foreach($errors->all() as $error)
                    <div>{{ is_array($error) ? implode(' ', $error) : $error }}</div>
                @endforeach
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
                <p><strong>Broj članova:</strong>
                    {{ $commission->members->reject(fn($m) => !empty($m->is_substitute))->count() }} / 5
                    @if($commission->members->contains(fn($m) => !empty($m->is_substitute) && $m->status === 'active')) + 1 zamjenski @endif
                </p>
            </div>

            <!-- Dodijeljeni konkursi (bez arhiviranih: closed/completed) -->
            @php
                $activeCompetitions = $commission->competitions->whereNotIn('status', ['closed', 'completed']);
            @endphp
            @if($activeCompetitions->count() > 0)
            <div class="info-card">
                <h2>Dodijeljeni konkursi</h2>
                <ul class="members-list">
                    @foreach($activeCompetitions as $competition)
                        <li class="member-item">
                            <div class="member-info">
                                <div class="member-name">
                                    <a href="{{ route('admin.competitions.show', $competition) }}" style="color: var(--primary); text-decoration: none;">
                                        {{ $competition->title }}
                                    </a>
                                </div>
                                <div class="member-details">
                                    <div>Godina: {{ $competition->year }}</div>
                                    <div>Status: 
                                        <span style="color: {{ $competition->status === 'published' ? '#10b981' : ($competition->status === 'closed' ? '#ef4444' : '#6b7280') }};">
                                            {{ $competition->status === 'published' ? 'Objavljen' : ($competition->status === 'closed' ? 'Zatvoren' : ($competition->status === 'draft' ? 'Nacrt' : 'Završen')) }}
                                        </span>
                                    </div>
                                    @if($competition->published_at)
                                        <div>Datum objave: {{ $competition->published_at->format('d.m.Y') }}</div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Sastav komisije (puna širina) -->
        <div class="info-card">
            <h2>Sastav komisije</h2>

            @foreach($compositionSlots as $compositionSlot)
                @php
                    $regularMember = $compositionSlot['regular_member'];
                    $activeSubstitute = $compositionSlot['active_substitute'];
                    $slotClass = 'composition-slot';
                    if ($activeSubstitute) {
                        $slotClass .= ' is-substituted';
                    } elseif (!$regularMember) {
                        $slotClass .= ' is-empty';
                    }
                @endphp
                <div class="{{ $slotClass }}">
                    <div class="composition-slot-title">{{ $compositionSlot['label'] }}</div>

                    @if($activeSubstitute)
                        <div class="composition-slot-name">
                            {{ $activeSubstitute->name }}
                            <span style="color: #92400e; font-size: 12px; font-weight: 700;">(Zamjenski član)</span>
                        </div>
                        <div class="composition-slot-meta">
                            Aktivno mijenja:
                            @if($regularMember)
                                {{ $regularMember->name }}
                                @if($regularMember->status !== 'active')
                                    — redovni član privremeno neaktivan
                                @endif
                            @else
                                nije dodijeljen redovni član na ovoj poziciji
                            @endif
                            @if($activeSubstitute->organization)
                                <br>Organizacija: {{ $activeSubstitute->organization }}
                            @endif
                        </div>
                        <div class="composition-slot-actions">
                            <form method="POST" action="{{ route('admin.commissions.members.delete', $activeSubstitute) }}" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da uklonite zamjenskog člana? Redovni član će biti vraćen u aktivni status.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">Ukloni zamjenskog</button>
                            </form>
                        </div>
                    @elseif($regularMember)
                        <div class="composition-slot-name">{{ $regularMember->name }}</div>
                        <div class="composition-slot-meta">
                            Status:
                            <span style="color: {{ $regularMember->status === 'active' ? '#10b981' : '#ef4444' }}; font-weight: 600;">
                                {{ $regularMember->status === 'active' ? 'Aktivan' : ($regularMember->status === 'inactive' ? 'Neaktivan' : ($regularMember->status === 'resigned' ? 'Podneo ostavku' : 'Razriješen')) }}
                            </span>
                            @if($regularMember->organization)
                                <br>Organizacija: {{ $regularMember->organization }}
                            @endif
                            @if($regularMember->user)
                                <br>E-mail: {{ $regularMember->user->email }}
                            @endif
                        </div>
                        <div class="composition-slot-actions">
                            <form method="POST" action="{{ route('admin.commissions.members.delete', $regularMember) }}" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovog člana?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">Obriši</button>
                            </form>
                        </div>
                    @else
                        <div class="composition-slot-name" style="color: #9ca3af; font-weight: 500;">Nije dodijeljen</div>
                    @endif
                </div>
            @endforeach

            <!-- Forma za dodavanje člana -->
            @php
                $regularMembersCount = $commission->members->reject(fn($m) => !empty($m->is_substitute))->count();
                $hasActiveSubstituteMember = $commission->members->contains(fn($m) => !empty($m->is_substitute) && $m->status === 'active');
            @endphp
            @if($regularMembersCount < 5)
            <div class="form-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Dodaj novog člana</h3>
                <form method="POST" action="{{ route('admin.commissions.members.add', $commission) }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">Korisnik iz sistema</label>
                        <select name="user_id" id="member_user_id" class="form-control" onchange="toggleMemberFields()">
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
                        <input type="text" name="name" id="member_name" class="form-control @error('name') error @enderror" value="{{ old('name', '') }}" autocomplete="off" required>
                        @error('name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" id="member_email_group">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" id="member_email" class="form-control @error('email') error @enderror" value="{{ old('email') }}">
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Obavezno ako član ne postoji u sistemu
                        </div>
                    </div>

                    <div class="form-group" id="member_password_group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" id="member_password" class="form-control @error('password') error @enderror" value="" minlength="8" autocomplete="new-password">
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Minimum 8 karaktera. Obavezno ako član ne postoji u sistemu.
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Pozicija *</label>
                            <select name="position" class="form-control" required>
                                @php
                                    $hasPredsjednik = $commission->members->where('position', 'predsjednik')->count() > 0;
                                @endphp
                                @if(!$hasPredsjednik)
                                    <option value="predsjednik">Predsjednik</option>
                                @endif
                                <option value="clan">Član</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tip člana *</label>
                            <select name="member_type" class="form-control" required>
                                @php
                                    // Opština može biti maksimalno 3 (1 predsjednik + 2 člana)
                                    $opstinaCount = $commission->members->where('member_type', 'opstina')->count();
                                    // Udruženje može biti maksimalno 1
                                    $udruzenjeCount = $commission->members->where('member_type', 'udruzenje')->count();
                                    // Ženske mreže može biti maksimalno 1
                                    $zeneMrezaCount = $commission->members->where('member_type', 'zene_mreza')->count();
                                @endphp
                                @if($opstinaCount < 3)
                                    <option value="opstina">Predstavnik Opštine Kotor</option>
                                @endif
                                @if($udruzenjeCount < 1)
                                    <option value="udruzenje">Predstavnica Udruženja preduzetnica Crne Gore / strukovnih udruženja / biznisa / akademske zajednice</option>
                                @endif
                                @if($zeneMrezaCount < 1)
                                    <option value="zene_mreza">Predstavnica Ženske političke mreže</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organizacija</label>
                        <input type="text" name="organization" class="form-control" value="{{ old('organization') }}" placeholder="Naziv organizacije...">
                    </div>

                    <button type="submit" class="btn-sm btn-success">Dodaj člana</button>
                </form>
            </div>
            @else
                <div class="alert alert-warning">
                    Komisija je popunjena (5/5 redovnih članova).
                    @if(!$hasActiveSubstituteMember)
                        Možete dodati još <strong>1 zamjenskog člana</strong> kroz <a href="{{ route('admin.commissions.edit', $commission) }}" style="color: var(--primary); font-weight: 700;">Izmijeni komisiju</a>.
                    @else
                        Aktivan zamjenski član je već imenovan.
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleMemberFields() {
    const userSelect = document.getElementById('member_user_id');
    const emailGroup = document.getElementById('member_email_group');
    const passwordGroup = document.getElementById('member_password_group');
    const emailInput = document.getElementById('member_email');
    const passwordInput = document.getElementById('member_password');
    const nameInput = document.getElementById('member_name');

    // Ako se forma ne prikazuje (npr. komisija je puna), elementi mogu biti null.
    if (!userSelect || !emailGroup || !passwordGroup || !emailInput || !passwordInput || !nameInput) {
        return;
    }
    
    if (userSelect.value) {
        // Ako je izabran postojeći korisnik, sakrij email i password polja
        emailGroup.style.display = 'none';
        passwordGroup.style.display = 'none';
        emailInput.removeAttribute('required');
        passwordInput.removeAttribute('required');
        
        // Preuzmi ime iz izabranog korisnika
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        if (selectedOption.value) {
            const userText = selectedOption.text;
            const nameMatch = userText.match(/^(.+?)\s*\(/);
            if (nameMatch) {
                nameInput.value = nameMatch[1].trim();
            }
        }
    } else {
        // Ako nije izabran postojeći korisnik, prikaži email i password polja
        emailGroup.style.display = 'block';
        passwordGroup.style.display = 'block';
        emailInput.setAttribute('required', 'required');
        passwordInput.setAttribute('required', 'required');
        // Očisti polja
        nameInput.value = '';
        emailInput.value = '';
        passwordInput.value = '';
    }
}

// Pozovi funkciju na učitavanju stranice
document.addEventListener('DOMContentLoaded', function() {
    // Očisti polja na učitavanju stranice
    const nameInput = document.getElementById('member_name');
    const emailInput = document.getElementById('member_email');
    const passwordInput = document.getElementById('member_password');
    const userSelect = document.getElementById('member_user_id');

    // Ako se forma ne prikazuje, izađi da ne bacimo JS grešku.
    if (!userSelect || !nameInput || !emailInput || !passwordInput) {
        return;
    }
    
    // Ako nije izabran korisnik, očisti polja
    if (!userSelect.value) {
        nameInput.value = '';
        emailInput.value = '';
        passwordInput.value = '';
    }
    
    toggleMemberFields();
});
</script>
@endsection

