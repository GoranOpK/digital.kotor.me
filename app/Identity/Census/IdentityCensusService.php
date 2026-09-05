<?php

namespace App\Identity\Census;

use App\Identity\Validation\JmbIdentifierValidator;
use App\Identity\Validation\PibIdentifierValidator;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;

/**
 * Read-only deterministic identity census. Queries and classifies only.
 */
final class IdentityCensusService
{
    private const CHUNK_SIZE = 100;

    private const OTHER_TYPOGRAPHIC = 'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)';

    public function __construct(
        private readonly JmbIdentifierValidator $jmbValidator = new JmbIdentifierValidator,
        private readonly PibIdentifierValidator $pibValidator = new PibIdentifierValidator,
    ) {
    }

    public function run(): IdentityCensusReport
    {
        $started = now()->toIso8601String();
        $rows = [];
        $maxId = 0;
        $jmbFingerprints = [];

        User::query()
            ->with('role')
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use (&$rows, &$maxId, &$jmbFingerprints): void {
                foreach ($users as $user) {
                    $trimmedJmb = $this->outerTrim($user->jmb);
                    if ($trimmedJmb !== null) {
                        $jmbFingerprints[hash('sha256', $trimmedJmb)][] = (int) $user->id;
                    }
                    $rows[] = $this->classify($user);
                    $maxId = max($maxId, (int) $user->id);
                }
            });

