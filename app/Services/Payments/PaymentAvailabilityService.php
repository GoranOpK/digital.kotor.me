<?php

namespace App\Services\Payments;

use App\Enums\PaymentAvailabilityOutcome;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\User;
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
        $type->loadMissing('availabilities');

        return PaymentAvailabilityEvaluation::evaluateType(
            PaymentAvailabilityFacts::fromUser($user),
            $type
        );
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

        return PaymentAvailabilityEvaluation::evaluateAccount(
            PaymentAvailabilityFacts::fromUser($user),
            $account
        );
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

}
