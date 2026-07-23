<?php

namespace Tests\Unit;

use App\Services\DocumentImageFingerprint;
use App\Services\DocumentImageFingerprintException;
use Tests\TestCase;

class DocumentImageFingerprintTest extends TestCase
{
    private DocumentImageFingerprint $fingerprint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fingerprint = new DocumentImageFingerprint;
    }

    public function test_same_pixels_different_png_metadata_match(): void
    {
        $this->requireImagick();

        $a = $this->tempPng($this->fixturePngRed1x1());
        $b = $this->tempPng($this->pngWithTextChunk($this->fixturePngRed1x1(), 'Comment', 'meta-b'));

        $this->assertNotSame(hash_file('sha256', $a), hash_file('sha256', $b));
        $this->assertSame(
            $this->fingerprint->fingerprint($a),
            $this->fingerprint->fingerprint($b)
        );
    }

    public function test_different_pixels_same_dimensions_differ(): void
    {
        $this->requireImagick();

        $a = $this->tempPng($this->fixturePngRed1x1());
        $b = $this->tempPng($this->fixturePngBlue1x1());

        $this->assertNotSame(
            $this->fingerprint->fingerprint($a),
            $this->fingerprint->fingerprint($b)
        );
    }

    public function test_chunk_size_does_not_change_fingerprint(): void
    {
        $this->requireImagick();

        $path = $this->tempPng($this->fixturePngRed1x1());

        $one = $this->fingerprint->fingerprint($path, 1);
        $eight = $this->fingerprint->fingerprint($path, 8);
        $sixteen = $this->fingerprint->fingerprint($path, 16);

        $this->assertSame($one, $eight);
        $this->assertSame($eight, $sixteen);
    }

    public function test_transparent_png_flattens_to_white_matches_opaque_white(): void
    {
        $this->requireImagick();

        $transparent = $this->tempPng($this->buildRgbaPngViaImagick(4, 4, 255, 255, 255, 0));
        $opaqueWhite = $this->tempPng($this->buildRgbaPngViaImagick(4, 4, 255, 255, 255, 255));

        $this->assertSame(
            $this->fingerprint->fingerprint($transparent),
            $this->fingerprint->fingerprint($opaqueWhite)
        );
    }

    public function test_exif_orientation_uses_final_dimensions(): void
    {
        $this->requireImagick();

        $path = $this->tempJpegWithOrientation6();
        $key = $this->fingerprint->fingerprint($path);

        // Orientation 6 rotates 90° CW: stored 20x10 → displayed 10x20
        $this->assertMatchesRegularExpression('/^10x20:[a-f0-9]{64}$/', $key);
    }

    public function test_corrupt_image_throws_controlled_exception(): void
    {
        $this->requireImagick();

        $path = $this->tempPng("\x89PNG\r\n\x1a\n".str_repeat('x', 64));

        try {
            $this->fingerprint->fingerprint($path);
            $this->fail('Expected DocumentImageFingerprintException');
        } catch (DocumentImageFingerprintException $e) {
            $this->assertStringContainsString('nije validna', $e->getMessage());
        }
    }

    public function test_oversized_dimensions_are_rejected(): void
    {
        $this->requireImagick();

        $path = tempnam(sys_get_temp_dir(), 'bigimg_');
        $this->assertNotFalse($path);
        $path .= '.png';

        $img = new \Imagick();
        $img->newImage(DocumentImageFingerprint::MAX_SIDE + 1, 10, new \ImagickPixel('red'));
        $img->setImageFormat('png');
        $img->writeImage($path);
        $img->clear();
        $img->destroy();

        try {
            $this->fingerprint->fingerprint($path);
            $this->fail('Expected DocumentImageFingerprintException');
        } catch (DocumentImageFingerprintException $e) {
            $this->assertStringContainsString('nedozvoljene dimenzije', $e->getMessage());
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_imagick_throws_unavailable_message(): void
    {
        if (extension_loaded('imagick')) {
            $this->markTestSkipped('Only meaningful when Imagick is absent');
        }

        $path = $this->tempPng($this->fixturePngRed1x1());

        $this->expectException(DocumentImageFingerprintException::class);
        $this->expectExceptionMessage('Validacija slike trenutno nije dostupna');
        $this->fingerprint->fingerprint($path);
    }

    private function requireImagick(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick required');
        }
    }

    private function tempPng(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fp_');
        $this->assertNotFalse($path);
        $path .= '.png';
        file_put_contents($path, $bytes);
        $this->beforeApplicationDestroyed(static function () use ($path) {
            @unlink($path);
        });

        return $path;
    }

    private function fixturePngRed1x1(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
    }

    private function fixturePngBlue1x1(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==',
            true
        );
    }

    private function pngWithTextChunk(string $pngBytes, string $keyword, string $text): string
    {
        $iend = strpos($pngBytes, 'IEND');
        $this->assertNotFalse($iend);
        $insertAt = $iend - 4;
        $data = $keyword."\0".$text;
        $chunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

        return substr($pngBytes, 0, $insertAt).$chunk.substr($pngBytes, $insertAt);
    }

    private function buildRgbaPngViaImagick(int $w, int $h, int $r, int $g, int $b, int $a): string
    {
        $img = new \Imagick();
        $img->newImage($w, $h, new \ImagickPixel(sprintf('rgba(%d,%d,%d,%F)', $r, $g, $b, $a / 255)));
        $img->setImageFormat('png');
        $blob = $img->getImageBlob();
        $img->clear();
        $img->destroy();

        return $blob;
    }

    private function tempJpegWithOrientation6(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ori_');
        $this->assertNotFalse($path);
        $path .= '.jpg';

        $img = new \Imagick();
        $img->newImage(20, 10, new \ImagickPixel('red'));
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(90);
        // EXIF Orientation 6 = Rotate 90 CW
        $img->setImageOrientation(\Imagick::ORIENTATION_RIGHTTOP);
        $img->writeImage($path);
        $img->clear();
        $img->destroy();

        $this->beforeApplicationDestroyed(static function () use ($path) {
            @unlink($path);
        });

        return $path;
    }
}
