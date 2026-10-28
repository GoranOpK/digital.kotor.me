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

    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
        <a href="{{ route('evaluation.index') }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad</a>
    </div>
</div>
@endsection
