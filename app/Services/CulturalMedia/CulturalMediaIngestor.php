<?php

namespace App\Services\CulturalMedia;

use App\Models\CulturalMedia;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Throwable;

/**
 * MED-I1 ingest: validacija → obrada → storage → DB create, uz cleanup fajla ako create padne.
 * namjena je obavezan tehnički parametar (nije korisnički UI); I2/I3 predaju event_cover / manifestation_cover.
 */
class CulturalMediaIngestor
{
    public function __construct(
        private CulturalMediaFileValidator $validator,
        private CulturalMediaImageProcessor $processor,
        private CulturalMediaStorage $storage,
    ) {}

    /**
     * @param  array{
     *     namjena: string,
     *     naziv?: string,
     *     alt_tekst?: string,
     *     status?: string,
     *     opis?: ?string,
     *     autor?: ?string,
     *     izvor?: ?string,
     *     licenca?: ?string,
     *     creator_id?: ?int
     * }  $attributes
     */
    public function ingest(UploadedFile $file, array $attributes): CulturalMedia
    {
        $namjena = $attributes['namjena'] ?? null;
        if (! is_string($namjena) || ! in_array($namjena, CulturalMedia::PURPOSES, true)) {
            throw new InvalidArgumentException('Tehnička namjena fotografije nije validna.');
        }

        $meta = $this->validator->validate($file);
        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            throw new \RuntimeException('Fajl nije moguće pročitati.');
        }

        $processed = $this->processor->process($sourcePath, $meta);
        $stored = $this->storage->storeContents($processed['contents'], $processed['ekstenzija']);

        $technicalName = $this->technicalName($meta['originalni_naziv']);

        try {
            return CulturalMedia::create([
                'naziv' => $this->optionalText($attributes['naziv'] ?? null) ?? $technicalName,
                'alt_tekst' => $this->optionalText($attributes['alt_tekst'] ?? null) ?? $technicalName,
                'namjena' => $namjena,
                'status' => $attributes['status'] ?? CulturalMedia::STATUS_ACTIVE,
                'opis' => $attributes['opis'] ?? null,
                'autor' => $attributes['autor'] ?? null,
                'izvor' => $attributes['izvor'] ?? null,
                'licenca' => $attributes['licenca'] ?? null,
                'tagovi' => null,
                'originalni_naziv' => $meta['originalni_naziv'],
                'interni_naziv' => $stored['interni_naziv'],
                'mime' => $processed['mime'],
                'format' => $processed['format'],
                'sirina' => $processed['sirina'],
                'visina' => $processed['visina'],
                'velicina' => $processed['velicina'],
                'storage_path' => $stored['storage_path'],
                'creator_id' => $attributes['creator_id'] ?? null,
            ]);
        } catch (Throwable $e) {
            try {
                $this->storage->deletePath($stored['storage_path']);
            } catch (Throwable) {
                // Rollback mora ostaviti originalnu DB grešku vidljivom.
            }
            throw $e;
        }
    }

    private function technicalName(string $originalName): string
    {
        $trimmed = trim($originalName);
        if ($trimmed === '') {
            return 'cover';
        }

        return mb_substr($trimmed, 0, 255);
    }

    private function optionalText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
