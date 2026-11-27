<?php

namespace Tests\Unit;

use App\Services\Competitions\MmspDecisionInflection;
use PHPUnit\Framework\TestCase;

class MmspDecisionInflectionTest extends TestCase
{
    public function test_applications_plans_and_days_follow_the_count(): void
    {
        $applications = [
            0 => '0 prijava',
            1 => '1 prijava',
            2 => '2 prijave',
            3 => '3 prijave',
            4 => '4 prijave',
            5 => '5 prijava',
            11 => '11 prijava',
            12 => '12 prijava',
            21 => '21 prijava',
            22 => '22 prijave',
            25 => '25 prijava',
        ];
        $plans = [
            0 => 'planova',
            1 => 'plan',
            2 => 'plana',
            3 => 'plana',
            4 => 'plana',
            5 => 'planova',
            11 => 'planova',
            12 => 'planova',
            21 => 'plan',
            22 => 'plana',
            25 => 'planova',
        ];
        $days = [
            0 => '0 dana',
            1 => '1 dan',
            2 => '2 dana',
            3 => '3 dana',
            4 => '4 dana',
            5 => '5 dana',
            11 => '11 dana',
            12 => '12 dana',
            21 => '21 dan',
            22 => '22 dana',
            25 => '25 dana',
        ];

        foreach ($applications as $count => $expected) {
            $this->assertSame($expected, MmspDecisionInflection::applications($count), (string) $count);
        }
        foreach ($plans as $count => $expected) {
            $this->assertSame($expected, MmspDecisionInflection::plans($count), (string) $count);
        }
        foreach ($days as $count => $expected) {
            $this->assertSame($expected, MmspDecisionInflection::days($count), (string) $count);
        }
    }

    public function test_first_session_paragraph_uses_a_single_grammatical_form(): void
    {
        $text = MmspDecisionInflection::firstSessionParagraph('25.08.2026', 12, 11, 2, 5, 22);
        $this->assertStringContainsString('pristiglo ukupno 12 prijava', $text);
        $this->assertStringContainsString('prvobitno 11 prijava nepotpuno', $text);
        $this->assertStringContainsString('prihvatanju 2 prigovora ili odbijanju 5 prigovora', $text);
        $this->assertStringContainsString('22 prijave mogu biti uzete', $text);
        $this->assertStringNotContainsString('je/su', $text);
        $this->assertStringNotContainsString('prijava/e', $text);
        $this->assertStringNotContainsString('plan/ova', $text);
    }
}
