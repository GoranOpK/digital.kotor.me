<?php

namespace Tests\Unit\Identity;

use App\Identity\IdentityRollout;
use Tests\TestCase;

class IdentityRolloutTest extends TestCase
{
    public function test_defaults_are_off_when_env_keys_are_absent(): void
    {
        $phpunitXml = (string) file_get_contents(base_path('phpunit.xml'));
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_READ', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_WRITE', $phpunitXml);

        $this->assertNull(env('IDENTITY_CANONICAL_READ'));
        $this->assertNull(env('IDENTITY_CANONICAL_WRITE'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));

        $rollout = new IdentityRollout;

        $this->assertFalse($rollout->readsCanonical());
        $this->assertFalse($rollout->writesCanonical());
    }
}
