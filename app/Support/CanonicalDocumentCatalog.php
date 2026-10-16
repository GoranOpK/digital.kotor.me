<?php

namespace App\Support;

use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Services\Competitions\Profile\CanonicalMmspPolicy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Canonical MMSP prateća dokumentacija (Odluka čl.14), wired as CanonicalMmspPolicy::DOCUMENT_CATALOG_HOOK.
 *
 * Two separate lists: preduzetnik (P1a, 13 stavki) and društvo (P1b, 15 stavki). Stavke 1–2
 * (Obrazac P1 and P2) are filled electronically; the rest are PDF uploads keyed to existing
 * application_documents.document_type values. Anything outside the list for the chosen legal
 * form — including a missing legal form — fails closed.
 */
final class CanonicalDocumentCatalog
{
    public const HOOK = CanonicalMmspPolicy::DOCUMENT_CATALOG_HOOK;

    public const VALIDITY_DAYS = 30;

    public const SOURCE_FORM = 'form';

    public const SOURCE_UPLOAD = 'upload';

    public const REQUIRED = 'required';

    public const CONDITIONAL = 'conditional';

    public const FORM_P1 = 'obrazac_p1';

    public const FORM_P2 = 'obrazac_p2';

    public const TYPE_VAT = 'pdv_resenje';

    public const TYPE_IOPPD = 'ioppd_obrazac';

    public const TYPE_ANNUAL_ACCOUNTS = 'godisnji_racuni';

    public const TYPE_CASH_REGISTER = 'izvjestaj_registar_kase';

    public const VARIANT_VAT_REGISTERED = 'pdv_rjesenje';

    public const VARIANT_VAT_NOT_REGISTERED = 'potvrda_nije_pdv_obveznik';

    public const VARIANT_IOPPD = 'ioppd_obrazac';

    public const VARIANT_NO_EMPLOYEES = 'potvrda_nema_zaposlenih';

    public const VARIANT_WITH_CUSTOMER_ANALYTICS = 'sa_analitikom_kupaca';

    public const VARIANT_WITHOUT_CUSTOMER_ANALYTICS = 'bez_analitike_kupaca';

    private const VAT_VARIANTS = [
        self::VARIANT_VAT_REGISTERED => 'Rješenje o registraciji za PDV (PDV obveznik)',
        self::VARIANT_VAT_NOT_REGISTERED => 'Potvrda da nije PDV obveznik',
    ];

    /**
     * @param  int|null  $callYear  Year of the Javni poziv (competitions.year); basis of the čl.14 "tekuća godina" exception.
     */
    private function __construct(public readonly ?string $legalForm, public readonly ?int $callYear = null)
    {
    }

    public static function forLegalForm(?string $legalForm, ?int $callYear = null): self
    {
        return new self(CanonicalLegalForm::isValid($legalForm) ? $legalForm : null, $callYear);
    }

    public static function forApplication(Application $application): self
    {
        $application->loadMissing('competition');
        $year = $application->competition?->year;

        return self::forLegalForm(
            CanonicalApplicantForm::query()->where('application_id', $application->id)->value('legal_form'),
            is_numeric($year) && (int) $year > 0 ? (int) $year : null,
        );
    }

    /**
     * Full čl.14 list for the legal form, in legal order.
     *
     * @return list<array{item: int, key: string, source: string, label: string, requirement: string, validity_days: ?int, variants: array<string, string>, condition: ?string, supplement: ?array{key: string, label: string, condition: string}}>
     */
    public function items(): array
    {
        return match ($this->legalForm) {
            CanonicalLegalForm::PREDUZETNIK => $this->entrepreneurItems(),
            CanonicalLegalForm::DRUSTVO => $this->companyItems(),
            default => [],
        };
    }

    /**
     * Document types the applicant may upload for this legal form (incl. the cash-register supplement).
     *
     * @return list<string>
     */
    public function uploadTypes(): array
    {
        $types = [];
        foreach ($this->items() as $item) {
            if ($item['source'] !== self::SOURCE_UPLOAD) {
                continue;
            }
            $types[] = $item['key'];
            if ($item['supplement'] !== null) {
                $types[] = $item['supplement']['key'];
            }
        }

        return $types;
    }

