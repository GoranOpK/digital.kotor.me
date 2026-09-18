<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Patch 4: Obrazac 2 note / Q4 label targets (static blade assertions).
 */
class Obrazac2BusinessPlanLabelTargetTest extends TestCase
{
    private string $bpBlade;

    private string $applicationCreateBlade;

    private const TARGET_NOTE = 'Ukoliko podnositeljka prijave u trenutku podnošenja prijave nema registrovanu djelatnost, a sredstva joj budu odobrena, dužna je da prije zaključenja ugovora o dodjeli sredstava izvrši registraciju preduzetnice, odnosno osnuje i registruje privredno društvo, u skladu sa oblikom obavljanja djelatnosti navedenim u prijavi, i dostavi dokaz o registraciji kod nadležnog organa, dokaz o poreskoj registraciji i dokaz o otvorenom poslovnom računu.';

    private const TARGET_Q4 = 'Ime i prezime preduzetnice i trgovački naziv za oblik registracije ‘Preduzetnik’, odnosno naziv privrednog društva:';

    protected function setUp(): void
    {
        parent::setUp();
        $root = dirname(__DIR__, 2);
        $bpPath = $root.'/resources/views/business-plans/create.blade.php';
        $appPath = $root.'/resources/views/applications/create.blade.php';
        $this->assertFileExists($bpPath);
        $this->assertFileExists($appPath);
        $this->bpBlade = file_get_contents($bpPath);
        $this->applicationCreateBlade = file_get_contents($appPath);
    }

    public function test_unregistered_applicant_note_matches_target_on_obrazac_2_and_1(): void
    {
        $this->assertStringContainsString(self::TARGET_NOTE, $this->bpBlade);
        $this->assertStringContainsString(self::TARGET_NOTE, $this->applicationCreateBlade);
        $this->assertStringContainsString('id="napomenaNemaRegistraciju"', $this->bpBlade);
        $this->assertStringContainsString('id="fizickoLiceNotice"', $this->applicationCreateBlade);
    }

    public function test_q4_company_name_label_matches_target(): void
    {
        $this->assertStringContainsString(self::TARGET_Q4, $this->bpBlade);
        $this->assertStringContainsString("companyNameLabelTarget = '".self::TARGET_Q4."'", $this->bpBlade);
    }

    public function test_old_q4_nositeljke_biznisa_wording_is_gone_from_obrazac_2(): void
    {
        $this->assertStringNotContainsString('ime i prezime nositeljke biznisa', $this->bpBlade);
        $this->assertStringNotContainsString('nositeljke biznisa*', $this->bpBlade);
        $this->assertStringNotContainsString('naziv društva za oblik registracije "DOO"', $this->bpBlade);
    }

    public function test_nositeljka_biznisa_footnote_is_removed(): void
    {
        $this->assertStringNotContainsString('*Nositeljka biznisa je', $this->bpBlade);
        $this->assertStringNotContainsString('Nositeljka biznisa je osnivačica', $this->bpBlade);
    }

    public function test_electronic_obrazac_2_has_no_potpis_field(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\bPotpis\s*:/', $this->bpBlade);
        $this->assertStringNotContainsString('name="potpis"', $this->bpBlade);
        $this->assertStringNotContainsString('name="signature"', $this->bpBlade);
    }
}
