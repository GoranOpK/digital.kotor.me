@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
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
    .decision-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .decision-header {
        text-align: center;
        margin-bottom: 40px;
        border-bottom: 3px solid var(--primary);
        padding-bottom: 20px;
    }
    .decision-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
    }
    .decision-subtitle {
        font-size: 16px;
        color: #6b7280;
    }
    .decision-section {
        margin-bottom: 32px;
    }
    .decision-section h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 16px;
    }
    .winners-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .winner-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .winner-info {
        flex: 1;
    }
    .winner-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .winner-details {
        font-size: 14px;
        color: #6b7280;
    }
    .winner-amount {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
    }
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header no-print">
            <h1>Odluka o dobitnicima</h1>
        </div>

        <div class="decision-card">
            <div class="decision-header">
                <div class="decision-title">ODLUKA</div>
                <div class="decision-subtitle">
                    o odabiru dobitnika {{ $competition->competition_number ?? '' }}. konkursa za podršku ženskom preduzetništvu
                    {{ $competition->year ?? date('Y') }}. godine
                </div>
            </div>

            <div class="decision-section">
                <h3>Osnovni podaci</h3>
                <p><strong>Konkurs:</strong> {{ $competition->title }}</p>
                <p><strong>Godina:</strong> {{ $competition->year ?? date('Y') }}</p>
                <p><strong>Ukupan budžet:</strong> {{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</p>
                <p><strong>Datum:</strong> {{ now()->format('d.m.Y') }}</p>
            </div>

            <div class="decision-section">
                <h3>Dobitnici sredstava</h3>
                @if($winners->count() > 0)
                    <ul class="winners-list">
                        @foreach($winners as $winner)
                            <li class="winner-item">
                                <div class="winner-info">
                                    <div class="winner-name">
                                        {{ $loop->iteration }}. {{ $winner->business_plan_name }}
                                    </div>
                                    <div class="winner-details">
                                        Podnosilac: {{ $winner->user->name ?? 'N/A' }} | 
                                        Tip: {{ $winner->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }} | 
                                        Ocjena: {{ number_format($winner->getDisplayScore(), 2) }} / 50
                                    </div>
                                </div>
                                <div class="winner-amount">
                                    {{ number_format($winner->approved_amount ?? 0, 2, ',', '.') }} €
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p style="margin-top: 20px; font-weight: 600;">
                        Ukupan iznos podrške: {{ number_format($winners->sum('approved_amount'), 2, ',', '.') }} €
                    </p>
                @else
                    <p style="color: #6b7280; text-align: center; padding: 40px;">
                        Nema odabranih dobitnika.
                    </p>
                @endif
            </div>

            <div class="decision-section">
                <h3>Napomena</h3>
                <p style="color: #6b7280; line-height: 1.8;">
                    Dobitnici sredstava su odabrani na osnovu ocjena komisije i dostupnog budžeta. 
                    Svaki dobitnik će biti obaviješten o odluci i pozvan na potpisivanje ugovora.
                </p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px;" class="no-print">
            <button onclick="window.print()" class="btn btn-primary">Štampaj Odluku</button>
            <a href="{{ route('admin.competitions.ranking', $competition) }}" class="btn btn-primary" style="background: #6b7280; margin-left: 8px;">Nazad na rang listu</a>
        </div>
    </div>
</div>
@endsection

