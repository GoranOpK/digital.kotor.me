<?php

namespace App\Support;

use App\Models\Application;
use App\Models\BusinessPlan;

/**
 * Jedini izvor paketa 1–4 i labela priloga za profil omladinsko.
 * Ne upisuje u bazu ni storage. Ne čuva broj paketa kao kolonu.
 */
final class KnOmladinskoDocumentPackage
{
    public const REALIZATION_DRUGO = 'nista_od_navedenog';

    public const TYPE_ZIRO = 'dokaz_ziro_racun';

    public const TYPE_IOPPD = 'ioppd_obrazac';

    public const TYPE_GODISNJI_RACUNI = 'godisnji_racuni';

    public const FORBIDDEN_UPLOAD_TYPES = [
        'potvrda_zavod_nezaposleni',
        'izvjestaj_realizacija',
        'finansijski_izvjestaj',
        'izvjestaj_registar_kase',
        'ostalo',
    ];

    /**
     * @param  int<1, 4>|null  $number
     */
    private function __construct(
        public readonly ?int $number,
        public readonly ?string $applicantType,
        public readonly ?string $businessStage,
        public readonly bool $isRegistered,
    ) {
    }

    public static function resolve(?string $applicantType, ?string $businessStage, bool $isRegistered): self
    {
        return new self(
            self::packageNumber($applicantType, $businessStage),
            $applicantType,
            $businessStage,
            $isRegistered,
        );
    }

    public static function forApplication(Application $application): self
    {
        return self::resolve(
            $application->applicant_type,
            $application->business_stage,
            (bool) $application->is_registered
        );
    }

    /**
     * @return int<1, 4>|null
     */
    public static function packageNumber(?string $applicantType, ?string $businessStage): ?int
    {
        if ($applicantType === 'fizicko_lice' && $businessStage === 'započinjanje') {
            return 1;
        }
        if ($applicantType === 'preduzetnik' && $businessStage === 'započinjanje') {
            return 1;
        }
        if ($applicantType === 'preduzetnik' && $businessStage === 'razvoj') {
            return 2;
        }
        if ($applicantType === 'privredno_drustvo' && $businessStage === 'započinjanje') {
            return 3;
        }
        if ($applicantType === 'privredno_drustvo' && $businessStage === 'razvoj') {
            return 4;
        }

        return null;
    }

    public function title(): string
    {
        return match ($this->number) {
            1 => 'Preduzetnici koje započinju biznis (čiji biznisi nisu stariji od godinu dana u trenutku raspisivanja konkursa ili tek planiraju otpočinjanje)',
            2 => 'Preduzetnici koje planiraju razvoj poslovanja',
            3 => 'Društva koja započinju biznis (čiji biznisi nisu stariji od godinu dana u trenutku raspisivanja konkursa ili tek planiraju otpočinjanje)',
            4 => 'Društva koja planiraju razvoj poslovanja',
            default => '',
        };
    }

    public function isM1b(): bool
    {
        return $this->number === 3 || $this->number === 4;
    }

    /**
     * @return array{m1: string, m2: string}
     */
    public function formTitles(): array
    {
        $m1 = $this->isM1b()
            ? 'Prijava na konkurs za podršku preduzetništvu mladih (obrazac M1b)'
            : 'Prijava na konkurs za podršku preduzetništvu mladih (obrazac M1a)';

        return [
            'm1' => $m1,
            'm2' => 'Popunjena forma za biznis plan (obrazac M2)',
        ];
    }

