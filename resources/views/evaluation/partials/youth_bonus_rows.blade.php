@php
    $youthBonusesLocked = $youthBonusesLocked ?? $application->bonuses_confirmed_at !== null;
    $youthPlannedRegistrationBonusEligible = $youthPlannedRegistrationBonusEligible
        ?? app(\App\Services\CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($application);
    $infoDay = (bool) old('bonus_info_day', $application->bonus_info_day ?? false);
    $training = (bool) old('bonus_training', $application->bonus_training ?? false);
    $attendanceConfirmed = $infoDay && $training;
    $attendanceChecked = (bool) old('bonus_info_and_training', $attendanceConfirmed);
    $newBusiness = (bool) old('bonus_new_business', $application->bonus_new_business ?? false);
    $green = (bool) old('bonus_green_innovative', $application->bonus_green_innovative ?? false);
    $attendancePoints = ($infoDay && $training) ? 1 : 0;
    $newBusinessPoints = ($newBusiness && $youthPlannedRegistrationBonusEligible) ? 2 : 0;
    $greenPoints = $green ? 3 : 0;
    $youthConfirmedBonusPoints = $youthBonusesLocked
        ? app(\App\Services\CanonicalIndividualScoringService::class)->youthBonusScore($application)
        : 0;
    $canEditYouthBonuses = $youthBonusEditable
        ?? (! $youthBonusesLocked
            && ! ($isApplicant ?? false)
            && ! ($isRejected ?? false)
            && ($isChairman ?? false)
            && (($scoringIsAllowed ?? false) || ($hasCompletedEvaluation ?? false)));
    $allMembers = $allMembers ?? collect();
    $allScores = $allScores ?? collect();
@endphp

<tr>
    <td class="criterion-col">Info dan i obuka (+1)</td>
    @foreach($allMembers as $member)
        @php
            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            $isChairmanMember = $member->position === 'predsjednik';
        @endphp
        <td style="text-align: center;">
            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                <input type="hidden" name="bonus_info_and_training" value="0" form="evaluationForm">
                <input type="checkbox" name="bonus_info_and_training" value="1" form="evaluationForm" {{ $attendanceChecked ? 'checked' : '' }}>
            @elseif($isChairmanMember)
                {{ $attendanceConfirmed ? '✓' : '—' }}
            @else
                —
            @endif
        </td>
    @endforeach
    <td class="average-col">
        @if($attendancePoints === 1)
            1.00
        @elseif($infoDay || $training)
            0
        @else
            —
        @endif
    </td>
</tr>
@error('bonus_info_and_training')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror
@error('bonus_info_day')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror
@error('bonus_training')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror

<tr>
    <td class="criterion-col">
        Planirana registracija
        @if(! $youthPlannedRegistrationBonusEligible)
            <div style="margin-top: 4px; font-size: 11px; font-weight: 500; color: #6b7280;">
                Nije dostupna za ovu prijavu.
            </div>
        @endif
    </td>
    @foreach($allMembers as $member)
        @php
            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            $isChairmanMember = $member->position === 'predsjednik';
        @endphp
        <td style="text-align: center;">
            @if($youthPlannedRegistrationBonusEligible && $canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                <input type="checkbox" name="bonus_new_business" value="1" form="evaluationForm" {{ $newBusiness ? 'checked' : '' }}>
            @elseif($newBusinessPoints > 0 && $isChairmanMember)
                ✓
            @else
                —
            @endif
        </td>
    @endforeach
    <td class="average-col">
        {{ $newBusinessPoints > 0 ? '2.00' : '—' }}
    </td>
</tr>
@error('bonus_new_business')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror

<tr>
    <td class="criterion-col">Inovativno/zeleno poslovanje</td>
    @foreach($allMembers as $member)
        @php
            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            $isChairmanMember = $member->position === 'predsjednik';
        @endphp
        <td style="text-align: center;">
            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                <input type="checkbox" name="bonus_green_innovative" value="1" form="evaluationForm" {{ $green ? 'checked' : '' }}>
            @elseif($green && $isChairmanMember)
                ✓
            @else
                —
            @endif
        </td>
    @endforeach
    <td class="average-col">
        {{ $greenPoints > 0 ? '3.00' : '—' }}
    </td>
</tr>
@error('bonus_green_innovative')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror

<tr class="final-score-row" id="youth-final-score-row">
    <td class="criterion-col" style="text-align: center; font-weight: bold !important;">
        <strong>UKUPAN ZBIR (10 kriterijuma + bonusi)</strong>
    </td>
    @foreach($allMembers as $member)
        <td style="font-weight: bold !important;">
            @php
                $memberScore = $allScores->get($member->id);
                $memberScoreIsFinal = $memberScore && $memberScore->completed_at !== null;
                $memberCriteriaTotal = $memberScoreIsFinal ? $memberScore->calculateTotalScore() : 0;
                $memberCombinedTotal = $memberCriteriaTotal > 0
                    ? $memberCriteriaTotal + $youthConfirmedBonusPoints
                    : 0;
                $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            @endphp
            @if($isCurrentMember && $memberScoreIsFinal)
                <strong>{{ $memberCombinedTotal > 0 ? $memberCombinedTotal : '—' }}</strong>
            @elseif(! $isCurrentMember && ($canViewOtherMembersScores ?? false) && $memberScoreIsFinal)
                <strong>{{ $memberCombinedTotal > 0 ? $memberCombinedTotal : '—' }}</strong>
            @else
                <strong style="color: #d1d5db;">—</strong>
            @endif
        </td>
    @endforeach
    <td class="average-col" id="final_score" style="font-weight: bold !important;">
        @if(($canViewOtherMembersScores ?? false) && ! empty($youthFinalScoreDisplay))
            <strong>{{ $youthFinalScoreDisplay }} / 56</strong>
        @else
            <strong>—</strong>
        @endif
    </td>
</tr>
