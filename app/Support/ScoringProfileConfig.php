<?php

namespace App\Support;

/**
 * Profilna konfiguracija individualnog bodovanja.
 * Ženski katalog, bonusi i 5 mjesta ostaju nepromijenjeni.
 */
final class ScoringProfileConfig
{
    public const YOUTH_SCORING_LOCKED_MESSAGE = 'Individualno bodovanje nije dostupno dok prijava ne ispunjava kapiju ocjenjivanja mladih.';

    public const YOUTH_ORAL_REQUIRED_FOR_LOCK_MESSAGE = 'Završetak ocjenjivanja je dozvoljen tek nakon završene evidencije usmenog predstavljanja konkretne prijave.';

    public const YOUTH_SECOND_SESSION_REQUIRED_FOR_LOCK_MESSAGE = 'Završetak ocjenjivanja je dozvoljen tek nakon potvrđene druge sjednice Komisije.';

    public const YOUTH_SCORE_CONFLICT_MESSAGE = 'Ocjena za ovo mjesto Komisije već postoji.';

    public const SCALE_MIN_LABEL = 'uopšte ne odgovara navedenom';

    public const SCALE_MAX_LABEL = 'u potpunosti odgovara navedenom';

    /**
     * @param  array<int, string>  $criteria
     * @param  list<int>  $allowedSeats
     */
    private function __construct(
        public readonly ?string $type,
        public readonly array $criteria,
        public readonly array $allowedSeats,
        public readonly int $requiredFinalCount,
        public readonly bool $allowsIncompleteDraft,
        public readonly bool $lockRequiresCompletedOral,
        public readonly bool $usesHistoricalCompletionWithoutTimestamp,
        public readonly bool $persistAggregatesOnCycleComplete,
        public readonly bool $showsZavodBonus,
        public readonly bool $appliesEvaluationDeadline,
        public readonly bool $hidesOtherScoresUntilLaterStep,
    ) {}

    public static function for(?string $type): self
    {
        return match ($type) {
            'omladinsko' => new self(
                type: 'omladinsko',
                criteria: self::youthCriteria(),
                allowedSeats: [1, 2, 3],
                requiredFinalCount: 3,
                allowsIncompleteDraft: true,
                lockRequiresCompletedOral: true,
                usesHistoricalCompletionWithoutTimestamp: false,
                persistAggregatesOnCycleComplete: false,
                showsZavodBonus: false,
                appliesEvaluationDeadline: false,
                hidesOtherScoresUntilLaterStep: true,
            ),
            default => new self(
                type: $type,
                criteria: self::zenskoCriteria(),
                allowedSeats: [1, 2, 3, 4, 5],
                requiredFinalCount: 5,
                allowsIncompleteDraft: false,
                lockRequiresCompletedOral: false,
                usesHistoricalCompletionWithoutTimestamp: true,
                persistAggregatesOnCycleComplete: true,
                showsZavodBonus: true,
                appliesEvaluationDeadline: true,
                hidesOtherScoresUntilLaterStep: false,
            ),
        };
    }

    public function isOmladinsko(): bool
    {
        return $this->type === 'omladinsko';
    }

    public function allowsSeat(int $seat): bool
    {
        return in_array($seat, $this->allowedSeats, true);
    }

    /**
     * @return array<int, string>
     */
    public static function zenskoCriteria(): array
    {
        return [
            1 => 'Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu.',
            2 => 'Jasno su identifikovani potencijalni kupci i njihove karakteristike.',
            3 => 'Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor.',
            4 => 'Prepoznata je i navedena konkurencija, kao i slabosti i snage iste.',
            5 => 'Jasno su navedeni potrebni resursi i identifikovani dobavljači.',
            6 => 'Biznis ideja je finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).',
            7 => 'Podaci o preduzetnici (preduzetnica posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).',
            8 => 'Preduzetnica planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.',
            9 => 'Razvijena matrica rizika je jasna i logična.',
            10 => 'Usmeno obrazloženje biznis plana (preduzetnica je uvjerljiva i sigurna u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja).',
        ];
    }

    /**
     * BM-ML-038 vjerno, uključujući niz tačaka u kriterijumu 6.
     *
     * @return array<int, string>
     */
    public static function youthCriteria(): array
    {
        return [
            1 => 'Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu.',
            2 => 'Jasno su identifikovani potencijalni kupci i njihove karakteristike.',
            3 => 'Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor.',
            4 => 'Prepoznata je i navedena konkurencija kao i slabosti i snage iste.',
            5 => 'Jasno su navedeni potrebni resursi i identifikovani dobavljači.',
            6 => 'Biznis ideja je ....................... finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).',
            7 => 'Podaci o preduzetniku (fizičko lice/preduzetnik posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).',
            8 => 'Preduzetnik planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.',
            9 => 'Razvijena matrica rizika je jasna i logična.',
            10 => 'Usmeno obrazloženje biznis plana (preduzetnik je uvjerljivi siguran u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovora na sva pitanja).',
        ];
    }
}
