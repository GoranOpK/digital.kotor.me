<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\DB;

/**
 * Minimal EP module settings. Not a platform settings framework.
 */
class EpModuleSettings
{
    public const NEW_PAYMENTS_ENABLED = 'new_payments_enabled';

    public function newPaymentsEnabled(): bool
    {
        $value = DB::table('ep_settings')->where('key', self::NEW_PAYMENTS_ENABLED)->value('value');

        return $value === '1' || $value === 1 || $value === true;
    }

    public function setNewPaymentsEnabled(bool $enabled): void
    {
        DB::table('ep_settings')->updateOrInsert(
            ['key' => self::NEW_PAYMENTS_ENABLED],
            [
                'value' => $enabled ? '1' : '0',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
