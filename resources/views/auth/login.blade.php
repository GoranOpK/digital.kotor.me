{{-- Forma za prijavu korisnika --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Prijava</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email adresa</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Šifra</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Prijavi se</button>
        <a href="{{ route('register') }}">Nemate nalog? Registrujte se</a>
    </form>
</div>
@endsection