    public function allowsUploadType(?string $documentType): bool
    {
        return is_string($documentType) && in_array($documentType, $this->uploadTypes(), true);
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->items() as $item) {
            if ($item['source'] !== self::SOURCE_UPLOAD) {
                continue;
            }
            $labels[$item['key']] = $item['label'];
            if ($item['supplement'] !== null) {
                $labels[$item['supplement']['key']] = $item['supplement']['label'];
            }
        }

        return $labels;
    }

    public function label(string $documentType): string
    {
        return $this->labels()[$documentType] ?? $documentType;
    }

    /**
     * @return array<string, string>
     */
    public function variantsFor(string $documentType): array
    {
        foreach ($this->items() as $item) {
            if ($item['key'] === $documentType && $item['source'] === self::SOURCE_UPLOAD) {
                return $item['variants'];
            }
        }

        return [];
    }

    public function requiresIssueDate(string $documentType): bool
    {
        foreach ($this->items() as $item) {
            if ($item['key'] === $documentType && $item['source'] === self::SOURCE_UPLOAD) {
                return $item['validity_days'] !== null;
            }
        }

        return false;
    }

    /**
     * Upload types required for FINAL given the P1 facts and the variants already declared.
     *
     * @param  array<string, CanonicalDocumentEvidence>  $evidenceByType
     * @return list<string>
     */
    public function requiredUploadTypes(?CanonicalApplicantForm $form, array $evidenceByType, CarbonInterface $at): array
    {
        $required = [];
        foreach ($this->items() as $item) {
            if ($item['source'] !== self::SOURCE_UPLOAD) {
                continue;
            }

            if ($item['key'] === self::TYPE_ANNUAL_ACCOUNTS && $this->foundedInCurrentYear($form)) {
                continue;
            }

            $required[] = $item['key'];

            if ($item['supplement'] !== null
                && ($evidenceByType[$item['key']]->variant ?? null) === self::VARIANT_WITHOUT_CUSTOMER_ANALYTICS) {
                $required[] = $item['supplement']['key'];
            }
        }

        return $required;
    }

    /**
     * čl.14 izuzetak: društvo registrovano u tekućoj godini ne dostavlja godišnje račune.
     * "Tekuća godina" is the year of the Javni poziv (čl.5), not the server date of the check;
     * without a call year the exception does not apply (fail closed).
     */
    public function foundedInCurrentYear(?CanonicalApplicantForm $form): bool
    {
        return $this->legalForm === CanonicalLegalForm::DRUSTVO
            && $this->callYear !== null
            && $form?->registration_date !== null
            && (int) $form->registration_date->format('Y') === $this->callYear;
    }

    /**
     * Document-side FINAL issues (missing, variant, 30-day validity, out-of-catalog uploads).
     *
     * @return list<string>
     */
    public function finalIssues(Application $application, CarbonInterface $at): array
    {
        if ($this->legalForm === null) {
            return ['Pravna forma podnosioca (preduzetnik ili društvo) nije izabrana.'];
        }

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        $documents = $application->documents()->orderBy('id')->get(['id', 'document_type']);
        $evidence = CanonicalDocumentEvidence::query()
            ->where('application_id', $application->id)
            ->get()
            ->keyBy('application_document_id');

        $issues = [];
        $evidenceByType = [];
        foreach ($documents as $document) {
            if (! $this->allowsUploadType($document->document_type)) {
                $issues[] = 'Prilog „'.$document->document_type.'“ nije dio liste dokumentacije za izabranu pravnu formu.';

                continue;
            }
            $row = $evidence->get($document->id);
            if ($row !== null) {
                $evidenceByType[$document->document_type] = $row;
            }
        }

        $uploadedTypes = $documents->pluck('document_type')->all();
        foreach ($this->requiredUploadTypes($form, $evidenceByType, $at) as $type) {
            if (! in_array($type, $uploadedTypes, true)) {
                $issues[] = 'Nedostaje prilog: '.$this->label($type).'.';
            }
        }

        foreach ($documents as $document) {
            $type = $document->document_type;
            if (! $this->allowsUploadType($type)) {
                continue;
            }
            $row = $evidence->get($document->id);

            $variants = $this->variantsFor($type);
            if ($variants !== [] && ! array_key_exists((string) $row?->variant, $variants)) {
                $issues[] = 'Za prilog „'.$this->label($type).'“ nije označeno koji je dokument priložen.';
            }

            if ($this->requiresIssueDate($type)) {
                $issue = $this->validityIssue($row?->issued_on, $at);
                if ($issue !== null) {
                    $issues[] = 'Prilog „'.$this->label($type).'“: '.$issue;
                }
            }
        }

        if ($this->legalForm === CanonicalLegalForm::PREDUZETNIK && $form?->employee_count !== null) {
            $ioppd = $evidenceByType[self::TYPE_IOPPD] ?? null;
            $expected = (int) $form->employee_count > 0 ? self::VARIANT_IOPPD : self::VARIANT_NO_EMPLOYEES;
            if ($ioppd !== null && $ioppd->variant !== null && $ioppd->variant !== $expected) {
                $issues[] = (int) $form->employee_count > 0
                    ? 'Prema broju zaposlenih iz Obrasca P1a prilaže se IOPPD obrazac, ne potvrda da nema zaposlenih.'
                    : 'Prema Obrascu P1a preduzetnik nema zaposlenih: prilaže se potvrda Poreske uprave da nema zaposlenih.';
            }
        }

        return $issues;
    }

    /**
     * Null when the issue date satisfies the 30-day rule at $at.
     */
    public function validityIssue(mixed $issuedOn, CarbonInterface $at): ?string
    {
        if ($issuedOn === null || $issuedOn === '') {
            return 'nije unesen datum izdavanja (dokument ne smije biti stariji od '.self::VALIDITY_DAYS.' dana).';
        }

        $issued = CarbonImmutable::parse($issuedOn)->startOfDay();
        $day = CarbonImmutable::parse($at)->startOfDay();

        if ($issued->greaterThan($day)) {
            return 'datum izdavanja ne može biti u budućnosti.';
        }
        if ($issued->lessThan($day->subDays(self::VALIDITY_DAYS))) {
            return 'dokument je stariji od '.self::VALIDITY_DAYS.' dana.';
        }

        return null;
    }

    /**
     * @return list<array{item: int, key: string, source: string, label: string, requirement: string, validity_days: ?int, variants: array<string, string>, condition: ?string, supplement: ?array{key: string, label: string, condition: string}}>
     */
    private function entrepreneurItems(): array
    {
        return [
            $this->form(1, self::FORM_P1, 'Prijava — Obrazac P1a (popunjava se elektronski)'),
            $this->form(2, self::FORM_P2, 'Plan ulaganja — Obrazac P2 (popunjava se elektronski)'),
            $this->upload(3, 'licna_karta', 'Ovjerena kopija lične karte'),
            $this->upload(4, 'crps_resenje', 'Rješenje o upisu u Centralni registar privrednih subjekata (CRPS)'),
            $this->upload(5, 'pib_resenje', 'Rješenje o registraciji PJ Poreske uprave'),
            $this->upload(6, self::TYPE_VAT, 'Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik', variants: self::VAT_VARIANTS),
            $this->upload(7, 'potvrda_neosudjivanost', 'Potvrda Osnovnog suda da se ne vodi krivični postupak na ime preduzetnika'),
            $this->upload(8, 'uvjerenje_opstina_porezi', 'Uvjerenje organa lokalne uprave o urednom izmirivanju poreza (prirez porezu, članski doprinos, lokalne komunalne takse i naknade), ne starije od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(9, 'uvjerenje_opstina_nepokretnost', 'Uvjerenje organa lokalne uprave o urednom izmirivanju poreza na nepokretnost, ne starije od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(10, 'potvrda_upc_porezi', 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa, ne starija od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(
                11,
                self::TYPE_IOPPD,
                'IOPPD obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene ili potvrda Poreske uprave da preduzetnik nema zaposlenih',
                requirement: self::CONDITIONAL,
                variants: [
                    self::VARIANT_IOPPD => 'IOPPD obrazac (preduzetnik ima zaposlene)',
                    self::VARIANT_NO_EMPLOYEES => 'Potvrda Poreske uprave da preduzetnik nema zaposlenih',
                ],
                condition: 'Vrsta dokumenta zavisi od broja zaposlenih iz Obrasca P1a.',
            ),
            $this->upload(12, 'dokaz_ziro_racun', 'Dokaz o broju poslovnog žiro računa'),
            $this->upload(13, 'predracuni_nabavka', 'Predračuni za planiranu nabavku'),
        ];
    }

    /**
     * @return list<array{item: int, key: string, source: string, label: string, requirement: string, validity_days: ?int, variants: array<string, string>, condition: ?string, supplement: ?array{key: string, label: string, condition: string}}>
     */
    private function companyItems(): array
    {
        return [
            $this->form(1, self::FORM_P1, 'Prijava — Obrazac P1b (popunjava se elektronski)'),
            $this->form(2, self::FORM_P2, 'Plan ulaganja — Obrazac P2 (popunjava se elektronski)'),
            $this->upload(3, 'licna_karta', 'Ovjerena kopija lične karte ovlašćenog lica'),
            $this->upload(4, 'crps_resenje', 'Rješenje o upisu u Centralni registar privrednih subjekata (CRPS)'),
            $this->upload(5, 'pib_resenje', 'Rješenje o registraciji PJ Poreske uprave'),
            $this->upload(6, self::TYPE_VAT, 'Rješenje o registraciji za PDV ili potvrda da društvo nije PDV obveznik', variants: self::VAT_VARIANTS),
            $this->upload(7, 'statut', 'Važeći Statut društva'),
            $this->upload(8, 'karton_potpisa', 'Važeći karton deponovanih potpisa'),
            $this->upload(
                9,
                self::TYPE_ANNUAL_ACCOUNTS,
                'Komplet obrazaca za godišnje račune za prethodnu godinu (bilans stanja, bilans uspjeha, analitika kupaca i analitika dobavljača)',
                requirement: self::CONDITIONAL,
                variants: [
                    self::VARIANT_WITH_CUSTOMER_ANALYTICS => 'Komplet sa analitikom kupaca',
                    self::VARIANT_WITHOUT_CUSTOMER_ANALYTICS => 'Društvo ne vodi analitiku kupaca (posluje isključivo sa fizičkim licima uz naplatu putem registar kase)',
                ],
                condition: 'Ne dostavlja društvo registrovano u tekućoj godini (datum osnivanja iz Obrasca P1b).',
                supplement: [
                    'key' => self::TYPE_CASH_REGISTER,
                    'label' => 'Periodični izvještaj sa registra kase',
                    'condition' => 'Obavezan ako društvo ne vodi analitiku kupaca.',
                ],
            ),
            $this->upload(10, 'potvrda_neosudjivanost', 'Potvrda Osnovnog suda da se ne vodi krivični postupak na ime društva, izvršnog direktora i osnivača'),
            $this->upload(11, 'uvjerenje_opstina_porezi', 'Uvjerenje organa lokalne uprave o urednom izmirivanju poreskih obaveza (prirez porezu, članski doprinos, lokalne komunalne takse i naknade), ne starije od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(12, 'uvjerenje_opstina_nepokretnost', 'Uvjerenje organa lokalne uprave o urednom izmirivanju poreza na nepokretnost, ne starije od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(13, 'potvrda_upc_porezi', 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa, ne starija od 30 dana', validityDays: self::VALIDITY_DAYS),
            $this->upload(14, self::TYPE_IOPPD, 'IOPPD obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene'),
            $this->upload(15, 'predracuni_nabavka', 'Predračuni za planiranu nabavku'),
        ];
    }

    /**
     * @return array{item: int, key: string, source: string, label: string, requirement: string, validity_days: ?int, variants: array<string, string>, condition: ?string, supplement: null}
     */
    private function form(int $item, string $key, string $label): array
    {
        return [
            'item' => $item,
            'key' => $key,
            'source' => self::SOURCE_FORM,
            'label' => $label,
            'requirement' => self::REQUIRED,
            'validity_days' => null,
            'variants' => [],
            'condition' => null,
            'supplement' => null,
        ];
    }

    /**
     * @param  array<string, string>  $variants
     * @param  array{key: string, label: string, condition: string}|null  $supplement
     * @return array{item: int, key: string, source: string, label: string, requirement: string, validity_days: ?int, variants: array<string, string>, condition: ?string, supplement: ?array{key: string, label: string, condition: string}}
     */
    private function upload(
        int $item,
        string $key,
        string $label,
        string $requirement = self::REQUIRED,
        ?int $validityDays = null,
        array $variants = [],
        ?string $condition = null,
        ?array $supplement = null,
    ): array {
        return [
            'item' => $item,
            'key' => $key,
            'source' => self::SOURCE_UPLOAD,
            'label' => $label,
            'requirement' => $requirement,
            'validity_days' => $validityDays,
            'variants' => $variants,
            'condition' => $condition,
            'supplement' => $supplement,
        ];
    }
}
