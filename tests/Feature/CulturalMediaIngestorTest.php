<?php

namespace Tests\Feature;

use App\Models\CulturalMedia;
use App\Services\CulturalMedia\CulturalMediaFileValidator;
use App\Services\CulturalMedia\CulturalMediaImageProcessor;
use App\Services\CulturalMedia\CulturalMediaIngestor;
use App\Services\CulturalMedia\CulturalMediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesCulturalMediaFixtures;
use Tests\TestCase;

class CulturalMediaIngestorTest extends TestCase
{
    use CreatesCulturalMediaFixtures;
    use RefreshDatabase;

    private CulturalMediaIngestor $ingestor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->ingestor = new CulturalMediaIngestor(
            new CulturalMediaFileValidator,
            new CulturalMediaImageProcessor,
            new CulturalMediaStorage,
        );
    }

    public function test_ingest_stores_generated_filename_under_cultural_media_with_final_metadata(): void
    {
        $file = $this->uploadJpeg('korisnicko-ime.jpg');
        $media = $this->ingestor->ingest($file, [
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
        ]);

        $this->assertStringStartsWith('cultural-media/', $media->storage_path);
        $this->assertDoesNotMatchRegularExpression('/korisnicko-ime/i', $media->interni_naziv);
        $this->assertSame('korisnicko-ime.jpg', $media->originalni_naziv);
        $this->assertSame('image/jpeg', $media->mime);
        $this->assertSame('jpeg', $media->format);
        $this->assertSame(1, $media->sirina);
        $this->assertSame(1, $media->visina);
        $this->assertSame(strlen($this->fixtureJpeg1x1()), $media->velicina);
        $this->assertSame(CulturalMedia::PURPOSE_EVENT_COVER, $media->namjena);
        $this->assertNotSame(CulturalMedia::PURPOSE_CATEGORY_DEFAULT, $media->namjena);
        Storage::disk('public')->assertExists($media->storage_path);
        $this->assertSame(
            Storage::disk('public')->size($media->storage_path),
            $media->velicina
        );
    }

    public function test_ingest_resized_jpeg_persists_final_dimensions_and_size(): void
    {
        $processor = new CulturalMediaImageProcessor;
        if (! $processor->canEncode('jpeg')) {
            $this->markTestSkipped('GD funkcije za JPEG nisu dostupne u test runtime-u.');
        }

        $path = $this->writeSolidJpeg(4000, 2000);
        try {
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'wide.jpg',
                (string) file_get_contents($path)
            );
            $media = $this->ingestor->ingest($file, [
                'namjena' => CulturalMedia::PURPOSE_MANIFESTATION_COVER,
            ]);

            $this->assertSame(1920, $media->sirina);
            $this->assertSame(960, $media->visina);
            $this->assertSame(
                Storage::disk('public')->size($media->storage_path),
                $media->velicina
            );
            $this->assertSame('image/jpeg', $media->mime);
        } finally {
            @unlink($path);
        }
    }

    public function test_ingest_requires_explicit_namjena_and_does_not_default_to_category_default(): void
    {
        $file = $this->uploadJpeg('cover.jpg');

        try {
            $this->ingestor->ingest($file, []);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(0, CulturalMedia::count());
            $this->assertSame([], Storage::disk('public')->allFiles('cultural-media'));
        }
    }

    public function test_db_create_failure_deletes_new_file(): void
    {
        $file = $this->uploadJpeg('cleanup.jpg');

        try {
            $this->ingestor->ingest($file, [
                'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
                'naziv' => str_repeat('n', 300),
            ]);
            $this->fail('Expected database failure');
        } catch (\Throwable $e) {
            $this->assertSame(0, CulturalMedia::count());
            $this->assertSame([], Storage::disk('public')->allFiles('cultural-media'));
        }
    }

    private function writeSolidJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 10, 20, 30);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        $path = tempnam(sys_get_temp_dir(), 'med-i1-ing-');
        $this->assertNotFalse($path);
        $this->assertTrue(imagejpeg($image, $path, 90) !== false);
        imagedestroy($image);

        return $path;
    }
}
