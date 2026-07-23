<?php

namespace Tests\Feature;

use App\Models\UserDocument;
use App\Services\DocumentImageFingerprintDiagnostics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CheckDocumentImageFingerprintCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_registered(): void
    {
        $exit = Artisan::call('list');
        $out = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('document:fingerprint-check', $out);
    }

    public function test_command_reports_missing_imagick_without_failing_hard_when_all_unsupported_or_pass(): void
    {
        if (extension_loaded('imagick')) {
            $this->markTestSkipped('Covers missing-Imagick path');
        }

        Bus::fake();
        $before = UserDocument::query()->count();

        $exit = Artisan::call('document:fingerprint-check');
        $out = Artisan::output();

        // Without Imagick the environment check is FAIL → exit 1 (UNSUPPORTED alone would be 0).
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Document image fingerprint check', $out);
        $this->assertStringContainsString('[FAIL]', $out);
        $this->assertStringContainsString('Imagick', $out);
        $this->assertStringNotContainsString('documents/user_', $out);
        $this->assertStringNotContainsString(storage_path('app'), $out);
        $this->assertSame($before, UserDocument::query()->count());
        Bus::assertNothingDispatched();
    }

    public function test_command_exit_zero_when_diagnostics_report_no_failures(): void
    {
        $this->mock(DocumentImageFingerprintDiagnostics::class, function ($mock) {
            $mock->shouldReceive('run')->once()->with(false)->andReturn([
                'checks' => [
                    ['name' => 'A. Imagick okruženje', 'status' => 'pass', 'message' => 'ok'],
                ],
                'pass' => 1,
                'fail' => 0,
                'unsupported' => 0,
                'cleanup_ok' => true,
                'peak_memory_bytes' => 1024 * 1024,
                'memory_limit' => '128M',
                'compare' => null,
            ]);
        });

        $exit = Artisan::call('document:fingerprint-check');
        $out = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('[PASS]', $out);
        $this->assertStringContainsString('DOCUMENT FINGERPRINT CHECK READY', $out);
        $this->assertStringContainsString('Cleanup: OK', $out);
    }

    public function test_command_exit_one_when_diagnostics_report_failure(): void
    {
        $this->mock(DocumentImageFingerprintDiagnostics::class, function ($mock) {
            $mock->shouldReceive('run')->once()->with(false)->andReturn([
                'checks' => [
                    ['name' => 'A. Imagick okruženje', 'status' => 'fail', 'message' => 'not loaded'],
                ],
                'pass' => 0,
                'fail' => 1,
                'unsupported' => 0,
                'cleanup_ok' => true,
                'peak_memory_bytes' => 1024,
                'memory_limit' => '128M',
                'compare' => null,
            ]);
        });

        $exit = Artisan::call('document:fingerprint-check');
        $out = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('[FAIL]', $out);
        $this->assertStringContainsString('DOCUMENT FINGERPRINT CHECK FAILED', $out);
    }

    public function test_compare_option_reports_missing_input_files_clearly(): void
    {
        $inputDir = storage_path('app/'.DocumentImageFingerprintDiagnostics::INPUT_RELATIVE_DIR);
        if (! is_dir($inputDir)) {
            mkdir($inputDir, 0755, true);
        }
        @unlink($inputDir.DIRECTORY_SEPARATOR.'capture01.png');
        @unlink($inputDir.DIRECTORY_SEPARATOR.'capture05.png');

        $exit = Artisan::call('document:fingerprint-check', ['--compare' => true]);
        $out = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('storage/app/document-fingerprint-input/capture01.png', $out);
        $this->assertStringNotContainsString(storage_path('app'), $out);
    }

    public function test_cleanup_helper_removes_work_directory(): void
    {
        $base = storage_path('app/document-fingerprint-diagnostics');
        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }
        $dir = $base.DIRECTORY_SEPARATOR.'unittest-'.bin2hex(random_bytes(4));
        mkdir($dir, 0755, true);
        file_put_contents($dir.DIRECTORY_SEPARATOR.'probe.txt', 'x');

        $ok = app(DocumentImageFingerprintDiagnostics::class)->cleanupDirectory($dir);

        $this->assertTrue($ok);
        $this->assertDirectoryDoesNotExist($dir);
    }
}
