<?php

namespace App\Console\Commands;

use App\Services\DocumentImageFingerprintDiagnostics;
use Illuminate\Console\Command;

class CheckDocumentImageFingerprint extends Command
{
    protected $signature = 'document:fingerprint-check
                            {--compare : Compare storage/app/document-fingerprint-input/capture01.png and capture05.png}';

    protected $description = 'Diagnose DocumentImageFingerprint (Imagick pixel duplicates) without using user documents';

    public function handle(DocumentImageFingerprintDiagnostics $diagnostics): int
    {
        $this->info('Digital Kotor — Document image fingerprint check');
        $this->line(str_repeat('=', 56));
        $this->line('Safe probe only (storage/app/document-fingerprint-diagnostics). No user documents.');
        if ($this->option('compare')) {
            $this->line('Compare mode: storage/app/document-fingerprint-input/capture01.png + capture05.png (not deleted).');
        }
        $this->newLine();

        $result = $diagnostics->run((bool) $this->option('compare'));

        foreach ($result['checks'] as $check) {
            $status = strtoupper((string) ($check['status'] ?? 'fail'));
            $line = sprintf('[%s] %s', $status, $check['name'] ?? 'check');
            if (($check['message'] ?? '') !== '') {
                $line .= ' — '.$this->shortMessage((string) $check['message']);
            }

            match ($check['status'] ?? 'fail') {
                'pass' => $this->line($line),
                'unsupported' => $this->warn($line),
                default => $this->error($line),
            };
        }

        $compare = $result['compare'] ?? null;
        if (is_array($compare)) {
            $this->newLine();
            $this->info('Compare capture files');
            if (! ($compare['ok'] ?? false)) {
                $this->error((string) ($compare['error'] ?? 'Compare failed'));
            } else {
                $this->line('Fajl A: '.$compare['file_a'].' ('.$compare['dims_a'].')');
                $this->line('Fajl B: '.$compare['file_b'].' ('.$compare['dims_b'].')');
                $this->line('Binarni SHA-256 isti: '.(($compare['binary_same'] ?? false) ? 'DA' : 'NE'));
                $this->line('Pixel fingerprint isti: '.(($compare['fingerprint_same'] ?? false) ? 'DA' : 'NE'));
                $this->line('Zaključak: '.(string) ($compare['verdict'] ?? ''));
            }
        }

        $this->newLine();
        $this->line('PASS: '.$result['pass']);
        $this->line('FAIL: '.$result['fail']);
        $this->line('UNSUPPORTED: '.$result['unsupported']);
        $this->line('Peak memory: '.$this->formatBytes((int) $result['peak_memory_bytes']));
        $this->line('memory_limit: '.(string) $result['memory_limit']);
        $this->line('Cleanup: '.(($result['cleanup_ok'] ?? false) ? 'OK' : 'FAILED'));

        $this->newLine();
        if ((int) $result['fail'] === 0) {
            $this->info('DOCUMENT FINGERPRINT CHECK READY');

            return self::SUCCESS;
        }

        $this->error('DOCUMENT FINGERPRINT CHECK FAILED');

        return self::FAILURE;
    }

    private function shortMessage(string $message): string
    {
        if (mb_strlen($message) <= 90) {
            return $message;
        }

        return mb_substr($message, 0, 89).'…';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
