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

    <div class="no-print" style="margin-top: 16px;">
        <a href="{{ route('evaluation.index') }}" style="color: #6b7280; text-decoration: none;">Nazad na listu</a>
    </div>
</div>
@endsection
