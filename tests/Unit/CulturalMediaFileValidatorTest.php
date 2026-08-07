<?php

namespace Tests\Unit;

use App\Services\CulturalMedia\CulturalMediaFileValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

class CulturalMediaFileValidatorTest extends TestCase
{
    use CreatesCulturalMediaFixtures;

    private CulturalMediaFileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CulturalMediaFileValidator;
    }

    public function test_accepts_jpeg(): void
    {
        $meta = $this->validator->validate($this->uploadJpeg());

        $this->assertSame('image/jpeg', $meta['mime']);
        $this->assertSame('jpeg', $meta['format']);
        $this->assertGreaterThanOrEqual(1, $meta['sirina']);
        $this->assertGreaterThanOrEqual(1, $meta['visina']);
    }

    public function test_accepts_png(): void
    {
        $meta = $this->validator->validate($this->uploadPng());

        $this->assertSame('image/png', $meta['mime']);
        $this->assertSame('png', $meta['format']);
    }

    public function test_accepts_webp(): void
    {
        $meta = $this->validator->validate($this->uploadWebp());

        $this->assertSame('image/webp', $meta['mime']);
        $this->assertSame('webp', $meta['format']);
    }

    public function test_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('big.jpg', 5121, 'image/jpeg');

        try {
            $this->validator->validate($file);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fajl', $e->errors());
            $this->assertStringContainsString('5 MB', $e->errors()['fajl'][0]);
        }
    }

    public function test_rejects_gif(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        $file = UploadedFile::fake()->createWithContent('anim.gif', $gif);

        $this->expectException(ValidationException::class);
        $this->validator->validate($file);
    }

    public function test_rejects_non_image_content_with_image_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('fake.jpg', 'this-is-not-an-image');

        try {
            $this->validator->validate($file);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fajl', $e->errors());
        }
    }

    public function test_rejects_extension_mismatched_with_content(): void
    {
        $file = UploadedFile::fake()->createWithContent('spoof.jpg', $this->fixturePng1x1());

        try {
            $this->validator->validate($file);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fajl', $e->errors());
        }
    }
}
