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

    public function test_complete_sentences_agree_for_each_required_count(): void
    {
        $received = [
            0 => 'da nije pristigla nijedna prijava',
            1 => 'da je pristigla ukupno 1 prijava',
            2 => 'da su pristigle ukupno 2 prijave',
            4 => 'da su pristigle ukupno 4 prijave',
            5 => 'da je pristiglo ukupno 5 prijava',
            11 => 'da je pristiglo ukupno 11 prijava',
            12 => 'da je pristiglo ukupno 12 prijava',
            21 => 'da je pristigla ukupno 21 prijava',
            22 => 'da su pristigle ukupno 22 prijave',
            25 => 'da je pristiglo ukupno 25 prijava',
        ];
        $incomplete = [
            0 => 'konstatovala da prvobitno nepotpunih prijava nema',
            1 => 'konstatovala da je prvobitno 1 prijava nepotpuna u smislu priložene dokumentacije, te ista nije dalje razmatrana',
            2 => 'konstatovala da su prvobitno 2 prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            4 => 'konstatovala da su prvobitno 4 prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            5 => 'konstatovala da je prvobitno 5 prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            11 => 'konstatovala da je prvobitno 11 prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            12 => 'konstatovala da je prvobitno 12 prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            21 => 'konstatovala da je prvobitno 21 prijava nepotpuna u smislu priložene dokumentacije, te ista nije dalje razmatrana',
            22 => 'konstatovala da su prvobitno 22 prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            25 => 'konstatovala da je prvobitno 25 prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
        ];
        $proceed = [
            0 => 'nijedna prijava ne može biti uzeta',
            1 => '1 prijava može biti uzeta',
            2 => '2 prijave mogu biti uzete',
            4 => '4 prijave mogu biti uzete',
            5 => '5 prijava može biti uzeto',
            11 => '11 prijava može biti uzeto',
            12 => '12 prijava može biti uzeto',
            21 => '21 prijava može biti uzeta',
            22 => '22 prijave mogu biti uzete',
            25 => '25 prijava može biti uzeto',
        ];

        foreach ($received as $count => $expected) {
            $text = MmspDecisionInflection::firstSessionParagraph('25.08.2026', $count, $count, 0, 0, $count);
            $this->assertStringContainsString($expected, $text, 'received '.$count);
            $this->assertStringContainsString($incomplete[$count], $text, 'incomplete '.$count);
            $this->assertStringContainsString($proceed[$count], $text, 'proceed '.$count);
            $this->assertStringContainsString('Prigovora nije bilo, te Komisija nije donijela odluku o prihvatanju ili odbijanju prigovora.', $text);
            $this->assertStringNotContainsString('0 prigovora', $text, (string) $count);
        }
    }

    public function test_appeal_sentence_mentions_only_decisions_that_exist(): void
    {
        $acceptedOnly = MmspDecisionInflection::firstSessionParagraph('25.08.2026', 1, 1, 1, 0, 1);
        $this->assertStringContainsString('Komisija je donijela odluku o prihvatanju 1 prigovora.', $acceptedOnly);
        $this->assertStringNotContainsString('odbijanju', $acceptedOnly);

        $rejectedOnly = MmspDecisionInflection::firstSessionParagraph('25.08.2026', 2, 2, 0, 4, 2);
        $this->assertStringContainsString('Komisija je donijela odluku o odbijanju 4 prigovora.', $rejectedOnly);
        $this->assertStringNotContainsString('prihvatanju', $rejectedOnly);

        $both = MmspDecisionInflection::firstSessionParagraph('25.08.2026', 21, 11, 21, 12, 22);
        $this->assertStringContainsString('pristigla ukupno 21 prijava', $both);
        $this->assertStringContainsString('prvobitno 11 prijava nepotpuno', $both);
        $this->assertStringContainsString('Komisija je donijela odluku o prihvatanju 21 prigovora i odbijanju 12 prigovora.', $both);
        $this->assertStringContainsString('22 prijave mogu biti uzete', $both);
        $this->assertStringNotContainsString('0 prigovora', $both);
    }
}
