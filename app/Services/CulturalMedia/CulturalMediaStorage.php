<?php

namespace App\Services\CulturalMedia;

use App\Models\CulturalMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Skladištenje katalog-medija na public disku pod cultural-media/.
 */
class CulturalMediaStorage
{
    public const DIRECTORY = 'cultural-media';

    public const DISK = 'public';

    /**
     * @param  array{
     *     mime: string,
     *     format: string,
     *     sirina: int,
     *     visina: int,
     *     velicina: int,
     *     originalni_naziv: string,
     *     ekstenzija: string
     * }  $meta
     * @return array{storage_path: string, interni_naziv: string}
     */
    public function store(UploadedFile $file, array $meta): array
    {
        $interniNaziv = Str::uuid()->toString().'.'.$meta['ekstenzija'];
        $storagePath = self::DIRECTORY.'/'.$interniNaziv;

        $stored = Storage::disk(self::DISK)->putFileAs(
            self::DIRECTORY,
            $file,
            $interniNaziv
        );

        if ($stored === false) {
            throw new \RuntimeException('Neuspješno čuvanje medijskog fajla.');
        }

        return [
            'storage_path' => $storagePath,
            'interni_naziv' => $interniNaziv,
        ];
    }

    /**
     * @return array{storage_path: string, interni_naziv: string}
     */
    public function storeContents(string $contents, string $extension): array
    {
        $interniNaziv = Str::uuid()->toString().'.'.$extension;
        $storagePath = self::DIRECTORY.'/'.$interniNaziv;

        $stored = Storage::disk(self::DISK)->put($storagePath, $contents);

        if ($stored === false) {
            throw new \RuntimeException('Neuspješno čuvanje medijskog fajla.');
        }

        return [
            'storage_path' => $storagePath,
            'interni_naziv' => $interniNaziv,
        ];
    }

    public function isManagedPath(string $storagePath): bool
    {
        if ($storagePath === '' || str_contains($storagePath, '..')) {
            return false;
        }

        $normalized = str_replace('\\', '/', $storagePath);
        if (preg_match('#^[a-zA-Z]:/#', $normalized) === 1 || str_starts_with($normalized, '/')) {
            return false;
        }

        return str_starts_with($normalized, self::DIRECTORY.'/');
    }

    public function deletePath(string $storagePath): void
    {
        if (! $this->isManagedPath($storagePath)) {
            return;
        }

        Storage::disk(self::DISK)->delete($storagePath);
    }

    public function deleteFile(CulturalMedia $media): void
    {
        $this->deletePath((string) $media->storage_path);
    }
}
