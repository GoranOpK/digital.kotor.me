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
    $canEditYouthBonuses = ! $youthBonusesLocked
        && ! ($isApplicant ?? false)
        && ! ($isRejected ?? false)
        && ($isChairman ?? false)
        && (($scoringIsAllowed ?? false) || ($hasCompletedEvaluation ?? false));
    $allMembers = $allMembers ?? collect();
@endphp

<div class="form-section print-segment-bonus-page" style="margin-top: 32px;">
    <label class="form-label form-label-large">Dodatni bodovi mladih</label>
    <p style="color: #4b5563; font-size: 13px; margin-bottom: 16px;">
        Predsjednik evidentira činjenice u ime Komisije na osnovu evidencije organizatora.
        Info dan i obuka se vode kao dva zasebna prisustva: zajedno nose +1, a samo jedno 0 bodova.
        Planirana registracija fizičkog lica koje ispunjava omladinske uslove nosi +2.
        Inovativno i/ili zeleno poslovanje je jedna kategorija od +3, bez dupliranja bodova.
        Ukupan zbir ne prelazi 6. Bonus Zavoda se ne prikazuje i ne ulazi u obračun.
    </p>

    @if($youthBonusesLocked)
        <div id="youth-bonus-locked-banner" class="no-print" style="padding: 12px; background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px; color: #065f46; margin-bottom: 16px;">
            Dodatni bodovi su zaključani.
        </div>
    @endif

    @if(! $youthPlannedRegistrationBonusEligible)
        <input type="hidden" name="bonus_new_business" value="0">
    @endif
        <table class="evaluation-table evaluation-table-bonus">
            <thead>
                <tr>
                    <th class="criterion-col">DODATNI BODOVI</th>
                    @foreach($allMembers as $member)
                        <th style="font-size: 11px;">
                            {{ $member->position === 'predsjednik' ? 'Predsjednik' : 'Član ' . ($loop->index) }}
                        </th>
                    @endforeach
                    <th class="average-col">Bodovi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="criterion-col">
                        Evidentirano prisustvo Info danu, na osnovu evidencije organizatora.
                        <div style="margin-top: 4px; font-size: 11px; font-weight: 500; color: #6b7280;">
                            Zajedno sa obukom nosi +1. Samo Info dan nosi 0 bodova i može ostati u nacrtu.
                        </div>
                    </td>
                    @foreach($allMembers as $member)
                        @php
                            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
                            $isChairmanMember = $member->position === 'predsjednik';
                        @endphp
                        <td style="text-align: center;">
                            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                                <input
                                    type="checkbox"
                                    name="bonus_info_day"
                                    value="1"
                                    {{ $infoDay ? 'checked' : '' }}
                                >
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
                    <td class="criterion-col">
                        Evidentirano prisustvo obuci za pisanje biznis plana, na osnovu evidencije organizatora.
                        <div style="margin-top: 4px; font-size: 11px; font-weight: 500; color: #6b7280;">
                            Zajedno sa Info danom nosi +1. Samo obuka nosi 0 bodova i može ostati u nacrtu.
                        </div>
                    </td>
                    @foreach($allMembers as $member)
                        @php
                            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
                            $isChairmanMember = $member->position === 'predsjednik';
                        @endphp
                        <td style="text-align: center;">
                            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                                <input
                                    type="checkbox"
                                    name="bonus_training"
                                    value="1"
                                    {{ $training ? 'checked' : '' }}
                                >
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
                        Planirana registracija fizičkog lica koje ispunjava omladinske uslove (+2)
                        @if(! $youthPlannedRegistrationBonusEligible)
                            <div style="margin-top: 4px; font-size: 11px; font-weight: 500; color: #6b7280;">
                                Nije dostupna. Zaključana kategorija prijave nije fizičko lice koje planira registraciju.
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
                                <input
                                    type="checkbox"
                                    name="bonus_new_business"
                                    value="1"
                                    {{ $newBusiness ? 'checked' : '' }}
                                >
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
                    <td class="criterion-col">
                        Inovativno i/ili zeleno poslovanje — jedna kategorija Komisije (+3), bez dupliranja bodova.
                    </td>
                    @foreach($allMembers as $member)
                        @php
                            $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
                            $isChairmanMember = $member->position === 'predsjednik';
                        @endphp
                        <td style="text-align: center;">
                            @if($canEditYouthBonuses && $isCurrentMember && $isChairmanMember)
                                <input
                                    type="checkbox"
                                    name="bonus_green_innovative"
                                    value="1"
                                    {{ $green ? 'checked' : '' }}
                                >
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

                <tr class="final-score-row">
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
            </tbody>
        </table>

        @if($canEditYouthBonuses)
            <div class="no-print" style="margin-top: 16px; text-align: center;">
                <button type="submit" name="youth_bonus_action" value="draft" class="btn-primary" form="evaluationForm">Sačuvaj nacrt bonusa</button>
                <button type="submit" name="youth_bonus_action" value="confirm" class="btn-primary" style="margin-left: 12px;" form="evaluationForm">Potvrdi i zaključaj bonuse</button>
            </div>
        @endif
</div>
