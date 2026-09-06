<?php

namespace App\Identity\Shadow;

/**
 * Explicit Step 6 shadow rollout scope. Default is the full five-flow run.
 * Not a generic bypass. Invalid values fail closed.
 */
final class IdentityShadowScope
{
    public const FULL = 'full';

    public const ACTIVE_IDENTITY_WAVE = 'active-identity-wave';

    /**
     * @param  list<string>  $allowed
     */
    public static function resolve(mixed $value): string
    {
        if ($value === null) {
            return self::FULL;
        }

        if (! is_string($value)) {
            throw new IdentityShadowException(self::invalidMessage());
        }

        if ($value === self::FULL) {
            return self::FULL;
        }

        if ($value === self::ACTIVE_IDENTITY_WAVE) {
            return self::ACTIVE_IDENTITY_WAVE;
        }

        throw new IdentityShadowException(self::invalidMessage());
    }

    /**
     * @return list<string>
     */
    public static function requiredFlows(string $scope): array
    {
        return $scope === self::ACTIVE_IDENTITY_WAVE
            ? IdentityShadowFlow::ACTIVE_IDENTITY_WAVE
            : IdentityShadowFlow::REQUIRED;
    }

    public static function includesCatalog(string $scope): bool
    {
        return $scope !== self::ACTIVE_IDENTITY_WAVE;
    }

    public static function isActiveIdentityWave(string $scope): bool
    {
        return $scope === self::ACTIVE_IDENTITY_WAVE;
    }

    /**
     * @return array<string, mixed>
     */
    public static function deferredGates(string $scope): array
    {
        if (! self::isActiveIdentityWave($scope)) {
            return [];
        }

        return [
            IdentityShadowFlow::EP_AVAILABILITY => [
                'status' => 'OPEN',
                'reason' => 'ep_module_undeployed',
                'required_before' => [
                    'mode' => 'earliest_of',
                    'events' => [
                        'ep_production_activation',
                        'canonical_writer_authority',
                    ],
                ],
            ],
        ];
    }

    private static function invalidMessage(): string
    {
        return 'identity:shadow-production --scope is invalid. Omit for full five-flow shadow, or pass --scope=active-identity-wave.';
    }
}
