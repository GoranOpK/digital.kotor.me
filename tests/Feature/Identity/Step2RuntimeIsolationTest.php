<?php

namespace Tests\Feature\Identity;

use Tests\TestCase;

class Step2RuntimeIsolationTest extends TestCase
{
    public function test_phpunit_xml_does_not_enable_canonical_authority(): void
    {
        $phpunitXml = (string) file_get_contents(base_path('phpunit.xml'));
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_READ', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_WRITE', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_WRITE_FREEZE', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_EP_IDENTITY_FLOWS', $phpunitXml);
    }

    public function test_deploy_defaults_keep_legacy_authority(): void
    {
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.identity_write_freeze'));
        $this->assertSame(false, config('identity.ep_identity_flows'));
    }
}
