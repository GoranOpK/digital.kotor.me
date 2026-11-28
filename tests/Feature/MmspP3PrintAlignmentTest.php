<?php

namespace Tests\Feature;

use Tests\TestCase;

class MmspP3PrintAlignmentTest extends TestCase
{
    public function test_p3_print_compacts_the_admin_card_and_keeps_the_screen_total_blue(): void
    {
        $css = file_get_contents(resource_path('views/evaluation/partials/mmsp_p3_styles.blade.php'));
        $split = strpos($css, '@media print');
        $this->assertNotFalse($split);
        $screen = substr($css, 0, $split);
        $print = substr($css, $split);

        $this->assertStringContainsString('background: var(--primary);', $screen);
        $this->assertStringContainsString('color: #fff !important;', $screen);
        $this->assertStringNotContainsString('mmsp-p3-admin-card', $screen);
        $this->assertStringNotContainsString('#f3f4f6', $screen);

        $this->assertStringContainsString('.mmsp-p3 .mmsp-p3-admin-card {', $print);
        $this->assertStringContainsString('font-size: 9pt;', $print);
        $this->assertStringContainsString('padding: 4px 6px !important;', $print);
        $this->assertStringContainsString('margin-top: 4px !important;', $print);
        $this->assertStringContainsString('.mmsp-p3 .evaluation-table {', $print);
        $this->assertStringContainsString('page-break-inside: auto;', $print);
        $this->assertStringContainsString('display: table-header-group;', $print);
        $this->assertStringContainsString('.mmsp-p3 .evaluation-table ~ div[style*="margin-top: 16px"]', $print);
        $this->assertStringContainsString('background: #f3f4f6 !important;', $print);
        $this->assertStringContainsString('color: #000 !important;', $print);
        $this->assertStringContainsString('border: 1px solid #000 !important;', $print);
        $this->assertStringNotContainsString('background: var(--primary)', $print);

        $rows = strstr($print, '.mmsp-p3 .evaluation-table tr');
        $this->assertNotFalse($rows);
        $this->assertStringContainsString('page-break-inside: avoid;', $rows);
        $this->assertStringContainsString('break-inside: avoid;', $rows);
        $this->assertStringNotContainsString('page-break-inside: auto;', $rows);

        $admin = file_get_contents(resource_path('views/evaluation/partials/mmsp_administrative_review.blade.php'));
        $this->assertStringContainsString('class="mmsp-p3-admin-card"', $admin);
        $this->assertStringContainsString('$criterion[\'statement\']', $admin);
        $this->assertStringContainsString('MmspP3Form::NE_STAR_FOOTNOTE', $admin);
        $this->assertStringContainsString('Napomena', $admin);
        $this->assertStringContainsString('Rezultat:', $admin);

        $youth = file_get_contents(resource_path('views/evaluation/create.blade.php'));
        $this->assertStringContainsString('.form-section-compact div[style*="background"]', $youth);
        $this->assertStringNotContainsString('mmsp-p3-admin-card', $youth);
        $this->assertStringNotContainsString('#f3f4f6', $youth);
    }
}
