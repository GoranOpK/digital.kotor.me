<?php

namespace App\Support;

use App\Models\Application;

/**
 * Shared ŽP and PM order for equal final scores.
 * Startup is shown before development. Rank numbers are not derived from that order.
 * The same stage keeps a stable technical order by application id.
 */
final class EqualScoreDisplayOrder
{
    public static function stageKey(?string $stage): int
    {
        return match ($stage) {
            KnApplicationClassification::STAGE_ZAPOCINJANJE => 0,
            KnApplicationClassification::STAGE_RAZVOJ => 1,
            default => 2,
        };
    }

    public static function compareTied(Application $left, Application $right): int
    {
        $stage = self::stageKey($left->business_stage) <=> self::stageKey($right->business_stage);

        return $stage !== 0 ? $stage : ($left->id <=> $right->id);
    }

    public static function compareRanked(Application $left, Application $right): int
    {
        $leftPosition = $left->ranking_position ?? PHP_INT_MAX;
        $rightPosition = $right->ranking_position ?? PHP_INT_MAX;
        if ($leftPosition !== $rightPosition) {
            return $leftPosition <=> $rightPosition;
        }

        return self::compareTied($left, $right);
    }

    public static function compareByScore(Application $left, Application $right, string $leftScore, string $rightScore, int $scale): int
    {
        $score = bccomp($rightScore, $leftScore, $scale);

        return $score !== 0 ? $score : self::compareTied($left, $right);
    }

    /**
     * Higher score first. Equal scores share a rank and the next score skips occupied places.
     * Stage and id order the group; they do not create a different rank.
     *
     * @param  iterable<Application>  $applications
     * @param  callable(Application): string  $scoreOf
     * @return list<array{application: Application, rank: int}>
     */
    public static function ranked(iterable $applications, callable $scoreOf, int $scale): array
    {
        $rows = [];
        foreach ($applications as $application) {
            $rows[] = $application;
        }

        usort($rows, function (Application $left, Application $right) use ($scoreOf, $scale): int {
            $score = bccomp((string) $scoreOf($right), (string) $scoreOf($left), $scale);

            return $score !== 0 ? $score : self::compareTied($left, $right);
        });

        $ranked = [];
        $previousScore = null;
        $rank = 0;
        foreach ($rows as $index => $application) {
            $score = (string) $scoreOf($application);
            $rank = ($previousScore !== null && bccomp($score, $previousScore, $scale) === 0)
                ? $rank
                : $index + 1;
            $previousScore = $score;
            $ranked[] = ['application' => $application, 'rank' => $rank];
        }

        return $ranked;
    }
}
