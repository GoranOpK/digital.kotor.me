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
    .error-message { color: #ef4444; font-size: 13px; margin-top: 4px; }
</style>
<div class="session-page">
    <div class="session-card">
        <h1>Evidencija prve sjednice</h1>
        <p style="color:#4b5563; font-size:14px; margin-bottom:20px;">
            {{ $competition->title }} — Platforma ne predlaže niti zakazuje termin. Predsjednik unosi vrijeme održavanja i prisutne članove.
        </p>

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

        @php
            $presentIds = old('present_member_ids', $session?->attendances?->where('present', true)->pluck('commission_member_id')->all() ?? []);
        @endphp

        @if($readonly)
            <p style="font-weight:600; color:#065f46;">Sjednica je potvrđena i ne može se mijenjati.</p>
            <p><strong>Vrijeme održavanja:</strong> {{ $session->held_at?->format('d.m.Y H:i') }}</p>
            @if($session->notes)
                <p><strong>Napomena:</strong> {{ $session->notes }}</p>
            @endif
            <p><strong>Prisutni:</strong></p>
            <ul>
                @foreach($eligibleMembers as $member)
                    <li>{{ $member->name }} — {{ in_array($member->id, $presentIds) ? 'prisutan' : 'nije prisutan' }}</li>
                @endforeach
            </ul>
        @else
            <form method="POST" action="{{ $session ? route('commission-sessions.first.update', $competition) : route('commission-sessions.first.store', $competition) }}">
                @csrf
                @if($session)
                    @method('PUT')
                @endif

                <div class="form-group">
                    <label class="form-label">Vrijeme održavanja *</label>
                    <input type="datetime-local" name="held_at" class="form-control" required
                           value="{{ old('held_at', $session?->held_at?->format('Y-m-d\\TH:i')) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Napomena</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $session?->notes) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Prisutni članovi</label>
                    @forelse($eligibleMembers as $member)
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <input type="checkbox" name="present_member_ids[]" value="{{ $member->id }}"
                                {{ in_array($member->id, $presentIds) ? 'checked' : '' }}>
                            {{ $member->name }}
                            (mjesto {{ $member->canonicalSeatNumber() }}{{ $member->position === 'predsjednik' ? ', predsjednik' : '' }})
                        </label>
                    @empty
                        <p style="color:#6b7280;">Nema aktivnih članova sa važećim mjestom.</p>
                    @endforelse
                </div>

                <button type="submit" class="btn-primary">Sačuvaj nacrt</button>
            </form>

            @if($session)
                <form method="POST" action="{{ route('commission-sessions.first.confirm', $competition) }}" style="margin-top:16px;">
                    @csrf
                    <button type="submit" class="btn-success">Potvrdi sjednicu</button>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
