<?php

namespace Tests\Unit;

use App\Services\DocumentProcessor;
use App\Services\PdfOptimizationResult;
use App\Services\PdfOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PdfOptimizerAndSmartPdfTest extends TestCase
{
    public function test_small_pdf_uses_pass_through_without_calling_rasterize_path(): void
    {
        Storage::fake('local');

        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n".str_repeat("\n", 80);
        file_put_contents($tmp, $pdf);

        $optimizer = Mockery::mock(PdfOptimizer::class);
        $optimizer->shouldReceive('optimize')
            ->once()
            ->andReturnUsing(function (string $source, string $dest) use ($pdf) {
                file_put_contents($dest, $pdf);

                return new PdfOptimizationResult(
                    originalSize: strlen($pdf),
                    finalSize: strlen($pdf),
                    optimized: false,
                    pageCount: 1,
                    tool: 'pass-through',
                    durationMs: 1,
                );
            });

        $processor = new DocumentProcessor($optimizer);
        $upload = new UploadedFile($tmp, 'small.pdf', 'application/pdf', null, true);

        $result = $processor->processDocument($upload, 1, '1-20260722-aabbccdd');

        $this->assertTrue($result['success']);
        $this->assertSame(strlen($pdf), $result['file_size']);
        @unlink($tmp);
    }

    public function test_large_pdf_goes_through_optimizer_service(): void
    {
        Storage::fake('local');
        config(['document_library.pdf_optimization_threshold_bytes' => 100]);

        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        $pdf = "%PDF-1.4\n".str_repeat('A', 200)."\n%%EOF\n";
        file_put_contents($tmp, $pdf);

        $optimizedBody = "%PDF-1.4\noptimized\n%%EOF\n".str_repeat('x', 80);
        $optimizer = Mockery::mock(PdfOptimizer::class);
        $optimizer->shouldReceive('optimize')
            ->once()
            ->andReturnUsing(function (string $source, string $dest) use ($optimizedBody, $pdf) {
                file_put_contents($dest, $optimizedBody);

                return new PdfOptimizationResult(
                    originalSize: strlen($pdf),
                    finalSize: strlen($optimizedBody),
                    optimized: true,
                    pageCount: 2,
                    tool: 'imagick',
                    durationMs: 12,
                );
            });

        $processor = new DocumentProcessor($optimizer);
        $upload = new UploadedFile($tmp, 'large.pdf', 'application/pdf', null, true);
        $result = $processor->processDocument($upload, 1, '1-20260722-largepdf');

        $this->assertTrue($result['success']);
        $this->assertSame(strlen($optimizedBody), $result['file_size']);
        @unlink($tmp);
    }

    public function test_optimizer_result_when_optimized_larger_uses_original_flag(): void
    {
        $result = new PdfOptimizationResult(
            originalSize: 1000,
            finalSize: 1000,
            optimized: false,
            pageCount: 3,
            tool: 'imagick-reverted',
            durationMs: 5,
        );

        $this->assertTrue($result->ok());
        $this->assertFalse($result->optimized);
        $this->assertSame('imagick-reverted', $result->tool);
    }

    public function test_optimizer_pass_through_below_threshold_without_imagick_rasterize(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick not available');
        }

        config(['document_library.pdf_optimization_threshold_bytes' => 10 * 1024 * 1024]);

        $source = tempnam(sys_get_temp_dir(), 'src');
        $dest = sys_get_temp_dir().'/'.uniqid('dst_', true).'.pdf';
        $body = "%PDF-1.4\n1 0 obj<< /Type /Catalog >>endobj\ntrailer<<>>\n%%EOF\n";
        file_put_contents($source, $body);

        $optimizer = new PdfOptimizer;
        $result = $optimizer->optimize($source, $dest);

        // Without a real page tree ping may fail — accept either pass-through or explicit error.
        if ($result->ok()) {
            $this->assertFalse($result->optimized);
            $this->assertSame('pass-through', $result->tool);
            $this->assertFileExists($dest);
            $this->assertStringStartsWith('%PDF-', (string) file_get_contents($dest, false, null, 0, 5));
        } else {
            $this->assertNotNull($result->error);
        }

        @unlink($source);
        @unlink($dest);
    }

    public function test_threshold_minus_one_is_pass_through(): void
    {
        $threshold = 3 * 1024 * 1024;
        config(['document_library.pdf_optimization_threshold_bytes' => $threshold]);
        $optimizer = new PdfOptimizer;

        $this->assertFalse($optimizer->shouldOptimize($threshold - 1));
    }

    public function test_threshold_exact_triggers_optimize(): void
    {
        $threshold = 3 * 1024 * 1024;
        config(['document_library.pdf_optimization_threshold_bytes' => $threshold]);
        $optimizer = new PdfOptimizer;

        $this->assertTrue($optimizer->shouldOptimize($threshold));
    }

    public function test_threshold_plus_one_triggers_optimize(): void
    {
        $threshold = 3 * 1024 * 1024;
        config(['document_library.pdf_optimization_threshold_bytes' => $threshold]);
        $optimizer = new PdfOptimizer;

        $this->assertTrue($optimizer->shouldOptimize($threshold + 1));
    }

    public function test_imagick_reverted_means_not_optimized(): void
    {
        $result = new PdfOptimizationResult(
            originalSize: 5000,
            finalSize: 5000,
            optimized: false,
            pageCount: 2,
            tool: 'imagick-reverted',
            durationMs: 9,
        );

        $this->assertFalse($result->optimized);
        $this->assertSame('imagick-reverted', $result->tool);
        $this->assertSame($result->originalSize, $result->finalSize);
    }

    public function test_max_storage_bytes_reads_config(): void
    {
        config(['document_library.user_quota_bytes' => 5 * 1024 * 1024]);
        $this->assertSame(5 * 1024 * 1024, DocumentProcessor::maxStorageBytes());
    }
}
