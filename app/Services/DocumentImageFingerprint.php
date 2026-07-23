<?php

namespace App\Services;

/**
 * Stable normalized pixel fingerprint for Document Library image uploads.
 *
 * Business rule: transparent areas are flattened onto white so two documents that
 * look the same on a white background compare equal (alpha differences ignored).
 *
 * Memory: pixels are hashed in horizontal strips (default 8 rows) — never a full
 * PHP array of all RGB samples for the image.
 */
final class DocumentImageFingerprint
{
    public const MAX_SIDE = 8192;

    public const MAX_PIXELS = 8_388_608;

    public const DEFAULT_ROW_CHUNK = 8;

    /**
     * @return string Fingerprint key "{width}x{height}:{sha256}"
     *
     * @throws DocumentImageFingerprintException
     */
    public function fingerprint(string $path, ?int $rowChunk = null): string
    {
        if (! extension_loaded('imagick')) {
            throw new DocumentImageFingerprintException(
                'Validacija slike trenutno nije dostupna. Pokušajte ponovo ili uploadujte jedan fajl.'
            );
        }

        if ($path === '' || ! is_file($path)) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika nije validna ili se ne može pročitati.'
            );
        }

        $chunk = $rowChunk ?? self::DEFAULT_ROW_CHUNK;
        if ($chunk < 1 || $chunk > 16) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika nije validna ili se ne može pročitati.'
            );
        }

        $ping = null;
        $image = null;

        try {
            $ping = new \Imagick();
            $ping->pingImage($path);
            $pingWidth = (int) $ping->getImageWidth();
            $pingHeight = (int) $ping->getImageHeight();
            $ping->clear();
            $ping->destroy();
            $ping = null;

            $this->assertSafeDimensions($pingWidth, $pingHeight);

            $image = new \Imagick();
            $image->readImage($path);

            $frames = (int) $image->getNumberImages();
            if ($frames < 1) {
                throw new DocumentImageFingerprintException(
                    'Jedna od slika nije validna ili se ne može pročitati.'
                );
            }
            // Document Library accepts only JPEG/PNG; reject multi-frame (e.g. APNG).
            if ($frames > 1) {
                throw new DocumentImageFingerprintException(
                    'Jedna od slika nije validna ili se ne može pročitati.'
                );
            }
            $image->setIteratorIndex(0);

            if (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            } elseif (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            }

            $image->stripImage();

            if (defined('Imagick::COLORSPACE_SRGB')) {
                $image->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            }

            // Intentional: flatten alpha onto white (document preview on white background).
            if (method_exists($image, 'getImageAlphaChannel') && $image->getImageAlphaChannel()) {
                $image->setImageBackgroundColor(new \ImagickPixel('white'));
                if (defined('Imagick::LAYERMETHOD_FLATTEN')) {
                    $flat = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $image->clear();
                    $image->destroy();
                    $image = $flat;
                }
                if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                    $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
            }

            $image->setImageDepth(8);

            // Final dimensions AFTER autoOrient / flatten (EXIF may swap width/height).
            $width = (int) $image->getImageWidth();
            $height = (int) $image->getImageHeight();
            $this->assertSafeDimensions($width, $height);

            $ctx = hash_init('sha256');
            // Ambiguous-free dimension prefix inside the digest.
            hash_update($ctx, pack('N2', $width, $height));

            for ($y = 0; $y < $height; $y += $chunk) {
                $rows = min($chunk, $height - $y);
                $expected = $width * $rows * 3;
                $pixels = $image->exportImagePixels(0, $y, $width, $rows, 'RGB', \Imagick::PIXEL_CHAR);

                if (! is_array($pixels) || count($pixels) !== $expected) {
                    unset($pixels);
                    throw new DocumentImageFingerprintException(
                        'Jedna od slika nije validna ili se ne može pročitati.'
                    );
                }

                $binary = '';
                foreach ($pixels as $value) {
                    $v = (int) $value;
                    if ($v < 0 || $v > 255) {
                        unset($pixels, $binary);
                        throw new DocumentImageFingerprintException(
                            'Jedna od slika nije validna ili se ne može pročitati.'
                        );
                    }
                    $binary .= chr($v);
                }
                unset($pixels);

                if (strlen($binary) !== $expected) {
                    unset($binary);
                    throw new DocumentImageFingerprintException(
                        'Jedna od slika nije validna ili se ne može pročitati.'
                    );
                }

                hash_update($ctx, $binary);
                unset($binary);
            }

            $image->clear();
            $image->destroy();
            $image = null;

            return $width.'x'.$height.':'.hash_final($ctx);
        } catch (DocumentImageFingerprintException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika nije validna ili se ne može pročitati.',
                0,
                $e
            );
        } finally {
            if ($ping instanceof \Imagick) {
                try {
                    $ping->clear();
                    $ping->destroy();
                } catch (\Throwable) {
                }
            }
            if ($image instanceof \Imagick) {
                try {
                    $image->clear();
                    $image->destroy();
                } catch (\Throwable) {
                }
            }
        }
    }

    /**
     * Overflow-safe dimension / pixel-count guard (pre- and post-orient).
     *
     * @throws DocumentImageFingerprintException
     */
    private function assertSafeDimensions(int $width, int $height): void
    {
        if ($width < 1 || $height < 1) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika nije validna ili se ne može pročitati.'
            );
        }

        if ($width > self::MAX_SIDE || $height > self::MAX_SIDE) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika ima nedozvoljene dimenzije za validaciju.'
            );
        }

        // Avoid $width * $height overflowing PHP int on 32-bit; use float compare.
        if (((float) $width * (float) $height) > (float) self::MAX_PIXELS) {
            throw new DocumentImageFingerprintException(
                'Jedna od slika ima nedozvoljene dimenzije za validaciju.'
            );
        }
    }
}
