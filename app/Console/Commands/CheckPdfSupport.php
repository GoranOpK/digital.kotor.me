<?php

namespace App\Console\Commands;

use App\Services\PdfEnvironmentDiagnostics;
use Illuminate\Console\Command;

class CheckPdfSupport extends Command
{
    protected $signature = 'pdf:check';

    protected $description = 'Diagnose PDF read/write support (Imagick, ImageMagick CLI, Ghostscript) without using user documents';

    public function handle(PdfEnvironmentDiagnostics $diagnostics): int
    {
        $this->info('Digital Kotor — PDF environment check');
        $this->line(str_repeat('=', 48));
        $this->line('Safe probe only (storage/app/pdf-diagnostics). No user documents.');
        $this->newLine();

        $result = $diagnostics->run();
        $checks = $result['checks'];

        $rows = [];
        foreach ([
            'imagick' => 'PHP Imagick',
            'imagemagick_cli' => 'ImageMagick CLI',
            'ghostscript' => 'Ghostscript binary',
            'png_to_pdf' => 'PNG → PDF',
            'pdf_write' => 'PDF write',
            'pdf_read' => 'PDF read',
            'pdf_to_pdf' => 'PDF → PDF',
            'multipage' => 'Multi-page PDF',
        ] as $key => $label) {
            $check = $checks[$key] ?? ['status' => 'fail', 'message' => 'missing', 'details' => []];
            $status = strtoupper((string) ($check['status'] ?? 'fail'));
            $this->line(sprintf('[%s] %s — %s', $status, $label, $check['message'] ?? ''));

            if ($this->output->isVerbose() && ! empty($check['details'])) {
                foreach ($check['details'] as $detailKey => $detailValue) {
                    $rendered = is_scalar($detailValue)
                        ? (string) $detailValue
                        : json_encode($detailValue, JSON_UNESCAPED_UNICODE);
                    $this->line('    '.$detailKey.': '.$rendered);
                }
            }

            $rows[] = [$label, $status, $this->shortMessage((string) ($check['message'] ?? ''))];
        }

        $this->newLine();
        $this->table(['Check', 'Status', 'Message'], $rows);

        foreach ($result['notes'] ?? [] as $note) {
            $this->warn((string) $note);
        }

        $this->newLine();
        if ($result['ready'] ?? false) {
            $this->info($result['verdict']);
            $this->line('Key tests passed: PDF read, PDF write, PDF → PDF, multi-page.');

            return self::SUCCESS;
        }

        $this->error($result['verdict']);
        $this->line('Paket 2D PDF optimization remains blocked until production pdf:check is READY.');

        return self::FAILURE;
    }

    private function shortMessage(string $message): string
    {
        if (mb_strlen($message) <= 72) {
            return $message;
        }

        return mb_substr($message, 0, 71).'…';
    }
}
