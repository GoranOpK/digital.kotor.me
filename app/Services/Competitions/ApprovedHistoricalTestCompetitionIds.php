<?php

namespace App\Services\Competitions;

/**
 * Spisak konkretnih ID-jeva kojima je dozvoljeno naknadno označavanje.
 * Prazan spisak ne odobrava nijedan konkurs.
 */
class ApprovedHistoricalTestCompetitionIds
{
    public function allows(int $competitionId): bool
    {
        return in_array($competitionId, $this->ids(), true);
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return self::parse(config('archived_test_competitions.mark_ids'));
    }

    /**
     * @return list<int>
     */
    public static function parse(mixed $raw): array
    {
        if (is_int($raw)) {
            return $raw > 0 ? [$raw] : [];
        }

        if (is_array($raw)) {
            $ids = [];
            foreach ($raw as $value) {
                foreach (self::parse($value) as $id) {
                    $ids[$id] = $id;
                }
            }

            return array_values($ids);
        }

        if (! is_string($raw)) {
            return [];
        }

        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            if (! is_string($part) || ! preg_match('/^[1-9][0-9]{0,17}$/', $part)) {
                continue;
            }

            $id = (int) $part;
            if ((string) $id !== $part) {
                continue;
            }

            $ids[$id] = $id;
        }

        return array_values($ids);
    }
}
