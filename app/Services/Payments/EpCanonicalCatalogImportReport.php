<?php

namespace App\Services\Payments;

final class EpCanonicalCatalogImportReport
{
    public int $typesCreated = 0;

    public int $typesSkipped = 0;

    public int $accountsCreated = 0;

    public int $accountsSkipped = 0;

    public int $typeRulesCreated = 0;

    public int $accountRulesCreated = 0;

    /** @var list<string> */
    public array $conflicts = [];

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }
}
