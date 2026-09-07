<?php

namespace App\Identity\Shadow;

/**
 * PII-safe per user+flow Step 6 result. Not persisted.
 */
final readonly class IdentityShadowUserOutcome
{
    /**
     * @param  list<string>  $reasonCodes
     * @param  list<string>  $fieldCategories
     * @param  array<string, int>  $coverage
     */
    public function __construct(
        public int $userId,
        public string $flowCode,
        public string $status,
        public array $reasonCodes,
        public array $fieldCategories,
        public array $coverage = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $row = [
            'user_id' => $this->userId,
            'flow_code' => $this->flowCode,
            'status' => $this->status,
            'reason_codes' => $this->reasonCodes,
            'field_categories' => $this->fieldCategories,
        ];
        if ($this->coverage !== []) {
            $row['coverage'] = $this->coverage;
        }

        return $row;
    }
}
