<?php

namespace App\Services;

use App\Support\KnApplicationStartContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Per-start opaque tokens. Unique per click so two tabs do not overwrite each other.
 */
final class KnApplicationStartContextStore
{
    public const TTL_SECONDS = 7200;

    public function put(KnApplicationStartContext $context): string
    {
        $token = Str::uuid()->toString();
        Cache::put($this->key($token), $context->toArray(), self::TTL_SECONDS);

        return $token;
    }

    public function get(?string $token): ?KnApplicationStartContext
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $payload = Cache::get($this->key($token));
        if (! is_array($payload)) {
            return null;
        }

        return KnApplicationStartContext::fromArray($payload);
    }

    public function forget(?string $token): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        Cache::forget($this->key($token));
    }

    private function key(string $token): string
    {
        return 'kn:application-start-context:'.$token;
    }
}
