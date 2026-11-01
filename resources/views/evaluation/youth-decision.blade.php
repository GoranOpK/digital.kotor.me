@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4" style="padding: 24px 0;">
    @if(session('success'))
        <div class="alert no-print" role="status" style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="page-header no-print" style="background: linear-gradient(90deg,#0B3D91,#0A347B); color: #fff; padding: 24px; border-radius: 16px; margin-bottom: 24px;">
        <h1 style="color: #fff; font-size: 28px; font-weight: 700; margin: 0;">Odluka o dodjeli sredstava</h1>
        <p style="margin: 8px 0 0; color: #e5e7eb;">{{ $competition->title }}</p>
    </div>

    @include('evaluation._youth_allocation_list_confirmation', [
        'youthAllocationConfirmationBoard' => $youthAllocationConfirmationBoard ?? null,
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
            <form method="POST" action="{{ route('evaluation.youth-decision.generate', $competition) }}" data-testid="youth-decision-generate-form">
                @csrf
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
        @if(!empty($canDownloadYouthDecision))
            <a href="{{ route('evaluation.youth-decision.download', $competition) }}" data-testid="youth-decision-download" class="btn" style="background: #0B3D91; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; margin-right: 8px;">Preuzmi odluku</a>
        @endif
        <a href="{{ route('evaluation.youth-ranking', $competition) }}" class="btn" style="background: #6b7280; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;">Nazad na rang listu</a>
        @if(!empty($canCloseYouthCompetition))
            <form method="POST" action="{{ route('admin.competitions.close', $competition) }}" style="display: inline; margin-left: 8px;">
                @csrf
                <button type="submit" class="btn" data-testid="youth-close-competition" style="background: #dc2626; color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Zatvori konkurs</button>
            </form>
        @elseif(!empty($youthCloseBlockedByDocument))
            <p data-testid="youth-close-document-required" style="display: inline; margin-left: 8px; color: #991b1b;">{{ $youthCloseDocumentMessage }}</p>
        @endif
    </div>
</div>
@endsection
