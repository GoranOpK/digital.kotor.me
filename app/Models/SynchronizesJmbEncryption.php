<?php

namespace App\Models;

use App\Security\JmbDualWrite;

trait SynchronizesJmbEncryption
{
    /**
     * Explicit logical JMB/JMBG updates for the current save cycle.
     * Presence of a key (including null) is intentional clear vs omit.
     *
     * @var array<string, string|null>
     */
    private array $logicalJmbUpdates = [];

    protected static function bootSynchronizesJmbEncryption(): void
    {
        static::saving(function ($model): void {
            JmbDualWrite::syncModel($model);
        });
    }

    public function recordLogicalJmb(string $column, #[\SensitiveParameter] ?string $value): void
    {
        $this->logicalJmbUpdates[$column] = $value;
    }

    /**
     * @return array<string, string|null>
     */
    public function peekLogicalJmbUpdates(): array
    {
        return $this->logicalJmbUpdates;
    }

    /**
     * @return array<string, string|null>
     */
    public function takeLogicalJmbUpdates(): array
    {
        $updates = $this->logicalJmbUpdates;
        $this->logicalJmbUpdates = [];

        return $updates;
    }
}