    /**
     * @return list<string>
     */
    public function catalogDocumentTypes(): array
    {
        return match ($this->number) {
            1 => [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                self::TYPE_ZIRO,
                'predracuni_nabavka',
            ],
            2 => [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                self::TYPE_IOPPD,
                self::TYPE_ZIRO,
                'predracuni_nabavka',
            ],
            3 => [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'predracuni_nabavka',
            ],
            4 => [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                self::TYPE_GODISNJI_RACUNI,
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                self::TYPE_IOPPD,
                'predracuni_nabavka',
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public function registrationConditionedTypes(): array
    {
        return match ($this->number) {
            1 => ['crps_resenje', 'pib_resenje', 'pdv_resenje', self::TYPE_ZIRO],
            3 => ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa'],
            default => [],
        };
    }

    /**
     * Prilozi koji se prikazuju (bez M1/M2). Uslovni registracioni prilozi samo ako je registrovan.
     *
     * @return list<string>
     */
    public function displayedDocumentTypes(): array
    {
        $types = $this->catalogDocumentTypes();
        if ($this->isRegistered || $this->registrationConditionedTypes() === []) {
            return $types;
        }

        return array_values(array_diff($types, $this->registrationConditionedTypes()));
    }

    /**
     * Prilozi čiji nedostatak ulazi u upozorenje o nepotpunosti. Žiro nikad.
     *
     * @return list<string>
     */
    public function strictlyRequiredDocumentTypes(): array
    {
        return array_values(array_diff(
            $this->displayedDocumentTypes(),
            [self::TYPE_ZIRO]
        ));
    }

    public function isDisplayed(string $documentType): bool
    {
        return in_array($documentType, $this->displayedDocumentTypes(), true);
    }

    public function isStrictlyRequired(string $documentType): bool
    {
        return in_array($documentType, $this->strictlyRequiredDocumentTypes(), true);
    }

    public function isNonBlocking(string $documentType): bool
    {
        return $documentType === self::TYPE_ZIRO && $this->isDisplayed($documentType);
    }

    public function isConditional(string $documentType): bool
    {
        return in_array($documentType, $this->registrationConditionedTypes(), true)
            && $this->isDisplayed($documentType)
            && ! $this->isNonBlocking($documentType);
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        $labels = $this->baseLabels();
        $filtered = [];
        foreach ($this->displayedDocumentTypes() as $type) {
            if (isset($labels[$type])) {
                $filtered[$type] = $labels[$type];
            }
        }

        return $filtered;
    }

    /**
     * @return list<string>
     */
    public function previewItems(): array
    {
        if ($this->number === null) {
            return [];
        }

        $items = [
            $this->formTitles()['m1'],
            $this->formTitles()['m2'],
        ];
        foreach ($this->displayedDocumentTypes() as $type) {
            $items[] = $this->labels()[$type] ?? $type;
        }

        return $items;
    }

    public static function allowsUploadType(self $package, string $documentType): bool
    {
        if (in_array($documentType, self::FORBIDDEN_UPLOAD_TYPES, true)) {
            return false;
        }

        return $package->isDisplayed($documentType);
    }

    public static function filledPurchaseCount(mixed $table): int
    {
        if (! is_array($table)) {
            return 0;
        }

        $count = 0;
        foreach ($table as $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = trim((string) ($row['type'] ?? ''));
            $price = $row['price'] ?? null;
            if ($type === '' || $price === null || $price === '') {
                continue;
            }
            $count++;
        }

        return $count;
    }

    public static function hasPurchaseItem(?BusinessPlan $plan): bool
    {
        return self::filledPurchaseCount($plan?->funding_sources_table) >= 1;
    }

    public static function realizationError(?BusinessPlan $plan): ?string
    {
        $choice = is_string($plan?->realization_type) ? trim($plan->realization_type) : '';
        if ($choice === '') {
            return 'Tačka 7 biznis plana mora imati tačno jedan odgovor.';
        }

        $allowed = [
            'stvori_novi',
            'unaprijedi',
            'uveca_obim',
            'nista_se_nece_promijeniti',
            self::REALIZATION_DRUGO,
        ];
        if (! in_array($choice, $allowed, true)) {
            return 'Tačka 7 biznis plana mora imati tačno jedan odgovor.';
        }

        if ($choice === self::REALIZATION_DRUGO) {
            $other = trim((string) ($plan?->product_service ?? ''));
            if ($other === '') {
                return 'Ako je u tački 7 izabrano „Drugo“, obavezno je tekstualno objašnjenje.';
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function baseLabels(): array
    {
        $ioppdEntrepreneur = 'Odgovarajući obrazac ovjeren od strane Poreske uprave za poslijednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od strane Poreske uprave da preduzetnik nema zaposlenih';
        $ioppdCompany = 'IOPPD za poslijednji mjesec uplate poreza i doprinosa za zaposlene, ovjeren od strane Poreske uprave, kao dokaz o broju zaposlenih (IOPPD Obrazac), ili potvrdu Poreske uprave da nema zaposlenih';

        return match ($this->number) {
            1 => [
                'licna_karta' => 'Ovjerena kopija lične karte',
                'crps_resenje' => 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)',
                'pib_resenje' => 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)',
                'pdv_resenje' => 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)',
                'potvrda_neosudjivanost' => 'Potvrda da se ne vodi krivični postupak na ime podnosioca prijave odnosno preduzetnika izdata od Osnovnog suda',
                'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnosioca prijave odnosno preduzetnika po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada',
                'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnosioca prijave odnosno preduzetnika',
                self::TYPE_ZIRO => 'Dokaz o broju poslovnog broja žiro računa (ukoliko ima registrovanu djelatnost)',
                'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
            ],
            2 => [
                'licna_karta' => 'Ovjerena kopija lične karte',
                'crps_resenje' => 'Rješenje o upisu u CRPS',
                'pib_resenje' => 'Rješenje o registraciji PJ Poreske uprave',
                'pdv_resenje' => 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)',
                'potvrda_neosudjivanost' => 'Potvrda da se ne vodi krivični postupak na ime preduzetnika izdata od Osnovnog suda',
                'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime preduzetnika po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada na ime preduzetnika',
                'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnika',
                'potvrda_upc_porezi' => 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne starija od 30 dana, na ime preduzetnika',
                self::TYPE_IOPPD => $ioppdEntrepreneur,
                self::TYPE_ZIRO => 'Dokaz o broju poslovnog broja žiro računa (ukoliko ima registrovanu djelatnost)',
                'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
            ],
            3 => [
                'licna_karta' => 'Ovjerena kopija lične karte nosioca biznisa',
                'crps_resenje' => 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)',
                'pib_resenje' => 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)',
                'pdv_resenje' => 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)',
                'statut' => 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)',
                'karton_potpisa' => 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)',
                'potvrda_neosudjivanost' => 'Potvrda da se ne vodi krivični postupak na podnosioca prijave odnosno na ime nosioca biznisa izdata od strane Osnovnog suda',
                'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnosioca prijave odnosno na ime nosioca biznisa po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada',
                'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnosioca prijave odnosno na ime nosioca biznisa',
                'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
            ],
            4 => [
                'licna_karta' => 'Ovjerena kopija lične karte nosioca biznisa',
                'crps_resenje' => 'Rješenje o upisu u CRPS',
                'pib_resenje' => 'Rješenje o registraciji PJ Poreske uprave',
                'pdv_resenje' => 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)',
                'statut' => 'Važeći Statut društva',
                'karton_potpisa' => 'Važeći karton deponovanih potpisa',
                self::TYPE_GODISNJI_RACUNI => 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu. Napomena: U slučaju da ne vodi analitiku kupaca tj. posluje isključivo sa fizičkim licima i naplata se vrši odmah putem registar kase, ima obavezu dostaviti periodični izvještaj sa registar kase',
                'potvrda_neosudjivanost' => 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa izdata od strane Osnovnog suda',
                'uvjerenje_opstina_porezi' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime nosioca biznisa i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada',
                'uvjerenje_opstina_nepokretnost' => 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa i na ime društva',
                'potvrda_upc_porezi' => 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne starija od 30 dana, na ime nosioca biznisa i na ime društva',
                self::TYPE_IOPPD => $ioppdCompany,
                'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
            ],
            default => [],
        };
    }
}
