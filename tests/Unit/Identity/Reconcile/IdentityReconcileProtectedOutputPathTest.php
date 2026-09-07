<?php

namespace Tests\Unit\Identity\Reconcile;

use App\Identity\Reconcile\IdentityReconcileProtectedOutputPath;
use InvalidArgumentException;
use Tests\TestCase;

class IdentityReconcileProtectedOutputPathTest extends TestCase
{
    public function test_protected_path_under_reconcile_directory_is_accepted(): void
    {
        $path = IdentityReconcileProtectedOutputPath::resolve(
            storage_path('app/private/identity-reconcile/unit-aggregate.json')
        );

        $this->assertStringContainsString('identity-reconcile', $path);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $path));
    }

    public function test_public_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityReconcileProtectedOutputPath::resolve(public_path('reconcile-aggregate.json'));
    }

    public function test_public_storage_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityReconcileProtectedOutputPath::resolve(storage_path('app/public/reconcile-aggregate.json'));
    }

    public function test_traversal_into_public_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityReconcileProtectedOutputPath::resolve(
            'storage/app/private/identity-reconcile/../../../public/reconcile-aggregate.json'
        );
    }
}
