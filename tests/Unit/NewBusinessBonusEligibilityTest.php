<?php

namespace Tests\Unit;

use App\Models\Application;
use Tests\TestCase;

/**
 * Patch 2A: new-business +2 eligibility and getBonusScore defense-in-depth.
 * Uses in-memory Application instances (no DB writes).
 */
class NewBusinessBonusEligibilityTest extends TestCase
{
    public function test_unregistered_fizicko_lice_with_flag_gets_two_points(): void
    {
        $application = $this->application([
            'applicant_type' => 'fizicko_lice',
            'is_registered' => false,
            'bonus_new_business' => true,
        ]);

        $this->assertTrue($application->isEligibleForNewBusinessBonus());
        $this->assertSame(2, $application->newBusinessBonusPoints());
        $this->assertSame(2, $application->getBonusScore());
    }

    public function test_unregistered_planned_company_doo_with_flag_gets_two_points(): void
    {
        $application = $this->application([
            'applicant_type' => 'doo',
            'is_registered' => false,
            'bonus_new_business' => true,
            'business_stage' => 'započinjanje',
        ]);

        $this->assertTrue($application->isEligibleForNewBusinessBonus());
        $this->assertSame(2, $application->getBonusScore());
    }

    public function test_unregistered_planned_company_privredno_drustvo_with_flag_gets_two_points(): void
    {
        $application = $this->application([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'bonus_new_business' => true,
        ]);

        $this->assertSame(2, $application->getBonusScore());
    }

    public function test_registered_preduzetnica_with_flag_gets_zero_even_on_zapocinjanje(): void
    {
        $application = $this->application([
            'applicant_type' => 'preduzetnica',
            'is_registered' => true,
            'business_stage' => 'započinjanje',
            'bonus_new_business' => true,
        ]);

        $this->assertFalse($application->isEligibleForNewBusinessBonus());
        $this->assertSame(0, $application->newBusinessBonusPoints());
        $this->assertSame(0, $application->getBonusScore());
    }

    public function test_registered_company_with_flag_gets_zero(): void
    {
        $application = $this->application([
            'applicant_type' => 'doo',
            'is_registered' => true,
            'business_stage' => 'započinjanje',
            'bonus_new_business' => true,
        ]);

        $this->assertFalse($application->isEligibleForNewBusinessBonus());
        $this->assertSame(0, $application->getBonusScore());
    }

    public function test_eligible_with_flag_false_gets_zero(): void
    {
        $application = $this->application([
            'applicant_type' => 'fizicko_lice',
            'is_registered' => false,
            'bonus_new_business' => false,
        ]);

        $this->assertTrue($application->isEligibleForNewBusinessBonus());
        $this->assertSame(0, $application->getBonusScore());
    }

    public function test_other_bonuses_unaffected_when_new_business_ineligible(): void
    {
        $application = $this->application([
            'applicant_type' => 'preduzetnica',
            'is_registered' => true,
            'bonus_new_business' => true,
            'bonus_info_day' => true,
            'bonus_zavod_nezaposleni' => true,
            'bonus_green_innovative' => true,
        ]);

        // 1 + 0 + 2 + 3 = 6 (new-business suppressed)
        $this->assertSame(6, $application->getBonusScore());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function application(array $attributes): Application
    {
        return new Application($attributes);
    }
}
