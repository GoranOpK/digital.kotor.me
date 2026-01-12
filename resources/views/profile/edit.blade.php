@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .profile-edit {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .profile-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .profile-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0;
    }
    .profile-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        margin-bottom: 24px;
    }
    .profile-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .form-label .required {
        color: #dc2626;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color .2s;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11,61,145,.1);
    }
    .form-error {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 14px;
        transition: background-color .2s;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        border: none;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
        border: none;
    }
    .btn-secondary:hover {
        background: #4b5563;
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
</style>

<div class="profile-edit">
    <div class="container mx-auto px-4">
        <div class="profile-header">
            <h1>Izmjena profila</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert" style="background: #fee2e2; border-color: #dc2626; color: #991b1b;">
                <strong>Greška:</strong> Molimo provjerite unijete podatke.
                <ul style="margin: 8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Osnovni podaci -->
        <div class="profile-card">
            <h2>Osnovni podaci</h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="first_name" class="form-label">Ime <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" 
                           value="{{ old('first_name', $user->first_name) }}" required>
                    @error('first_name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Prezime <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" 
                           value="{{ old('last_name', $user->last_name) }}" required>
                    @error('last_name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email adresa <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Broj telefona <span class="required">*</span></label>
                    <input type="text" name="phone" id="phone" class="form-control" 
                           value="{{ old('phone', $user->phone) }}" required>
                    @error('phone')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Adresa <span class="required">*</span></label>
                    <input type="text" name="address" id="address" class="form-control" 
                           value="{{ old('address', $user->address) }}" required>
                    @error('address')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="user_type" class="form-label">Tip korisnika <span class="required">*</span></label>
                    <select name="user_type" id="user_type" class="form-control" required onchange="toggleUserTypeFields()">
                        <option value="Fizičko lice" {{ old('user_type', $user->user_type) === 'Fizičko lice' ? 'selected' : '' }}>Fizičko lice</option>
                        @php
                            $businessTypes = [
                                'Preduzetnik',
                                'Ortačko društvo',
                                'Komanditno društvo',
                                'Društvo sa ograničenom odgovornošću',
                                'Akcionarsko društvo',
                                'Dio stranog društva (predstavništvo ili poslovna jedinica)',
                                'Udruženje (nvo, fondacije, sportske organizacije)',
                                'Ustanova (državne i privatne)',
                                'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)'
                            ];
                        @endphp
                        @foreach($businessTypes as $type)
                            <option value="{{ $type }}" {{ old('user_type', $user->user_type) === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_type')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="residential_status" class="form-label">Status rezidentnosti <span class="required">*</span></label>
                    <select name="residential_status" id="residential_status" class="form-control" required>
                        <option value="resident" {{ old('residential_status', $user->residential_status) === 'resident' ? 'selected' : '' }}>Rezident</option>
                        <option value="non-resident" {{ old('residential_status', $user->residential_status) === 'non-resident' ? 'selected' : '' }}>Nerezident</option>
                        <option value="ex-non-resident" {{ old('residential_status', $user->residential_status) === 'ex-non-resident' ? 'selected' : '' }}>Bivši nerezident</option>
                    </select>
                    @error('residential_status')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Polja za fizičko lice -->
                <div id="physicalPersonFields" class="{{ old('user_type', $user->user_type) === 'Fizičko lice' ? '' : 'conditional-field' }}" style="{{ old('user_type', $user->user_type) === 'Fizičko lice' ? '' : 'display: none;' }}">
                    <div class="form-group">
                        <label for="jmb" class="form-label">JMB <span class="required">*</span></label>
                        <input type="text" name="jmb" id="jmb" class="form-control" 
                               value="{{ old('jmb', $user->jmb) }}" 
                               maxlength="13" 
                               pattern="[0-9]{13}"
                               placeholder="13 cifara">
                        @error('jmb')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Jedinstveni matični broj (13 cifara)
                        </div>
                    </div>
                </div>

                <!-- Polja za pravno lice -->
                <div id="legalEntityFields" class="{{ old('user_type', $user->user_type) !== 'Fizičko lice' ? '' : 'conditional-field' }}" style="{{ old('user_type', $user->user_type) !== 'Fizičko lice' ? '' : 'display: none;' }}">
                    <div class="form-group">
                        <label for="pib" class="form-label">PIB <span class="required">*</span></label>
                        <input type="text" name="pib" id="pib" class="form-control" 
                               value="{{ old('pib', $user->pib) }}" 
                               maxlength="9"
                               pattern="[0-9]{9}"
                               placeholder="9 cifara">
                        @error('pib')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Poreski identifikacioni broj (9 cifara)
                        </div>
                    </div>
                </div>

                <!-- Polje za passport (opciono za nerezidente) -->
                <div id="passportFields" class="{{ old('residential_status', $user->residential_status) !== 'resident' ? '' : 'conditional-field' }}" style="{{ old('residential_status', $user->residential_status) !== 'resident' ? '' : 'display: none;' }}">
                    <div class="form-group">
                        <label for="passport_number" class="form-label">Broj pasoša</label>
                        <input type="text" name="passport_number" id="passport_number" class="form-control" 
                               value="{{ old('passport_number', $user->passport_number) }}" 
                               maxlength="50"
                               placeholder="Broj pasoša">
                        @error('passport_number')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Obavezno za nerezidente koji nemaju JMB
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">Sačuvaj izmjene</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Otkaži</a>
                </div>
            </form>
        </div>

        <!-- Promjena lozinke -->
        <div class="profile-card">
            <h2>Promjena lozinke</h2>
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="current_password" class="form-label">Trenutna lozinka <span class="required">*</span></label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                    @error('current_password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Nova lozinka <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                    @error('password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Potvrda nove lozinke <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">Promijeni lozinku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleUserTypeFields() {
    const userType = document.getElementById('user_type').value;
    const physicalPersonFields = document.getElementById('physicalPersonFields');
    const legalEntityFields = document.getElementById('legalEntityFields');
    const jmbInput = document.getElementById('jmb');
    const pibInput = document.getElementById('pib');
    
    if (userType === 'Fizičko lice') {
        if (physicalPersonFields) {
            physicalPersonFields.style.display = 'block';
        }
        if (legalEntityFields) {
            legalEntityFields.style.display = 'none';
        }
        if (jmbInput) {
            jmbInput.setAttribute('required', 'required');
        }
        if (pibInput) {
            pibInput.removeAttribute('required');
        }
    } else {
        if (physicalPersonFields) {
            physicalPersonFields.style.display = 'none';
        }
        if (legalEntityFields) {
            legalEntityFields.style.display = 'block';
        }
        if (jmbInput) {
            jmbInput.removeAttribute('required');
        }
        if (pibInput) {
            pibInput.setAttribute('required', 'required');
        }
    }
    
    // Takođe proveri residential_status za passport polje
    togglePassportField();
}

function togglePassportField() {
    const residentialStatus = document.getElementById('residential_status').value;
    const passportFields = document.getElementById('passportFields');
    const passportInput = document.getElementById('passport_number');
    
    if (residentialStatus !== 'resident') {
        if (passportFields) {
            passportFields.style.display = 'block';
        }
    } else {
        if (passportFields) {
            passportFields.style.display = 'none';
        }
        if (passportInput) {
            passportInput.removeAttribute('required');
        }
    }
}

// Dodaj event listener za residential_status
document.addEventListener('DOMContentLoaded', function() {
    const residentialStatusSelect = document.getElementById('residential_status');
    if (residentialStatusSelect) {
        residentialStatusSelect.addEventListener('change', togglePassportField);
    }
    
    // Pozovi funkcije na učitavanju
    toggleUserTypeFields();
});
</script>
@endsection
