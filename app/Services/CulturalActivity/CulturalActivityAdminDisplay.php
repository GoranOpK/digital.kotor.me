<?php

namespace App\Services\CulturalActivity;

use App\Models\CulturalActivityRecord;

/**
 * Privacy-safe F8-04 list rendering. Does not mutate audit rows.
 */
final class CulturalActivityAdminDisplay
{
    public static function actor(CulturalActivityRecord $record): string
    {
        if ($record->isSystemActor() || $record->actor_user_id === null) {
            if ($record->isSystemActor()) {
                return 'Sistem';
            }

            return 'Korisnik — nedostupan nalog';
        }

        $user = $record->actorUser;
        if ($user === null) {
            return 'Korisnik #'.$record->actor_user_id.' — nedostupan nalog';
        }

        return $user->name.' (#'.$record->actor_user_id.')';
    }

    public static function target(CulturalActivityRecord $record): string
    {
        if ($record->target_id === null) {
            return $record->target_type;
        }

        return $record->target_type.' #'.$record->target_id;
    }

    public static function organizerContext(CulturalActivityRecord $record): string
    {
        if ($record->organizer_context_id === null) {
            return '—';
        }

        return '#'.$record->organizer_context_id;
    }

    public static function context(CulturalActivityRecord $record): string
    {
        $context = $record->context;
        if (! is_array($context) || $context === []) {
            return '—';
        }

        $parts = [];
        foreach ($context as $key => $value) {
            if (! is_string($key) || self::isSensitiveKey($key)) {
                continue;
            }

            if ($value === null) {
                $parts[] = $key.'=—';

                continue;
            }

            if (! is_scalar($value)) {
                continue;
            }

            $parts[] = $key.'='.(string) $value;
        }

        return $parts === [] ? '—' : implode('; ', $parts);
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);
        $exact = [
            'password', 'passwd', 'token', 'secret', 'authorization', 'cookie',
            'csrf', 'session', 'email', 'request', 'body', 'payload', 'ledger',
            'access_token', 'unsubscribe_token', 'csrf_token', 'session_id',
        ];

        if (in_array($normalized, $exact, true)) {
            return true;
        }

        foreach (['_token', '_secret', '_password', '_passwd', '_email'] as $suffix) {
            if (str_ends_with($normalized, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
