<?php

namespace App\Services\CulturalActivity;

use Carbon\CarbonInterface;

/**
 * Deterministic event_id for TS12 emitters (idempotent ingest).
 * Does not claim reconstructable identity after process-end audit failure.
 * repeatable() is not a global uniqueness guarantee when catalog id,
 * entity identity, canonical payload and persist µs are identical.
 *
 * once() — terminal / single-row actions (request id, create, publish, cycle_id).
 * repeatable() — actions that can recur: SHA of canonical business scalars
 * plus the in-memory persist clock (microseconds, captured before DB round-trip).
 */
final class CulturalActivityEventId
{
    public static function once(string $catalogId, int|string $id): string
    {
        return $catalogId.':'.$id;
    }

    /**
     * @param  array<string, scalar|null|array<int, scalar|null>>  $canonical
     */
    public static function repeatable(
        string $catalogId,
        int|string $id,
        array $canonical,
        CarbonInterface $persistedAt,
    ): string {
        return self::of($catalogId, $id, self::digest($canonical), self::clock($persistedAt));
    }

    public static function of(string $catalogId, int|string ...$parts): string
    {
        return $catalogId.':'.implode(':', $parts);
    }

    public static function clock(CarbonInterface $at): string
    {
        return $at->copy()->timezone((string) config('app.timezone'))->format('YmdHisu');
    }

    /**
     * @param  array<string, scalar|null|array<int, scalar|null>>  $canonical
     */
    public static function digest(array $canonical): string
    {
        return substr(
            hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
            0,
            16
        );
    }
}
