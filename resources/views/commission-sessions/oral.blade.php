@extends('layouts.app')

@section('content')
<style>
    .session-page { background: #f9fafb; min-height: 100vh; padding: 24px 0; }
    .session-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 720px; margin: 0 auto; }
    .session-card h1 { font-size: 24px; font-weight: 700; color: #0B3D91; margin: 0 0 16px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; }
    .btn-primary { background: #0B3D91; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-success { background: #059669; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
</style>
<div class="session-page">
    <div class="session-card">
        <h1>Usmeno predstavljanje</h1>
        <p style="color:#4b5563; font-size:14px; margin-bottom:20px;">
            {{ $competition->title }} — {{ $application->business_plan_name }}
        </p>
        <p style="margin-bottom:16px;">
            <a href="{{ route('commission-sessions.second.edit', $competition) }}">Nazad na drugu sjednicu</a>
        </p>

        @if(! $sessionConfirmed)
            <div style="background:#fef3c7; border:1px solid #f59e0b; color:#92400e; padding:12px; border-radius:8px; margin-bottom:16px;">
                Usmeno se može završiti tek nakon potvrđene druge sjednice sa sva 3 člana. Planirani termin može se unijeti u nacrtu.
            </div>
        @endif

        @if(session('success'))
            <div style="background:#d1fae5; border:1px solid #10b981; color:#065f46; padding:12px; border-radius:8px; margin-bottom:16px;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:#fee2e2; border:1px solid #ef4444; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if($readonly)
            <p style="font-weight:600; color:#065f46;">Evidencija usmenog je završena i ne može se mijenjati.</p>
            <p><strong>Planirani termin:</strong> {{ $oral->scheduled_at?->format('d.m.Y H:i') ?: '—' }}</p>
            <p><strong>Stvarni termin:</strong> {{ $oral->held_at?->format('d.m.Y H:i') ?: '—' }}</p>
            <p><strong>Prisustvo podnosioca:</strong>
                @if($oral->applicant_attended === true)
                    prisustvovao
                @elseif($oral->applicant_attended === false)
                    nije prisustvovao
                @else
                    —
                @endif
            </p>
            @if($oral->notes)
                <p><strong>Napomena:</strong> {{ $oral->notes }}</p>
            @endif
        @else
            <form method="POST" action="{{ $oral ? route('commission-sessions.oral.update', [$competition, $application]) : route('commission-sessions.oral.store', [$competition, $application]) }}">
                @csrf
                @if($oral)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label class="form-label">Planirani termin</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control"
                           value="{{ old('scheduled_at', $oral?->scheduled_at?->format('Y-m-d\\TH:i')) }}">
                </div>

                @if($sessionConfirmed)
                    <div class="form-group">
                        <label class="form-label">Stvarni termin</label>
                        <input type="datetime-local" name="held_at" class="form-control"
                               value="{{ old('held_at', $oral?->held_at?->format('Y-m-d\\TH:i')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prisustvo podnosioca</label>
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <input type="radio" name="applicant_attended" value="1"
                                {{ (string) old('applicant_attended', $oral?->applicant_attended === true ? '1' : '') === '1' ? 'checked' : '' }}>
                            Prisustvovao
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="applicant_attended" value="0"
                                {{ (string) old('applicant_attended', $oral?->applicant_attended === false ? '0' : '') === '0' ? 'checked' : '' }}>
                            Nije prisustvovao
                        </label>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Napomena</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $oral?->notes) }}</textarea>
                </div>

                <button type="submit" class="btn-primary">Sačuvaj nacrt</button>
            </form>

            @if($oral && $sessionConfirmed)
                <form method="POST" action="{{ route('commission-sessions.oral.complete', [$competition, $application]) }}" style="margin-top:16px;">
                    @csrf
                    <input type="hidden" name="scheduled_at" value="{{ $oral->scheduled_at?->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="held_at" value="{{ old('held_at', $oral->held_at?->format('Y-m-d H:i:s')) }}">
                    <input type="hidden" name="applicant_attended" value="{{ old('applicant_attended', $oral->applicant_attended === false ? '0' : ($oral->applicant_attended === true ? '1' : '')) }}">
                    <input type="hidden" name="notes" value="{{ old('notes', $oral->notes) }}">
                    <button type="submit" class="btn-success">Završi evidenciju</button>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
