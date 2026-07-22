<?php

namespace App\Services\ExternalArchive;

/**
 * Conservative classification of MEGA client / process errors for v1 retry policy.
 * When uncertain, errors are non-transient.
 */
final class MegaArchiveFailureClassifier
{
    private const MAX_STORED_LENGTH = 500;

    /**
     * @var list<string>
     */
    private const TRANSIENT_SUBSTRINGS = [
        'timeout',
        'timed out',
        'etimedout',
        'econnreset',
        'enotfound',
        'eai_again',
        'econnrefused',
        'econnaborted',
        'enetunreach',
        'epipe',
        'socket hang up',
        'socket hangup',
        'temporary failure',
        'temporarily unavailable',
        'service unavailable',
        'bad gateway',
        'gateway timeout',
        ' 502',
        ' 503',
        ' 504',
        '503',
        '502',
        '504',
        'rate limit',
        'too many requests',
    ];

    /**
     * Non-transient hints checked before transient (stricter).
     *
     * @var list<string>
     */
    private const NON_TRANSIENT_SUBSTRINGS = [
        'wrong password',
        'invalid password',
        'bad credentials',
        'authentication',
        'login failed',
        'login fail',
        'unable to log in',
        'folder not found',
        'folder missing',
        'no such folder',
        'unknown folder',
        'base folder',
        'not configured',
        'mega_email',
        'mega_password',
        'mega-archive.js missing',
        'empty mega script output',
        'invalid json from mega script',
        'local file does not exist',
        'does not exist on private disk',
        'corrupt',
        'invalid image',
        'archive is not in uploaded state',
        'unsafe local path',
        'enoent',
    ];

    public function isTransient(string $errorMessage): bool
    {
        $norm = mb_strtolower(trim($errorMessage));
        if ($norm === '') {
            return false;
        }

        foreach (self::NON_TRANSIENT_SUBSTRINGS as $needle) {
            if (str_contains($norm, $needle)) {
                return false;
            }
        }

        foreach (self::TRANSIENT_SUBSTRINGS as $needle) {
            if (str_contains($norm, $needle)) {
                return true;
            }
        }

        if (str_contains($norm, 'mega process failed')) {
            return $this->processFailureLooksTransient($norm);
        }

        return false;
    }

    public function shortReason(string $errorMessage): string
    {
        return mb_substr($this->sanitize($errorMessage), 0, 200);
    }

    /**
     * Sanitize error text for DB/log: redact secrets, collapse whitespace, truncate.
     */
    public function sanitize(string $errorMessage): string
    {
        $t = trim($errorMessage);
        if ($t === '') {
            return 'unknown_error';
        }

        $t = preg_replace('/\s+/', ' ', $t) ?? $t;
        $t = preg_replace('/MEGA_PASSWORD\s*[=:]\s*\S+/i', 'MEGA_PASSWORD=[redacted]', $t) ?? $t;
        $t = preg_replace('/password\s*[=:]\s*\S+/i', 'password=[redacted]', $t) ?? $t;
        $t = preg_replace('/--password[=\s]+\S+/i', '--password=[redacted]', $t) ?? $t;
        $t = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[redacted-email]', $t) ?? $t;

        return mb_substr($t, 0, self::MAX_STORED_LENGTH);
    }

    private function processFailureLooksTransient(string $norm): bool
    {
        foreach (self::TRANSIENT_SUBSTRINGS as $needle) {
            if (str_contains($norm, $needle)) {
                return true;
            }
        }

        return false;
    }
}
