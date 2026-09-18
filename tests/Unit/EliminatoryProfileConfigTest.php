<?php

namespace Tests\Unit;

use App\Support\EliminatoryProfileConfig;
use PHPUnit\Framework\TestCase;

/**
 * Patch 10: ŽP default EliminatoryProfileConfig criterion 3 uses Article 11.
 */
class EliminatoryProfileConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Integration worktree may share vendor with another checkout; ensure this
        // worktree's class file is loaded for App\Support\EliminatoryProfileConfig.
        if (! class_exists(EliminatoryProfileConfig::class, false)) {
            require_once dirname(__DIR__, 2).'/app/Support/EliminatoryProfileConfig.php';
        }
    }

    public function test_default_zp_criterion_3_statement_uses_article_11(): void
    {
        $profile = EliminatoryProfileConfig::for(null);
        $statement = $profile->statement(3);

        $this->assertStringContainsString('članu 11 Odluke', $statement);
        $this->assertStringNotContainsString('članu 10 Odluke', $statement);
        $this->assertSame(
            'Biznis plan je vezan za prioritetne oblasti navedene u članu 11 Odluke?',
            $statement
        );
    }

    public function test_default_zp_profile_for_zensko_type_also_uses_article_11(): void
    {
        $profile = EliminatoryProfileConfig::for('zensko');
        $this->assertStringContainsString('članu 11 Odluke', $profile->statement(3));
        $this->assertStringNotContainsString('članu 10 Odluke', $profile->statement(3));
    }

    public function test_omladinsko_criterion_3_statement_unchanged(): void
    {
        $profile = EliminatoryProfileConfig::for('omladinsko');
        $statement = $profile->statement(3);

        $this->assertStringContainsString('člana 12 Odluke o mladima', $statement);
        $this->assertStringNotContainsString('članu 11 Odluke', $statement);
        $this->assertStringNotContainsString('članu 10 Odluke', $statement);
    }
}
