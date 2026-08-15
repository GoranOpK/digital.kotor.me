<?php

namespace Tests\Unit;

use App\Services\CulturalMedia\CulturalMediaFileValidator;
use App\Services\CulturalMedia\CulturalMediaImageProcessor;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

class CulturalMediaImageProcessorTest extends TestCase
{
    use CreatesCulturalMediaFixtures;

    private CulturalMediaImageProcessor $processor;

    private CulturalMediaFileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CulturalMediaImageProcessor;
        $this->validator = new CulturalMediaFileValidator;
    }

    public function test_small_image_is_kept_without_reencode(): void
    {
        $file = $this->uploadJpeg('small.jpg');
        $meta = $this->validator->validate($file);
        $processed = $this->processor->process($file->getRealPath(), $meta);

        $this->assertFalse($processed['resized']);
        $this->assertSame($this->fixtureJpeg1x1(), $processed['contents']);
        $this->assertSame('image/jpeg', $processed['mime']);
        $this->assertSame('jpeg', $processed['format']);
        $this->assertLessThan(800, $processed['duza_strana']);
    }

    public function test_png_and_webp_without_resize_keep_original_bytes_and_format(): void
    {
        $png = $this->uploadPng();
        $pngProcessed = $this->processor->process($png->getRealPath(), $this->validator->validate($png));
        $this->assertFalse($pngProcessed['resized']);
        $this->assertSame($this->fixturePng1x1(), $pngProcessed['contents']);
        $this->assertSame('image/png', $pngProcessed['mime']);

        $webp = $this->uploadWebp();
        $webpProcessed = $this->processor->process($webp->getRealPath(), $this->validator->validate($webp));
        $this->assertFalse($webpProcessed['resized']);
        $this->assertSame($this->fixtureWebp1x1(), $webpProcessed['contents']);
        $this->assertSame('image/webp', $webpProcessed['mime']);
    }

    public function test_landscape_over_1920_is_resized_proportionally(): void
    {
        $this->skipUnlessCanEncode('jpeg');
        $path = $this->writeGdImage(4000, 2000, 'jpeg');

        try {
            $meta = $this->metaFromPath($path, 'landscape.jpg', 'jpeg');
            $processed = $this->processor->process($path, $meta);

            $this->assertTrue($processed['resized']);
            $this->assertSame(1920, $processed['sirina']);
            $this->assertSame(960, $processed['visina']);
            $this->assertSame(1920, $processed['duza_strana']);
            $this->assertSame($processed['sirina'] * 2000, $processed['visina'] * 4000);
            $this->assertSame('image/jpeg', $processed['mime']);
            $this->assertSame('jpeg', $processed['format']);
            $this->assertSame($processed['velicina'], strlen($processed['contents']));
        } finally {
            @unlink($path);
        }
    }

    public function test_portrait_over_1920_is_resized_proportionally(): void
    {
        $this->skipUnlessCanEncode('jpeg');
        $path = $this->writeGdImage(2000, 4000, 'jpeg');

        try {
            $meta = $this->metaFromPath($path, 'portrait.jpg', 'jpeg');
            $processed = $this->processor->process($path, $meta);

            $this->assertTrue($processed['resized']);
            $this->assertSame(960, $processed['sirina']);
            $this->assertSame(1920, $processed['visina']);
            $this->assertSame(1920, $processed['duza_strana']);
        } finally {
            @unlink($path);
        }
    }

    public function test_exactly_1920_is_not_resized(): void
    {
        $this->skipUnlessCanEncode('jpeg');
        $path = $this->writeGdImage(1920, 800, 'jpeg');
        $original = (string) file_get_contents($path);

        try {
            $meta = $this->metaFromPath($path, 'exact.jpg', 'jpeg');
            $processed = $this->processor->process($path, $meta);

            $this->assertFalse($processed['resized']);
            $this->assertSame($original, $processed['contents']);
            $this->assertSame(1920, $processed['sirina']);
            $this->assertSame(800, $processed['visina']);
        } finally {
            @unlink($path);
        }
    }

    public function test_below_1920_is_not_upscaled(): void
    {
        $this->skipUnlessCanEncode('jpeg');
        $path = $this->writeGdImage(800, 600, 'jpeg');
        $original = (string) file_get_contents($path);

        try {
            $meta = $this->metaFromPath($path, 'below.jpg', 'jpeg');
            $processed = $this->processor->process($path, $meta);

            $this->assertFalse($processed['resized']);
            $this->assertSame($original, $processed['contents']);
            $this->assertSame(800, $processed['sirina']);
            $this->assertSame(600, $processed['visina']);
        } finally {
            @unlink($path);
        }
    }

    public function test_png_resize_preserves_format_and_transparency(): void
    {
        $this->skipUnlessCanEncode('png');
        $path = $this->writeTransparentPng(2000, 1000);

        try {
            $meta = $this->metaFromPath($path, 'alpha.png', 'png');
            $processed = $this->processor->process($path, $meta);

            $this->assertTrue($processed['resized']);
            $this->assertSame('image/png', $processed['mime']);
            $this->assertSame('png', $processed['format']);
            $this->assertSame(1920, $processed['sirina']);
            $this->assertSame(960, $processed['visina']);

            $image = imagecreatefromstring($processed['contents']);
            $this->assertNotFalse($image);
            $rgba = imagecolorat($image, 0, 0);
            $alpha = ($rgba & 0x7F000000) >> 24;
            imagedestroy($image);
            $this->assertGreaterThan(0, $alpha);
        } finally {
            @unlink($path);
        }
    }

    public function test_webp_resize_preserves_format_when_runtime_supports_it(): void
    {
        $this->skipUnlessCanEncode('webp');
        $path = $this->writeGdImage(2000, 1000, 'webp');

        try {
            $meta = $this->metaFromPath($path, 'wide.webp', 'webp');
            $processed = $this->processor->process($path, $meta);

            $this->assertTrue($processed['resized']);
            $this->assertSame('image/webp', $processed['mime']);
            $this->assertSame('webp', $processed['format']);
            $this->assertSame(1920, $processed['sirina']);
            $this->assertSame(960, $processed['visina']);
        } finally {
            @unlink($path);
        }
    }

    private function skipUnlessCanEncode(string $format): void
    {
        if (! $this->processor->canEncode($format)) {
            $this->markTestSkipped(
                'GD funkcije za format '.$format.' nisu dostupne u test runtime-u.'
            );
        }
    }

    /**
     * @return array{mime: string, format: string, sirina: int, visina: int, velicina: int, originalni_naziv: string, ekstenzija: string}
     */
    private function metaFromPath(string $path, string $name, string $format): array
    {
        $info = getimagesize($path);
        $this->assertNotFalse($info);

        $mime = match ($format) {
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        };

        return [
            'mime' => $mime,
            'format' => $format,
            'sirina' => (int) $info[0],
            'visina' => (int) $info[1],
            'velicina' => (int) filesize($path),
            'originalni_naziv' => $name,
            'ekstenzija' => $format === 'jpeg' ? 'jpg' : $format,
        ];
    }

    private function writeGdImage(int $width, int $height, string $format): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 40, 80, 160);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        $path = tempnam(sys_get_temp_dir(), 'med-i1-');
        $this->assertNotFalse($path);

        $ok = match ($format) {
            'jpeg' => imagejpeg($image, $path, 90),
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, 90),
        };
        imagedestroy($image);
        $this->assertTrue($ok !== false);

        return $path;
    }

    private function writeTransparentPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, $width, $height, $transparent);

        $path = tempnam(sys_get_temp_dir(), 'med-i1-png-');
        $this->assertNotFalse($path);
        $this->assertTrue(imagepng($image, $path) !== false);
        imagedestroy($image);

        return $path;
    }
}
