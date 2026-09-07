<?php

namespace Tests\Unit\Identity\Shadow;

use App\Identity\Shadow\IdentityShadowException;
use App\Identity\Shadow\IdentityShadowFlow;
use App\Identity\Shadow\IdentityShadowScope;
use Tests\TestCase;

class IdentityShadowScopeTest extends TestCase
{
    public function test_omitted_scope_is_full_five_flow(): void
    {
        $this->assertSame(IdentityShadowScope::FULL, IdentityShadowScope::resolve(null));
        $this->assertSame(IdentityShadowFlow::REQUIRED, IdentityShadowScope::requiredFlows(IdentityShadowScope::FULL));
        $this->assertTrue(IdentityShadowScope::includesCatalog(IdentityShadowScope::FULL));
        $this->assertSame([], IdentityShadowScope::deferredGates(IdentityShadowScope::FULL));
        $this->assertContains(IdentityShadowFlow::EP_AVAILABILITY, IdentityShadowScope::requiredFlows(IdentityShadowScope::FULL));
        $this->assertCount(5, IdentityShadowScope::requiredFlows(IdentityShadowScope::FULL));
    }

    public function test_explicit_full_is_full_five_flow(): void
    {
        $this->assertSame(IdentityShadowScope::FULL, IdentityShadowScope::resolve('full'));
    }

    public function test_active_identity_wave_excludes_ep_and_records_open_gate(): void
    {
        $scope = IdentityShadowScope::resolve('active-identity-wave');
        $this->assertSame(IdentityShadowScope::ACTIVE_IDENTITY_WAVE, $scope);
        $this->assertSame(IdentityShadowFlow::ACTIVE_IDENTITY_WAVE, IdentityShadowScope::requiredFlows($scope));
        $this->assertFalse(IdentityShadowScope::includesCatalog($scope));
        $this->assertNotContains(IdentityShadowFlow::EP_AVAILABILITY, IdentityShadowScope::requiredFlows($scope));
        $this->assertCount(4, IdentityShadowScope::requiredFlows($scope));
        $gate = IdentityShadowScope::deferredGates($scope)[IdentityShadowFlow::EP_AVAILABILITY];
        $this->assertSame('OPEN', $gate['status']);
        $this->assertSame('ep_module_undeployed', $gate['reason']);
        $this->assertSame('earliest_of', $gate['required_before']['mode']);
        $this->assertSame(
            ['ep_production_activation', 'canonical_writer_authority'],
            $gate['required_before']['events']
        );
    }

    public function test_invalid_scope_fails_closed(): void
    {
        $this->expectException(IdentityShadowException::class);
        IdentityShadowScope::resolve('skip-ep');
    }

    public function test_empty_scope_string_fails_closed(): void
    {
        $this->expectException(IdentityShadowException::class);
        IdentityShadowScope::resolve('');
    }
}
