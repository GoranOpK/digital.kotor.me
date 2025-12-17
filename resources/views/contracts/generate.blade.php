@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .contract-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
</style>

<div class="contract-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Generisanje ugovora</h1>
        </div>

        <div class="info-card">
            <h2>Informacije o prijavi</h2>
            <p><strong>Naziv biznis plana:</strong> {{ $application->business_plan_name }}</p>
            <p><strong>Podnosilac:</strong> {{ $application->user->name ?? 'N/A' }}</p>
            <p><strong>Odobreni iznos:</strong> {{ number_format($application->approved_amount ?? 0, 2, ',', '.') }} €</p>
        </div>

        @if($contract)
            <div class="alert alert-info">
                <strong>Ugovor već postoji.</strong> 
                <a href="{{ route('contracts.show', $contract) }}" style="color: #1e40af; text-decoration: underline;">
                    Pregledaj ugovor
                </a>
            </div>
        @else
            <div class="info-card">
                <h2>Kreiraj ugovor</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">
                    Klikom na dugme ispod, kreiraće se osnovni ugovor za ovu prijavu.
                    Nakon kreiranja, možete ga preuzeti, potpisati i upload-ovati.
                </p>
                <form method="POST" action="{{ route('contracts.store', $application) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Kreiraj ugovor</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

