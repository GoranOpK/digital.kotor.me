@extends('layouts.app')

@section('content')
@php
    $statusLabels = [
        'draft' => 'Nacrt',
        'published' => 'Objavljen',
        'closed' => 'Zatvoren',
        'completed' => 'Završen',
    ];
    $countLabels = [
        'applications' => 'Prijave',
        'application_documents' => 'Dokumenti uz prijave',
        'youth_decision_documents' => 'PM odluke',
        'zensko_decision_proposal_snapshots' => 'ŽP predlozi odluke',
        'competition_official_decision_copies' => 'Službene PDF odluke',
        'evaluation_scores' => 'Ocjene',
        'business_plans' => 'Biznis planovi',
        'contracts' => 'Ugovori',
        'reports' => 'Izvještaji',
        'application_scores' => 'Bodovi prijava',
        'youth_equal_score_round_applications' => 'Prijave u krugu jednakih bodova',
        'youth_equal_score_groups' => 'Grupe jednakih bodova',
        'commission_sessions' => 'Sjednice komisije',
        'evaluation_criteria' => 'Kriterijumi',
        'notices' => 'Javna obavještenja',
        'competition_official_decision_lifecycle_events' => 'Događaji službene odluke',
    ];
@endphp
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0 48px;
    }
    .archive-wrap {
        width: min(960px, calc(100% - 32px));
        margin: 0 auto;
    }
    .back-link {
        display: inline-block;
        margin-bottom: 16px;
        color: var(--primary);
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
    }
    .back-link:hover {
        text-decoration: underline;
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
        margin: 0 0 8px;
    }
    .page-header p {
        margin: 0;
        color: #e0e7ff;
        font-size: 14px;
        line-height: 1.5;
    }
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        max-width: 280px;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 16px;
        background: #fff;
        color: #111827;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-danger {
        background: #dc2626;
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-danger:hover {
        background: #991b1b;
    }
    .alert-success {
        background: #d1fae5;
        border: 1px solid #10b981;
        color: #065f46;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    .alert-error {
        background: #fee2e2;
        border: 1px solid #ef4444;
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    .danger-note {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        line-height: 1.5;
    }
    .detail-grid,
    .count-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin: 0 0 20px;
    }
    .detail-item {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
    }
    .detail-item span {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin-bottom: 4px;
    }
    .detail-item strong {
        color: #111827;
        font-size: 16px;
        word-break: break-word;
    }
    .section-title {
        margin: 8px 0 12px;
        font-size: 18px;
        color: #111827;
    }
    .file-list {
        list-style: none;
        margin: 0 0 20px;
        padding: 0;
    }
    .file-list li {
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
        word-break: break-word;
        font-size: 14px;
        color: #374151;
    }
    .file-action {
        display: inline-block;
        margin-top: 4px;
        font-weight: 700;
    }
    .file-action-remove {
        color: #991b1b;
    }
    .file-action-keep {
        color: #065f46;
    }
    @media (max-width: 640px) {
        .page-header {
            padding: 16px;
        }
        .page-header h1 {
            font-size: 22px;
        }
        .form-card {
            padding: 16px;
        }
        .form-control,
        .btn-primary,
        .btn-danger {
            max-width: none;
            width: 100%;
        }
        .detail-grid,
        .count-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-page">
    <div class="archive-wrap">
        <a href="{{ route('competitions.archive') }}" class="back-link">Nazad na arhivu konkursa</a>

        <div class="page-header">
            <h1>Uklanjanje jednog arhiviranog testnog konkursa</h1>
            <p>
                Postupak je odvojen od redovnog brisanja. Super administrator unosi ID jednog zatvorenog ili završenog
                konkursa koji je izričito označen kao testni. Naziv, datum i ID ne određuju tu oznaku.
            </p>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="form-card">
            <form method="POST" action="{{ route('admin.archive-test-competition.preview') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="competition_id">ID konkursa</label>
                    <input id="competition_id" name="competition_id" class="form-control" type="number" min="1" step="1" required
                           value="{{ old('competition_id', $preview['competition']->id ?? '') }}">
                </div>
                <button type="submit" class="btn-primary">Prikaži konkurs</button>
            </form>
        </div>

        @if($preview)
            @php
                $competition = $preview['competition'];
                $typeLabel = \App\Services\Competitions\Profile\CompetitionProfileResolver::resolve($competition->type)->displayName();
                $statusLabel = $statusLabels[$competition->status] ?? $competition->status;
            @endphp
            <section class="form-card">
                <h2 style="margin: 0 0 16px; font-size: 20px; color: #111827;">Konkurs {{ $competition->id }}</h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span>Naziv</span>
                        <strong>{{ $competition->title }}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Tip</span>
                        <strong>{{ $typeLabel }}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Status</span>
                        <strong>{{ $statusLabel }}</strong>
                    </div>
                    <div class="detail-item">
                        <span>Oznaka testnog konkursa</span>
                        <strong>{{ $competition->is_test ? 'da' : 'ne' }}</strong>
                    </div>
                </div>

                @if(! $preview['eligible'])
                    <div class="alert-error" style="margin-bottom: 20px;"><strong>Ne može se ukloniti:</strong> {{ $preview['reason'] }}</div>
                @endif

                <h3 class="section-title">Povezani zapisi</h3>
                <div class="count-grid">
                    @foreach($preview['counts'] as $table => $count)
                        <div class="detail-item">
                            <span>{{ $countLabels[$table] ?? $table }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>

                <h3 class="section-title">Posljedice za dokumente</h3>
                @if($preview['files'] === [])
                    <p style="margin: 0 0 20px; color: #374151;">Nema povezanih fajlova.</p>
                @else
                    <ul class="file-list">
                        @foreach($preview['files'] as $file)
                            <li>
                                {{ $file['kind'] }} — {{ $file['path'] }}
                                <span class="file-action {{ $file['action'] === 'ukloniti' ? 'file-action-remove' : 'file-action-keep' }}">
                                    {{ $file['action'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($preview['eligible'])
                    <form method="POST" action="{{ route('admin.archive-test-competition.destroy') }}">
                        @csrf
                        <input type="hidden" name="competition_id" value="{{ $competition->id }}">
                        <div class="danger-note">
                            <strong>Brisanje je trajno i nepovratno.</strong>
                            Biće uklonjeni podaci i isključivi dokumenti samo ovog konkursa. Fajl koji koristi drugi zapis ostaje.
                            Za potvrdu upišite <strong>UKLONI {{ $competition->id }}</strong>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirmation">Potvrda</label>
                            <input id="confirmation" name="confirmation" class="form-control" type="text" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn-danger">Trajno ukloni ovaj konkurs</button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
