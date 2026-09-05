<?php

namespace App\Identity\Census;

/**
 * Report-only census row. Not persisted. Contains no raw identifiers or secrets.
 */
final readonly class IdentityCensusRow
{
    public const NON_SUBJECT_ACCOUNT = 'NON_SUBJECT_ACCOUNT';

    public const UNSUPPORTED = 'UNSUPPORTED';

    public const AMBIGUOUS_MAPPING = 'AMBIGUOUS_MAPPING';

    public const INVALID_LEGACY = 'INVALID_LEGACY';

    public const MISSING_REQUIRED = 'MISSING_REQUIRED';

    public const BACKFILLABLE = 'BACKFILLABLE';

    /**
     * @param  array<string, string>  $fieldStatuses
     * @param  list<string>  $reasonCodes
     * @param  list<string>  $d10MissingCodes
     * @param  list<string>  $d14InvalidCodes
     */
    public function __construct(
        public int $userId,
        public ?string $roleName,
        public bool $isStaffAccount,
        public ?string $legacyUserType,
        public string $rowStatus,
        public ?string $subjectType,
        public ?string $legalForm,
        public bool $isEntrepreneur,
        public ?string $idDocumentType,
        public array $fieldStatuses,
        public array $reasonCodes,
        public bool $fullyBackfillable,
        public array $d10MissingCodes,
        public array $d14InvalidCodes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role_name' => $this->roleName,
            'is_staff_account' => $this->isStaffAccount,
            'legacy_user_type' => $this->legacyUserType,
            'row_status' => $this->rowStatus,
            'subject_type' => $this->subjectType,
            'legal_form' => $this->legalForm,
            'is_entrepreneur' => $this->isEntrepreneur,
            'id_document_type' => $this->idDocumentType,
            'field_statuses' => $this->fieldStatuses,
            'reason_codes' => $this->reasonCodes,
            'fully_backfillable' => $this->fullyBackfillable,
            'd10_missing_codes' => $this->d10MissingCodes,
            'd14_invalid_codes' => $this->d14InvalidCodes,
        ];
    }
}
