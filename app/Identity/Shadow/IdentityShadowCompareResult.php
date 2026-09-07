<?php

namespace App\Identity\Shadow;

final readonly class IdentityShadowCompareResult
{
    /**
     * @param  list<string>  $reasonCodes
     * @param  list<string>  $fieldCategories
     * @param  array<string, int>  $coverage
     */
    public function __construct(
        public string $status,
        public array $reasonCodes,
        public array $fieldCategories,
        public array $coverage = [],
    ) {
    }

    public static function match(array $fieldCategories = [], array $reasonCodes = [], array $coverage = []): self
    {
        return new self(IdentityShadowStatus::MATCH, $reasonCodes, $fieldCategories, $coverage);
    }

    public static function mismatch(array $reasonCodes, array $fieldCategories, array $coverage = []): self
    {
        return new self(IdentityShadowStatus::MISMATCH, $reasonCodes, $fieldCategories, $coverage);
    }

    public static function notEvaluable(array $reasonCodes, array $fieldCategories, array $coverage = []): self
    {
        return new self(IdentityShadowStatus::NOT_EVALUABLE, $reasonCodes, $fieldCategories, $coverage);
    }
}
