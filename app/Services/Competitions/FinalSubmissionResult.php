<?php

namespace App\Services\Competitions;

final class FinalSubmissionResult
{
    private function __construct(
        public readonly bool $succeeded,
        public readonly bool $idempotent,
        public readonly ?string $error,
    ) {
    }

    public static function submitted(): self
    {
        return new self(true, false, null);
    }

    public static function alreadyFinal(): self
    {
        return new self(true, true, null);
    }

    public static function failed(string $error): self
    {
        return new self(false, false, $error);
    }
}
