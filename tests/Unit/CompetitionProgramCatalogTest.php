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

    public function test_zensko_active_omladinsko_and_canonical_development_not_public(): void
    {
        $definitions = CompetitionProgramCatalog::definitions();

        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, $definitions['zensko']['status']);
        $this->assertSame(
            'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
            $definitions['zensko']['description']
        );
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $definitions['omladinsko']['status']);
        $this->assertSame(
            'Profil je u implementaciji i još nije pušten korisnicima.',
            $definitions['omladinsko']['description']
        );

        $canonical = CompetitionProfileResolver::TYPE_CANONICAL;
        $this->assertArrayHasKey($canonical, $definitions);
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $definitions[$canonical]['status']);
        $this->assertSame(
            'Preduzetnici mikro, mala i srednja preduzeća',
            $definitions[$canonical]['title']
        );
        $this->assertSame(
            'Profil je u implementaciji i još nije pušten korisnicima.',
            $definitions[$canonical]['description']
        );

        $this->assertSame(['zensko'], CompetitionProgramCatalog::publiclyAvailableTypes());
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('zensko'));
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable($canonical));
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('ostalo'));
    }

    public function test_test_override_to_active_does_not_change_canonical_default_after_clear(): void
    {
        CompetitionProgramCatalog::overrideStatusForTests(
            'omladinsko',
            CompetitionProgramCatalog::STATUS_ACTIVE
        );

        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertSame(['zensko', 'omladinsko'], CompetitionProgramCatalog::publiclyAvailableTypes());

        CompetitionProgramCatalog::clearTestOverrides();

        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }
}
