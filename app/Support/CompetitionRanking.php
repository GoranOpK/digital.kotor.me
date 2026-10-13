<?php

namespace App\Support;

/**
 * Profile-neutral competition ranking on full-precision decimal strings.
 *
 * Genuinely equal CALCULATION VALUES share a rank and the next distinct value skips places
 * (1, 2, 2, 4). Values that only look equal after display rounding stay distinct.
 * Id order inside a shared rank is display-only and never implies funding priority.
 */
final class CompetitionRanking
{
    public const SCALE = 10;

    /**
     * @param  array<int, string>  $scoresById  id => full-precision score
     * @return list<array{id: int, score: string, rank: int}> ordered by score DESC, then id ASC
     */
    public static function rank(array $scoresById, int $scale = self::SCALE): array
    {
        $rows = [];
        foreach ($scoresById as $id => $score) {
            $rows[] = ['id' => (int) $id, 'score' => (string) $score];
        }

        usort($rows, function (array $a, array $b) use ($scale) {
            $cmp = bccomp($b['score'], $a['score'], $scale);

            return $cmp !== 0 ? $cmp : $a['id'] <=> $b['id'];
        });

        $ranked = [];
        $previousScore = null;
        $previousRank = 0;
        foreach ($rows as $index => $row) {
            $rank = ($previousScore !== null && bccomp($row['score'], $previousScore, $scale) === 0)
                ? $previousRank
                : $index + 1;
            $ranked[] = ['id' => $row['id'], 'score' => $row['score'], 'rank' => $rank];
            $previousRank = $rank;
            $previousScore = $row['score'];
        }

        return $ranked;
    }

    /**
     * @param  array<int, string>  $scoresById
     * @return array<int, int> id => rank
     */
    public static function positions(array $scoresById, int $scale = self::SCALE): array
    {
        $positions = [];
        foreach (self::rank($scoresById, $scale) as $row) {
            $positions[$row['id']] = $row['rank'];
        }

        return $positions;
    }

    /**
     * Half-up display rounding of a non-negative or negative decimal string.
     */
    public static function roundHalfUp(string $value, int $scale = 2): string
    {
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? substr($value, 1) : $value;
        $nudge = bcdiv('5', bcpow('10', (string) ($scale + 1), 0), $scale + 1);
        $rounded = bcadd($absolute, $nudge, $scale);

        return $negative && bccomp($rounded, '0', $scale) !== 0 ? '-'.$rounded : $rounded;
    }
}
