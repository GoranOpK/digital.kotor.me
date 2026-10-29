@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4" style="padding: 24px 0;">
    @if(session('success'))
        <div class="alert no-print" role="status" style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @include('evaluation._youth_preliminary_ranking', [
        'youthRankingView' => $youthRankingView,
        'youthRankingTitle' => $youthRankingTitle,
        'canEditYouthAllocationDraft' => $canEditYouthAllocationDraft,
        'youthEqualScoreVotingBoard' => $youthEqualScoreVotingBoard,
        'youthEqualScoreCompetition' => $youthEqualScoreCompetition,
        'youthAllocationConfirmationBoard' => $youthAllocationConfirmationBoard,
    ])

    @if(!empty($canGenerateYouthDecision))
        <div class="no-print" data-testid="youth-decision-generate" style="max-width: 720px; margin: 24px auto; background: #fff; border: 1px solid #dbe3ef; border-radius: 12px; padding: 20px;">
            <h2 style="margin: 0 0 8px 0; color: #0B3D91; font-size: 20px;">Generiši odluku</h2>
            <p style="margin: 0 0 16px 0; color: #374151;">Word dokument je radni predlog. Potpisana Odluka se naknadno učitava kao PDF. Ručna izmjena datuma ne mijenja evidentirane podatke.</p>
            @if($errors->any())
                <div class="error-message" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('evaluation.youth-decision.generate', $youthEqualScoreCompetition) }}" data-testid="youth-decision-generate-form">
                @csrf
                <label style="display: block; margin-bottom: 12px;">
                    Broj Odluke
                    <input type="text" name="decision_number" value="{{ old('decision_number') }}" required maxlength="80" style="display: block; width: 100%; margin-top: 4px; padding: 8px;">
                </label>
                @foreach([
                    'first_session_date' => 'Datum prve sjednice',
                    'second_session_date' => 'Datum druge sjednice',
                    'third_session_date' => 'Datum treće sjednice',
                    'oral_presentation_date' => 'Datum usmenih obrazloženja',
                ] as $field => $label)
                    @php
                        $suggested = match ($field) {
                            'first_session_date' => $youthDecisionDates['first'] ?? null,
                            'second_session_date' => $youthDecisionDates['second'] ?? null,
                            'third_session_date' => $youthDecisionDates['third'] ?? null,
                            default => $youthDecisionDates['oral'] ?? null,
                        };
                    @endphp
                    <label style="display: block; margin-bottom: 12px;">
                        {{ $label }}
                        <input type="date" name="{{ $field }}" value="{{ old($field, $suggested) }}" required style="display: block; width: 100%; margin-top: 4px; padding: 8px;">
                    </label>
                @endforeach
                <button type="submit" class="btn btn-primary" style="background: #0B3D91; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600;">Generiši odluku</button>
            </form>
        </div>
    @endif

    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
        <a href="{{ route('evaluation.index') }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad</a>
    </div>
</div>
@endsection
