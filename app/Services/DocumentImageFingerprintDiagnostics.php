<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Isolated diagnostics for DocumentImageFingerprint (Artisan Toolkit / pdf:check pattern).
 * Does not touch user documents, quota, queue, or MEGA.
 */
class DocumentImageFingerprintDiagnostics
{
    public const INPUT_RELATIVE_DIR = 'document-fingerprint-input';

    public function __construct(
        private DocumentImageFingerprint $fingerprint
    ) {}

    /**
     * @return array{
     *   checks: list<array{name: string, status: string, message: string}>,
     *   pass: int,
     *   fail: int,
     *   unsupported: int,
     *   cleanup_ok: bool,
     *   peak_memory_bytes: int,
     *   memory_limit: string,
     *   compare: array<string, mixed>|null
     * }
     */
    public function run(bool $compare = false): array
    {
        $checks = [];
        $workDir = null;
        $cleanupOk = true;
        $compareResult = null;

        try {
            $workDir = $this->createWorkDir();

            $checks[] = $this->checkEnvironment();
            $checks[] = $this->checkSamePixelsDifferentMetadata($workDir);
            $checks[] = $this->checkSamePixelsDifferentCompression($workDir);
            $checks[] = $this->checkDifferentPixels($workDir);
            $checks[] = $this->checkTransparencyFlatten($workDir);
            $checks[] = $this->checkCorruptImage($workDir);
            $checks[] = $this->checkPixelLimit($workDir);
            $checks[] = $this->checkMultiFrameRejected($workDir);
            $checks[] = $this->checkDeterminism($workDir);
            $checks[] = $this->checkChunkStability($workDir);

            if ($compare) {
                $compareResult = $this->compareCaptureInputs();
                $checks[] = $this->compareCheckFromResult($compareResult);
            }
        } catch (Throwable $e) {
            $checks[] = $this->fail('Suite runner', $this->sanitize($e->getMessage()));
        } finally {
            if ($workDir !== null) {
                $cleanupOk = $this->cleanupDirectory($workDir);
            }
        }

        $pass = 0;
        $fail = 0;
        $unsupported = 0;
        foreach ($checks as $check) {
            match ($check['status']) {
                'pass' => $pass++,
                'fail' => $fail++,
                default => $unsupported++,
            };
        }

        return [
            'checks' => $checks,
            'pass' => $pass,
            'fail' => $fail,
            'unsupported' => $unsupported,
            'cleanup_ok' => $cleanupOk,
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'memory_limit' => (string) ini_get('memory_limit'),
            'compare' => $compareResult,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkEnvironment(): array
    {
        if (! extension_loaded('imagick')) {
            return $this->fail('A. Imagick okruženje', 'PHP Imagick extension not loaded');
        }

        try {
            $ver = \Imagick::getVersion();
            $versionString = is_array($ver) ? (string) ($ver['versionString'] ?? '') : '';
            $formats = \Imagick::queryFormats();
            $formats = is_array($formats) ? array_map('strtoupper', $formats) : [];
            $png = in_array('PNG', $formats, true);
            $jpeg = in_array('JPEG', $formats, true) || in_array('JPG', $formats, true);

            if ($versionString !== '' && $png && $jpeg) {
                // Avoid absolute paths; show only short version token.
                $short = preg_replace('/\s+.*/', '', $versionString) ?? 'Imagick';

                return $this->pass('A. Imagick okruženje', $short.' + PNG + JPEG');
            }

            return $this->fail(
                'A. Imagick okruženje',
                'PNG='.($png ? 'yes' : 'no').' JPEG='.($jpeg ? 'yes' : 'no')
            );
        } catch (Throwable $e) {
            return $this->fail('A. Imagick okruženje', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkSamePixelsDifferentMetadata(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('B. Isti pikseli / različita metadata', 'Imagick unavailable');
        }

        try {
            $a = $workDir.DIRECTORY_SEPARATOR.'meta-a.png';
            $b = $workDir.DIRECTORY_SEPARATOR.'meta-b.png';
            $this->writeSolidPng($a, 32, 24, 40, 80, 120, null);
            $this->writeSolidPng($b, 32, 24, 40, 80, 120, 'capture-metadata-b');

            $shaSame = hash_file('sha256', $a) === hash_file('sha256', $b);
            $fpSame = $this->fingerprint->fingerprint($a) === $this->fingerprint->fingerprint($b);

            if (! $shaSame && $fpSame) {
                return $this->pass('B. Isti pikseli / različita metadata', 'binary differs, fingerprint matches');
            }

            return $this->fail(
                'B. Isti pikseli / različita metadata',
                'binary_same='.($shaSame ? 'yes' : 'no').' fp_same='.($fpSame ? 'yes' : 'no')
            );
        } catch (Throwable $e) {
            return $this->fail('B. Isti pikseli / različita metadata', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkSamePixelsDifferentCompression(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('C. Isti pikseli / različita kompresija', 'Imagick unavailable');
        }

        try {
            $a = $workDir.DIRECTORY_SEPARATOR.'cmp-a.png';
            $b = $workDir.DIRECTORY_SEPARATOR.'cmp-b.png';
            $this->writeSolidPng($a, 40, 30, 10, 20, 30, null, 1);
            $this->writeSolidPng($b, 40, 30, 10, 20, 30, null, 9);

            $shaSame = hash_file('sha256', $a) === hash_file('sha256', $b);
            $fpSame = $this->fingerprint->fingerprint($a) === $this->fingerprint->fingerprint($b);

            if ($fpSame) {
                return $this->pass(
                    'C. Isti pikseli / različita kompresija',
                    $shaSame
                        ? 'fingerprint matches (binary also identical on this build)'
                        : 'binary differs, fingerprint matches'
                );
            }

            return $this->fail('C. Isti pikseli / različita kompresija', 'fingerprint mismatch');
        } catch (Throwable $e) {
            return $this->fail('C. Isti pikseli / različita kompresija', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDifferentPixels(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('D. Različiti pikseli', 'Imagick unavailable');
        }

        try {
            $a = $workDir.DIRECTORY_SEPARATOR.'pix-a.png';
            $b = $workDir.DIRECTORY_SEPARATOR.'pix-b.png';
            $this->writeSolidPng($a, 16, 16, 1, 2, 3);
            $this->writeSolidPng($b, 16, 16, 1, 2, 4);

            $fpSame = $this->fingerprint->fingerprint($a) === $this->fingerprint->fingerprint($b);

            return $fpSame
                ? $this->fail('D. Različiti pikseli', 'fingerprints unexpectedly match')
                : $this->pass('D. Različiti pikseli', 'fingerprints differ');
        } catch (Throwable $e) {
            return $this->fail('D. Različiti pikseli', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkTransparencyFlatten(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('E. Transparentnost (flatten bijelo)', 'Imagick unavailable');
        }

        try {
            $transparent = $workDir.DIRECTORY_SEPARATOR.'alpha.png';
            $opaque = $workDir.DIRECTORY_SEPARATOR.'opaque-white.png';
            $this->writeRgbaPng($transparent, 8, 8, 255, 255, 255, 0);
            $this->writeRgbaPng($opaque, 8, 8, 255, 255, 255, 255);

            $fpSame = $this->fingerprint->fingerprint($transparent) === $this->fingerprint->fingerprint($opaque);

            return $fpSame
                ? $this->pass('E. Transparentnost (flatten bijelo)', 'fingerprints match after flatten')
                : $this->fail('E. Transparentnost (flatten bijelo)', 'fingerprints differ');
        } catch (Throwable $e) {
            return $this->fail('E. Transparentnost (flatten bijelo)', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkCorruptImage(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('F. Corrupt image', 'Imagick unavailable');
        }

        $path = $workDir.DIRECTORY_SEPARATOR.'bad.png';
        file_put_contents($path, "\x89PNG\r\n\x1a\n".str_repeat('x', 64));

        try {
            $this->fingerprint->fingerprint($path);

            return $this->fail('F. Corrupt image', 'no exception thrown');
        } catch (DocumentImageFingerprintException $e) {
            return $this->pass('F. Corrupt image', 'DocumentImageFingerprintException thrown');
        } catch (Throwable $e) {
            return $this->fail('F. Corrupt image', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkPixelLimit(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('G. Limit dimenzija', 'Imagick unavailable');
        }

        try {
            $path = $workDir.DIRECTORY_SEPARATOR.'oversized.png';
            $img = new \Imagick();
            $img->newImage(DocumentImageFingerprint::MAX_SIDE + 1, 8, new \ImagickPixel('red'));
            $img->setImageFormat('png');
            $img->writeImage($path);
            $img->clear();
            $img->destroy();

            try {
                $this->fingerprint->fingerprint($path);

                return $this->fail('G. Limit dimenzija', 'oversized image accepted');
            } catch (DocumentImageFingerprintException $e) {
                return $this->pass('G. Limit dimenzija', 'rejected safely');
            }
        } catch (Throwable $e) {
            return $this->fail('G. Limit dimenzija', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkMultiFrameRejected(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('H. Multi-frame', 'Imagick unavailable');
        }

        try {
            $path = $workDir.DIRECTORY_SEPARATOR.'multi.tiff';
            $img = new \Imagick();
            $frame1 = new \Imagick();
            $frame1->newImage(12, 12, new \ImagickPixel('red'));
            $frame1->setImageFormat('tiff');
            $img->addImage($frame1);
            $frame2 = new \Imagick();
            $frame2->newImage(12, 12, new \ImagickPixel('blue'));
            $frame2->setImageFormat('tiff');
            $img->addImage($frame2);
            $img->writeImages($path, true);
            $frame1->clear();
            $frame1->destroy();
            $frame2->clear();
            $frame2->destroy();
            $img->clear();
            $img->destroy();

            try {
                $this->fingerprint->fingerprint($path);

                return $this->fail('H. Multi-frame', 'multi-frame accepted');
            } catch (DocumentImageFingerprintException $e) {
                return $this->pass('H. Multi-frame', 'rejected');
            }
        } catch (Throwable $e) {
            return $this->unsupported('H. Multi-frame', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDeterminism(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('I. Determinističnost', 'Imagick unavailable');
        }

        try {
            $path = $workDir.DIRECTORY_SEPARATOR.'det.png';
            $this->writeSolidPng($path, 24, 18, 70, 80, 90);
            $a = $this->fingerprint->fingerprint($path);
            $b = $this->fingerprint->fingerprint($path);

            return $a === $b
                ? $this->pass('I. Determinističnost', 'repeated fingerprint identical')
                : $this->fail('I. Determinističnost', 'repeated fingerprint differs');
        } catch (Throwable $e) {
            return $this->fail('I. Determinističnost', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkChunkStability(string $workDir): array
    {
        if (! extension_loaded('imagick')) {
            return $this->unsupported('J. Chunk stabilnost', 'Imagick unavailable');
        }

        try {
            $path = $workDir.DIRECTORY_SEPARATOR.'chunk.png';
            $this->writeSolidPng($path, 48, 36, 90, 100, 110);
            $a = $this->fingerprint->fingerprint($path, 1);
            $b = $this->fingerprint->fingerprint($path, 8);
            $c = $this->fingerprint->fingerprint($path, 16);

            return ($a === $b && $b === $c)
                ? $this->pass('J. Chunk stabilnost', 'chunk 1/8/16 identical')
                : $this->fail('J. Chunk stabilnost', 'chunk sizes diverge');
        } catch (Throwable $e) {
            return $this->fail('J. Chunk stabilnost', $this->sanitize($e->getMessage()));
        }
    }

    /**
     * Manual capture compare — does NOT delete input files.
     *
     * @return array<string, mixed>
     */
    public function compareCaptureInputs(): array
    {
        $dir = storage_path('app/'.self::INPUT_RELATIVE_DIR);
        $nameA = 'capture01.png';
        $nameB = 'capture05.png';
        $pathA = $dir.DIRECTORY_SEPARATOR.$nameA;
        $pathB = $dir.DIRECTORY_SEPARATOR.$nameB;

        foreach ([$pathA => $nameA, $pathB => $nameB] as $path => $name) {
            if (! is_file($path) || ! is_readable($path)) {
                return [
                    'ok' => false,
                    'error' => 'Nedostaje fajl: storage/app/'.self::INPUT_RELATIVE_DIR.'/'.$name,
                ];
            }
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                return [
                    'ok' => false,
                    'error' => 'Nedozvoljena ekstenzija za '.$name.' (dozvoljeno: png/jpg/jpeg).',
                ];
            }
        }

        try {
            $pingA = new \Imagick();
            $pingA->pingImage($pathA);
            $dimsA = ((int) $pingA->getImageWidth()).'x'.((int) $pingA->getImageHeight());
            $pingA->clear();
            $pingA->destroy();

            $pingB = new \Imagick();
            $pingB->pingImage($pathB);
            $dimsB = ((int) $pingB->getImageWidth()).'x'.((int) $pingB->getImageHeight());
            $pingB->clear();
            $pingB->destroy();

            $binarySame = hash_file('sha256', $pathA) === hash_file('sha256', $pathB);
            $fpSame = $this->fingerprint->fingerprint($pathA) === $this->fingerprint->fingerprint($pathB);

            return [
                'ok' => true,
                'file_a' => $nameA,
                'file_b' => $nameB,
                'dims_a' => $dimsA,
                'dims_b' => $dimsB,
                'binary_same' => $binarySame,
                'fingerprint_same' => $fpSame,
                'verdict' => $fpSame ? 'DUPLIKAT' : 'RAZLIČIT SADRŽAJ',
            ];
        } catch (DocumentImageFingerprintException $e) {
            return ['ok' => false, 'error' => $this->sanitize($e->getMessage())];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $this->sanitize($e->getMessage())];
        }
    }

    /**
     * @param  array<string, mixed>  $compare
     * @return array{name: string, status: string, message: string}
     */
    private function compareCheckFromResult(array $compare): array
    {
        $name = 'K. Compare capture01/capture05';
        if (! ($compare['ok'] ?? false)) {
            return $this->fail($name, (string) ($compare['error'] ?? 'compare failed'));
        }

        $msg = 'binary_same='.(($compare['binary_same'] ?? false) ? 'DA' : 'NE')
            .' fp_same='.(($compare['fingerprint_same'] ?? false) ? 'DA' : 'NE')
            .' verdict='.(string) ($compare['verdict'] ?? '');

        // Informational: duplicate expected for production smoke, but either outcome is a successful check run.
        return $this->pass($name, $msg);
    }

    private function createWorkDir(): string
    {
        $base = storage_path('app/document-fingerprint-diagnostics');
        if (! is_dir($base) && ! mkdir($base, 0755, true) && ! is_dir($base)) {
            throw new \RuntimeException('Cannot create document-fingerprint-diagnostics directory');
        }

        $dir = $base.DIRECTORY_SEPARATOR.bin2hex(random_bytes(8));
        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Cannot create diagnostic work directory');
        }

        return $dir;
    }

    public function cleanupDirectory(string $dir): bool
    {
        if (! is_dir($dir)) {
            return true;
        }

        try {
            File::deleteDirectory($dir);

            return ! is_dir($dir);
        } catch (Throwable) {
            return false;
        }
    }

    private function writeSolidPng(
        string $path,
        int $w,
        int $h,
        int $r,
        int $g,
        int $b,
        ?string $comment = null,
        int $compression = 6
    ): void {
        $img = new \Imagick();
        $img->newImage($w, $h, new \ImagickPixel(sprintf('rgb(%d,%d,%d)', $r, $g, $b)));
        $img->setImageFormat('png');
        $img->setImageCompressionQuality(max(0, min(9, $compression)) * 10);
        if ($comment !== null) {
            $img->commentImage($comment);
            $img->setImageProperty('comment', $comment);
        }
        $img->writeImage($path);
        $img->clear();
        $img->destroy();
    }

    private function writeRgbaPng(string $path, int $w, int $h, int $r, int $g, int $b, int $a): void
    {
        $img = new \Imagick();
        $img->newImage(
            $w,
            $h,
            new \ImagickPixel(sprintf('rgba(%d,%d,%d,%F)', $r, $g, $b, $a / 255))
        );
        $img->setImageFormat('png');
        $img->writeImage($path);
        $img->clear();
        $img->destroy();
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function pass(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'pass', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function fail(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'fail', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function unsupported(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'unsupported', 'message' => $message];
    }

    private function sanitize(string $message): string
    {
        $message = preg_replace('#[A-Za-z]:\\\\[^\s]+#', '[path]', $message) ?? $message;
        $message = preg_replace('#/(?:var|home|tmp|storage|usr)[^\s]+#', '[path]', $message) ?? $message;

        return mb_substr($message, 0, 200);
    }
}
