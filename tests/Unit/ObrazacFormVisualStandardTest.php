<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Obrazac M1b visual standard: shared partial + M2 table action columns.
 */
class ObrazacFormVisualStandardTest extends TestCase
{
    private string $sharedCss;

    private string $sharedPartial;

    private string $m1Blade;

    private string $m2Blade;

    private string $report4Blade;

    private string $report4aBlade;

    private string $zaglavljePartial;

    protected function setUp(): void
    {
        parent::setUp();
        $root = dirname(__DIR__, 2);
        $this->sharedCss = file_get_contents($root.'/resources/css/obrazac-form.css');
        $this->sharedPartial = file_get_contents($root.'/resources/views/partials/obrazac-form-styles.blade.php');
        $this->m1Blade = file_get_contents($root.'/resources/views/applications/create.blade.php');
        $this->m2Blade = file_get_contents($root.'/resources/views/business-plans/create.blade.php');
        $this->report4Blade = file_get_contents($root.'/resources/views/reports/create.blade.php');
        $this->report4aBlade = file_get_contents($root.'/resources/views/reports/create-financial.blade.php');
        $this->zaglavljePartial = file_get_contents($root.'/resources/views/partials/obrazac-zaglavlje.blade.php');
    }

    public function test_shared_css_carries_m1b_tokens(): void
    {
        $this->assertStringContainsString('--primary: #0B3D91;', $this->sharedCss);
        $this->assertStringContainsString('--obrazac-label: #374151;', $this->sharedCss);
        $this->assertStringContainsString('--obrazac-text: #111;', $this->sharedCss);
        $this->assertStringContainsString('--obrazac-control-padding: 10px 14px;', $this->sharedCss);
        $this->assertStringContainsString('--obrazac-radius-control: 8px;', $this->sharedCss);
        $this->assertStringContainsString('.obrazac-form-page .dynamic-table .col-actions', $this->sharedCss);
        $this->assertStringContainsString('border: 1px solid #ccc;', $this->sharedCss);
    }

    public function test_blade_partial_mirrors_shared_css_and_is_included(): void
    {
        $this->assertStringContainsString('--primary: #0B3D91;', $this->sharedPartial);
        $this->assertStringContainsString("@include('partials.obrazac-form-styles')", $this->m1Blade);
        $this->assertStringContainsString("@include('partials.obrazac-form-styles')", $this->m2Blade);
        $this->assertStringContainsString('obrazac-form-page', $this->m1Blade);
        $this->assertStringContainsString('obrazac-form-page', $this->m2Blade);
    }

    public function test_m1_no_longer_duplicates_root_brand_block(): void
    {
        $this->assertStringNotContainsString('--primary: #0B3D91;', $this->m1Blade);
        $this->assertStringNotContainsString('.form-control {'."\n".'        width: 100%;'."\n".'        padding: 10px 14px;', $this->m1Blade);
    }

    public function test_m2_does_not_force_print_text_to_pure_black(): void
    {
        $this->assertStringNotContainsString('color: #000 !important;', $this->m2Blade);
        $this->assertStringNotContainsString('color: #000;', $this->m2Blade);
        $this->assertStringContainsString('color: var(--obrazac-text)', $this->m2Blade);
        $this->assertStringContainsString('color: var(--obrazac-label)', $this->m2Blade);
    }

    public function test_m2_tables_22_24_25_use_col_actions_without_surplus_colspan_six(): void
    {
        $this->assertStringContainsString('id="fundingSourcesTable"', $this->m2Blade);
        $this->assertStringContainsString('id="revenueProjectionTable"', $this->m2Blade);
        $this->assertStringContainsString('id="expenseProjectionTable"', $this->m2Blade);

        $this->assertMatchesRegularExpression(
            '/id="fundingSourcesTable"[\s\S]*?<th class="col-actions"><\/th>/',
            $this->m2Blade
        );
        $this->assertMatchesRegularExpression(
            '/id="revenueProjectionTable"[\s\S]*?<th class="col-actions"><\/th>/',
            $this->m2Blade
        );
        $this->assertMatchesRegularExpression(
            '/id="expenseProjectionTable"[\s\S]*?<th class="col-actions"><\/th>/',
            $this->m2Blade
        );

        $this->assertStringContainsString('colspan="5">Investicioni troškovi', $this->m2Blade);
        $this->assertStringContainsString('colspan="5">Tekući troškovi', $this->m2Blade);
        $this->assertStringNotContainsString('colspan="6">Investicioni troškovi', $this->m2Blade);
        $this->assertStringNotContainsString('colspan="6">Tekući troškovi', $this->m2Blade);
        $this->assertStringContainsString("actionCell.className = 'col-actions';", $this->m2Blade);
    }

    public function test_reports_4_and_4a_use_shared_zaglavlje_not_local_page_header(): void
    {
        $this->assertStringContainsString('class="obrazac-zaglavlje"', $this->zaglavljePartial);
        $this->assertStringContainsString('obrazac-naslov-prijava', $this->zaglavljePartial);

        foreach ([$this->report4Blade, $this->report4aBlade] as $blade) {
            $this->assertStringContainsString("@include('partials.obrazac-form-styles')", $blade);
            $this->assertStringContainsString("@include('partials.obrazac-zaglavlje'", $blade);
            $this->assertStringContainsString('obrazac-form-page', $blade);
            $this->assertStringNotContainsString('page-header', $blade);
            $this->assertStringNotContainsString('class="page-header"', $blade);
        }

        $this->assertStringContainsString("'obrazacLabel' => 'Obrazac 4'", $this->report4Blade);
        $this->assertStringContainsString("'obrazacTitle' => 'IZVJEŠTAJ O REALIZACIJI BIZNIS PLANA'", $this->report4Blade);
        $this->assertStringContainsString("'obrazacLabel' => 'Obrazac 4a'", $this->report4aBlade);
        $this->assertStringContainsString("'obrazacTitle' => 'FINANSIJSKI IZVJEŠTAJ'", $this->report4aBlade);
    }
}
