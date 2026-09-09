<?php

namespace App\Security;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Reversible JMB/JMBG encryption with a dedicated keyring (never APP_KEY).
 *
 * Stored representation (Phase A TEXT columns, no extra DB fields):
 *   jmb:<key_id>:<laravel_payload>
 *
 * - `jmb` is a fixed scheme marker.
 * - `key_id` identifies which JMB key encrypted the payload.
 * - `laravel_payload` is Illuminate\Encryption\Encrypter::encryptString()
 *   output for AES-256-GCM (base64 JSON: iv, value, mac, tag).
 *
 * encrypt() always uses the active key_id (JMB_ENCRYPTION_KEY_ID).
 * decrypt() uses exactly the envelope key_id from the active key or
 * JMB_ENCRYPTION_PREVIOUS_KEYS (decrypt-only). No key fallback.
 *
 * Null and empty string are stored as SQL NULL (not encrypted).
 * Decrypt never returns the ciphertext or a guessed plaintext on failure.
 */
final class JmbEncryptionService
{
    public const SCHEME = 'jmb';

    public const CIPHER = 'AES-256-GCM';

    /**
     * @param  array<string, Encrypter>  $encrypters
     */
    public function __construct(
        private readonly string $activeKeyId,
        private readonly array $encrypters,
    ) {
        self::assertKeyId($activeKeyId);

        if (! isset($this->encrypters[$this->activeKeyId])) {
            throw new JmbEncryptionException('Active JMB encryption key is missing from the keyring.');
        }
    }

    public static function fromConfig(?Repository $config = null): self
    {
        $config ??= config();

        $cipher = (string) $config->get('jmb.encryption.cipher', self::CIPHER);
        $activeKeyId = (string) $config->get('jmb.encryption.key_id', 'v1');
        self::assertKeyId($activeKeyId);

        $encrypters = [
            $activeKeyId => self::makeEncrypter(
                self::parseKey((string) $config->get('jmb.encryption.key', ''), $cipher),
                $cipher
            ),
        ];

        foreach (self::parsePreviousKeyMap($config->get('jmb.encryption.previous_keys'), $activeKeyId, $cipher) as $keyId => $rawKey) {
            $encrypters[$keyId] = self::makeEncrypter($rawKey, $cipher);
        }

        return new self($activeKeyId, $encrypters);
    }

    public function encrypt(#[\SensitiveParameter] ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (self::looksLikeEnvelope($value)) {
            throw new JmbEncryptionException('Refusing to encrypt a value that is already JMB ciphertext.');
        }

        try {
            $payload = $this->encrypters[$this->activeKeyId]->encryptString($value);
        } catch (EncryptException $e) {
            throw new JmbEncryptionException('Unable to encrypt JMB value.', 0, $e);
        }

        return self::SCHEME.':'.$this->activeKeyId.':'.$payload;
    }

    public function decrypt(#[\SensitiveParameter] ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        [$keyId, $payload] = $this->parseEnvelope($value);

        if (! isset($this->encrypters[$keyId])) {
            throw new JmbEncryptionException('Unsupported JMB encryption key id.');
        }

        try {
            return $this->encrypters[$keyId]->decryptString($payload);
        } catch (DecryptException $e) {
            throw new JmbEncryptionException('Unable to decrypt JMB value.', 0, $e);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseEnvelope(string $value): array
    {
        $parts = explode(':', $value, 3);

        if (count($parts) !== 3 || $parts[0] !== self::SCHEME || $parts[1] === '' || $parts[2] === '') {
            throw new JmbEncryptionException('Malformed JMB ciphertext.');
        }

        return [$parts[1], $parts[2]];
    }

    private static function looksLikeEnvelope(string $value): bool
    {
        $parts = explode(':', $value, 3);

        return count($parts) === 3 && $parts[0] === self::SCHEME && $parts[1] !== '' && $parts[2] !== '';
    }

    private static function assertKeyId(string $keyId): void
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,31}$/', $keyId)) {
            throw new JmbEncryptionException('JMB encryption key id is invalid.');
        }
    }

    private static function makeEncrypter(#[\SensitiveParameter] string $rawKey, string $cipher): Encrypter
    {
        try {
            return new Encrypter($rawKey, $cipher);
        } catch (RuntimeException $e) {
            throw new JmbEncryptionException(
                'JMB encryption key is invalid for AES-256-GCM. Expected 32 bytes. Do not use APP_KEY.',
                0,
                $e
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private static function parsePreviousKeyMap(mixed $raw, string $activeKeyId, string $cipher): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode(trim($raw), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new JmbEncryptionException('JMB previous keys configuration is invalid.');
            }
            $raw = $decoded;
        }

        if (! is_array($raw) || ($raw !== [] && array_is_list($raw))) {
            throw new JmbEncryptionException('JMB previous keys configuration is invalid.');
        }

        $keys = [];
        foreach ($raw as $keyId => $key) {
            $keyId = (string) $keyId;
            self::assertKeyId($keyId);

            if ($keyId === $activeKeyId) {
                throw new JmbEncryptionException('JMB previous key id collides with the active key id.');
            }

            $keys[$keyId] = self::parseKey((string) $key, $cipher);
        }

        return $keys;
    }

    private static function parseKey(#[\SensitiveParameter] string $key, string $cipher): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new JmbEncryptionException(
                'JMB encryption key is missing. Set JMB_ENCRYPTION_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY.'
            );
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || $decoded === '') {
                throw new JmbEncryptionException(
                    'JMB encryption key is invalid. Set JMB_ENCRYPTION_KEY to a dedicated 32-byte key (base64:...). Do not use APP_KEY.'
                );
            }
            $key = $decoded;
        }

        if (! Encrypter::supported($key, $cipher)) {
            throw new JmbEncryptionException(
                'JMB encryption key is invalid for AES-256-GCM. Expected 32 bytes. Do not use APP_KEY.'
            );
        }

        return $key;
    }
}
