<?php

namespace Tests\Unit;

use App\Support\CompetitionProgramCatalog;
use PHPUnit\Framework\TestCase;

class CompetitionProgramCatalogTest extends TestCase
{
    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_omladinsko_defaults_to_development_and_is_not_public(): void
    {
        $definitions = CompetitionProgramCatalog::definitions();

        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $definitions['omladinsko']['status']);
        $this->assertSame(
            'Profil je u implementaciji i još nije pušten korisnicima.',
            $definitions['omladinsko']['description']
        );
        $this->assertSame(CompetitionProgramCatalog::STATUS_ACTIVE, $definitions['zensko']['status']);
        $this->assertSame(
            'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
            $definitions['zensko']['description']
        );
        $this->assertSame(['zensko'], CompetitionProgramCatalog::publiclyAvailableTypes());
        $this->assertFalse(CompetitionProgramCatalog::isPubliclyAvailable('omladinsko'));
        $this->assertTrue(CompetitionProgramCatalog::isPubliclyAvailable('zensko'));
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
