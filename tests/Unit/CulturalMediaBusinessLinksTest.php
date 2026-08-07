<?php

namespace Tests\Unit;

use App\Models\CulturalMedia;
use Tests\TestCase;

class CulturalMediaBusinessLinksTest extends TestCase
{
    public function test_korak_1_has_no_business_links(): void
    {
        $media = new CulturalMedia;

        $this->assertFalse($media->hasBusinessLinks());
        $this->assertTrue($media->canBePermanentlyDeleted());
        $this->assertSame(0, $media->businessLinkCount());
    }

    public function test_cannot_delete_when_business_links_exist(): void
    {
        $media = new class extends CulturalMedia
        {
            public function businessLinkCount(): int
            {
                return 2;
            }
        };

        $this->assertTrue($media->hasBusinessLinks());
        $this->assertFalse($media->canBePermanentlyDeleted());
    }
}
