{{-- Lista tendera --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Tenderska dokumentacija</h2>
    {{-- Ovdje prikazi listu tendera --}}
    <ul>
        {{-- Primjer statički --}}
        <li>
            <a href="{{ route('tenders.show', 1) }}">Tender za poslovni prostor 1</a>
            <form method="POST" action="{{ route('tenders.purchase') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="tender_id" value="1">
                <button type="submit" class="btn btn-primary btn-sm">Otkupi dokumentaciju</button>
            </form>
        </li>
        <li>
            <a href="{{ route('tenders.show', 2) }}">Tender za poslovni prostor 2</a>
            <form method="POST" action="{{ route('tenders.purchase') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="tender_id" value="2">
                <button type="submit" class="btn btn-primary btn-sm">Otkupi dokumentaciju</button>
            </form>
        </li>
    </ul>
</div>
@endsection