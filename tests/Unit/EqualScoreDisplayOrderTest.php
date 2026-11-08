<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Support\EqualScoreDisplayOrder;
use App\Support\KnApplicationClassification;
use Tests\TestCase;

class EqualScoreDisplayOrderTest extends TestCase
{
    public function test_shared_ranks_skip_occupied_places_and_show_startup_first(): void
    {
        $applications = [
            $this->application(10, '60', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(11, '70', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(12, '50', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(14, '40', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(13, '40', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(15, '30', KnApplicationClassification::STAGE_ZAPOCINJANJE),
        ];

        $ranked = EqualScoreDisplayOrder::ranked($applications, fn (Application $application) => (string) $application->final_score, 2);

        $this->assertSame([11, 10, 12, 13, 14, 15], array_map(fn (array $row) => $row['application']->id, $ranked));
        $this->assertSame([1, 2, 3, 4, 4, 6], array_map(fn (array $row) => $row['rank'], $ranked));
    }

    public function test_three_applications_sharing_fourth_place_continue_at_seventh(): void
    {
        $applications = [
            $this->application(1, '80', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(2, '70', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(3, '60', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(6, '50', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(4, '50', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(5, '50', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(7, '40', KnApplicationClassification::STAGE_RAZVOJ),
        ];

        $ranked = EqualScoreDisplayOrder::ranked($applications, fn (Application $application) => (string) $application->final_score, 2);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], array_map(fn (array $row) => $row['application']->id, $ranked));
        $this->assertSame([1, 2, 3, 4, 4, 4, 7], array_map(fn (array $row) => $row['rank'], $ranked));
    }

    public function test_same_stage_keeps_id_order_without_changing_the_shared_rank(): void
    {
        $applications = [
            $this->application(9, '40', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(3, '40', KnApplicationClassification::STAGE_ZAPOCINJANJE),
            $this->application(8, '40', KnApplicationClassification::STAGE_RAZVOJ),
            $this->application(4, '40', KnApplicationClassification::STAGE_RAZVOJ),
        ];

        $ranked = EqualScoreDisplayOrder::ranked($applications, fn (Application $application) => (string) $application->final_score, 2);
        $ordered = collect($ranked)->map(fn (array $row) => $row['application']);

        $this->assertSame([3, 9, 4, 8], $ordered->map->id->all());
        $this->assertSame([1, 1, 1, 1], array_map(fn (array $row) => $row['rank'], $ranked));
        $this->assertSame(
            $ordered->map->id->all(),
            $ordered->sort(fn (Application $left, Application $right) => EqualScoreDisplayOrder::compareRanked($left, $right))->map->id->values()->all()
        );
    }

    private function application(int $id, string $score, string $stage): Application
    {
        $application = new Application;
        $application->id = $id;
        $application->final_score = $score;
        $application->business_stage = $stage;
        $application->ranking_position = 4;

        return $application;
    }
}
