<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Patch 3: Obrazac 1a/1b title and label targets (static blade assertions).
 */
class Obrazac1a1bLabelTargetTest extends TestCase
{
    private string $createBlade;

    protected function setUp(): void
    {
        parent::setUp();
        $path = dirname(__DIR__, 2).'/resources/views/applications/create.blade.php';
        $this->assertFileExists($path);
        $this->createBlade = file_get_contents($path);
    }

    public function test_1a_title_target_in_php_and_js_headers(): void
    {
        $this->assertStringContainsString("'Obrazac 1a – preduzetnica'", $this->createBlade);
        $this->assertStringContainsString("headerLabel.textContent = knIsOmladinsko ? 'Obrazac M1a' : 'Obrazac 1a – preduzetnica'", $this->createBlade);
    }

    public function test_1b_title_target_in_php_and_js_headers(): void
    {
        $this->assertStringContainsString("'Obrazac 1b – privredno društvo'", $this->createBlade);
        $this->assertStringContainsString("headerLabel.textContent = knIsOmladinsko ? 'Obrazac M1b' : 'Obrazac 1b – privredno društvo'", $this->createBlade);
    }

    public function test_1b_uses_podnositeljke_prijave_not_nositeljke_biznisa(): void
    {
        $this->assertStringContainsString('Ime i prezime {{ $isOmladinsko ? \'podnosioca\' : \'podnositeljke\' }} prijave:', $this->createBlade);
        $this->assertStringNotContainsString('nositeljke\' }} biznisa', $this->createBlade);
        $this->assertStringNotContainsString('Ime i prezime nositeljke biznisa', $this->createBlade);
    }

    public function test_1b_shows_adresa_field_for_doo_address(): void
    {
        [$obrazac1b] = $this->extractBetween(
            $this->createBlade,
            '<!-- Obrazac 1b: Za DOO i Ostalo -->',
            "id=\"accuracy_declaration_1b\""
        );

        $this->assertStringContainsString('<label class="form-label">Adresa:</label>', $obrazac1b);
        $this->assertStringContainsString('name="doo_address"', $obrazac1b);
    }

    public function test_1b_founder_and_director_label_targets(): void
    {
        $this->assertStringContainsString('Ime i prezime osnivačice/osnivačica:', $this->createBlade);
        $this->assertStringContainsString('Ime i prezime izvršne direktorice:', $this->createBlade);
        $this->assertStringNotContainsString('*Osnivač/ica:', $this->createBlade);
        $this->assertStringNotContainsString('*Izvršni direktor/ica:', $this->createBlade);
    }

    public function test_1a_and_1b_registration_notes(): void
    {
        $this->assertStringContainsString('* Popunjava {{ $isOmladinsko ? \'registrovani preduzetnik\' : \'registrovana preduzetnica\' }}.', $this->createBlade);
        $this->assertStringContainsString('* Popunjava se samo ako je privredno društvo registrovano.', $this->createBlade);
    }

    public function test_1a_keeps_ime_prezime_and_jmbg_labels(): void
    {
        [$obrazac1a] = $this->extractBetween(
            $this->createBlade,
            '<!-- Obrazac 1a: Za Preduzetnice (PREDUZETNIK) -->',
            '<!-- Obrazac 1b: Za DOO i Ostalo -->'
        );

        $this->assertStringContainsString('<label class="form-label">Ime i prezime:</label>', $obrazac1a);
        $this->assertStringContainsString('<label class="form-label">JMBG: <span class="required">*</span></label>', $obrazac1a);
        $this->assertStringNotContainsString('Ime i prezime podnositeljke prijave', $obrazac1a);
        $this->assertStringNotContainsString('JMBG podnositeljke prijave', $obrazac1a);
    }

    /**
     * @return array{0: string}
     */
    private function extractBetween(string $text, string $start, string $end): array
    {
        $i = strpos($text, $start);
        $j = strpos($text, $end, $i !== false ? $i : 0);
        $this->assertNotFalse($i);
        $this->assertNotFalse($j);

        return [substr($text, $i, $j - $i)];
    }
}
