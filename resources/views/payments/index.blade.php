{{-- Prikaz forme i istorije uplata --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Online plaćanje opštinskih prihoda</h2>
    <form method="POST" action="{{ route('payments.pay') }}">
        @csrf
        <div class="mb-3">
            <label for="payment_type" class="form-label">Vrsta prihoda</label>
            <select name="payment_type" class="form-control">
                <option value="komunalije">Komunalije</option>
                <option value="renta">Renta</option>
                <option value="takse">Administrativne takse</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Iznos</label>
            <input type="number" name="amount" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Plati online</button>
    </form>
    <hr>
    <h4>Istorija uplata</h4>
    {{-- Ovdje prikazi listu uplata korisnika --}}
</div>
@endsection