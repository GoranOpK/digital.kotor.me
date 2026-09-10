<?php

namespace App\Security;

use Illuminate\Contracts\Config\Repository;

/**
 * Deterministic keyed JMB lookup digest. Dedicated lookup key only
 * (never APP_KEY, never JMB_ENCRYPTION_KEY).
 *
 * Stored representation: 64-character lowercase hex HMAC-SHA-256.
 * Does not prepend key_id. Does not log plaintext, digest, or keys.
 *
 * Null / empty / whitespace-only input yields null without requiring a key.
 * Non-empty input that is not exactly 13 ASCII digits fails closed.
 * Missing or malformed lookup key fails closed for non-empty JMB.
 */
final class JmbLookupService
{
    public const DIGEST_LENGTH = 64;

    public function __construct(
        #[\SensitiveParameter] private readonly mixed $configuredKey,
        private readonly string $keyId,
    ) {
    }

    public static function fromConfig(?Repository $config = null): self
    {
        $config ??= config();

        return new self(
            $config->get('jmb.lookup.key'),
            (string) $config->get('jmb.lookup.key_id', 'v1'),
        );
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function digest(#[\SensitiveParameter] ?string $jmb): ?string
    {
        $normalized = $this->normalize($jmb);
        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, $this->rawKey());
    }

    private function normalize(#[\SensitiveParameter] ?string $jmb): ?string
    {
        if ($jmb === null) {
            return null;
        }

        $normalized = trim($jmb);
        if ($normalized === '') {
            return null;
        }

        if (! preg_match('/^[0-9]{13}$/', $normalized)) {
            throw new JmbLookupException('JMB lookup value is invalid.');
        }

        return $normalized;
    }

    private function rawKey(): string
    {
        if (! is_string($this->configuredKey)) {
            throw new JmbLookupException(
                'JMB lookup key is missing. Set JMB_LOOKUP_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY or JMB_ENCRYPTION_KEY.'
            );
        }

        $key = trim($this->configuredKey);
        if ($key === '') {
            throw new JmbLookupException(
                'JMB lookup key is missing. Set JMB_LOOKUP_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY or JMB_ENCRYPTION_KEY.'
            );
        }

        if (! str_starts_with($key, 'base64:')) {
            throw new JmbLookupException(
                'JMB lookup key is invalid. Set JMB_LOOKUP_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY or JMB_ENCRYPTION_KEY.'
            );
        }

        $decoded = base64_decode(substr($key, 7), true);
        if ($decoded === false || $decoded === '') {
            throw new JmbLookupException(
                'JMB lookup key is invalid. Set JMB_LOOKUP_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY or JMB_ENCRYPTION_KEY.'
            );
        }

        if (strlen($decoded) !== 32) {
            throw new JmbLookupException(
                'JMB lookup key is invalid. Expected 32 bytes. Do not use APP_KEY or JMB_ENCRYPTION_KEY.'
            );
        }

        return $decoded;
    }
}
