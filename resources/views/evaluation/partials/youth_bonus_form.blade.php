@php
    $youthBonusesLocked = $youthBonusesLocked ?? $application->bonuses_confirmed_at !== null;
    $youthPlannedRegistrationBonusEligible = $youthPlannedRegistrationBonusEligible
        ?? app(\App\Services\CanonicalIndividualScoringService::class)->youthQualifiesForPlannedRegistrationBonus($application);
    $infoDay = old('bonus_info_day', $application->bonus_info_day ?? false);
    $training = old('bonus_training', $application->bonus_training ?? false);
    $newBusiness = old('bonus_new_business', $application->bonus_new_business ?? false);
    $green = old('bonus_green_innovative', $application->bonus_green_innovative ?? false);
@endphp

<div class="form-section no-print" style="margin-top: 32px; padding: 20px; border: 1px solid #d1d5db; border-radius: 12px; background: #f9fafb;">
    <label class="form-label form-label-large">Dodatni bodovi mladih</label>
    <p style="color: #4b5563; font-size: 13px; margin-bottom: 16px;">
        Predsjednik evidentira činjenice u ime Komisije. Maksimalno 6 dodatnih bodova. Bonus Zavoda se ne primjenjuje.
        Bod +1 nastaje samo ako su potvrđeni i Info dan i obuka. Jedna potvrda sama nosi 0 bodova, ali smije ostati u nacrtu.
        Inovativno i/ili zeleno je jedna kategorija od +3, ne +3 i još +3.
    </p>

    @if($youthBonusesLocked)
        <div style="padding: 12px; background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px; color: #065f46; margin-bottom: 16px;">
            Dodatni bodovi su zaključani.
            <div style="margin-top: 8px; font-size: 13px;">
                Potvrdio: {{ $application->bonuses_confirmed_by_name ?: '—' }}
            </div>
            <div style="font-size: 13px;">
                Datum i vrijeme: {{ $application->bonuses_confirmed_at?->format('d.m.Y. H:i') ?: '—' }}
            </div>
        </div>
        <ul style="margin: 0; padding-left: 18px; color: #374151; font-size: 14px;">
            <li>Info dan — {{ $application->bonus_info_day ? 'evidentirano prisustvo' : 'nije evidentirano' }}</li>
            <li>Obuka — {{ $application->bonus_training ? 'evidentirano prisustvo' : 'nije evidentirano' }}</li>
            <li>Planirana registracija — {{ $application->bonus_new_business ? 'evidentirano' : 'nije evidentirano' }}</li>
            <li>Inovativno i/ili zeleno — {{ $application->bonus_green_innovative ? 'evidentirano' : 'nije evidentirano' }}</li>
        </ul>
    @else
        <form method="POST" action="{{ route('evaluation.store', $application) }}">
            @csrf
            <div style="display: grid; gap: 12px; margin-bottom: 16px;">
                <label style="display: flex; align-items: flex-start; gap: 8px;">
                    <input type="checkbox" name="bonus_info_day" value="1" {{ $infoDay ? 'checked' : '' }}>
                    <span>Info dan — evidentirano prisustvo na osnovu evidencije organizatora</span>
                </label>
                @error('bonus_info_day')
                    <div class="error-message">{{ $message }}</div>
                @enderror

                <label style="display: flex; align-items: flex-start; gap: 8px;">
                    <input type="checkbox" name="bonus_training" value="1" {{ $training ? 'checked' : '' }}>
                    <span>Obuka — evidentirano prisustvo na osnovu evidencije organizatora</span>
                </label>
                @error('bonus_training')
                    <div class="error-message">{{ $message }}</div>
                @enderror

                <p style="margin: 0; color: #92400e; font-size: 13px;">
                    +1 nastaje samo ako su oba prisustva potvrđena. Jedna potvrda sama ne donosi bod.
                </p>

                @if($youthPlannedRegistrationBonusEligible)
                    <label style="display: flex; align-items: flex-start; gap: 8px;">
                        <input type="checkbox" name="bonus_new_business" value="1" {{ $newBusiness ? 'checked' : '' }}>
                        <span>Planirana registracija — fizičko lice koje planira registraciju biznisa (+2)</span>
                    </label>
                @else
                    <input type="hidden" name="bonus_new_business" value="0">
                    <p style="margin: 0; color: #6b7280; font-size: 13px;">
                        Planirana registracija (+2) nije dostupna. Zaključana kategorija prijave nije fizičko lice koje planira registraciju.
                    </p>
                @endif
                @error('bonus_new_business')
                    <div class="error-message">{{ $message }}</div>
                @enderror

                <label style="display: flex; align-items: flex-start; gap: 8px;">
                    <input type="checkbox" name="bonus_green_innovative" value="1" {{ $green ? 'checked' : '' }}>
                    <span>Inovativno i/ili zeleno poslovanje — jedna kategorija Komisije (+3)</span>
                </label>
                @error('bonus_green_innovative')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" name="youth_bonus_action" value="draft" class="btn-primary">Sačuvaj nacrt bonusa</button>
            <button type="submit" name="youth_bonus_action" value="confirm" class="btn-primary" style="margin-left: 12px;">Potvrdi i zaključaj bonuse</button>
        </form>
    @endif
</div>
