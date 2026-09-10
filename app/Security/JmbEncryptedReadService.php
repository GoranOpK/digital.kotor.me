<?php

namespace App\Security;

use Illuminate\Support\Facades\Log;

/**
 * Encrypted-first VALUE read for stored JMB/JMBG pairs (Faza D).
 *
 * Does not inspect plaintext when ciphertext is present.
 * Temporary plaintext fallback only when ciphertext is absent and
 * plaintext retirement is disabled.
 * Does not log JMB, ciphertext, encryption keys, or APP_KEY.
 */
final class JmbEncryptedReadService
{
    /**
     * @var array<string, true>
     */
    private array $plaintextFallbackEmitted = [];

    public function readValue(
        #[\SensitiveParameter] ?string $encrypted,
        #[\SensitiveParameter] ?string $plaintext,
        ?string $table = null,
        int|string|null $recordId = null,
        ?string $pair = null,
    ): ?string {
        $encrypted = $this->present($encrypted) ? $encrypted : null;
        $plaintext = $this->present($plaintext) ? $plaintext : null;

        if ($encrypted !== null) {
            try {
                $decrypted = app(JmbEncryptionService::class)->decrypt($encrypted);
            } catch (JmbEncryptionException $e) {
                throw new JmbEncryptedReadException(
                    'Encrypted identifier could not be decrypted.',
                    0,
                    $e
                );
            }

            if (! $this->present($decrypted)) {
                throw new JmbEncryptedReadException('Encrypted identifier could not be decrypted.');
            }

            return $decrypted;
        }

        if ($plaintext === null || (bool) config('jmb.plaintext_retirement.enabled', false)) {
            return null;
        }

        $this->emitPlaintextFallback($table, $recordId, $pair);

        return $plaintext;
    }

    public static function pairIsPresent(?string $encrypted, ?string $plaintext): bool
    {
        return self::isPresent($encrypted) || self::isPresent($plaintext);
    }

    private function emitPlaintextFallback(?string $table, int|string|null $recordId, ?string $pair): void
    {
        $dedupeKey = implode('|', [
            (string) $table,
            (string) $recordId,
            (string) $pair,
        ]);

        if (isset($this->plaintextFallbackEmitted[$dedupeKey])) {
            return;
        }

        $this->plaintextFallbackEmitted[$dedupeKey] = true;

        Log::warning('jmb.encrypted_read', [
            'table' => $table,
            'id' => $recordId,
            'pair' => $pair,
            'reason' => 'plaintext_fallback',
        ]);
    }

    private function present(?string $value): bool
    {
        return self::isPresent($value);
    }

    private static function isPresent(?string $value): bool
    {
        return $value !== null && $value !== '';
    }
}
