{{-- Detalji tendera --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Detalji tendera</h2>
    <p>Naziv: Tender za poslovni prostor 1</p>
    <p>Opis: Otkup dokumentacije za poslovni prostor u centru Kotora.</p>
    <p>Status: Aktivan</p>
    <form method="POST" action="{{ route('tenders.purchase') }}">
        @csrf
        <input type="hidden" name="tender_id" value="1">
        <button type="submit" class="btn btn-success">Otkupi dokumentaciju</button>
    </form>
</div>
@endsection