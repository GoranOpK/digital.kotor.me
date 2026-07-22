<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Read-only PDF capability probe for hosting (Plesk Toolkit Artisan).
 * Does not touch user document storage or DocumentProcessor.
 */
class PdfEnvironmentDiagnostics
{
    public const VERDICT_READY = 'READY FOR PDF OPTIMIZATION';

    public const VERDICT_BLOCKED = 'PDF OPTIMIZATION BLOCKED';

    private const PROCESS_TIMEOUT = 30;

    private const MAX_PROCESS_OUTPUT = 2000;

    /**
     * @return array{
     *     checks: array<string, array{status: string, message: string, details: array<string, mixed>}>,
     *     ready: bool,
     *     verdict: string,
     *     notes: list<string>,
     *     work_dir: string|null
     * }
     */
    public function run(): array
    {
        $workDir = null;
        $checks = [
            'imagick' => $this->emptyCheck('not run'),
            'imagemagick_cli' => $this->emptyCheck('not run'),
            'ghostscript' => $this->emptyCheck('not run'),
            'png_to_pdf' => $this->emptyCheck('not run'),
            'pdf_read' => $this->emptyCheck('not run'),
            'pdf_write' => $this->emptyCheck('not run'),
            'pdf_to_pdf' => $this->emptyCheck('not run'),
            'multipage' => $this->emptyCheck('not run'),
        ];
        $notes = [];

        try {
            $workDir = $this->createWorkDir();
            $checks['imagick'] = $this->checkImagick();
            $checks['imagemagick_cli'] = $this->checkImageMagickCli();
            $checks['ghostscript'] = $this->checkGhostscript();

            $pngPath = $workDir.DIRECTORY_SEPARATOR.'probe.png';
            $pngOk = $this->createProbePng($pngPath, $checks['imagick']['status'] === 'pass');
            if (! $pngOk) {
                $checks['png_to_pdf'] = $this->failCheck('Could not generate probe PNG (Imagick/GD unavailable)');
                $checks['pdf_write'] = $this->failCheck('Skipped: no probe PNG');
                $checks['pdf_read'] = $this->failCheck('Skipped: no probe PDF');
                $checks['pdf_to_pdf'] = $this->failCheck('Skipped: no probe PDF');
                $checks['multipage'] = $this->failCheck('Skipped: Imagick unavailable or PNG probe failed');
            } else {
                $pdfFromPng = $workDir.DIRECTORY_SEPARATOR.'from-png.pdf';
                $pngToPdf = $this->testPngToPdf($pngPath, $pdfFromPng, $checks);
                $checks['png_to_pdf'] = $pngToPdf;
                $checks['pdf_write'] = $pngToPdf['status'] === 'pass'
                    ? $this->passCheck('PDF written from PNG', $pngToPdf['details'])
                    : $this->failCheck('PDF write (PNG → PDF) failed', $pngToPdf['details']);

                if ($pngToPdf['status'] === 'pass' && is_file($pdfFromPng)) {
                    $checks['pdf_read'] = $this->testPdfRead($pdfFromPng, $checks);
                    $checks['pdf_to_pdf'] = $this->testPdfToPdf($pdfFromPng, $workDir.DIRECTORY_SEPARATOR.'from-pdf.pdf', $checks);
                } else {
                    $checks['pdf_read'] = $this->failCheck('Skipped: PNG → PDF failed');
                    $checks['pdf_to_pdf'] = $this->failCheck('Skipped: PNG → PDF failed');
                }

                $checks['multipage'] = $this->testMultipage($workDir, $checks);
            }

            if (
                $checks['ghostscript']['status'] !== 'pass'
                && $this->functionalPdfOk($checks)
            ) {
                $notes[] = 'Ghostscript binary nije vidljiv na PATH-u, ali ImageMagick PDF delegate funkcionalno radi.';
            }
        } catch (Throwable $e) {
            Log::warning('PdfEnvironmentDiagnostics exception', [
                'error' => $e->getMessage(),
            ]);
            $notes[] = 'Unexpected diagnostic error: '.$this->sanitizeMessage($e->getMessage());
            foreach ($checks as $key => $check) {
                if (($check['message'] ?? '') === 'not run') {
                    $checks[$key] = $this->failCheck('Aborted: '.$this->sanitizeMessage($e->getMessage()));
                }
            }
        } finally {
            if ($workDir !== null) {
                $this->cleanupDirectory($workDir);
            }
        }

        $ready = $this->functionalPdfOk($checks);

        return [
            'checks' => $checks,
            'ready' => $ready,
            'verdict' => $ready ? self::VERDICT_READY : self::VERDICT_BLOCKED,
            'notes' => $notes,
            'work_dir' => null,
        ];
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     */
    private function functionalPdfOk(array $checks): bool
    {
        foreach (['pdf_read', 'pdf_write', 'pdf_to_pdf', 'multipage'] as $key) {
            if (($checks[$key]['status'] ?? '') !== 'pass') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function emptyCheck(string $message): array
    {
        return ['status' => 'fail', 'message' => $message, 'details' => []];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function passCheck(string $message, array $details = []): array
    {
        return ['status' => 'pass', 'message' => $message, 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function warnCheck(string $message, array $details = []): array
    {
        return ['status' => 'warn', 'message' => $message, 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function failCheck(string $message, array $details = []): array
    {
        return ['status' => 'fail', 'message' => $message, 'details' => $details];
    }

    private function createWorkDir(): string
    {
        $base = storage_path('app/pdf-diagnostics');
        if (! is_dir($base) && ! mkdir($base, 0755, true) && ! is_dir($base)) {
            throw new \RuntimeException('Cannot create pdf-diagnostics directory');
        }

        $dir = $base.DIRECTORY_SEPARATOR.bin2hex(random_bytes(8));
        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Cannot create diagnostic work directory');
        }

        return $dir;
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function checkImagick(): array
    {
        if (! extension_loaded('imagick')) {
            return $this->failCheck('PHP Imagick extension not loaded');
        }

        $details = [];
        try {
            if (method_exists(\Imagick::class, 'getVersion')) {
                $ver = \Imagick::getVersion();
                $details['version'] = is_array($ver)
                    ? (string) ($ver['versionString'] ?? json_encode($ver))
                    : (string) $ver;
            }
        } catch (Throwable $e) {
            $details['version_error'] = $this->sanitizeMessage($e->getMessage());
        }

        try {
            if (method_exists(\Imagick::class, 'getQuantumDepth')) {
                $qd = \Imagick::getQuantumDepth();
                $details['quantum_depth'] = is_array($qd) ? ($qd['quantumDepthString'] ?? $qd) : $qd;
            }
        } catch (Throwable) {
            // optional
        }

        $formats = [];
        try {
            $all = \Imagick::queryFormats();
            foreach (['PDF', 'PS', 'EPS', 'PNG', 'JPEG'] as $fmt) {
                $formats[$fmt] = in_array($fmt, $all, true) || in_array(strtolower($fmt), array_map('strtolower', $all), true);
            }
        } catch (Throwable $e) {
            $details['formats_error'] = $this->sanitizeMessage($e->getMessage());
        }
        $details['formats'] = $formats;

        try {
            if (method_exists(\Imagick::class, 'getConfigureOptions')) {
                $opts = \Imagick::getConfigureOptions();
                if (is_array($opts)) {
                    $relevant = [];
                    foreach (['DELEGATES', 'FEATURES', 'LIBS', 'NAME', 'VERSION'] as $key) {
                        if (isset($opts[$key])) {
                            $relevant[$key] = $this->truncate((string) $opts[$key], 200);
                        }
                    }
                    if ($relevant !== []) {
                        $details['configure'] = $relevant;
                    }
                }
            }
        } catch (Throwable) {
            // optional / may be unsupported
        }

        $pdfListed = ($formats['PDF'] ?? false) === true;
        if (! $pdfListed) {
            return $this->warnCheck('Imagick loaded but PDF not listed in queryFormats()', $details);
        }

        return $this->passCheck('Imagick extension available', $details);
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function checkImageMagickCli(): array
    {
        foreach (['magick', 'convert'] as $binary) {
            $result = $this->runProcess([$binary, '-version']);
            if ($result['exit_code'] === 0) {
                return $this->passCheck('ImageMagick CLI found: '.$binary, [
                    'binary' => $binary,
                    'exit_code' => 0,
                    'version' => $this->firstLine($result['output']),
                ]);
            }
        }

        return $this->warnCheck('magick/convert not found or failed on PATH', [
            'tried' => ['magick', 'convert'],
        ]);
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function checkGhostscript(): array
    {
        foreach (['gs', 'gswin64c', 'gswin32c'] as $binary) {
            $result = $this->runProcess([$binary, '--version']);
            if ($result['exit_code'] === 0) {
                return $this->passCheck('Ghostscript found: '.$binary, [
                    'binary' => $binary,
                    'exit_code' => 0,
                    'version' => $this->firstLine($result['output']),
                ]);
            }

            // Some builds only support -v
            $result = $this->runProcess([$binary, '-v']);
            if ($result['exit_code'] === 0 || str_contains(strtolower($result['output']), 'ghostscript')) {
                return $this->passCheck('Ghostscript found: '.$binary, [
                    'binary' => $binary,
                    'exit_code' => $result['exit_code'],
                    'version' => $this->firstLine($result['output']),
                ]);
            }
        }

        return $this->warnCheck('Ghostscript binary not visible on PATH (gs/gswin64c/gswin32c)', [
            'tried' => ['gs', 'gswin64c', 'gswin32c'],
        ]);
    }

    private function createProbePng(string $path, bool $tryImagick): bool
    {
        if ($tryImagick && extension_loaded('imagick')) {
            try {
                $img = new \Imagick();
                $img->newImage(420, 80, new \ImagickPixel('white'));
                $img->setImageFormat('png');
                $draw = new \ImagickDraw();
                $draw->setFillColor(new \ImagickPixel('black'));
                $draw->setFontSize(16);
                $draw->annotation(12, 48, 'Digital Kotor PDF diagnostic');
                $img->drawImage($draw);
                $ok = $img->writeImage($path);
                $img->clear();
                $img->destroy();
                if ($ok && is_file($path) && filesize($path) > 0) {
                    return true;
                }
            } catch (Throwable $e) {
                Log::info('PdfEnvironmentDiagnostics Imagick PNG probe failed, trying GD', [
                    'error' => $this->sanitizeMessage($e->getMessage()),
                ]);
            }
        }

        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $im = imagecreatetruecolor(420, 80);
        if ($im === false) {
            return false;
        }
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 419, 79, $white);
        imagestring($im, 5, 10, 30, 'Digital Kotor PDF diagnostic', $black);
        $ok = imagepng($im, $path);
        imagedestroy($im);

        return $ok && is_file($path) && filesize($path) > 0;
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function testPngToPdf(string $pngPath, string $pdfPath, array $checks): array
    {
        $details = [];

        if (($checks['imagick']['status'] ?? '') !== 'fail' && extension_loaded('imagick')) {
            try {
                $img = new \Imagick($pngPath);
                $img->setImageFormat('pdf');
                $ok = $img->writeImage($pdfPath);
                $img->clear();
                $img->destroy();
                if ($ok && $this->isValidPdfFile($pdfPath)) {
                    return $this->passCheck('PNG → PDF via Imagick', [
                        'via' => 'imagick',
                        'bytes' => filesize($pdfPath),
                    ]);
                }
                $details['imagick'] = 'write failed or invalid PDF header';
            } catch (Throwable $e) {
                $details['imagick'] = $this->classifyPdfError($e->getMessage());
            }
        }

        $cliBinary = $checks['imagemagick_cli']['details']['binary'] ?? null;
        if (is_string($cliBinary) && $cliBinary !== '') {
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }
            $args = $cliBinary === 'magick'
                ? [$cliBinary, $pngPath, '-colorspace', 'Gray', $pdfPath]
                : [$cliBinary, $pngPath, '-colorspace', 'Gray', $pdfPath];
            $result = $this->runProcess($args);
            $details['cli_exit'] = $result['exit_code'];
            if ($result['exit_code'] === 0 && $this->isValidPdfFile($pdfPath)) {
                return $this->passCheck('PNG → PDF via CLI '.$cliBinary, [
                    'via' => 'cli:'.$cliBinary,
                    'bytes' => filesize($pdfPath),
                    'exit_code' => 0,
                ]);
            }
            $details['cli'] = $this->classifyPdfError($result['output']);
        }

        return $this->failCheck('PNG → PDF failed', $details);
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function testPdfRead(string $pdfPath, array $checks): array
    {
        $details = [];

        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick();
                $img->setResolution(72, 72);
                $img->readImage($pdfPath.'[0]');
                $count = $img->getNumberImages();
                $w = $img->getImageWidth();
                $h = $img->getImageHeight();
                $img->clear();
                $img->destroy();
                if ($count >= 1 && $w > 0 && $h > 0) {
                    $details['imagick'] = [
                        'images' => $count,
                        'width' => $w,
                        'height' => $h,
                    ];

                    $this->tryCliIdentify($pdfPath, $checks, $details);

                    return $this->passCheck('PDF read OK (Imagick)', $details);
                }
                $details['imagick'] = 'empty or zero dimensions';
            } catch (Throwable $e) {
                $details['imagick'] = $this->classifyPdfError($e->getMessage());
            }
        }

        $cliOk = $this->tryCliIdentify($pdfPath, $checks, $details);
        if ($cliOk) {
            return $this->passCheck('PDF read OK (CLI identify)', $details);
        }

        return $this->failCheck('PDF read failed', $details);
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     * @param  array<string, mixed>  $details
     */
    private function tryCliIdentify(string $pdfPath, array $checks, array &$details): bool
    {
        $cliBinary = $checks['imagemagick_cli']['details']['binary'] ?? null;
        $candidates = [];
        if (is_string($cliBinary) && $cliBinary === 'magick') {
            $candidates[] = ['magick', 'identify', $pdfPath];
        }
        $candidates[] = ['identify', $pdfPath];
        if (is_string($cliBinary) && $cliBinary === 'convert') {
            // convert alone cannot identify; try identify separately
        }

        foreach ($candidates as $args) {
            $result = $this->runProcess($args);
            if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
                $details['cli_identify'] = [
                    'command' => $args[0],
                    'exit_code' => 0,
                    'output' => $this->truncate($this->firstLine($result['output']), 160),
                ];

                return true;
            }
            if ($result['output'] !== '') {
                $details['cli_identify_error'] = $this->classifyPdfError($result['output']);
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function testPdfToPdf(string $inputPdf, string $outputPdf, array $checks): array
    {
        $details = [];

        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick();
                $img->setResolution(72, 72);
                $img->readImage($inputPdf);
                foreach ($img as $frame) {
                    $frame->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
                    $frame->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $frame->setImageCompressionQuality(70);
                    $frame->setImageFormat('pdf');
                }
                $ok = $img->writeImages($outputPdf, true);
                $img->clear();
                $img->destroy();

                if ($ok && $this->isValidPdfFile($outputPdf) && $this->canRereadPdf($outputPdf)) {
                    return $this->passCheck('PDF → PDF via Imagick', [
                        'via' => 'imagick',
                        'bytes' => filesize($outputPdf),
                    ]);
                }
                $details['imagick'] = 'write/reread failed';
            } catch (Throwable $e) {
                $details['imagick'] = $this->classifyPdfError($e->getMessage());
            }
        }

        $cliBinary = $checks['imagemagick_cli']['details']['binary'] ?? null;
        if (is_string($cliBinary) && $cliBinary !== '') {
            if (is_file($outputPdf)) {
                @unlink($outputPdf);
            }
            $args = [$cliBinary, '-density', '72', $inputPdf, '-colorspace', 'Gray', '-compress', 'JPEG', $outputPdf];
            $result = $this->runProcess($args);
            $details['cli_exit'] = $result['exit_code'];
            if ($result['exit_code'] === 0 && $this->isValidPdfFile($outputPdf) && $this->canRereadPdf($outputPdf)) {
                return $this->passCheck('PDF → PDF via CLI '.$cliBinary, [
                    'via' => 'cli:'.$cliBinary,
                    'bytes' => filesize($outputPdf),
                    'exit_code' => 0,
                ]);
            }
            $details['cli'] = $this->classifyPdfError($result['output']);
        }

        return $this->failCheck('PDF → PDF failed', $details);
    }

    /**
     * @param  array<string, array{status: string, message: string, details: array<string, mixed>}>  $checks
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    private function testMultipage(string $workDir, array $checks): array
    {
        if (! extension_loaded('imagick')) {
            return $this->failCheck('Multi-page test requires Imagick (merge path uses Imagick)');
        }

        $outPath = $workDir.DIRECTORY_SEPARATOR.'multipage.pdf';

        try {
            $merged = new \Imagick();
            foreach ([0, 1] as $i) {
                $page = new \Imagick();
                $page->newImage(200, 200, new \ImagickPixel($i === 0 ? 'white' : '#dddddd'));
                $page->setImageFormat('pdf');
                $merged->addImage($page);
                $page->clear();
                $page->destroy();
            }

            $ok = $merged->writeImages($outPath, true);
            $merged->clear();
            $merged->destroy();

            if (! $ok || ! $this->isValidPdfFile($outPath)) {
                return $this->failCheck('Multi-page PDF write failed');
            }

            $read = new \Imagick();
            $read->setResolution(72, 72);
            $read->readImage($outPath);
            $pages = $read->getNumberImages();
            $read->clear();
            $read->destroy();

            if ($pages !== 2) {
                return $this->failCheck('Expected 2 pages, got '.$pages, ['pages' => $pages]);
            }

            return $this->passCheck('Multi-page PDF (2 pages) OK', [
                'pages' => 2,
                'bytes' => filesize($outPath),
            ]);
        } catch (Throwable $e) {
            return $this->failCheck('Multi-page PDF failed', [
                'error' => $this->classifyPdfError($e->getMessage()),
            ]);
        }
    }

    private function canRereadPdf(string $pdfPath): bool
    {
        if (! extension_loaded('imagick')) {
            return $this->isValidPdfFile($pdfPath);
        }

        try {
            $img = new \Imagick();
            $img->setResolution(72, 72);
            $img->readImage($pdfPath.'[0]');
            $ok = $img->getNumberImages() >= 1;
            $img->clear();
            $img->destroy();

            return $ok;
        } catch (Throwable) {
            return false;
        }
    }

    private function isValidPdfFile(string $path): bool
    {
        if (! is_file($path) || filesize($path) <= 0) {
            return false;
        }

        $header = (string) file_get_contents($path, false, null, 0, 5);

        return str_starts_with($header, '%PDF-');
    }

    /**
     * @param  list<string>  $command
     * @return array{exit_code: int, output: string}
     */
    private function runProcess(array $command): array
    {
        try {
            $result = Process::timeout(self::PROCESS_TIMEOUT)->run($command);
            $output = $result->output().$result->errorOutput();

            return [
                'exit_code' => $result->exitCode() ?? 1,
                'output' => $this->truncate($output, self::MAX_PROCESS_OUTPUT),
            ];
        } catch (Throwable $e) {
            return [
                'exit_code' => 1,
                'output' => $this->sanitizeMessage($e->getMessage()),
            ];
        }
    }

    private function classifyPdfError(string $message): string
    {
        $lower = strtolower($message);
        if (
            str_contains($lower, 'not authorized')
            || str_contains($lower, 'security policy')
            || (str_contains($lower, 'policy') && str_contains($lower, 'pdf'))
        ) {
            return 'PDF operation blocked by ImageMagick security policy';
        }

        return $this->truncate($this->sanitizeMessage($message), 240);
    }

    private function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/[A-Za-z]:\\\\[^\s]+/', '[path]', $message) ?? $message;
        $message = preg_replace('/\/(?:home|var|usr|tmp|storage)[^\s]*/', '[path]', $message) ?? $message;

        return trim($message);
    }

    private function firstLine(string $output): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($output)) ?: [];

        return $this->truncate((string) ($lines[0] ?? ''), 200);
    }

    private function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 1).'…';
    }

    private function cleanupDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->cleanupDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
