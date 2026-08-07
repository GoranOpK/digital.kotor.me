<?php

namespace App\Services\CulturalMedia;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Stroga serverska validacija fotografije (TS-008 / BM-MD-11).
 * JPEG / PNG / WebP, max 5 MB, čitljiva slika, usklađenost sadržaja ↔ MIME ↔ ekstenzija.
 */
class CulturalMediaFileValidator
{
    public const MAX_BYTES = 5_242_880; // 5 MB = 5120 KB

    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    private const MIME_BY_IMAGE_TYPE = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    private const FORMAT_BY_IMAGE_TYPE = [
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    private const EXTENSIONS_BY_MIME = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    /**
     * @return array{
     *     mime: string,
     *     format: string,
     *     sirina: int,
     *     visina: int,
     *     velicina: int,
     *     originalni_naziv: string,
     *     ekstenzija: string
     * }
     *
     * @throws ValidationException
     */
    public function validate(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'fajl' => 'Upload fajla nije uspio. Pokušajte ponovo.',
            ]);
        }

        $size = $file->getSize();
        if ($size === false || $size > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'fajl' => 'Fotografija ne smije biti veća od 5 MB.',
            ]);
        }

        if ($size < 1) {
            throw ValidationException::withMessages([
                'fajl' => 'Fajl nije validna fotografija.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'fajl' => 'Dozvoljeni formati su JPEG, PNG i WebP.',
            ]);
        }

        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'fajl' => 'Fajl nije moguće pročitati.',
            ]);
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false || ! isset($imageInfo[0], $imageInfo[1], $imageInfo[2])) {
            throw ValidationException::withMessages([
                'fajl' => 'Fajl nije čitljiva slika dozvoljenog formata.',
            ]);
        }

        $imageType = (int) $imageInfo[2];
        if (! isset(self::MIME_BY_IMAGE_TYPE[$imageType])) {
            throw ValidationException::withMessages([
                'fajl' => 'Dozvoljeni formati su JPEG, PNG i WebP.',
            ]);
        }

        $detectedMime = self::MIME_BY_IMAGE_TYPE[$imageType];
        $clientMime = strtolower((string) $file->getMimeType());
        $finfoMime = $this->detectMimeWithFinfo($path);

        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'fajl' => 'Dozvoljeni formati su JPEG, PNG i WebP.',
            ]);
        }

        if ($finfoMime !== null && $finfoMime !== $detectedMime) {
            throw ValidationException::withMessages([
                'fajl' => 'Sadržaj fajla nije usklađen sa MIME tipom.',
            ]);
        }

        if ($clientMime !== '' && $clientMime !== $detectedMime && ! $this->isCompatibleJpegAlias($clientMime, $detectedMime)) {
            throw ValidationException::withMessages([
                'fajl' => 'Sadržaj fajla nije usklađen sa prijavljenim tipom.',
            ]);
        }

        $allowedExtForMime = self::EXTENSIONS_BY_MIME[$detectedMime] ?? [];
        if (! in_array($extension, $allowedExtForMime, true)) {
            throw ValidationException::withMessages([
                'fajl' => 'Ekstenzija fajla nije usklađena sa formatom slike.',
            ]);
        }

        return [
            'mime' => $detectedMime,
            'format' => self::FORMAT_BY_IMAGE_TYPE[$imageType],
            'sirina' => (int) $imageInfo[0],
            'visina' => (int) $imageInfo[1],
            'velicina' => (int) $size,
            'originalni_naziv' => $file->getClientOriginalName(),
            'ekstenzija' => $extension === 'jpeg' ? 'jpg' : $extension,
        ];
    }

    private function detectMimeWithFinfo(string $path): ?string
    {
        if (! class_exists(\finfo::class)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        if (! is_string($mime) || $mime === '') {
            return null;
        }

        $mime = strtolower($mime);

        return in_array($mime, self::ALLOWED_MIMES, true) ? $mime : $mime;
    }

    private function isCompatibleJpegAlias(string $clientMime, string $detectedMime): bool
    {
        if ($detectedMime !== 'image/jpeg') {
            return false;
        }

        return in_array($clientMime, ['image/jpeg', 'image/jpg', 'image/pjpeg'], true);
    }
}
