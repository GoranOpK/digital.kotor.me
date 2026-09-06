<?php

namespace Tests\Unit\Identity\Verify;

use App\Identity\Verify\IdentityVerifyProtectedOutputPath;
use InvalidArgumentException;
use Tests\TestCase;

class IdentityVerifyProtectedOutputPathTest extends TestCase
{
    public function test_protected_path_under_verify_directory_is_accepted(): void
    {
        $path = IdentityVerifyProtectedOutputPath::resolve(
            storage_path('app/private/identity-verify/unit-aggregate.json')
        );

        $this->assertStringContainsString('identity-verify', $path);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $path));
    }

    public function test_public_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityVerifyProtectedOutputPath::resolve(public_path('verify-aggregate.json'));
    }

    public function test_public_storage_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityVerifyProtectedOutputPath::resolve(storage_path('app/public/verify-aggregate.json'));
    }

    public function test_traversal_into_public_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityVerifyProtectedOutputPath::resolve(
            'storage/app/private/identity-verify/../../../public/verify-aggregate.json'
        );
    }
}
