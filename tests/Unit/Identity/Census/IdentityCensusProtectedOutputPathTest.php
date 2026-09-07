<?php

namespace Tests\Unit\Identity\Census;

use App\Identity\Census\IdentityCensusProtectedOutputPath;
use InvalidArgumentException;
use Tests\TestCase;

class IdentityCensusProtectedOutputPathTest extends TestCase
{
    public function test_protected_path_under_census_directory_is_accepted(): void
    {
        $path = IdentityCensusProtectedOutputPath::resolve(
            storage_path('app/private/identity-census/unit-aggregate.json')
        );

        $this->assertStringContainsString('identity-census', $path);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $path));
    }

    public function test_public_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityCensusProtectedOutputPath::resolve(public_path('census-aggregate.json'));
    }

    public function test_public_storage_path_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityCensusProtectedOutputPath::resolve(storage_path('app/public/census-aggregate.json'));
    }

    public function test_traversal_into_public_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdentityCensusProtectedOutputPath::resolve(
            'storage/app/private/identity-census/../../../public/census-aggregate.json'
        );
    }
}
