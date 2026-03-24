@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="text-center mb-4">
        <img
            src="{{ asset('img/kalendar-kulture-logo.png') }}"
            alt="Logo Kalendara kulture"
            style="max-width: 260px; width: 100%; height: auto;"
        >
    </div>

    <h2 class="mb-3">Kalendar kulture</h2>
    <p class="text-muted mb-4">
        Dobrodošli u modul za pregled kulturnih događaja. Uskoro će ovdje biti dostupan kalendar sa najavama manifestacija, koncerata, izložbi i drugih dešavanja.
    </p>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-2">Modul je aktivan</h5>
            <p class="card-text mb-0">
                Ovo je početna stranica modula <strong>Kalendar kulture</strong>.
            </p>
        </div>
    </div>
</div>
@endsection
