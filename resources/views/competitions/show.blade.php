{{-- Detalji konkursa --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Detalji konkursa</h2>
    {{-- Prikazi detalje o konkursu --}}
    <p>Naziv: Konkurs za žensko preduzetništvo</p>
    <p>Status: Aktivan</p>
    <p>Opis: Podrška ženskom preduzetništvu iz budžeta Opštine Kotor.</p>
    <form method="POST" action="{{ route('competitions.apply') }}">
        @csrf
        <input type="hidden" name="competition_id" value="1">
        <button type="submit" class="btn btn-success">Prijavi se</button>
    </form>
</div>
@endsection