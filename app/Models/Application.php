<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    public const DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI = 'potvrda_zavod_nezaposleni';

    protected $fillable = [
        'competition_id',
        'user_id',
        'business_plan_name',
        'applicant_type',
        'business_stage',
        'founder_name',
        'director_name',
        'company_seat',
        'physical_person_name',
        'physical_person_jmbg',
        'physical_person_phone',
        'physical_person_email',
        'requested_amount',
        'total_budget_needed',
        'approved_amount',
        'business_area',
        'website',
        'bank_account',
        'vat_number',
        'pib',
        'crps_number',
        'registration_form',
        'previous_support_declaration',
        'is_registered',
        'accuracy_declaration',
        'status',
        'final_score',
        'bonus_training',
        'bonus_women_business_mark',
        'bonus_info_day',
        'bonus_new_business',
        'bonus_zavod_nezaposleni',
        'bonus_green_innovative',
        'ranking_position',
        'rejection_reason',
        'email',
        'commission_decision',
        'commission_justification',
        'commission_notes',
        'commission_decision_date',
        'signed_by_chairman',
        'signed_by_members',
        'submitted_at',
        'evaluated_at',
        'interview_scheduled_at',
        'redni_broj',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'total_budget_needed' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'final_score' => 'decimal:2',
        'previous_support_declaration' => 'boolean',
        'is_registered' => 'boolean',
        'accuracy_declaration' => 'boolean',
        'bonus_training' => 'boolean',
        'bonus_women_business_mark' => 'boolean',
        'bonus_info_day' => 'boolean',
        'bonus_new_business' => 'boolean',
        'bonus_zavod_nezaposleni' => 'boolean',
        'bonus_green_innovative' => 'boolean',
        'email' => 'integer',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'interview_scheduled_at' => 'datetime',
        'commission_decision_date' => 'date',
        'signed_by_chairman' => 'boolean',
        'signed_by_members' => 'array',
    ];

    // Veza: aplikacija pripada konkursu
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    // Veza: aplikacija ima više dokumenata
    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    // Veza: aplikacija ima više ocjena (score)
    public function scores()
    {
        return $this->hasMany(ApplicationScore::class);
    }

    // Veza: aplikacija ima ocjene od komisije (evaluation scores)
    public function evaluationScores()
    {
        return $this->hasMany(EvaluationScore::class);
    }

    // Veza: aplikacija ima biznis plan
    public function businessPlan()
    {
        return $this->hasOne(BusinessPlan::class);
    }

    // Veza: aplikacija pripada korisniku
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Veza: aplikacija ima izvještaje realizacije
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // Veza: aplikacija ima jedan ugovor
    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Izračunava prosječnu ocjenu po kriterijumu (zbir ocjena svih članova / broj članova)
     */
    public function calculateAverageScorePerCriterion(int $criterionNumber): float
    {
        $scores = $this->evaluationScores()
            ->whereNotNull("criterion_{$criterionNumber}")
            ->pluck("criterion_{$criterionNumber}")
            ->toArray();

        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Izračunava konačnu ocjenu (zbir prosječnih ocjena po svim kriterijumima)
     */
    public function calculateFinalScore(): float
    {
        $totalScore = 0;
        
        for ($i = 1; $i <= 10; $i++) {
            $totalScore += $this->calculateAverageScorePerCriterion($i);
        }

        // Dodaj dodatne bodove prema Odluci (obuka + žig „Ženski biznis“)
        $totalScore += $this->getBonusScore();

        return round($totalScore, 2);
    }

    /**
     * Vraća zbir dodatnih bodova na osnovu dodatnih kriterijuma:
     * - Prisustvo Info danu i radionici (1 bod)
     * - Novi biznis (2 boda)
     * - Evidencija Zavoda za zapošljavanje duže od 12 mjeseci (2 boda)
     * - Inovativna i/ili „zelena“ ideja (3 boda)
     */
    public function getBonusScore(): int
    {
        $bonus = 0;

        if ($this->bonus_info_day) {
            $bonus += 1;
        }
        if ($this->bonus_new_business) {
            $bonus += 2;
        }
        if ($this->bonus_zavod_nezaposleni) {
            $bonus += 2;
        }
        if ($this->bonus_green_innovative) {
            $bonus += 3;
        }

        return $bonus;
    }

    /**
     * Proverava da li prijava zadovoljava minimum (30 bodova)
     */
    public function meetsMinimumScore(): bool
    {
        $finalScore = $this->final_score ?? $this->calculateFinalScore();
        return $finalScore >= 30;
    }

    /**
     * Vraća ocjenu za prikaz u sekciji Ocjena.
     * - Odbijena zbog nedostatka dokumenata: 0
     * - Odbijena zbog nedostatka bodova (< 30): stvarna ocjena
     * - Ostalo: final_score ili izračunata ocjena
     */
    public function getDisplayScore(): float
    {
        if ($this->isRejectedForMissingDocuments()) {
            return 0;
        }
        return (float) ($this->final_score ?? $this->calculateFinalScore());
    }

    /**
     * Prijava odbijena jer predsjednik komisije nije prihvatio dokumentaciju (bez bodovanja ostalih članova).
     */
    public function isRejectedForMissingDocuments(): bool
    {
        return $this->status === 'rejected'
            && $this->rejection_reason
            && str_contains($this->rejection_reason, 'Nedostaju potrebna dokumenta');
    }

    /**
     * Vraća tekst podnosioca prijave u formatu za Odluku o dodjeli
     * DOO: "NAZIV" DOO, koga zastupa osnivačica i izvršna direktorica [Ime]
     * Preduzetnica: Preduzetnica [Ime] koja obavlja djelatnost u [oblast]
     * Fizičko lice: [Ime]
     */
    public function getApplicantDisplayForDecision(): string
    {
        $companyName = $this->businessPlan?->company_name;
        $repName = $this->founder_name ?: $this->director_name;
        $userName = $this->user?->name ?? '';

        if (in_array($this->applicant_type, ['doo', 'ostalo']) && ($companyName || $repName || $userName)) {
            $name = $companyName ?: $userName;
            $suffix = $this->applicant_type === 'doo' ? ' DOO' : '';
            $quoted = str_contains($name, '"') ? $name : '"' . $name . '"';
            $rep = $repName ?: $userName;
            if ($rep) {
                return $quoted . $suffix . ', koga zastupa osnivačica i izvršna direktorica ' . $rep . '.';
            }
            return $quoted . $suffix . '.';
        }

        if ($this->applicant_type === 'preduzetnica') {
            $name = $repName ?: $userName ?: 'N/A';
            $oblast = $this->business_area ? ' u ' . $this->business_area : '';
            return 'Preduzetnica ' . $name . ' koja obavlja djelatnost' . $oblast . '.';
        }

        return $this->physical_person_name ?: $userName ?: 'N/A';
    }

    /**
     * Proverava da li su svi članovi komisije ocjenili prijavu
     */
    public function isFullyEvaluated(): bool
    {
        $competition = $this->competition;
        if (!$competition) {
            return false;
        }

        // Pronađi aktivnu komisiju za godinu konkursa
        $commission = \App\Models\Commission::where('year', $competition->year)
            ->where('status', 'active')
            ->first();

        if (!$commission) {
            return false;
        }

        $activeMembersCount = $commission->activeMembers()->count();
        $evaluationCount = $this->evaluationScores()->count();

        return $evaluationCount >= $activeMembersCount;
    }

    /**
     * Proverava da li je Obrazac 1a/1b kompletno popunjen
     * (sva obavezna polja + svi obavezni checkbox-ovi)
     */
    public function isObrazacComplete(): bool
    {
        // Proveri osnovna obavezna polja (bez finansijskih iznosa – oni se više ne unose u Obrazac 1a/1b)
        if (!$this->business_plan_name || 
            !$this->applicant_type || 
            !$this->business_stage || 
            !$this->business_area) {
            return false;
        }

        // Proveri polja specifična za tip podnosioca
        if ($this->applicant_type === 'fizicko_lice') {
            if (!$this->physical_person_name || 
                !$this->physical_person_jmbg || 
                !$this->physical_person_phone || 
                !$this->physical_person_email) {
                return false;
            }
        } elseif ($this->applicant_type === 'doo' || $this->applicant_type === 'ostalo') {
            if (!$this->founder_name || 
                !$this->director_name || 
                !$this->company_seat) {
                return false;
            }
        }

        // Proveri oblik registracije (obavezan za sve osim fizičkog lica)
        if ($this->applicant_type !== 'fizicko_lice' && !$this->registration_form) {
            return false;
        }

        // Izjava o tačnosti je obavezna za sve tipove prijave
        if (!$this->accuracy_declaration) {
            return false;
        }

        return true;
    }

    /**
     * Proverava da li prijava ima sve obavezne dokumente
     */
    public function hasAllRequiredDocuments(): bool
    {
        $requiredDocs = $this->getStrictlyRequiredDocuments();
        $uploadedDocs = $this->documents()
            ->where('is_required', true)
            ->pluck('document_type')
            ->toArray();

        foreach ($requiredDocs as $docType) {
            if (!in_array($docType, $uploadedDocs)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Dokumenti koji se prikazuju u listi obavezne dokumentacije, ali nisu obavezni za podnošenje prijave
     * (npr. potvrda Zavoda za dodatne bodove).
     */
    public static function getConditionallyRequiredDocumentTypes(): array
    {
        return [self::DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI];
    }

    public static function getZavodNezaposleniDocumentLabel(): string
    {
        return 'Potvrda Zavoda za zapošljavanje Crne Gore da se podnositeljka prijave nalazi na evidenciji nezaposlenih lica duže od 12 mjeseci (ukoliko ostvaruje pravo na dodatne bodove po tom osnovu)';
    }

    /**
     * Obavezni dokumenti bez uslovnih stavki (za provjeru kompletnosti prijave).
     */
    public function getStrictlyRequiredDocuments(): array
    {
        return array_values(array_diff(
            $this->getRequiredDocuments(),
            self::getConditionallyRequiredDocumentTypes()
        ));
    }

    /**
     * Tipovi obaveznih dokumenata koji nisu priloženi.
     *
     * @return list<string>
     */
    public function getMissingRequiredDocumentTypes(): array
    {
        $uploadedDocs = $this->relationLoaded('documents')
            ? $this->documents->pluck('document_type')->toArray()
            : $this->documents()->pluck('document_type')->toArray();

        return array_values(array_diff($this->getStrictlyRequiredDocuments(), $uploadedDocs));
    }

    /**
     * Nazivi nedostajućih obaveznih dokumenata (za mail i prikaz).
     *
     * @return list<string>
     */
    public function getMissingRequiredDocumentLabels(): array
    {
        $labels = $this->getDocumentLabelsMap();

        return array_values(array_map(
            fn (string $docType) => $labels[$docType] ?? $docType,
            $this->getMissingRequiredDocumentTypes()
        ));
    }

    /**
     * Mapa tipova dokumenata na nazive prema tipu prijave (Odluka, čl. 13).
     *
     * @return array<string, string>
     */
    public function getDocumentLabelsMap(): array
    {
        $isPreduzetnica = in_array($this->applicant_type, ['preduzetnica', 'fizicko_lice'], true);
        $isDooOstalo = in_array($this->applicant_type, ['doo', 'ostalo'], true);
        $isZapocinjanje = $this->business_stage === 'započinjanje';
        $isRazvoj = $this->business_stage === 'razvoj';

        $documentLabels = [];
        $documentLabels['potvrda_zavod_nezaposleni'] = self::getZavodNezaposleniDocumentLabel();
        $documentLabels['licna_karta'] = ($isDooOstalo && $isRazvoj)
            ? 'Ovjerenu kopiju lične karte nosioca biznisa (osnivačica ili jedna od osnivača i izvršna direktorica)'
            : (($isDooOstalo && $isZapocinjanje) ? 'Ovjerenu kopiju lične karte' : 'Ovjerena kopija lične karte');
        $documentLabels['crps_resenje'] = 'Rješenje o upisu u CRPS' . (($isPreduzetnica && $isZapocinjanje) ? ' (ukoliko ima registrovanu djelatnost)' : (($isDooOstalo && $isZapocinjanje) ? ' (ukoliko ima registrovanu djelatnost)' : ''));
        if ($isPreduzetnica && $isZapocinjanje) {
            $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
        } elseif ($isDooOstalo && ($isZapocinjanje || $isRazvoj)) {
            $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Poreske uprave' . ($isZapocinjanje ? ' (ukoliko ima registrovanu djelatnost)' : '');
        } elseif ($isPreduzetnica && $isRazvoj) {
            $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Poreske uprave';
        } else {
            $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Uprave prihoda i carina';
        }
        if ($isPreduzetnica && $isZapocinjanje) {
            $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
        } elseif ($isPreduzetnica && $isRazvoj) {
            $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
        } elseif ($isDooOstalo && $isZapocinjanje) {
            $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
        } elseif ($isDooOstalo && $isRazvoj) {
            $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
        } else {
            $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV' . ($isRazvoj ? ' (ako je obveznik PDV-a)' : '');
        }
        $documentLabels['statut'] = ($isDooOstalo && $isZapocinjanje) ? 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)' : 'Važeći Statut društva';
        $documentLabels['karton_potpisa'] = ($isDooOstalo && $isZapocinjanje) ? 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)' : 'Važeći karton deponovanih potpisa';
        $documentLabels['potvrda_neosudjivanost'] = ($this->applicant_type === 'preduzetnica' && $isRazvoj)
            ? 'Potvrda da se ne vodi krivični postupak na ime preduzetnice izdatu od Osnovnog suda'
            : ($isPreduzetnica
                ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda'
                : (($isDooOstalo && $isZapocinjanje)
                    ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda'
                    : (($isDooOstalo && $isRazvoj)
                        ? 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda'
                        : 'Potvrda o neosuđivanosti')));
        if ($this->applicant_type === 'preduzetnica' && $isRazvoj) {
            $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
        } elseif ($isPreduzetnica && ($isZapocinjanje || $isRazvoj)) {
            $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
        } elseif ($isDooOstalo && $isRazvoj) {
            $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
        } elseif ($isDooOstalo && $isZapocinjanje) {
            $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
        } else {
            $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
        }
        if ($this->applicant_type === 'preduzetnica' && $isRazvoj) {
            $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
        } elseif ($isPreduzetnica) {
            $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice';
        } elseif ($isDooOstalo && $isRazvoj) {
            $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva';
        } elseif ($isDooOstalo && $isZapocinjanje) {
            $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice)';
        } else {
            $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
        }
        $documentLabels['potvrda_upc_porezi'] = ($this->applicant_type === 'preduzetnica' && $isRazvoj)
            ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice'
            : (($isPreduzetnica && $isRazvoj)
                ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana na ime preduzetnika'
                : (($isDooOstalo && $isRazvoj)
                    ? 'Potvrdu Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva'
                    : 'Potvrda Uprave za javne prihode o urednom izmirivanju poreza'));
        $documentLabels['ioppd_obrazac'] = ($this->applicant_type === 'preduzetnica' && $isRazvoj)
            ? 'Odgovarajući obrazac ovjeren od strane Poreske uprave za poslijednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od strane Poreske uprave da preduzetnica nema zaposlenih'
            : (($isPreduzetnica && $isRazvoj)
                ? 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od Poreske uprave'
                : (($isDooOstalo && $isRazvoj)
                    ? 'Odgovarajući obrazac za poslijednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od strane Poreske uprave, kao dokaz o broju zaposlenih (IOPPD Obrazac)'
                    : 'Obrazac IOPPD'));
        $documentLabels['godisnji_racuni'] = $isDooOstalo
            ? 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu. Napomena: U slučaju da preduzetnica/društvo ne vodi analitiku kupaca tj. posluje isključivo sa fizičkim licima i naplata se vrši odmah putem registar kase, preduzetnica/društvo ima obavezu dostaviti periodični izvještaj sa registar kase'
            : 'Godišnji računi';
        $documentLabels['izvjestaj_registar_kase'] = 'Izvještaj sa registra kase';
        $documentLabels['izvjestaj_realizacija'] = 'Izvještaj o realizaciji';
        $documentLabels['finansijski_izvjestaj'] = 'Finansijski izvještaj';
        $documentLabels['dokaz_ziro_racun'] = ($this->applicant_type === 'preduzetnica' && $isRazvoj)
            ? 'Dokaz o broju poslovnog žiro računa preduzetnice'
            : (($this->applicant_type === 'preduzetnica' && $isZapocinjanje)
                ? 'Dokaz o broju poslovnog žiro računa preduzetnice (ukoliko ima registrovanu djelatnost)'
                : 'Dokaz o broju poslovnog žiro računa preduzetnice');
        $documentLabels['predracuni_nabavka'] = $isDooOstalo ? 'Predračune za planiranu nabavku' : 'Predračuni za planiranu nabavku';
        $documentLabels['ostalo'] = 'Ostalo';

        return $documentLabels;
    }

    private static function insertZavodNezaposleniDocument(array $documents, string $applicantType, ?string $businessStage): array
    {
        if (!$businessStage
            || !in_array($businessStage, ['započinjanje', 'razvoj'], true)
            || !in_array($applicantType, ['preduzetnica', 'doo', 'ostalo', 'fizicko_lice'], true)
            || count($documents) === 0) {
            return $documents;
        }

        if (in_array(self::DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI, $documents, true)) {
            return $documents;
        }

        $insertBefore = array_search('predracuni_nabavka', $documents, true);
        if ($insertBefore === false) {
            $insertBefore = count($documents) - 1;
        }

        array_splice($documents, $insertBefore, 0, [self::DOCUMENT_POTVRDA_ZAVOD_NEZAPOSLENI]);

        return $documents;
    }

    /**
     * Vraća listu obaveznih dokumenata prema tipu prijave
     * Prema Odluci, član 12
     * 
     * VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti
     *        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik)
     */
    public function getRequiredDocuments(): array
    {
        $documents = [];
        $isRegistered = $this->is_registered ?? false;
        
        // Preduzetnice koje započinju biznis (započinjanje)
        if ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'crps_resenje',      // ukoliko ima registrovanu djelatnost
                'pib_resenje',       // ukoliko ima registrovanu djelatnost
                'pdv_resenje',       // ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a, ili potvrda da nije PDV obveznik
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',  // ne starije od 30 dana, o urednom izmirivanju poreza po osnovu prireza, članskog doprinosa, lokalnih taksi i naknada
                'uvjerenje_opstina_nepokretnost',  // ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost
                'dokaz_ziro_racun',  // ukoliko ima registrovanu djelatnost
                'predracuni_nabavka',
            ];
            
            // Dokumenti vezani za registraciju samo ako ima registrovanu djelatnost (crps, pib, pdv, žiro račun)
            // U listi su svi navedeni, a obaveznost se provjerava u evaluaciji prema napomenama
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']));
            }
        }
        // Preduzetnice koje planiraju razvoj poslovanja (razvoj) – prema novoj Odluci
        elseif ($this->applicant_type === 'preduzetnica' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',  // ako je obveznik PDV-a ili potvrda da nije PDV obveznik
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'dokaz_ziro_racun',
                'predracuni_nabavka',
            ];
        }
        // Društva (DOO) koja započinju biznis (započinjanje)
        // NAPOMENA: Za DOO više se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($this->applicant_type === 'doo' && $this->business_stage === 'započinjanje') {
            $documents = [
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
            ];
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']));
            }
        }
        // Društva (DOO) koja planiraju razvoj poslovanja (razvoj)
        // NAPOMENA: Za DOO više se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($this->applicant_type === 'doo' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'godisnji_racuni',
                'izvjestaj_registar_kase',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'predracuni_nabavka',
            ];
        }
        // Ostalo (druga društva) - isti dokumenti kao DOO
        // NAPOMENA: Za ova društva takođe se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($this->applicant_type === 'ostalo' && $this->business_stage === 'započinjanje') {
            $documents = [
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
            ];
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']));
            }
        } elseif ($this->applicant_type === 'ostalo' && $this->business_stage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'godisnji_racuni',
                'izvjestaj_registar_kase',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'predracuni_nabavka',
            ];
        }
        // Fizičko lice BEZ registrovane djelatnosti
        // Ako je korisnik registrovan kao "Fizičko lice (Rezident)", tretira se kao preduzetnica
        // na osnovu business_stage (započinjanje ili razvoj)
        elseif ($this->applicant_type === 'fizicko_lice') {
            // Fizičko lice rezident - prijava kao Preduzetnica koja započinje/razvija - ista lista kao preduzetnica
            if ($this->business_stage) {
                if ($this->business_stage === 'započinjanje') {
$documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'dokaz_ziro_racun',
                'predracuni_nabavka',
            ];
                    if (!$isRegistered) {
                        $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']));
                    }
                } elseif ($this->business_stage === 'razvoj') {
                    // Preduzetnica/Fizičko lice koja planira razvoj – prema Odluci (bez nepokretnosti)
                    $documents = [
                        'licna_karta',
                        'crps_resenje',
                        'pib_resenje',
                        'pdv_resenje',
                        'potvrda_neosudjivanost',
                        'uvjerenje_opstina_porezi',
                        'potvrda_upc_porezi',
                        'ioppd_obrazac',
                        'predracuni_nabavka',
                    ];
                }
            } else {
                $documents = [
                    'licna_karta',
                ];
            }
        }

        $documents = self::insertZavodNezaposleniDocument($documents, $this->applicant_type, $this->business_stage);

        // Ako je prethodno dobijala podršku, dodaj izvještaj (ne za započinjanje: ni preduzetnica/fizičko lice, ni DOO/ostalo; ne za DOO/ostalo razvoj)
        $isZapocinjanjePreduzetnik = ($this->applicant_type === 'preduzetnica' || $this->applicant_type === 'fizicko_lice') && $this->business_stage === 'započinjanje';
        $isZapocinjanjeDooOstalo = ($this->applicant_type === 'doo' || $this->applicant_type === 'ostalo') && $this->business_stage === 'započinjanje';
        $isRazvojDooOstalo = ($this->applicant_type === 'doo' || $this->applicant_type === 'ostalo') && $this->business_stage === 'razvoj';
        if ($this->previous_support_declaration && !$isZapocinjanjePreduzetnik && !$isZapocinjanjeDooOstalo && !$isRazvojDooOstalo) {
            $documents[] = 'izvjestaj_realizacija';
            $documents[] = 'finansijski_izvjestaj';
        }

        return array_values($documents); // Reindex array
    }

    /**
     * Statička metoda za generisanje liste dokumenata na osnovu tipa prijave i faze biznisa
     * Koristi se za prikaz liste dokumenata prije nego što korisnik krene u prijavu
     */
    public static function getRequiredDocumentsForType(string $applicantType, string $businessStage, bool $isRegistered = false): array
    {
        $documents = [];
        
        // Preduzetnice koje započinju biznis (započinjanje)
        if ($applicantType === 'preduzetnica' && $businessStage === 'započinjanje') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'dokaz_ziro_racun',
                'predracuni_nabavka',
            ];
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']));
            }
        }
        // Preduzetnice koje planiraju razvoj poslovanja (razvoj) – prema novoj Odluci
        elseif ($applicantType === 'preduzetnica' && $businessStage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'dokaz_ziro_racun',
                'predracuni_nabavka',
            ];
        }
        // Fizičko lice – razvoj (ista lista kao preduzetnica razvoj)
        elseif ($applicantType === 'fizicko_lice' && $businessStage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'predracuni_nabavka',
            ];
        }
        // Društva (DOO) koja započinju biznis (započinjanje)
        // NAPOMENA: Za DOO više se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($applicantType === 'doo' && $businessStage === 'započinjanje') {
            $documents = [
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
            ];
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']));
            }
        }
        // Društva (DOO) koja planiraju razvoj poslovanja (razvoj)
        // NAPOMENA: Za DOO više se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($applicantType === 'doo' && $businessStage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'godisnji_racuni',
                'izvjestaj_registar_kase',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'predracuni_nabavka',
            ];
        }
        // Ostalo (druga društva) - isti dokumenti kao DOO
        // NAPOMENA: I ovdje se NE traži "Dokaz o broju poslovnog žiro računa društva"
        elseif ($applicantType === 'ostalo' && $businessStage === 'započinjanje') {
            $documents = [
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
            ];
            if (!$isRegistered) {
                $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']));
            }
        } elseif ($applicantType === 'ostalo' && $businessStage === 'razvoj') {
            $documents = [
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'godisnji_racuni',
                'izvjestaj_registar_kase',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'predracuni_nabavka',
            ];
        }
        // Fizičko lice (rezident) - prijava kao Preduzetnica koja započinje biznis - ista lista kao preduzetnica
        elseif ($applicantType === 'fizicko_lice' && $businessStage) {
            if ($businessStage === 'započinjanje') {
                $documents = [
                    'licna_karta',
                    'crps_resenje',
                    'pib_resenje',
                    'pdv_resenje',
                    'potvrda_neosudjivanost',
                    'uvjerenje_opstina_porezi',
                    'uvjerenje_opstina_nepokretnost',
                    'dokaz_ziro_racun',
                    'predracuni_nabavka',
                ];
                if (!$isRegistered) {
                    $documents = array_values(array_diff($documents, ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']));
                }
            } elseif ($businessStage === 'razvoj') {
                $documents = [
                    'licna_karta',
                    'crps_resenje',
                    'pib_resenje',
                    'pdv_resenje',
                    'potvrda_neosudjivanost',
                    'uvjerenje_opstina_porezi',
                    'uvjerenje_opstina_nepokretnost',
                    'potvrda_upc_porezi',
                    'ioppd_obrazac',
                ];
            }
        }

        $documents = self::insertZavodNezaposleniDocument($documents, $applicantType, $businessStage);

        return array_values($documents);
    }

    /**
     * Tekst podataka iz obrasca 1a/1b za e-mail spiska kandidata (jedna prijava).
     */
    public function getObrazacTextForEmail(): string
    {
        $labels = [
            'applicant_type' => 'Tip podnosioca',
            'business_plan_name' => 'Naziv biznis plana',
            'business_stage' => 'Faza (započinjanje/razvoj)',
            'business_area' => 'Područje djelatnosti',
            'founder_name' => 'Ime i prezime osnivača',
            'director_name' => 'Ime i prezime direktora',
            'company_seat' => 'Sjedište',
            'physical_person_name' => 'Ime i prezime (fizičko lice)',
            'physical_person_jmbg' => 'JMBG (fizičko lice)',
            'physical_person_phone' => 'Telefon (fizičko lice)',
            'physical_person_email' => 'E-mail (fizičko lice)',
            'requested_amount' => 'Traženi iznos (€)',
            'total_budget_needed' => 'Ukupna vrijednost biznis plana (€)',
            'bank_account' => 'Žiro račun',
            'vat_number' => 'PDV broj',
            'pib' => 'PIB',
            'crps_number' => 'CRPS broj',
            'website' => 'Web stranica',
            'registration_form' => 'Oblik registracije',
        ];
        $applicantTypeLabels = [
            'fizicko_lice' => 'Fizičko lice (nema registrovanu djelatnost)',
            'preduzetnica' => 'Preduzetnica',
            'doo' => 'DOO',
            'ostalo' => 'Ostalo',
        ];
        $stageLabels = ['započinjanje' => 'Započinjanje', 'razvoj' => 'Razvoj'];

        $lines = [];
        foreach ($labels as $key => $label) {
            $value = $this->getAttribute($key);
            if ($value === null || $value === '') {
                continue;
            }
            if ($key === 'applicant_type') {
                $value = $applicantTypeLabels[$value] ?? $value;
            }
            if ($key === 'business_stage') {
                $value = $stageLabels[$value] ?? $value;
            }
            if (in_array($key, ['requested_amount', 'total_budget_needed']) && is_numeric($value)) {
                $value = number_format((float) $value, 2, ',', '.');
            }
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }
}