<?php

namespace App\Support;

use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;

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

    public const WOMEN_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE = 'Ocjenjivanje počinje tek kada istekne rok od 20 dana za prijave na konkurs. Nakon toga počinje rok od 45 dana za donošenje odluke od strane komisije.';

    public const YOUTH_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE = 'Ocjenjivanje počinje tek kada istekne rok za prijave na konkurs.';

    public const CANONICAL_SCORING_DISABLED_MESSAGE = 'Bodovanje kanonskog MMSP Poziva nije aktivno. Kriterijumi i formula uvode se u KN-SYNC-007.';

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
        /** @var list<string> */
        public readonly array $bonusFlagKeys = [],
        public readonly bool $scoringFormulaActive = true,
    ) {}

    public static function for(?string $type): self
    {
        return match ($type) {
            CompetitionProfileResolver::TYPE_CANONICAL => new self(
                type: CompetitionProfileResolver::TYPE_CANONICAL,
                criteria: self::canonicalCriteria(),
                allowedSeats: [1, 2, 3],
                requiredFinalCount: 3,
                allowsIncompleteDraft: false,
                lockRequiresCompletedOral: false,
                usesHistoricalCompletionWithoutTimestamp: false,
                persistAggregatesOnCycleComplete: false,
                showsZavodBonus: false,
                appliesEvaluationDeadline: false,
                hidesOtherScoresUntilLaterStep: true,
                bonusFlagKeys: [],
            ),
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
                bonusFlagKeys: CanonicalIndividualScoringService::YOUTH_BONUS_FLAG_KEYS,
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
                bonusFlagKeys: CanonicalIndividualScoringService::BONUS_FLAG_KEYS,
            ),
        };
    }

    public function isOmladinsko(): bool
    {
        return $this->type === 'omladinsko';
    }

    public function isCanonical(): bool
    {
        return $this->type === CompetitionProfileResolver::TYPE_CANONICAL;
    }

    /**
     * Fail closed for profiles whose scoring criteria/formula are not active yet.
     */
    public function assertScoringFormulaActive(): void
    {
        if (! $this->scoringFormulaActive) {
            abort(403, self::CANONICAL_SCORING_DISABLED_MESSAGE);
        }
    }

    public function evaluationOpensAfterApplicationDeadlineMessage(): string
    {
        return $this->isOmladinsko()
            ? self::YOUTH_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE
            : self::WOMEN_EVALUATION_OPENS_AFTER_APPLICATION_DEADLINE_MESSAGE;
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
            7 => 'Podaci o podnositeljki prijave (posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti)',
            8 => 'Podnositeljka prijave planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.',
            9 => 'Razvijena matrica rizika je jasna i logična.',
            10 => 'Usmeno obrazloženje biznis plana (podnositeljka prijave je uvjerljiva i sigurna u svoju biznis ideju, pokazuje visoku motivisanost za njenu realizaciju i spremno odgovara na sva pitanja).',
        ];
    }

    /**
     * Kanonski MMSP: deset pozitivnih kriterijuma iz čl.20 Odluke i obrasca P3 (KN-BM §17.1, KN-BR-031/032).
     *
     * @return array<int, string>
     */
    public static function canonicalCriteria(): array
    {
        return [
            1 => 'Kvalitet plana ulaganja (ciljevi, opis aktivnosti, period realizacije).',
            2 => 'Relevantnost ulaganja za razvoj biznisa.',
            3 => 'Finansijska opravdanost (realni troškovi i jasna finansijska konstrukcija).',
            4 => 'Jačanje konkurentnosti.',
            5 => 'Lokalna autentičnost proizvoda/usluga.',
            6 => 'Inovativnost (novina ili značajno unapređenje; nova tehnologija i sl.).',
            7 => 'Doprinos digitalizaciji poslovanja (digitalni alati, online prodaja/promocija, sistemi za upravljanje).',
            8 => 'Uticaj na lokalnu ekonomiju (zapošljavanje, turizam, saradnja sa lokalnim dobavljačima).',
            9 => 'Održivost i dugoročni efekti.',
            10 => 'Prezentacija plana na intervjuu (jasan plan, spremno odgovaranje, vizija održivosti).',
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
