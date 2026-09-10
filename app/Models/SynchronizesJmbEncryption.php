<?php

namespace App\Models;

use App\Security\JmbDualWrite;

trait SynchronizesJmbEncryption
{
    protected static function bootSynchronizesJmbEncryption(): void
    {
        static::saving(function ($model): void {
            JmbDualWrite::syncModel($model);
        });
    }
}
