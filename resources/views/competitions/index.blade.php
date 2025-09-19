{{-- Lista konkursa --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Konkursi za podršku preduzetništvu</h2>
    {{-- Ovdje prikazi listu konkursa --}}
    <ul>
        {{-- Primjer statički --}}
        <li>
            <a href="{{ route('competitions.show', 1) }}">Konkurs za žensko preduzetništvo</a> (Aktivan)
        </li>
        <li>
            <a href="{{ route('competitions.show', 2) }}">Konkurs za omladinsko preduzetništvo</a> (Završen)
        </li>
    </ul>
</div>
@endsection