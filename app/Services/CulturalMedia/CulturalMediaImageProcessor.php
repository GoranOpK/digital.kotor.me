<?php

namespace App\Services\CulturalMedia;

use RuntimeException;

/**
 * GD obrada naslovne fotografije (MED-11 / MED-12 / MED-13).
 * Resize samo kada je duža strana > 1920 px; format se ne konvertuje; bez crop/upscale.
 */
class CulturalMediaImageProcessor
{
    public const MAX_LONG_SIDE = 1920;

    public const JPEG_QUALITY = 85;

    public const WEBP_QUALITY = 85;

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
     * @return array{
     *     contents: string,
     *     resized: bool,
     *     mime: string,
     *     format: string,
     *     ekstenzija: string,
     *     sirina: int,
     *     visina: int,
     *     velicina: int,
     *     duza_strana: int,
     *     originalna_duza_strana: int
     * }
     */
    public function process(string $sourcePath, array $meta): array
    {
        $width = (int) $meta['sirina'];
        $height = (int) $meta['visina'];
        $originalLongSide = max($width, $height);

        if ($originalLongSide <= self::MAX_LONG_SIDE) {
            $contents = file_get_contents($sourcePath);
            if ($contents === false || $contents === '') {
                throw new RuntimeException('Neuspješno čitanje validirane fotografije.');
            }

            return $this->result(
                contents: $contents,
                resized: false,
                meta: $meta,
                width: $width,
                height: $height,
                originalLongSide: $originalLongSide,
            );
        }

        $this->assertGdCanEncode($meta['format']);

        $scale = self::MAX_LONG_SIDE / $originalLongSide;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $source = $this->createImage($sourcePath, $meta['format']);
        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($destination === false) {
            imagedestroy($source);
            throw new RuntimeException('Neuspješno kreiranje izlazne slike.');
        }

        try {
            $this->preserveTransparency($source, $destination, $meta['format']);
            $copied = imagecopyresampled(
                $destination,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height
            );
            if ($copied === false) {
                throw new RuntimeException('Neuspješno skaliranje fotografije.');
            }

            $contents = $this->encode($destination, $meta['format']);
        } finally {
            imagedestroy($source);
            imagedestroy($destination);
        }

        return $this->result(
            contents: $contents,
            resized: true,
            meta: $meta,
            width: $targetWidth,
            height: $targetHeight,
            originalLongSide: $originalLongSide,
        );
    }

    public function gdIsAvailable(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled');
    }

    public function canEncode(string $format): bool
    {
        if (! $this->gdIsAvailable()) {
            return false;
        }

        return match ($format) {
            'jpeg' => function_exists('imagecreatefromjpeg') && function_exists('imagejpeg'),
            'png' => function_exists('imagecreatefrompng') && function_exists('imagepng'),
            'webp' => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
            default => false,
        };
    }

    private function assertGdCanEncode(string $format): void
    {
        if (! $this->canEncode($format)) {
            throw new RuntimeException(
                'Obrada fotografije nije moguća: GD funkcije za format '.$format.' nisu dostupne.'
            );
        }
    }

    /**
     * @return \GdImage
     */
    private function createImage(string $path, string $format): object
    {
        $image = match ($format) {
            'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Fotografija se ne može otvoriti za obradu.');
        }

        return $image;
    }

    /**
     * @param  \GdImage  $source
     * @param  \GdImage  $destination
     */
    private function preserveTransparency(object $source, object $destination, string $format): void
    {
        if ($format !== 'png' && $format !== 'webp') {
            return;
        }

        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($destination, 0, 0, imagesx($destination), imagesy($destination), $transparent);
        }

        imagealphablending($source, false);
        imagesavealpha($source, true);
    }

    /**
     * @param  \GdImage  $image
     */
    private function encode(object $image, string $format): string
    {
        ob_start();
        try {
            $ok = match ($format) {
                'jpeg' => imagejpeg($image, null, self::JPEG_QUALITY),
                'png' => imagepng($image, null, 6),
                'webp' => imagewebp($image, null, self::WEBP_QUALITY),
                default => false,
            };
            $contents = (string) ob_get_clean();
        } catch (RuntimeException $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw new RuntimeException('Neuspješno kodiranje obrađene fotografije.', 0, $e);
        }

        if ($ok === false || $contents === '') {
            throw new RuntimeException('Neuspješno kodiranje obrađene fotografije.');
        }

        return $contents;
    }

    /**
     * @param  array{mime: string, format: string, ekstenzija: string}  $meta
     * @return array{
     *     contents: string,
     *     resized: bool,
     *     mime: string,
     *     format: string,
     *     ekstenzija: string,
     *     sirina: int,
     *     visina: int,
     *     velicina: int,
     *     duza_strana: int,
     *     originalna_duza_strana: int
     * }
     */
    private function result(
        string $contents,
        bool $resized,
        array $meta,
        int $width,
        int $height,
        int $originalLongSide,
    ): array {
        return [
            'contents' => $contents,
            'resized' => $resized,
            'mime' => $meta['mime'],
            'format' => $meta['format'],
            'ekstenzija' => $meta['ekstenzija'],
            'sirina' => $width,
            'visina' => $height,
            'velicina' => strlen($contents),
            'duza_strana' => max($width, $height),
            'originalna_duza_strana' => $originalLongSide,
        ];
    }
}