        return new IdentityCensusReport(
            $rows,
            [
                'started_at' => $started,
                'ended_at' => now()->toIso8601String(),
                'environment' => (string) app()->environment(),
                'commit_hash' => $this->commitHash(),
                'spec' => 'DK-TS-002 D15 Step 3',
                'row_count' => count($rows),
                'max_user_id' => $maxId === 0 ? null : $maxId,
            ],
            $this->aggregates($rows, $jmbFingerprints),
        );
    }

    public function classify(User $user): IdentityCensusRow
    {
        $roleName = $user->role?->name;
        $isStaff = $user->isStaffAccount();
        $legacyType = is_string($user->user_type) && $user->user_type !== '' ? $user->user_type : null;

        $reasons = [];
        $d10 = [];
        $d14 = [];
        $fields = $this->emptyFields();

        if ($isStaff) {
            $reasons[] = 'staff_account';
            if ($legacyType !== null) {
                $reasons[] = 'leftover_legacy_user_type';
            }

            return $this->row(
                $user,
                $roleName,
                true,
                $legacyType,
                IdentityCensusRow::NON_SUBJECT_ACCOUNT,
                null,
                null,
                false,
                $fields,
                $reasons,
                $d10,
                $d14,
            );
        }

        if ($this->isUnsupportedType($legacyType)) {
            $reasons[] = 'unsupported_user_type';
            $this->markUnsupportedFields($fields);

            return $this->row(
                $user,
                $roleName,
                false,
                $legacyType,
                IdentityCensusRow::UNSUPPORTED,
                null,
                null,
                false,
                $fields,
                $reasons,
                $d10,
                $d14,
            );
        }

        if ($legacyType === null) {
            $reasons[] = 'subject_unresolved';
            $this->markUnsupportedFields($fields);

            return $this->row(
                $user,
                $roleName,
                false,
                null,
                IdentityCensusRow::AMBIGUOUS_MAPPING,
                null,
                null,
                false,
                $fields,
                $reasons,
                $d10,
                $d14,
            );
        }

        if ($legacyType === UserType::LEGACY_ASSOCIATION_BUNDLE) {
            $this->evaluateLegalEntity($user, $fields, $reasons, $d10, $d14, legalForm: null, formAmbiguous: true);

            return $this->row(
                $user,
                $roleName,
                false,
                $legacyType,
                IdentityCensusRow::AMBIGUOUS_MAPPING,
                PlatformIdentity::SUBJECT_LEGAL_ENTITY,
                null,
                false,
                $fields,
                $reasons,
                $d10,
                $d14,
            );
        }

        $mapped = $this->mapDeterministic($legacyType);
        if ($mapped === null) {
            $reasons[] = 'unsupported_user_type';
            $this->markUnsupportedFields($fields);

            return $this->row(
                $user,
                $roleName,
                false,
                $legacyType,
                IdentityCensusRow::UNSUPPORTED,
                null,
                null,
                false,
                $fields,
                $reasons,
                $d10,
                $d14,
            );
        }

        [$subject, $form, $entrepreneur] = $mapped;

        $idDocumentType = null;
        if ($subject === PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
            $idDocumentType = $this->evaluatePhysicalPerson($user, $fields, $reasons, $d10, $d14, $entrepreneur);
        } elseif ($subject === PlatformIdentity::SUBJECT_LEGAL_ENTITY) {
            $this->evaluateLegalEntity($user, $fields, $reasons, $d10, $d14, $form, false);
        } else {
            $this->evaluateForeignBranch($user, $fields, $reasons, $d10, $d14);
        }

        $rowStatus = $this->deriveRowStatus($reasons);

        return $this->row(
            $user,
            $roleName,
            false,
            $legacyType,
            $rowStatus,
            $subject,
            $form,
            $entrepreneur,
            $fields,
            $reasons,
            $d10,
            $d14,
            $idDocumentType,
        );
    }

    /**
     * @return array{0: string, 1: ?string, 2: bool}|null
     */
    private function mapDeterministic(string $legacyType): ?array
    {
        return match ($legacyType) {
            UserType::PHYSICAL_PERSON => [PlatformIdentity::SUBJECT_PHYSICAL_PERSON, null, false],
            UserType::ENTREPRENEUR => [PlatformIdentity::SUBJECT_PHYSICAL_PERSON, null, true],
            UserType::GENERAL_PARTNERSHIP => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_OD, false],
            UserType::LIMITED_PARTNERSHIP => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_KD, false],
            UserType::LIMITED_LIABILITY_COMPANY => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_DOO, false],
            UserType::JOINT_STOCK_COMPANY => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_AD, false],
            UserType::NGO_ASSOCIATION => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_NVO_ASSOCIATION, false],
            UserType::SPORTS_ORGANIZATION => [PlatformIdentity::SUBJECT_LEGAL_ENTITY, LegalEntityIdentity::FORM_SPORTS_ORGANIZATION, false],
            UserType::LEGACY_FOREIGN_BRANCH => [PlatformIdentity::SUBJECT_FOREIGN_BRANCH, null, false],
            default => null,
        };
    }

    private function isUnsupportedType(?string $legacyType): bool
    {
        return $legacyType === UserType::LEGACY_INSTITUTION_BUNDLE
            || $legacyType === UserType::LEGACY_OTHER_ORGANIZATIONS
            || $legacyType === self::OTHER_TYPOGRAPHIC;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     * @param  list<string>  $d14
     */
    private function evaluatePhysicalPerson(
        User $user,
        array &$fields,
        array &$reasons,
        array &$d10,
        array &$d14,
        bool $entrepreneur,
    ): ?string {
        $fields['authorized_person'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['representative'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['foreign_company_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['branch_name_in_montenegro'] = IdentityCensusFieldStatus::NOT_APPLICABLE;

        $this->textField($user->first_name, 'first_name', true, $fields, $reasons, $d10);
        $this->textField($user->last_name, 'last_name', true, $fields, $reasons, $d10);
        $this->textField($user->address, 'address', true, $fields, $reasons, $d10);
        $this->textField($user->city, 'city', true, $fields, $reasons, $d10);
        $this->textField($user->phone, 'phone', false, $fields, $reasons, $d10);

        $residency = $this->outerTrim($user->residential_status);
        if ($residency === null) {
            $fields['residential_status'] = IdentityCensusFieldStatus::MISSING;
            $this->missing('residential_status', $reasons, $d10);
        } elseif ($residency === 'resident' || $residency === 'non-resident') {
            $fields['residential_status'] = IdentityCensusFieldStatus::PRESENT_VALID;
        } else {
            $fields['residential_status'] = IdentityCensusFieldStatus::INVALID;
            $this->invalid('residential_status', $reasons, $d14);
        }

        $isResident = $residency === 'resident';
        $isNonresident = $residency === 'non-resident';

        $jmbStatus = $this->jmbStatus($user->jmb);
        $fields['jmb'] = $jmbStatus;
        $passportStatus = $this->passportStatus($user->passport_number);
        $fields['passport_number'] = $passportStatus;

        $idDocumentType = null;
        if ($isResident) {
            $fields['id_document_type'] = IdentityCensusFieldStatus::PRESENT_VALID;
            $idDocumentType = PhysicalPersonIdentity::DOCUMENT_JMB;
            $fields['residence_country_code'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($jmbStatus === IdentityCensusFieldStatus::MISSING) {
                $this->missing('jmb', $reasons, $d10);
            } elseif ($jmbStatus === IdentityCensusFieldStatus::INVALID) {
                $this->invalid('jmb', $reasons, $d14);
            }
        } elseif ($isNonresident) {
            $fields['residence_country_code'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
            $this->unavailable('residence_country_code', $reasons, $d10);

            $jmbUsable = $jmbStatus === IdentityCensusFieldStatus::PRESENT_VALID;
            $passportUsable = $passportStatus === IdentityCensusFieldStatus::PRESENT_VALID;

            if ($jmbUsable && $passportUsable) {
                $fields['id_document_type'] = IdentityCensusFieldStatus::AMBIGUOUS;
                $reasons[] = 'id_document_type_ambiguous';
            } elseif ($jmbUsable) {
                $fields['id_document_type'] = IdentityCensusFieldStatus::PRESENT_VALID;
                $idDocumentType = PhysicalPersonIdentity::DOCUMENT_JMB;
            } elseif ($passportUsable) {
                $fields['id_document_type'] = IdentityCensusFieldStatus::PRESENT_VALID;
                $idDocumentType = PhysicalPersonIdentity::DOCUMENT_PASSPORT;
            } else {
                $fields['id_document_type'] = IdentityCensusFieldStatus::MISSING;
                $this->missing('id_document_type', $reasons, $d10);
                if ($jmbStatus === IdentityCensusFieldStatus::INVALID) {
                    $this->invalid('jmb', $reasons, $d14);
                }
                if ($passportStatus === IdentityCensusFieldStatus::INVALID) {
                    $this->invalid('passport_number', $reasons, $d14);
                }
            }
        } else {
            $fields['id_document_type'] = IdentityCensusFieldStatus::MISSING;
            $fields['residence_country_code'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($jmbStatus === IdentityCensusFieldStatus::INVALID) {
                $this->invalid('jmb', $reasons, $d14);
            }
        }

        if ($entrepreneur) {
            $this->textField($user->company_name, 'entrepreneur_business_name', true, $fields, $reasons, $d10);
            $fields['legal_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            $this->pibField($user->pib, true, $fields, $reasons, $d10, $d14);
            $fields['crps'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
            $this->unavailable('crps', $reasons, $d10);
        } else {
            $fields['entrepreneur_business_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            $fields['legal_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            $pib = $this->outerTrim($user->pib);
            if ($pib === null) {
                $fields['pib'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
            } elseif ($this->pibValidator->isValid($pib)) {
                $fields['pib'] = IdentityCensusFieldStatus::PRESENT_VALID;
                $reasons[] = 'leftover_pib_on_non_entrepreneur';
            } else {
                $fields['pib'] = IdentityCensusFieldStatus::INVALID;
                $reasons[] = 'leftover_pib_on_non_entrepreneur';
            }
            $fields['crps'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        }

        return $idDocumentType;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     * @param  list<string>  $d14
     */
    private function evaluateLegalEntity(
        User $user,
        array &$fields,
        array &$reasons,
        array &$d10,
        array &$d14,
        ?string $legalForm,
        bool $formAmbiguous,
    ): void {
        $this->naPhysicalExclusive($fields);
        $fields['representative'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['entrepreneur_business_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['id_document_type'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['jmb'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['passport_number'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['residential_status'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['residence_country_code'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['first_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['last_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;

        if ($formAmbiguous) {
            $reasons[] = 'legal_form_unresolved';
        }

        $this->textField($user->company_name, 'legal_name', true, $fields, $reasons, $d10);
        $this->textField($user->address, 'address', true, $fields, $reasons, $d10);
        $this->textField($user->city, 'city', true, $fields, $reasons, $d10);
        $this->textField($user->phone, 'phone', false, $fields, $reasons, $d10);
        $this->pibField($user->pib, true, $fields, $reasons, $d10, $d14);

        $crpsRequired = in_array($legalForm, [
            LegalEntityIdentity::FORM_OD,
            LegalEntityIdentity::FORM_KD,
            LegalEntityIdentity::FORM_AD,
            LegalEntityIdentity::FORM_DOO,
        ], true);

        if ($formAmbiguous || $legalForm === LegalEntityIdentity::FORM_NVO_ASSOCIATION
            || $legalForm === LegalEntityIdentity::FORM_NVO_FOUNDATION
            || $legalForm === LegalEntityIdentity::FORM_SPORTS_ORGANIZATION) {
            $fields['crps'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        } elseif ($crpsRequired) {
            $fields['crps'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
            $this->unavailable('crps', $reasons, $d10);
        } else {
            $fields['crps'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        }

        $fields['authorized_person'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
        $this->unavailable('authorized_person', $reasons, $d10);
        $fields['foreign_company_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['branch_name_in_montenegro'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     * @param  list<string>  $d14
     */
    private function evaluateForeignBranch(
        User $user,
        array &$fields,
        array &$reasons,
        array &$d10,
        array &$d14,
    ): void {
        $this->naPhysicalExclusive($fields);
        $fields['authorized_person'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['entrepreneur_business_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['legal_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['id_document_type'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['jmb'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['passport_number'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['residential_status'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['residence_country_code'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['first_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
        $fields['last_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;

        $fields['foreign_company_name'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
        $fields['branch_name_in_montenegro'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
        $reasons[] = 'dspd_names_unresolved';
        $this->unavailable('foreign_company_name', $reasons, $d10);
        $this->unavailable('branch_name_in_montenegro', $reasons, $d10);

        $this->textField($user->address, 'address', true, $fields, $reasons, $d10);
        $this->textField($user->city, 'city', true, $fields, $reasons, $d10);
        $this->textField($user->phone, 'phone', false, $fields, $reasons, $d10);
        $this->pibField($user->pib, true, $fields, $reasons, $d10, $d14);

        $fields['crps'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
        $this->unavailable('crps', $reasons, $d10);
        $fields['representative'] = IdentityCensusFieldStatus::SOURCE_UNAVAILABLE;
        $this->unavailable('representative', $reasons, $d10);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function naPhysicalExclusive(array &$fields): void
    {
        $fields['entrepreneur_business_name'] = IdentityCensusFieldStatus::NOT_APPLICABLE;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     */
    private function textField(
        mixed $raw,
        string $key,
        bool $required,
        array &$fields,
        array &$reasons,
        array &$d10,
    ): void {
        $value = $this->outerTrim(is_string($raw) ? $raw : null);
        if ($value === null) {
            $fields[$key] = IdentityCensusFieldStatus::MISSING;
            if ($required) {
                $this->missing($key, $reasons, $d10);
            }

            return;
        }

        $fields[$key] = IdentityCensusFieldStatus::PRESENT_VALID;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     * @param  list<string>  $d14
     */
    private function pibField(
        mixed $raw,
        bool $required,
        array &$fields,
        array &$reasons,
        array &$d10,
        array &$d14,
    ): void {
        $value = $this->outerTrim(is_string($raw) ? $raw : null);
        if ($value === null) {
            $fields['pib'] = IdentityCensusFieldStatus::MISSING;
            if ($required) {
                $this->missing('pib', $reasons, $d10);
            }

            return;
        }

        if ($this->pibValidator->isValid($value)) {
            $fields['pib'] = IdentityCensusFieldStatus::PRESENT_VALID;

            return;
        }

        $fields['pib'] = IdentityCensusFieldStatus::INVALID;
        $this->invalid('pib', $reasons, $d14);
    }

    private function jmbStatus(mixed $raw): string
    {
        $value = $this->outerTrim(is_string($raw) ? $raw : null);
        if ($value === null) {
            return IdentityCensusFieldStatus::MISSING;
        }

        return $this->jmbValidator->isValid($value)
            ? IdentityCensusFieldStatus::PRESENT_VALID
            : IdentityCensusFieldStatus::INVALID;
    }

    private function passportStatus(mixed $raw): string
    {
        $value = $this->outerTrim(is_string($raw) ? $raw : null);

        return $value === null
            ? IdentityCensusFieldStatus::MISSING
            : IdentityCensusFieldStatus::PRESENT_VALID;
    }

    /**
     * @param  list<string>  $reasons
     */
    private function deriveRowStatus(array $reasons): string
    {
        if (in_array('id_document_type_ambiguous', $reasons, true)
            || in_array('legal_form_unresolved', $reasons, true)
            || in_array('subject_unresolved', $reasons, true)) {
            return IdentityCensusRow::AMBIGUOUS_MAPPING;
        }

        foreach ($reasons as $reason) {
            if (str_ends_with($reason, '_invalid')) {
                return IdentityCensusRow::INVALID_LEGACY;
            }
        }

        foreach ($reasons as $reason) {
            if (str_ends_with($reason, '_missing') || str_ends_with($reason, '_source_unavailable')) {
                return IdentityCensusRow::MISSING_REQUIRED;
            }
        }

        return IdentityCensusRow::BACKFILLABLE;
    }

    /**
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     */
    private function missing(string $field, array &$reasons, array &$d10): void
    {
        $code = $field.'_missing';
        $reasons[] = $code;
        $d10[] = $code;
    }

    /**
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     */
    private function unavailable(string $field, array &$reasons, array &$d10): void
    {
        $code = $field.'_source_unavailable';
        $reasons[] = $code;
        $d10[] = $code;
    }

    /**
     * @param  list<string>  $reasons
     * @param  list<string>  $d14
     */
    private function invalid(string $field, array &$reasons, array &$d14): void
    {
        $code = $field.'_invalid';
        $reasons[] = $code;
        $d14[] = $code;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $reasons
     * @param  list<string>  $d10
     * @param  list<string>  $d14
     */
    private function row(
        User $user,
        ?string $roleName,
        bool $isStaff,
        ?string $legacyType,
        string $rowStatus,
        ?string $subject,
        ?string $form,
        bool $entrepreneur,
        array $fields,
        array $reasons,
        array $d10,
        array $d14,
        ?string $idDocumentType = null,
    ): IdentityCensusRow {
        $reasons = array_values(array_unique($reasons));
        $d10 = array_values(array_unique($d10));
        $d14 = array_values(array_unique($d14));

        return new IdentityCensusRow(
            userId: (int) $user->id,
            roleName: $roleName,
            isStaffAccount: $isStaff,
            legacyUserType: $legacyType,
            rowStatus: $rowStatus,
            subjectType: $subject,
            legalForm: $form,
            isEntrepreneur: $entrepreneur,
            idDocumentType: $idDocumentType,
            fieldStatuses: $fields,
            reasonCodes: $reasons,
            fullyBackfillable: $rowStatus === IdentityCensusRow::BACKFILLABLE,
            d10MissingCodes: $d10,
            d14InvalidCodes: $d14,
        );
    }

    /**
     * @return array<string, string>
     */
    private function emptyFields(): array
    {
        $na = IdentityCensusFieldStatus::NOT_APPLICABLE;

        return [
            'id_document_type' => $na,
            'jmb' => $na,
            'passport_number' => $na,
            'pib' => $na,
            'crps' => $na,
            'residential_status' => $na,
            'residence_country_code' => $na,
            'first_name' => $na,
            'last_name' => $na,
            'legal_name' => $na,
            'entrepreneur_business_name' => $na,
            'address' => $na,
            'city' => $na,
            'phone' => $na,
            'authorized_person' => $na,
            'representative' => $na,
            'foreign_company_name' => $na,
            'branch_name_in_montenegro' => $na,
        ];
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function markUnsupportedFields(array &$fields): void
    {
        $fields = $this->emptyFields();
    }

    private function outerTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function commitHash(): ?string
    {
        $gitDir = $this->gitDirectory();
        if ($gitDir === null) {
            return null;
        }

        $headPath = $gitDir.DIRECTORY_SEPARATOR.'HEAD';
        if (! is_file($headPath)) {
            return null;
        }

        $head = trim((string) file_get_contents($headPath));
        if ($head === '') {
            return null;
        }

        if (str_starts_with($head, 'ref: ')) {
            $refPath = $gitDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, substr($head, 5));
            if (! is_file($refPath)) {
                return null;
            }

            $hash = trim((string) file_get_contents($refPath));

            return $hash === '' ? null : $hash;
        }

        return $head;
    }

    private function gitDirectory(): ?string
    {
        $git = base_path('.git');
        if (is_dir($git)) {
            return $git;
        }

        if (! is_file($git)) {
            return null;
        }

        $contents = trim((string) file_get_contents($git));
        if (! str_starts_with($contents, 'gitdir:')) {
            return null;
        }

        $path = trim(substr($contents, strlen('gitdir:')));
        if ($path === '') {
            return null;
        }

        if (! preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\/)/', $path)) {
            $path = base_path($path);
        }

        return is_dir($path) ? $path : null;
    }

    /**
     * @param  list<IdentityCensusRow>  $rows
     * @param  array<string, list<int>>  $jmbFingerprints
     * @return array<string, mixed>
     */
    private function aggregates(array $rows, array $jmbFingerprints = []): array
    {
        $inc = static function (array &$bag, string $key): void {
            $bag[$key] = ($bag[$key] ?? 0) + 1;
        };

        $byType = [];
        $byStatus = [
            IdentityCensusRow::NON_SUBJECT_ACCOUNT => 0,
            IdentityCensusRow::UNSUPPORTED => 0,
            IdentityCensusRow::AMBIGUOUS_MAPPING => 0,
            IdentityCensusRow::INVALID_LEGACY => 0,
            IdentityCensusRow::MISSING_REQUIRED => 0,
            IdentityCensusRow::BACKFILLABLE => 0,
        ];
        $bySubject = ['physical_person' => 0, 'entrepreneur' => 0, 'legal_entity' => 0, 'foreign_branch' => 0];
        $jmb = ['valid' => 0, 'missing' => 0, 'invalid' => 0, 'not_applicable' => 0];
        $pib = ['valid' => 0, 'missing' => 0, 'invalid' => 0, 'not_applicable' => 0];
        $crps = ['source_unavailable' => 0, 'not_applicable' => 0];
        $residency = ['valid' => 0, 'missing' => 0, 'invalid' => 0, 'not_applicable' => 0];
        $city = ['present' => 0, 'missing' => 0, 'not_applicable' => 0];
        $doc = ['present_valid' => 0, 'missing' => 0, 'ambiguous' => 0, 'not_applicable' => 0];
        $ap = ['source_unavailable' => 0, 'not_applicable' => 0];
        $rep = ['source_unavailable' => 0, 'not_applicable' => 0];

        $nonSubject = 0;
        $candidates = 0;
        $deterministic = 0;
        $ambiguous = 0;
        $unsupported = 0;

        foreach ($rows as $row) {
            $typeKey = $row->legacyUserType ?? 'NULL';
            $inc($byType, $typeKey);
            $inc($byStatus, $row->rowStatus);

            if ($row->rowStatus === IdentityCensusRow::NON_SUBJECT_ACCOUNT) {
                $nonSubject++;
            } else {
                $candidates++;
            }

            if ($row->rowStatus === IdentityCensusRow::UNSUPPORTED) {
                $unsupported++;
            } elseif ($row->rowStatus === IdentityCensusRow::AMBIGUOUS_MAPPING) {
                $ambiguous++;
            } elseif ($row->subjectType !== null) {
                $deterministic++;
            }

            if ($row->isEntrepreneur) {
                $bySubject['entrepreneur']++;
            } elseif ($row->subjectType === PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
                $bySubject['physical_person']++;
            } elseif ($row->subjectType === PlatformIdentity::SUBJECT_LEGAL_ENTITY) {
                $bySubject['legal_entity']++;
            } elseif ($row->subjectType === PlatformIdentity::SUBJECT_FOREIGN_BRANCH) {
                $bySubject['foreign_branch']++;
            }

            $this->countStatus($jmb, $row->fieldStatuses['jmb'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE);
            $this->countStatus($pib, $row->fieldStatuses['pib'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE);
            $crpsStatus = $row->fieldStatuses['crps'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($crpsStatus === IdentityCensusFieldStatus::SOURCE_UNAVAILABLE) {
                $crps['source_unavailable']++;
            } else {
                $crps['not_applicable']++;
            }
            $this->countStatus($residency, $row->fieldStatuses['residential_status'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE);

            $cityStatus = $row->fieldStatuses['city'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($cityStatus === IdentityCensusFieldStatus::PRESENT_VALID) {
                $city['present']++;
            } elseif ($cityStatus === IdentityCensusFieldStatus::MISSING) {
                $city['missing']++;
            } else {
                $city['not_applicable']++;
            }

            $docStatus = $row->fieldStatuses['id_document_type'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($docStatus === IdentityCensusFieldStatus::PRESENT_VALID) {
                $doc['present_valid']++;
            } elseif ($docStatus === IdentityCensusFieldStatus::MISSING) {
                $doc['missing']++;
            } elseif ($docStatus === IdentityCensusFieldStatus::AMBIGUOUS) {
                $doc['ambiguous']++;
            } else {
                $doc['not_applicable']++;
            }

            $apStatus = $row->fieldStatuses['authorized_person'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($apStatus === IdentityCensusFieldStatus::SOURCE_UNAVAILABLE) {
                $ap['source_unavailable']++;
            } else {
                $ap['not_applicable']++;
            }
            $repStatus = $row->fieldStatuses['representative'] ?? IdentityCensusFieldStatus::NOT_APPLICABLE;
            if ($repStatus === IdentityCensusFieldStatus::SOURCE_UNAVAILABLE) {
                $rep['source_unavailable']++;
            } else {
                $rep['not_applicable']++;
            }
        }

        return [
            'total_users' => count($rows),
            'non_subject_internal' => $nonSubject,
            'registered_subject_candidates' => $candidates,
            'by_legacy_user_type' => $byType,
            'deterministic_mapping' => $deterministic,
            'ambiguous_mapping' => $ambiguous,
            'unsupported_mapping' => $unsupported,
            'row_statuses' => $byStatus,
            'by_subject' => $bySubject,
            'jmb' => $jmb,
            'pib' => $pib,
            'crps' => $crps,
            'residency' => $residency,
            'city' => $city,
            'id_document_type' => $doc,
            'authorized_person' => $ap,
            'representative' => $rep,
            'jmb_duplicate_fingerprint_groups' => count(array_filter(
                $jmbFingerprints,
                static fn (array $ids): bool => count($ids) > 1
            )),
        ];
    }

    /**
     * @param  array<string, int>  $bag
     */
    private function countStatus(array &$bag, string $status): void
    {
        if ($status === IdentityCensusFieldStatus::PRESENT_VALID) {
            $bag['valid']++;
        } elseif ($status === IdentityCensusFieldStatus::MISSING) {
            $bag['missing']++;
        } elseif ($status === IdentityCensusFieldStatus::INVALID) {
            $bag['invalid']++;
        } else {
            $bag['not_applicable']++;
        }
    }
}
