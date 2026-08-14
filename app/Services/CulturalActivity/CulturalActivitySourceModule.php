<?php

namespace App\Services\CulturalActivity;

use App\Exceptions\CulturalActivityRecordException;

/**
 * Kanonski source_module iz TS-012 §7.1 / §8.3.
 */
final class CulturalActivitySourceModule
{
    public const TS_001 = 'TS-001';

    public const TS_003 = 'TS-003';

    public const TS_004 = 'TS-004';

    public const TS_005 = 'TS-005';

    public const TS_011 = 'TS-011';

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return [
            self::TS_001,
            self::TS_003,
            self::TS_004,
            self::TS_005,
            self::TS_011,
        ];
    }

    public static function assertValid(string $sourceModule): string
    {
        $normalized = trim($sourceModule);
        if (! in_array($normalized, self::allowed(), true)) {
            throw new CulturalActivityRecordException('Nepoznat source_module za Evidenciju aktivnosti.');
        }

        return $normalized;
    }
}
