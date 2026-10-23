@php
    $youthBonusesLocked = $youthBonusesLocked ?? $application->bonuses_confirmed_at !== null;
    $youthPlannedRegistrationBonusEligible = $youthPlannedRegistrationBonusEligible
        ?? app(\App\Services\CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($application);
@endphp

@if(! $youthPlannedRegistrationBonusEligible)
    <input type="hidden" name="bonus_new_business" value="0">
@endif

@if($youthBonusesLocked)
    <div id="youth-bonus-locked-banner" class="no-print" style="padding: 12px; background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px; color: #065f46; margin-top: 16px; margin-bottom: 16px;">
        Dodatni bodovi su zaključani.
    </div>
@endif
