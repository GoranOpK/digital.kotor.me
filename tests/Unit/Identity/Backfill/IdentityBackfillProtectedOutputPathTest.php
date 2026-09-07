<?php

namespace Tests\Unit\Identity\Backfill;

use App\Identity\Backfill\IdentityBackfillProtectedOutputPath;
use InvalidArgumentException;
use Tests\TestCase;

class IdentityBackfillProtectedOutputPathTest extends TestCase
{
    public function test_protected_path_under_backfill_directory_is_accepted(): void
    {
        $path = IdentityBackfillProtectedOutputPath::resolve(
            storage_path('app/private/identity-backfill/unit-aggregate.json')
        );

        $this->assertStringContainsString('identity-backfill', $path);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $path));
    }

    public function test_public_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityBackfillProtectedOutputPath::resolve(public_path('backfill-aggregate.json'));
    }

    public function test_public_storage_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityBackfillProtectedOutputPath::resolve(storage_path('app/public/backfill-aggregate.json'));
    }

    public function test_traversal_into_public_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityBackfillProtectedOutputPath::resolve(
            'storage/app/private/identity-backfill/../../../public/backfill-aggregate.json'
        );
    }
}
