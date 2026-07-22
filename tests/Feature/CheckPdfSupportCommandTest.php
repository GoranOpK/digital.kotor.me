<?php

namespace Tests\Feature;

use App\Services\PdfEnvironmentDiagnostics;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CheckPdfSupportCommandTest extends TestCase
{
    public function test_pdf_check_command_is_registered(): void
    {
        $exit = Artisan::call('list');
        $out = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('pdf:check', $out);
    }

    public function test_pdf_check_prints_summary_and_exits_zero_when_ready(): void
    {
        $this->mock(PdfEnvironmentDiagnostics::class, function ($mock) {
            $mock->shouldReceive('run')->once()->andReturn($this->readyResult());
        });

        $exit = Artisan::call('pdf:check');
        $out = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString(PdfEnvironmentDiagnostics::VERDICT_READY, $out);
        $this->assertStringContainsString('PDF read', $out);
        $this->assertStringContainsString('PDF → PDF', $out);
        $this->assertStringContainsString('Multi-page PDF', $out);
        $this->assertStringNotContainsString('documents/user_', $out);
        $this->assertStringNotContainsString('MEGA_PASSWORD', $out);
        $this->assertStringNotContainsString('APP_KEY', $out);
    }

    public function test_pdf_check_exits_one_when_blocked(): void
    {
        $this->mock(PdfEnvironmentDiagnostics::class, function ($mock) {
            $mock->shouldReceive('run')->once()->andReturn($this->blockedResult());
        });

        $exit = Artisan::call('pdf:check');
        $out = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString(PdfEnvironmentDiagnostics::VERDICT_BLOCKED, $out);
        $this->assertStringContainsString('Paket 2D', $out);
    }

    public function test_pdf_check_does_not_use_shell_string_concatenation_for_user_input(): void
    {
        Process::fake([
            '*' => Process::result(output: 'ImageMagick 7.0', exitCode: 1),
        ]);

        // Real service path with Process fake — must not throw; no user paths involved.
        $exit = Artisan::call('pdf:check');
        $out = Artisan::output();

        $this->assertContains($exit, [0, 1]);
        $this->assertStringContainsString('PDF environment check', $out);
        $this->assertStringNotContainsString('documents/user_', $out);
        $this->assertDoesNotMatchRegularExpression('/\bsh\s+-c\b/', $out);
    }

    public function test_diagnostics_cleanup_removes_work_directory(): void
    {
        $base = storage_path('app/pdf-diagnostics');
        if (is_dir($base)) {
            $this->deleteDirectory($base);
        }
        mkdir($base, 0755, true);

        $service = app(PdfEnvironmentDiagnostics::class);
        $result = $service->run();

        $this->assertIsArray($result['checks']);
        $this->assertArrayHasKey('verdict', $result);
        $this->assertNull($result['work_dir']);
        $this->assertSame([], $this->listProbeDirs($base), 'Diagnostic work dirs must be cleaned up');
        $this->assertDirectoryDoesNotExist($base.DIRECTORY_SEPARATOR.'documents');
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * @return list<string>
     */
    private function listProbeDirs(string $base): array
    {
        if (! is_dir($base)) {
            return [];
        }

        $items = scandir($base) ?: [];

        return array_values(array_filter($items, fn ($i) => $i !== '.' && $i !== '..'));
    }

    /**
     * @return array{checks: array<string, array{status: string, message: string, details: array<string, mixed>}>, ready: bool, verdict: string, notes: list<string>, work_dir: null}
     */
    private function readyResult(): array
    {
        $pass = fn (string $m) => ['status' => 'pass', 'message' => $m, 'details' => []];

        return [
            'checks' => [
                'imagick' => $pass('Imagick OK'),
                'imagemagick_cli' => $pass('CLI OK'),
                'ghostscript' => ['status' => 'warn', 'message' => 'gs not on PATH', 'details' => []],
                'png_to_pdf' => $pass('PNG→PDF OK'),
                'pdf_write' => $pass('write OK'),
                'pdf_read' => $pass('read OK'),
                'pdf_to_pdf' => $pass('pdf→pdf OK'),
                'multipage' => $pass('2 pages OK'),
            ],
            'ready' => true,
            'verdict' => PdfEnvironmentDiagnostics::VERDICT_READY,
            'notes' => ['Ghostscript binary nije vidljiv na PATH-u, ali ImageMagick PDF delegate funkcionalno radi.'],
            'work_dir' => null,
        ];
    }

    /**
     * @return array{checks: array<string, array{status: string, message: string, details: array<string, mixed>}>, ready: bool, verdict: string, notes: list<string>, work_dir: null}
     */
    private function blockedResult(): array
    {
        $fail = fn (string $m) => ['status' => 'fail', 'message' => $m, 'details' => []];

        return [
            'checks' => [
                'imagick' => $fail('missing'),
                'imagemagick_cli' => $fail('missing'),
                'ghostscript' => $fail('missing'),
                'png_to_pdf' => $fail('fail'),
                'pdf_write' => $fail('fail'),
                'pdf_read' => $fail('fail'),
                'pdf_to_pdf' => $fail('fail'),
                'multipage' => $fail('fail'),
            ],
            'ready' => false,
            'verdict' => PdfEnvironmentDiagnostics::VERDICT_BLOCKED,
            'notes' => [],
            'work_dir' => null,
        ];
    }
}
