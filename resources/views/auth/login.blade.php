@extends('layouts.app')

@section('content')
<style>
    .auth-wrapper {
        max-width: 420px;
        margin: 80px auto;
        padding: 32px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .auth-wrapper h2 {
        text-align: center;
        margin-bottom: 24px;
        font-size: 22px;
        font-weight: 600;
        color: #1f2937;
    }
    .auth-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 18px;
    }
    .auth-field label {
        font-size: 14px;
        color: #374151;
    }
    .auth-field input {
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        width: 100%;
        box-sizing: border-box;
    }
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .password-wrapper input {
        padding-right: 40px;
    }
    .password-toggle {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        font-size: 18px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
    }
    .password-toggle:hover {
        color: #374151;
    }
    .auth-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 4px;
    }
    .auth-actions button {
        background: #4f46e5;
        color: #fff;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .auth-actions a {
        font-size: 14px;
        color: #4f46e5;
        text-decoration: none;
        font-weight: 600;
    }
</style>

<div class="auth-wrapper">
    <h2>Prijava</h2>
    <form method="POST" action="{{ route('login') }}" autocomplete="on" name="login">
        @csrf

        <div class="auth-field">
            <label for="email">Email adresa</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus
            >
            <!-- Skriveno "username" polje za password managere koji očekuju taj naziv -->
            <input type="text" name="username" value="{{ old('email') }}" autocomplete="username" style="display:none;">
        </div>

        <div class="auth-field">
            <label for="password">Šifra</label>
            <div class="password-wrapper">
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Prikaži/Sakrij lozinku">
                    <span id="password-toggle-icon">👁️</span>
                </button>
            </div>
        </div>

        <div class="auth-field" style="flex-direction: row; align-items: center; gap: 8px; margin-bottom: 8px;">
            <input id="remember" type="checkbox" name="remember" value="1" style="width:16px; height:16px; margin:0;">
            <label for="remember" style="margin:0; font-size: 14px;">Zapamti me</label>
        </div>

        <div class="auth-actions">
            <button type="submit">Prijavi se</button>
            <a href="{{ route('register') }}">Registruj nalog</a>
        </div>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('password-toggle-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    }
</script>
@endsection