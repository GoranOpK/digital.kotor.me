<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Redacts JMB/JMBG identifier values from log context before any logger sink.
 * Keeps field names for diagnostics. Does not hash, mask, or encode the value.
 */
final class SensitiveIdentifierLogSanitizer
{
    public const REDACTED = '[REDACTED]';

    public static function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && self::isSensitiveKey($key)) {
                    $out[$key] = self::REDACTED;
                    continue;
                }

                $out[$key] = self::redact($item);
            }

            return $out;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return self::redact($value->toArray());
        }

        if ($value instanceof JsonSerializable) {
            return self::redact($value->jsonSerialize());
        }

        if ($value instanceof \stdClass) {
            return self::redact(get_object_vars($value));
        }

        return $value;
    }

    public static function isSensitiveKey(string $key): bool
    {
        $tokens = preg_split('/(?<=[a-z])(?=[A-Z])|[\\s_\\-]+/', $key) ?: [];
        foreach ($tokens as $token) {
            $normalized = strtolower($token);
            if ($normalized === 'jmb' || $normalized === 'jmbg') {
                return true;
            }
        }

        $collapsed = strtolower(preg_replace('/[^a-z0-9]/i', '', $key) ?? '');

        return str_contains($collapsed, 'jmbg');
    }
}
