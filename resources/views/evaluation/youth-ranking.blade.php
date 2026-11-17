@extends('layouts.app')

@section('content')
<div class="admin-page" style="background: #f9fafb; min-height: 100vh; padding: 24px 0;">
<div class="container mx-auto px-4">
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
        'youthRankingApplicantReadOnly' => $youthRankingApplicantReadOnly ?? false,
    ])

    @if(empty($youthRankingApplicantReadOnly) && !empty($canGenerateYouthDecision))
        <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
            <a href="{{ route('evaluation.youth-decision', $youthEqualScoreCompetition) }}" data-testid="youth-decision-open" class="btn btn-primary" style="display: inline-block; background: #0B3D91; color: #fff; border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 600; text-decoration: none;">Generiši odluku</a>
        </div>
    @endif

    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
        @if(!empty($youthRankingApplicantReadOnly) && !empty($youthRankingReturnApplication))
            <a href="{{ route('applications.show', $youthRankingReturnApplication) }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad na prijavu</a>
        @else
            <a href="{{ route('evaluation.index') }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad</a>
        @endif
    </div>
</div>
</div>
@endsection
