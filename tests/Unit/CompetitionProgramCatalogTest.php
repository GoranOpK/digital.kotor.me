<?php

namespace Tests\Unit;

use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use PHPUnit\Framework\TestCase;

class CompetitionProgramCatalogTest extends TestCase
{
    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_zensko_and_omladinsko_active_canonical_development_not_public(): void
    {
        $definitions = CompetitionProgramCatalog::definitions();

        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, $definitions['omladinsko']['status']);
        $this->assertSame(
            'Konkurs za podršku preduzetništvu mladih — prijave, dokumentacija, komisija i evaluacija.',
            $definitions['omladinsko']['description']
        );
        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, $definitions['zensko']['status']);
        $this->assertSame(
            'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
            $definitions['zensko']['description']
        );

        $canonical = CompetitionProfileResolver::TYPE_CANONICAL;
        $this->assertArrayHasKey($canonical, $definitions);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $definitions[$canonical]['status']);
        $this->assertSame(
            'Podrška mikro, malim i srednjim preduzećima',
            $definitions[$canonical]['title']
        );
        $this->assertSame(
            'Profil je u implementaciji i još nije pušten korisnicima.',
            $definitions[$canonical]['description']
        );

        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('zensko'));
        $this->assertSame(['zensko', 'omladinsko'], CompetitionProgramCatalog::publiclyAvailableTypes());
        $this->assertStringNotContainsString(
            'Profil je u implementaciji i još nije pušten korisnicima.',
            $definitions['omladinsko']['description']
        );
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable($canonical));
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('ostalo'));
    }

    public function test_test_override_to_development_does_not_change_canonical_default_after_clear(): void
    {
        CompetitionProgramCatalog::overrideStatusForTests(
            'omladinsko',
            CompetitionProgramCatalog::STATUS_DEVELOPMENT
        );

        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertSame(['zensko'], CompetitionProgramCatalog::publiclyAvailableTypes());

        CompetitionProgramCatalog::clearTestOverrides();

        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_ACTIVE,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, CompetitionProgramCatalog::definitions()['zensko']['status']);
    }
}
