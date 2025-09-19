{{-- Korisnički dashboard/panel --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Moj profil / Dashboard</h2>
    <ul>
        <li><a href="{{ route('payments.index') }}">Moje uplate</a></li>
        <li><a href="{{ route('competitions.index') }}">Moje prijave na konkurse</a></li>
        <li><a href="{{ route('tenders.index') }}">Moje tenderske kupovine</a></li>
    </ul>
    <hr>
    <p>Dobrodošli, {{ auth()->user()->name ?? 'Korisnik' }}</p>
</div>
@endsection