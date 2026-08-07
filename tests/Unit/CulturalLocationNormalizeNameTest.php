<?php

namespace Tests\Unit;

use App\Models\CulturalLocation;
use PHPUnit\Framework\TestCase;

class CulturalLocationNormalizeNameTest extends TestCase
{
    public function test_normalize_trims_and_lowercases(): void
    {
        $this->assertSame(
            'trg od oružja',
            CulturalLocation::normalizeName('  Trg od Oružja  ')
        );
    }

    public function test_normalize_empty_after_trim(): void
    {
        $this->assertSame('', CulturalLocation::normalizeName('   '));
    }
}
