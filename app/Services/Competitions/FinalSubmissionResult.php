<?php

namespace App\Services\Competitions;

final class FinalSubmissionResult
{
    /**
     * @param  list<string>  $missingDocumentLabels
     */
    private function __construct(
        public readonly bool $succeeded,
        public readonly bool $idempotent,
        public readonly ?string $error,
        public readonly bool $needsMissingDocumentConfirmation = false,
        public readonly array $missingDocumentLabels = [],
        public readonly ?string $documentWarning = null,
    ) {
    }

    public static function submitted(?string $documentWarning = null): self
    {
        return new self(true, false, null, false, [], $documentWarning);
    }

    public static function alreadyFinal(): self
    {
        return new self(true, true, null);
    }

    public static function failed(string $error): self
    {
        return new self(false, false, $error);
    }

    /**
     * @param  list<string>  $labels
     */
    public static function needsMissingDocumentConfirmation(array $labels): self
    {
        return new self(false, false, null, true, $labels);
    }
}
