<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Smart PDF handling for Document Library (Paket 2D).
 * Uses PHP Imagick only — no ImageMagick CLI convert/magick.
 */
class PdfOptimizer
{
    /**
     * True when size is at or above threshold (optimize); false when strictly below (pass-through).
     */
    public function shouldOptimize(int $originalSize): bool
    {
        $threshold = (int) config('document_library.pdf_optimization_threshold_bytes', 3 * 1024 * 1024);

        return $originalSize >= $threshold;
    }

    public function optimize(string $sourcePath, string $destinationPath): PdfOptimizationResult
    {
        $started = hrtime(true);

        if (! is_file($sourcePath) || filesize($sourcePath) <= 0) {
            return $this->fail(0, 0, 0, 'Source PDF missing or empty');
        }

        $originalSize = (int) filesize($sourcePath);
        if (! $this->hasPdfHeader($sourcePath)) {
            return $this->fail($originalSize, 0, 0, 'Invalid PDF header');
        }

        if (! extension_loaded('imagick')) {
            return $this->fail($originalSize, 0, 0, 'PHP Imagick extension not loaded');
        }

        $pageCount = $this->pageCount($sourcePath);
        if ($pageCount < 1) {
            return $this->fail($originalSize, 0, 0, 'PDF has no readable pages');
        }

        // Strict: below threshold → pass-through; equal or above → optimize.
        if (! $this->shouldOptimize($originalSize)) {
            if (! $this->copyFile($sourcePath, $destinationPath)) {
                return $this->fail($originalSize, 0, $pageCount, 'Failed to copy small PDF');
            }

            return new PdfOptimizationResult(
                originalSize: $originalSize,
                finalSize: (int) filesize($destinationPath),
                optimized: false,
                pageCount: $pageCount,
                tool: 'pass-through',
                durationMs: $this->elapsedMs($started),
            );
        }

        $optimizedPath = $destinationPath.'.opt.tmp.pdf';
        try {
            $this->rasterizeWithImagick($sourcePath, $optimizedPath, $pageCount);

            if (! $this->hasPdfHeader($optimizedPath)) {
                @unlink($optimizedPath);

                return $this->fail($originalSize, 0, $pageCount, 'Optimized output is not a valid PDF');
            }

            $optSize = (int) filesize($optimizedPath);
            $optPages = $this->pageCount($optimizedPath);
            if ($optPages !== $pageCount) {
                @unlink($optimizedPath);

                return $this->fail($originalSize, 0, $pageCount, "Page count mismatch after optimize ({$optPages} vs {$pageCount})");
            }

            // Equal or larger than original → keep original (imagick-reverted, optimized=false).
            if ($optSize >= $originalSize) {
                @unlink($optimizedPath);
                if (! $this->copyFile($sourcePath, $destinationPath)) {
                    return $this->fail($originalSize, 0, $pageCount, 'Failed to keep original after larger optimize');
                }

                return new PdfOptimizationResult(
                    originalSize: $originalSize,
                    finalSize: (int) filesize($destinationPath),
                    optimized: false,
                    pageCount: $pageCount,
                    tool: 'imagick-reverted',
                    durationMs: $this->elapsedMs($started),
                );
            }

            if (is_file($destinationPath)) {
                @unlink($destinationPath);
            }
            if (! @rename($optimizedPath, $destinationPath) && ! $this->copyFile($optimizedPath, $destinationPath)) {
                @unlink($optimizedPath);

                return $this->fail($originalSize, 0, $pageCount, 'Failed to store optimized PDF');
            }
            @unlink($optimizedPath);

            return new PdfOptimizationResult(
                originalSize: $originalSize,
                finalSize: (int) filesize($destinationPath),
                optimized: true,
                pageCount: $pageCount,
                tool: 'imagick',
                durationMs: $this->elapsedMs($started),
            );
        } catch (Throwable $e) {
            if (is_file($optimizedPath)) {
                @unlink($optimizedPath);
            }
            if (is_file($destinationPath) && ! $this->hasPdfHeader($destinationPath)) {
                @unlink($destinationPath);
            }
            Log::warning('PdfOptimizer Imagick failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->fail($originalSize, 0, $pageCount, 'Imagick optimization failed: '.$e->getMessage());
        }
    }

    private function rasterizeWithImagick(string $sourcePath, string $destinationPath, int $expectedPages): void
    {
        $dpi = (int) config('document_library.pdf_target_dpi', 200);
        $quality = (int) config('document_library.pdf_jpeg_quality', 82);
        $grayscale = (bool) config('document_library.pdf_grayscale', true);

        $output = new \Imagick();
        try {
            // Resolution must be set before reading PDF pages.
            $output->setResolution($dpi, $dpi);

            for ($i = 0; $i < $expectedPages; $i++) {
                $page = new \Imagick();
                try {
                    $page->setResolution($dpi, $dpi);
                    $page->readImage($sourcePath.'['.$i.']');

                    if ($grayscale) {
                        $page->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
                    }

                    $page->setImageFormat('pdf');
                    $page->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $page->setImageCompressionQuality($quality);
                    $page->setImageResolution($dpi, $dpi);

                    $output->addImage($page);
                } finally {
                    $page->clear();
                    $page->destroy();
                    unset($page);
                }
            }

            if ($output->getNumberImages() !== $expectedPages) {
                throw new \RuntimeException('Imagick did not produce expected page count');
            }

            $ok = $output->writeImages($destinationPath, true);
            if (! $ok || ! is_file($destinationPath) || filesize($destinationPath) <= 0) {
                if (is_file($destinationPath)) {
                    @unlink($destinationPath);
                }
                throw new \RuntimeException('Imagick writeImages failed');
            }
        } finally {
            $output->clear();
            $output->destroy();
            unset($output);
        }
    }

    private function pageCount(string $path): int
    {
        try {
            $imagick = new \Imagick();
            $imagick->pingImage($path);
            $count = (int) $imagick->getNumberImages();
            $imagick->clear();
            $imagick->destroy();

            return max(0, $count);
        } catch (Throwable $e) {
            Log::info('PdfOptimizer pageCount failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    private function hasPdfHeader(string $path): bool
    {
        $header = @file_get_contents($path, false, null, 0, 5);

        return is_string($header) && str_starts_with($header, '%PDF-');
    }

    private function copyFile(string $from, string $to): bool
    {
        $dir = dirname($to);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        return @copy($from, $to) && is_file($to) && filesize($to) > 0;
    }

    private function elapsedMs(int $startedHrtime): int
    {
        return (int) round((hrtime(true) - $startedHrtime) / 1e6);
    }

    private function fail(int $originalSize, int $finalSize, int $pageCount, string $error): PdfOptimizationResult
    {
        return new PdfOptimizationResult(
            originalSize: $originalSize,
            finalSize: $finalSize,
            optimized: false,
            pageCount: $pageCount,
            tool: 'none',
            durationMs: 0,
            error: $error,
        );
    }
}
