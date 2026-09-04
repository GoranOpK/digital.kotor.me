<?php

namespace App\Services\Payments;

use App\Enums\PaymentAvailabilityOutcome;
use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\User;
use App\Support\ResidentialStatusDeclaration;
use App\Support\UserType;
use Illuminate\Support\Collection;

/**
 * SSOT for e-Plaćanje catalog availability evaluation.
 * Fail-closed. Does not redefine platform identity.
 */
class PaymentAvailabilityService
{
    /**
     * Type-level gate only (active type + matching type rule).
     * Does not require a usable account.
     */
    public function evaluateType(User $user, PaymentType $type): PaymentAvailabilityOutcome
    {
        $identity = $this->identityOutcome($user);
        if ($identity !== null) {
            return $identity;
        }

        if (! $type->is_active) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $type->loadMissing('availabilities');

        $userType = (string) $user->user_type;

        if ($this->naturalPersonNeedsDeclaration($user)) {
            return $this->hasActiveTypeRuleForUserType($type, $userType)
                ? PaymentAvailabilityOutcome::ResidentialDeclarationRequired
                : PaymentAvailabilityOutcome::NotAvailable;
        }

        return $this->typeRuleMatches($type, $userType, $this->matchResidential($user))
            ? PaymentAvailabilityOutcome::Available
            : PaymentAvailabilityOutcome::NotAvailable;
    }

    /**
     * Full intersection: active type + type rule + active account + account rule.
     */
    public function evaluateAccount(User $user, PaymentAccount $account): PaymentAvailabilityOutcome
    {
        $account->loadMissing(['paymentType.availabilities', 'availabilities']);

        $type = $account->paymentType;
        if ($type === null) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $typeOutcome = $this->evaluateType($user, $type);
        if ($typeOutcome === PaymentAvailabilityOutcome::NotAvailable) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        if (! $account->is_active) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        $userType = (string) $user->user_type;

        if ($typeOutcome === PaymentAvailabilityOutcome::ResidentialDeclarationRequired) {
            return $this->hasActiveAccountRuleForUserType($account, $userType)
                ? PaymentAvailabilityOutcome::ResidentialDeclarationRequired
                : PaymentAvailabilityOutcome::NotAvailable;
        }

        return $this->accountRuleMatches($account, $userType, $this->matchResidential($user))
            ? PaymentAvailabilityOutcome::Available
            : PaymentAvailabilityOutcome::NotAvailable;
    }

    /**
     * Types the user may actually use: AVAILABLE type with at least one AVAILABLE account.
     *
     * @return Collection<int, PaymentType>
     */
    public function usableTypesFor(User $user): Collection
    {
        $types = PaymentType::query()
            ->where('is_active', true)
            ->with([
                'availabilities',
                'accounts.availabilities',
            ])
            ->orderBy('name')
            ->get();

        return $types
            ->filter(function (PaymentType $type) use ($user): bool {
                if ($this->evaluateType($user, $type) !== PaymentAvailabilityOutcome::Available) {
                    return false;
                }

                return $this->usableAccountsFor($user, $type)->isNotEmpty();
            })
            ->values();
    }

    /**
     * @return Collection<int, PaymentAccount>
     */
    public function usableAccountsFor(User $user, PaymentType $type): Collection
    {
        $type->loadMissing(['availabilities', 'accounts.availabilities']);

        if ($this->evaluateType($user, $type) !== PaymentAvailabilityOutcome::Available) {
            return collect();
        }

        return $type->accounts
            ->filter(fn (PaymentAccount $account) => $this->evaluateAccount($user, $account) === PaymentAvailabilityOutcome::Available)
            ->values();
    }

    public function isTypeUsable(User $user, PaymentType $type): bool
    {
        return $this->usableAccountsFor($user, $type)->isNotEmpty();
    }

    public function isAccountAvailable(User $user, PaymentAccount $account): bool
    {
        return $this->evaluateAccount($user, $account) === PaymentAvailabilityOutcome::Available;
    }

    private function identityOutcome(User $user): ?PaymentAvailabilityOutcome
    {
        $userType = $user->user_type;
        if (! is_string($userType) || $userType === '' || ! UserType::isCanonical($userType)) {
            return PaymentAvailabilityOutcome::NotAvailable;
        }

        if (UserType::isNaturalPerson($userType)) {
            $status = $user->residential_status;
            if ($status !== null && ! in_array($status, ['resident', 'non-resident'], true)) {
                return PaymentAvailabilityOutcome::NotAvailable;
            }
        }

        return null;
    }

    private function naturalPersonNeedsDeclaration(User $user): bool
    {
        return UserType::isNaturalPerson($user->user_type)
            && ResidentialStatusDeclaration::isApplicable($user);
    }

    private function matchResidential(User $user): ?string
    {
        if (! UserType::isNaturalPerson($user->user_type)) {
            return null;
        }

        return $user->residential_status;
    }

    private function typeRuleMatches(PaymentType $type, string $userType, ?string $residential): bool
    {
        return $type->availabilities->contains(function (PaymentTypeAvailability $rule) use ($userType, $residential): bool {
            return $rule->is_active && $this->ruleMatches($rule->user_type, $rule->residential_status, $userType, $residential);
        });
    }

    private function accountRuleMatches(PaymentAccount $account, string $userType, ?string $residential): bool
    {
        return $account->availabilities->contains(function (PaymentAccountAvailability $rule) use ($userType, $residential): bool {
            return $rule->is_active && $this->ruleMatches($rule->user_type, $rule->residential_status, $userType, $residential);
        });
    }

    private function hasActiveTypeRuleForUserType(PaymentType $type, string $userType): bool
    {
        return $type->availabilities->contains(function (PaymentTypeAvailability $rule) use ($userType): bool {
            return $rule->is_active && $rule->user_type === $userType;
        });
    }

    private function hasActiveAccountRuleForUserType(PaymentAccount $account, string $userType): bool
    {
        return $account->availabilities->contains(function (PaymentAccountAvailability $rule) use ($userType): bool {
            return $rule->is_active && $rule->user_type === $userType;
        });
    }

    private function ruleMatches(string $ruleUserType, ?string $ruleResidential, string $userType, ?string $residential): bool
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
