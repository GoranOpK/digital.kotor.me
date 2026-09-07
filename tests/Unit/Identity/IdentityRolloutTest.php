<?php

namespace Tests\Unit\Identity;

use App\Identity\IdentityRollout;
use App\Identity\Runtime\IdentityMutationGuard;
use Tests\TestCase;

class IdentityRolloutTest extends TestCase
{
    public function test_defaults_are_off_when_env_keys_are_absent(): void
    {
        $phpunitXml = (string) file_get_contents(base_path('phpunit.xml'));
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_READ', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_CANONICAL_WRITE', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_WRITE_FREEZE', $phpunitXml);
        $this->assertStringNotContainsString('IDENTITY_EP_IDENTITY_FLOWS', $phpunitXml);

        $this->assertNull(env('IDENTITY_CANONICAL_READ'));
        $this->assertNull(env('IDENTITY_CANONICAL_WRITE'));
        $this->assertNull(env('IDENTITY_WRITE_FREEZE'));
        $this->assertNull(env('IDENTITY_EP_IDENTITY_FLOWS'));
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
        $this->assertSame(false, config('identity.identity_write_freeze'));
        $this->assertSame(false, config('identity.ep_identity_flows'));

        $rollout = new IdentityRollout;

        $this->assertFalse($rollout->readsCanonical());
        $this->assertFalse($rollout->writesCanonical());
        $this->assertFalse($rollout->identityWriteFrozen());
        $this->assertFalse($rollout->epIdentityFlowsEnabled());
    }

    public function test_canonical_http_write_requires_read_and_write(): void
    {
        $guard = new IdentityMutationGuard;

        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => false,
            'identity.identity_write_freeze' => false,
        ]);
        $this->assertFalse($guard->canonicalHttpWriteAllowed());
        $this->assertTrue($guard->legacyIdentityMutationAllowed());
        $this->assertTrue($guard->subjectCreationAllowed());

        config([
            'identity.canonical_read' => false,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
        $this->assertFalse($guard->canonicalHttpWriteAllowed());
        $this->assertFalse($guard->legacyIdentityMutationAllowed());
        $this->assertFalse($guard->subjectCreationAllowed());

        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => false,
            'identity.identity_write_freeze' => false,
        ]);
        $this->assertFalse($guard->canonicalHttpWriteAllowed());
        $this->assertTrue($guard->legacyIdentityMutationAllowed());
        $this->assertTrue($guard->subjectCreationAllowed());

        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => true,
        ]);
        $this->assertTrue($guard->canonicalHttpWriteAllowed());
        $this->assertFalse($guard->legacyIdentityMutationAllowed());
        $this->assertTrue($guard->subjectCreationAllowed());
    }
}
