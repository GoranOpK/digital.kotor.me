@extends('layouts.app')

@section('content')
@php
    $statusLabels = [
        'draft' => 'Nacrt',
        'published' => 'Objavljen',
        'closed' => 'Zatvoren',
        'completed' => 'Završen',
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
    .detail-grid {
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
    .info-note {
        padding: 12px 14px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        font-size: 14px;
        color: #1e3a8a;
        margin-bottom: 20px;
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
        .btn-primary {
            max-width: none;
            width: 100%;
        }
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-page">
    <div class="archive-wrap">
        <a href="{{ route('competitions.archive') }}" class="back-link">Nazad na arhivu konkursa</a>

        <div class="page-header">
            <h1>Označavanje ranijeg konkursa kao testnog</h1>
            <p>
                Postupak samo upisuje oznaku na jedan već objavljen konkurs podrške preduzetništvu mladih,
                ženskom preduzetništvu ili mikro, malim i srednjim preduzećima čiji je ID na odobrenom spisku.
                Ne briše konkurs i ne prepoznaje test po nazivu, datumu ili ID-u.
            </p>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="form-card">
            <form method="POST" action="{{ route('admin.archive-test-mark.preview') }}">
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
                        <span>Trenutna oznaka</span>
                        <strong>{{ $competition->is_test ? 'testni' : 'nije testni' }}</strong>
                    </div>
                </div>

                @if(! $preview['eligible'])
                    <div class="alert-error" style="margin-bottom: 0;"><strong>Ne može se označiti:</strong> {{ $preview['reason'] }}</div>
                @else
                    <form method="POST" action="{{ route('admin.archive-test-mark.store') }}">
                        @csrf
                        <input type="hidden" name="competition_id" value="{{ $competition->id }}">
                        <div class="info-note">
                            Ova radnja ne briše konkurs. Za označavanje samo ovog konkursa upišite
                            <strong>OZNACI {{ $competition->id }}</strong>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirmation">Potvrda</label>
                            <input id="confirmation" name="confirmation" class="form-control" type="text" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn-primary">Označi samo ovaj konkurs</button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
