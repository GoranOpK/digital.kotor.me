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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-left: 8px;
    }
    .btn-primary {
        background: #fff;
        color: var(--primary);
    }
    .btn-success {
        background: #10b981;
        color: #fff;
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-published { background: #d1fae5; color: #065f46; }
    .status-closed { background: #fee2e2; color: #991b1b; }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $competition->title }}</h1>
            <div>
                @if($competition->status === 'draft')
                    <form method="POST" action="{{ route('admin.competitions.publish', $competition) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">Objavi konkurs</button>
                    </form>
                @elseif($competition->status === 'published')
                    <form method="POST" action="{{ route('admin.competitions.close', $competition) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">Zatvori konkurs</button>
                    </form>
                @endif
                @if($competition->status === 'closed' || $competition->status === 'published')
                    <a href="{{ route('admin.competitions.ranking', $competition) }}" class="btn" style="background: #8b5cf6; color: #fff;">Rang lista</a>
                @endif
                <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn btn-primary">Izmeni</a>
            </div>
        </div>

        <div class="info-card">
            <h2 style="font-size: 20px; margin-bottom: 16px;">Osnovne informacije</h2>
            <p><strong>Status:</strong> <span class="status-badge status-{{ $competition->status }}">{{ $competition->status }}</span></p>
            <p><strong>Budžet:</strong> {{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</p>
            <p><strong>Maksimalna podrška:</strong> {{ $competition->max_support_percentage ?? 30 }}%</p>
            <p><strong>Rok za prijave:</strong> {{ $competition->deadline_days ?? 20 }} dana</p>
            <p><strong>Broj prijava:</strong> {{ $applications->total() }}</p>
        </div>

        <div class="info-card">
            <h2 style="font-size: 20px; margin-bottom: 16px;">Prijave</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;">Naziv biznis plana</th>
                        <th style="padding: 12px; text-align: left;">Podnosilac</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">{{ $app->business_plan_name }}</td>
                            <td style="padding: 12px;">{{ $app->user->name ?? 'N/A' }}</td>
                            <td style="padding: 12px;">{{ $app->status }}</td>
                            <td style="padding: 12px;">
                                <a href="{{ route('admin.applications.show', $app) }}" style="color: #3b82f6;">Pregled</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #6b7280;">
                                Nema prijava na ovaj konkurs.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div style="margin-top: 20px;">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

