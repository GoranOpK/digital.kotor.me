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
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .info-label {
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 2px;
        letter-spacing: 0.3px;
    }
    .info-value {
        font-size: 13px;
        color: #111827;
        font-weight: 500;
        line-height: 1.4;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-evaluated { background: #d1fae5; color: #065f46; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .document-item {
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }
    .btn {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Pregled prijave</h1>
        </div>

        <!-- Status prijave, Osnovni podaci i Priložena dokumentacija u istom redu -->
        <style>
            @media (min-width: 768px) {
                .status-and-basic-info {
                    grid-template-columns: repeat(3, 1fr) !important;
                }
            }
        </style>
        <div class="status-and-basic-info" style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px;">
                <!-- Status prijave -->
                <div class="info-card">
                    <h2>Status prijave</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="status-badge status-{{ $application->status }}">
                                    @if($application->status === 'draft') Nacrt
                                    @elseif($application->status === 'submitted') Podnesena
                                    @elseif($application->status === 'evaluated') Ocjenjena
                                    @elseif($application->status === 'approved') Odobrena
                                    @elseif($application->status === 'rejected') Odbijena
                                    @else {{ $application->status }}
                                    @endif
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Datum podnošenja</span>
                            <span class="info-value">
                                {{ $application->submitted_at ? $application->submitted_at->format('d.m.Y H:i') : 'N/A' }}
                            </span>
                        </div>
                        @if($application->final_score)
                        <div class="info-item">
                            <span class="info-label">Konačna ocjena</span>
                            <span class="info-value">{{ number_format($application->final_score, 2) }} / 50</span>
                        </div>
                        @endif
                        @if($application->ranking_position)
                        <div class="info-item">
                            <span class="info-label">Pozicija na rang listi</span>
                            <span class="info-value">#{{ $application->ranking_position }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Osnovni podaci -->
                <div class="info-card">
                    <h2>Osnovni podaci</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Naziv biznis plana</span>
                            <span class="info-value">{{ $application->business_plan_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Podnosilac</span>
                            <span class="info-value">{{ $application->user->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">{{ $application->user->email ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Konkurs</span>
                            <span class="info-value">{{ $application->competition->title ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tip podnosioca</span>
                            <span class="info-value">
                                {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }}
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Faza biznisa</span>
                            <span class="info-value">
                                {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Oblast biznisa</span>
                            <span class="info-value">{{ $application->business_area }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Traženi iznos</span>
                            <span class="info-value">{{ number_format($application->requested_amount, 2, ',', '.') }} €</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ukupan budžet</span>
                            <span class="info-value">{{ number_format($application->total_budget_needed, 2, ',', '.') }} €</span>
                        </div>
                        @if($application->approved_amount)
                        <div class="info-item">
                            <span class="info-label">Odobreni iznos</span>
                            <span class="info-value">{{ number_format($application->approved_amount, 2, ',', '.') }} €</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Priložena dokumentacija -->
                @php
                    $userRole = auth()->user()->role ? auth()->user()->role->name : null;
                @endphp
                @if($application->documents->count() > 0 && $userRole === 'komisija')
                <div class="info-card">
                    <h2>Priložena dokumentacija</h2>
                    <ul class="documents-list">
                        @foreach($application->documents as $doc)
                            <li class="document-item">
                                <span>{{ $doc->name }}</span>
                                <div style="display: inline-flex; gap: 8px;">
                                    {{-- Samo članovi komisije mogu da vide dokument (server dodatno provjerava dodijeljenu komisiju) --}}
                                    <a href="{{ route('applications.document.view', ['application' => $application, 'document' => $doc]) }}"
                                       class="btn btn-secondary"
                                       target="_blank">
                                        Pogledaj
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>

        <!-- Ocjene -->
        @if($application->evaluationScores->count() > 0)
        <div class="info-card">
            <h2>Ocjene komisije</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;">Član komisije</th>
                        <th style="padding: 12px; text-align: center;">Ocjena</th>
                        <th style="padding: 12px; text-align: left;">Napomene</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($application->evaluationScores as $score)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">{{ $score->commissionMember->name ?? 'N/A' }}</td>
                            <td style="padding: 12px; text-align: center;">{{ $score->final_score ?? $score->calculateTotalScore() }} / 50</td>
                            <td style="padding: 12px;">{{ $score->notes ? \Illuminate\Support\Str::limit($score->notes, 100) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="text-align: center; margin-top: 24px;">
            @php
                $userRole = auth()->user()->role ? auth()->user()->role->name : null;
            @endphp
            @if($userRole === 'konkurs_admin' || $userRole === 'komisija')
                <a href="{{ route('admin.competitions.show', $application->competition) }}" class="btn btn-primary">Nazad na konkurs</a>
            @else
                <a href="{{ route('admin.applications.index') }}" class="btn btn-primary">Nazad na listu</a>
            @endif
        </div>
    </div>
</div>
@endsection

