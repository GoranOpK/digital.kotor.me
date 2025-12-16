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

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">Sačuvaj izmjene</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Otkaži</a>
                </div>
            </form>
        </div>

        <!-- Promena lozinke -->
        <div class="profile-card">
            <h2>Promena lozinke</h2>
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
                    <button type="submit" class="btn btn-primary">Promeni lozinku</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
