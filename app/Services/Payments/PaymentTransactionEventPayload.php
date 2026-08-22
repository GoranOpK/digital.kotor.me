<?php

namespace App\Services\Payments;

/**
 * Whitelist for PaymentTransactionEvent.payload.
 * Financial evidence metadata only — never raw provider bodies, PII, or secrets.
 */
final class PaymentTransactionEventPayload
{
    public const KEYS = [
        'provider',
        'inquiry_outcome',
        'reason',
        'current_status',
        'incoming_status',
        'status',
    ];

    private const MAX_VALUE_LENGTH = 191;

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, string>
     */
    public static function sanitize(?array $payload): array
    {
        $safe = [];

        foreach (self::KEYS as $key) {
            $value = $payload[$key] ?? null;
            if (! is_string($value) || $value === '') {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }

            $safe[$key] = mb_substr($trimmed, 0, self::MAX_VALUE_LENGTH);
        }

        return $safe;
    }
}
