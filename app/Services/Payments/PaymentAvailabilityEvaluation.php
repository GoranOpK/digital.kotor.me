<?php

namespace App\Services\Payments;

use App\Enums\PaymentAvailabilityOutcome;
use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Support\UserType;

/**
 * Pure EP catalog availability evaluation over identity facts + in-memory catalog rows.
 * Preserves PaymentAvailabilityService semantics. Does not query. Does not persist.
 */
final class PaymentAvailabilityEvaluation
{
    public static function evaluateType(PaymentAvailabilityFacts $facts, PaymentType $type): PaymentAvailabilityOutcome
    {
        $identity = self::identityOutcome($facts);
        if ($identity !== null) {
            return $identity;
        }

        if (! $type->is_active) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $userType = (string) $facts->userType;

        if (self::naturalPersonNeedsDeclaration($facts)) {
            return self::hasActiveTypeRuleForUserType($type, $userType)
                ? PaymentAvailabilityOutcome::ResidentialDeclarationRequired
                : PaymentAvailabilityOutcome::NotAvailable;
        }

        return self::typeRuleMatches($type, $userType, self::matchResidential($facts))
            ? PaymentAvailabilityOutcome::Available
            : PaymentAvailabilityOutcome::NotAvailable;
    }

    public static function evaluateAccount(PaymentAvailabilityFacts $facts, PaymentAccount $account): PaymentAvailabilityOutcome
    {
        $type = $account->paymentType;
        if ($type === null) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $typeOutcome = self::evaluateType($facts, $type);
        if ($typeOutcome === PaymentAvailabilityOutcome::NotAvailable) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        if (! $account->is_active) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $userType = (string) $facts->userType;

        if ($typeOutcome === PaymentAvailabilityOutcome::ResidentialDeclarationRequired) {
            return self::hasActiveAccountRuleForUserType($account, $userType)
                ? PaymentAvailabilityOutcome::ResidentialDeclarationRequired
                : PaymentAvailabilityOutcome::NotAvailable;
        }

        return self::accountRuleMatches($account, $userType, self::matchResidential($facts))
            ? PaymentAvailabilityOutcome::Available
            : PaymentAvailabilityOutcome::NotAvailable;
    }

    private static function identityOutcome(PaymentAvailabilityFacts $facts): ?PaymentAvailabilityOutcome
    {
        $userType = $facts->userType;
        if (! is_string($userType) || $userType === '' || ! UserType::isCanonical($userType)) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        if (UserType::isNaturalPerson($userType)) {
            $status = $facts->residentialStatus;
            if ($status !== null && ! in_array($status, ['resident', 'non-resident'], true)) {
                return PaymentAvailabilityOutcome::NotAvailable;
            }
        }

        return null;
    }

    private static function naturalPersonNeedsDeclaration(PaymentAvailabilityFacts $facts): bool
    {
        $userType = is_string($facts->userType) ? $facts->userType : null;

        return UserType::isNaturalPerson($userType)
            && self::declarationIsApplicable($facts);
    }

    private static function declarationIsApplicable(PaymentAvailabilityFacts $facts): bool
    {
        if ($facts->isStaffAccount && $facts->userType === null) {
            return false;
        }

        $userType = is_string($facts->userType) ? $facts->userType : null;
        if (! UserType::isNaturalPerson($userType)) {
            return false;
        }

        return $facts->residentialStatus === null;
    }

    private static function matchResidential(PaymentAvailabilityFacts $facts): mixed
    {
        $userType = is_string($facts->userType) ? $facts->userType : null;
        if (! UserType::isNaturalPerson($userType)) {
            return null;
        }

        return $facts->residentialStatus;
    }

    private static function typeRuleMatches(PaymentType $type, string $userType, ?string $residential): bool
    {
        return $type->availabilities->contains(function (PaymentTypeAvailability $rule) use ($userType, $residential): bool {
            return $rule->is_active && self::ruleMatches($rule->user_type, $rule->residential_status, $userType, $residential);
        });
    }

    private static function accountRuleMatches(PaymentAccount $account, string $userType, ?string $residential): bool
    {
        return $account->availabilities->contains(function (PaymentAccountAvailability $rule) use ($userType, $residential): bool {
            return $rule->is_active && self::ruleMatches($rule->user_type, $rule->residential_status, $userType, $residential);
        });
    }

    private static function hasActiveTypeRuleForUserType(PaymentType $type, string $userType): bool
    {
        return $type->availabilities->contains(function (PaymentTypeAvailability $rule) use ($userType): bool {
            return $rule->is_active && $rule->user_type === $userType;
        });
    }

    private static function hasActiveAccountRuleForUserType(PaymentAccount $account, string $userType): bool
    {
        return $account->availabilities->contains(function (PaymentAccountAvailability $rule) use ($userType): bool {
            return $rule->is_active && $rule->user_type === $userType;
        });
    }

    private static function ruleMatches(string $ruleUserType, ?string $ruleResidential, string $userType, ?string $residential): bool
    {
        if ($ruleUserType !== $userType) {
            return false;
        }

        if (UserType::isNaturalPerson($userType)) {
            return $ruleResidential === $residential;
        }

        return $ruleResidential === null;
    }
}
