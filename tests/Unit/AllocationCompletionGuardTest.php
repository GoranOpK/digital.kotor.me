<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Services\Competitions\AllocationCompletionGuard;
use Tests\TestCase;

class AllocationCompletionGuardTest extends TestCase
{
    public function test_same_rank_startup_does_not_reserve_a_claim_against_development(): void
    {
        $guard = new AllocationCompletionGuard;
        $startup = $this->application(1, 1, 'započinjanje', '30000.00');
        $development = $this->application(2, 1, 'razvoj', '30000.00');

        $room = $guard->supportRoom(
            '50000.00',
            collect([$startup, $development]),
            $development,
            fn (Application $application) => $guard->zenskoMaxClaim($application, '50000.00'),
        );

        $this->assertSame('50000.00', $room);
    }

    public function test_saved_support_reduces_the_room_left_for_the_same_rank(): void
    {
        $guard = new AllocationCompletionGuard;
        $startup = $this->application(1, 1, 'započinjanje', '30000.00');
        $development = $this->application(2, 1, 'razvoj', '30000.00');
        $development->commission_decision = 'podrzava_potpuno';
        $development->approved_amount = '20000.00';

        $this->assertSame(
            '30000.00',
            $guard->supportRoom(
                '50000.00',
                collect([$startup, $development]),
                $startup,
                fn (Application $application) => $guard->zenskoMaxClaim($application, '50000.00'),
            )
        );
    }

    public function test_same_stage_peer_does_not_reserve_the_whole_claim(): void
    {
        $guard = new AllocationCompletionGuard;
        $first = $this->application(1, 2, 'započinjanje', '30000.00');
        $second = $this->application(2, 2, 'započinjanje', '30000.00');

        $room = $guard->supportRoom(
            '50000.00',
            collect([$first, $second]),
            $second,
            fn (Application $application) => $guard->zenskoMaxClaim($application, '50000.00'),
        );

        $this->assertSame('50000.00', $room);
    }

    public function test_youth_claim_reserves_requested_amount_without_percent(): void
    {
        $guard = new AllocationCompletionGuard;
        $higher = $this->application(1, 1, 'započinjanje', '100000.00');
        $lower = $this->application(2, 2, 'započinjanje', '80000.00');

        $this->assertSame('100000.00', $guard->youthMaxClaim($higher, '100000.00'));
        $this->assertSame(
            '0.00',
            $guard->supportRoom(
                '100000.00',
                collect([$higher, $lower]),
                $lower,
                fn (Application $application) => $guard->youthMaxClaim($application, '100000.00'),
            )
        );
        $this->assertSame('40000.00', $guard->youthMaxClaim(
            $this->application(3, 1, 'započinjanje', '40000.00'),
            '100000.00'
        ));
    }

    private function application(int $id, int $rank, string $stage, string $requested): Application
    {
        $application = new Application;
        $application->id = $id;
        $application->ranking_position = $rank;
        $application->business_stage = $stage;
        $application->requested_amount = $requested;
        $application->bonus_green_innovative = false;

        return $application;
    }
}
