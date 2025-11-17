{{-- Početna stranica portala digital.kotor.me --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dobrodošli na portal digital.kotor.me</h1>
    <p>Centralizovani servis Opštine Kotor za građane i privredu.</p>
    <a href="{{ route('login') }}" class="btn btn-primary">Prijava</a>
    <a href="{{ route('register') }}" class="btn btn-success">Registracija</a>
    <hr>
    <a href="{{ route('payments.index') }}" class="btn btn-info">Online plaćanja</a>
    <a href="{{ route('competitions.index') }}" class="btn btn-info">Konkursi</a>
    <a href="{{ route('tenders.index') }}" class="btn btn-info">Tenderska dokumentacija</a>
</div>
@endsection