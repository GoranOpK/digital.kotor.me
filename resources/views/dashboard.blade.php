{{-- Korisnički dashboard/panel --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Moj profil / Dashboard</h2>
    
    @if (session('verified'))
        <div class="alert alert-success" style="background:#d1fae5; border:1px solid #10b981; border-radius:8px; padding:12px 16px; color:#065f46; margin-bottom:20px;">
            <strong>Uspešno!</strong> Vaša email adresa je verifikovana.
        </div>
    @endif

    @if (!auth()->user()->hasVerifiedEmail())
        <div class="alert alert-warning" style="background:#fef3c7; border:1px solid #f59e0b; border-radius:8px; padding:12px 16px; color:#92400e; margin-bottom:20px;">
            <strong>Važno:</strong> Molimo vas da <a href="{{ route('verification.notice') }}" style="color:#92400e; text-decoration:underline;">verifikujete svoju email adresu</a>.
        </div>
    @endif

    <ul>
        <li><a href="{{ route('payments.index') }}">Moje uplate</a></li>
        <li><a href="{{ route('competitions.index') }}">Moje prijave na konkurse</a></li>
        <li><a href="{{ route('tenders.index') }}">Moje tenderske kupovine</a></li>
    </ul>
    <hr>
    <p>Dobrodošli, {{ auth()->user()->name ?? 'Korisnik' }}</p>
</div>
@endsection