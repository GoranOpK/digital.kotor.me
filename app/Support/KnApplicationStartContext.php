<?php

namespace App\Support;

/**
 * Opaque server-side start context for a single Obrazac 1 create/store attempt.
 * Query parameters and disabled radios are not authority.
 */
final class KnApplicationStartContext
{
    public const INTENT_FUTURE_ENTREPRENEUR = 'future_entrepreneur';

    public const INTENT_PLANNED_COMPANY = 'planned_company';

    public const INTENT_CANONICAL_ENTREPRENEUR = 'canonical_entrepreneur';

    public const INTENT_CANONICAL_COMPANY = 'canonical_company';

    public const INTENT_LEGACY_OTHER = 'legacy_other';

    public const INTENT_SAVED_APPLICATION = 'saved_application';

    public const TARGET_1A = '1a';

    public const TARGET_1B = '1b';

    public function __construct(
        public readonly int $userId,
        public readonly int $competitionId,
        public readonly string $intent,
        public readonly string $applicantType,
        public readonly bool $isRegistered,
        public readonly string $businessStage,
        public readonly ?string $commercialForm,
        public readonly ?string $registrationForm,
        public readonly string $targetForm,
        public readonly bool $stageLocked,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'competition_id' => $this->competitionId,
            'intent' => $this->intent,
            'applicant_type' => $this->applicantType,
            'is_registered' => $this->isRegistered,
            'business_stage' => $this->businessStage,
            'commercial_form' => $this->commercialForm,
            'registration_form' => $this->registrationForm,
            'target_form' => $this->targetForm,
            'stage_locked' => $this->stageLocked,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            competitionId: (int) $data['competition_id'],
            intent: (string) $data['intent'],
            applicantType: (string) $data['applicant_type'],
            isRegistered: (bool) $data['is_registered'],
            businessStage: (string) $data['business_stage'],
            commercialForm: isset($data['commercial_form']) && is_string($data['commercial_form']) && $data['commercial_form'] !== ''
                ? $data['commercial_form']
                : null,
            registrationForm: isset($data['registration_form']) && is_string($data['registration_form']) && $data['registration_form'] !== ''
                ? $data['registration_form']
                : null,
            targetForm: (string) $data['target_form'],
            stageLocked: (bool) $data['stage_locked'],
        );
    }

    public function isCommercialCompany(): bool
    {
        return KnCommercialCompanyForm::isValid($this->commercialForm);
    }
}
