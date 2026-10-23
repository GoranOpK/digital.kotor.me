<?php

namespace App\Services\Competitions;

final class ProcessWriteResult
{
    private function __construct(
        public readonly bool $succeeded,
        public readonly ?string $error,
        public readonly ?int $recordId = null,
        public readonly ?string $warning = null,
    ) {
    }

    public static function ok(?int $recordId = null, ?string $warning = null): self
    {
        return new self(true, null, $recordId, $warning);
    }

    public static function failed(string $error): self
    {
        return new self(false, $error);
    }
}
