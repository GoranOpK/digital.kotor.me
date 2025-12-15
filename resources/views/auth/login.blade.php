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
    .auth-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
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
    }
</style>

<div class="auth-wrapper">
    <h2>Prijava</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-field">
            <label for="email">Email adresa</label>
            <input id="email" type="email" name="email" required autofocus>
        </div>

        <div class="auth-field">
            <label for="password">Šifra</label>
            <input id="password" type="password" name="password" required>
        </div>

        <div class="auth-actions">
            <button type="submit">Prijavi se</button>
            <a href="{{ route('register') }}">Nemate nalog? Registrujte se</a>
        </div>
    </form>
</div>
@endsection