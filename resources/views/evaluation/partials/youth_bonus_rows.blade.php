@php
    $youthBonusesLocked = $youthBonusesLocked ?? $application->bonuses_confirmed_at !== null;
    $youthPlannedRegistrationBonusEligible = $youthPlannedRegistrationBonusEligible
        ?? app(\App\Services\CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($application);
    $infoDay = (bool) old('bonus_info_day', $application->bonus_info_day ?? false);
    $training = (bool) old('bonus_training', $application->bonus_training ?? false);
    $newBusiness = (bool) old('bonus_new_business', $application->bonus_new_business ?? false);
    $green = (bool) old('bonus_green_innovative', $application->bonus_green_innovative ?? false);
    $attendancePoints = ($infoDay && $training) ? 1 : 0;
    $newBusinessPoints = ($newBusiness && $youthPlannedRegistrationBonusEligible) ? 2 : 0;
    $greenPoints = $green ? 3 : 0;
    $youthBonusPreview = min(6, $attendancePoints + $newBusinessPoints + $greenPoints);
    $canEditYouthBonuses = $youthBonusEditable
        ?? (! $youthBonusesLocked
            && ! ($isApplicant ?? false)
            && ! ($isRejected ?? false)
            && ($isChairman ?? false)
            && (($scoringIsAllowed ?? false) || ($hasCompletedEvaluation ?? false)));
    $allMembers = $allMembers ?? collect();
@endphp

<tr>
    <td class="criterion-col">Info dan</td>
    @foreach($allMembers as $member)
        @php
            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            $isChairmanMember = $member->position === 'predsjednik';
        @endphp
        <td style="text-align: center;">
            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                <input type="checkbox" name="bonus_info_day" value="1" form="evaluationForm" {{ $infoDay ? 'checked' : '' }}>
            @elseif($infoDay && $isChairmanMember)
                ✓
            @else
                —
            @endif
        </td>
    @endforeach
    <td class="average-col">
        @if($attendancePoints === 1)
            1.00
        @elseif($infoDay)
            0
        @else
            —
        @endif
    </td>
</tr>
@error('bonus_info_day')
    <tr>
        <td colspan="{{ 2 + $allMembers->count() }}" class="criterion-col">
            <div class="error-message">{{ $message }}</div>
        </td>
    </tr>
@enderror

<tr>
    <td class="criterion-col">Obuka</td>
    @foreach($allMembers as $member)
        @php
            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
            $isChairmanMember = $member->position === 'predsjednik';
        @endphp
        <td style="text-align: center;">
            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                <input type="checkbox" name="bonus_training" value="1" form="evaluationForm" {{ $training ? 'checked' : '' }}>
            @elseif($training && $isChairmanMember)
                ✓
            @else
                —
            @endif
        </td>
    @endforeach
    <td class="average-col">
        @if($attendancePoints === 1)
            —
        @elseif($training)
            0
        @else
            —
        @endif
    </td>
</tr>
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

<tr class="youth-bonus-sum-row">
    <td class="criterion-col" style="text-align: center; font-weight: bold !important;">
        <strong>UKUPAN ZBIR (maksimum 6):</strong>
    </td>
    @foreach($allMembers as $member)
        <td>—</td>
    @endforeach
    <td class="average-col" style="font-weight: bold !important;">
        <strong>{{ number_format($youthBonusPreview, 2) }}</strong>
    </td>
</tr>
