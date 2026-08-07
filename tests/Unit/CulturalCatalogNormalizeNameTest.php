<?php

namespace Tests\Unit;

use App\Models\CulturalCategory;
use App\Models\CulturalTag;
use PHPUnit\Framework\TestCase;

class CulturalCatalogNormalizeNameTest extends TestCase
{
    public function test_normalize_trims_and_lowercases_utf8(): void
    {
        $this->assertSame(
            'književne večeri',
            CulturalCategory::normalizeName('  Književne večeri  ')
        );
        $this->assertSame(
            'književne večeri',
            CulturalTag::normalizeName('  Književne večeri  ')
        );
    }

    public function test_normalize_empty_after_trim(): void
    {
        $this->assertSame('', CulturalCategory::normalizeName('   '));
        $this->assertSame('', CulturalTag::normalizeName('   '));
    }

    public function test_forbidden_category_name_is_case_insensitive(): void
    {
        $this->assertTrue(CulturalCategory::isForbiddenName('Nešto drugo'));
        $this->assertTrue(CulturalCategory::isForbiddenName('  NEŠTO DRUGO  '));
        $this->assertFalse(CulturalCategory::isForbiddenName('Nešto treće'));
    }
}
