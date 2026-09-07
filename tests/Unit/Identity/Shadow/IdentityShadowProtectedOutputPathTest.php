<?php

namespace Tests\Unit\Identity\Shadow;

use App\Identity\Shadow\IdentityShadowProtectedOutputPath;
use InvalidArgumentException;
use Tests\TestCase;

class IdentityShadowProtectedOutputPathTest extends TestCase
{
    public function test_protected_path_under_shadow_directory_is_accepted(): void
    {
        $path = IdentityShadowProtectedOutputPath::resolve(
            storage_path('app/private/identity-shadow/unit-aggregate.json')
        );

        $this->assertStringContainsString('identity-shadow', $path);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $path));
    }

    public function test_public_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityShadowProtectedOutputPath::resolve(public_path('shadow-aggregate.json'));
    }

    public function test_public_storage_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityShadowProtectedOutputPath::resolve(storage_path('app/public/shadow-aggregate.json'));
    }

    public function test_traversal_into_public_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityShadowProtectedOutputPath::resolve(
            'storage/app/private/identity-shadow/../../../public/shadow-aggregate.json'
        );
    }
}
