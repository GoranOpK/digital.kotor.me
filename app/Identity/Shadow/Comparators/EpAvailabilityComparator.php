<?php

namespace App\Identity\Shadow\Comparators;

use App\Identity\IdentitySnapshot;
use App\Identity\Shadow\IdentityShadowCanonicalFacts;
use App\Identity\Shadow\IdentityShadowCompareResult;
use App\Identity\Shadow\IdentityShadowExcludedLegacyFields;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\User;
use App\Services\Payments\PaymentAvailabilityEvaluation;
use App\Services\Payments\PaymentAvailabilityFacts;
use Illuminate\Support\Collection;

/**
 * Compares EP catalog eligibility decisions. Reuses PaymentAvailabilityEvaluation exactly.
 */
class EpAvailabilityComparator
{
    /**
     * @param  Collection<int, PaymentType>  $types
     */
    public function compare(User $user, IdentitySnapshot $canonical, Collection $types): IdentityShadowCompareResult
    {
        $legacyFacts = PaymentAvailabilityFacts::fromUser($user);
        $canonicalFacts = new PaymentAvailabilityFacts(
            IdentityShadowCanonicalFacts::legacyUserType($canonical),
            IdentityShadowCanonicalFacts::legacyResidentialStatus($canonical),
            $user->isStaffAccount(),
        );

        $legacy = $this->outcomes($legacyFacts, $types);
        $canonicalOutcomes = $this->outcomes($canonicalFacts, $types);
        $coverage = $this->coverage($types, $legacy);

        $categories = ['eligibility', 'classification', 'residency'];
        $reasons = IdentityShadowExcludedLegacyFields::reasonCodes($user);

        if ($coverage['compared_decision_count'] === 0) {
            return IdentityShadowCompareResult::notEvaluable(
                array_values(array_unique([...$reasons, 'no_catalog_decisions'])),
                $categories,
                $coverage,
            );
        }

        if ($legacy === $canonicalOutcomes) {
            return IdentityShadowCompareResult::match($categories, $reasons, $coverage);
        }

        return IdentityShadowCompareResult::mismatch(
            array_values(array_unique([...$reasons, 'eligibility'])),
            $categories,
            $coverage,
        );
    }

    /**
     * @param  Collection<int, PaymentType>  $types
     * @param  array<string, string>  $outcomes
     * @return array{evaluated_type_count: int, evaluated_account_count: int, compared_decision_count: int}
     */
    private function coverage(Collection $types, array $outcomes): array
    {
        $accountCount = 0;
        foreach ($types as $type) {
            $accountCount += $type->accounts->count();
        }

        return [
            'evaluated_type_count' => $types->count(),
            'evaluated_account_count' => $accountCount,
            'compared_decision_count' => count($outcomes),
        ];
    }

    /**
     * @param  Collection<int, PaymentType>  $types
     * @return array<string, string>
     */
    private function outcomes(PaymentAvailabilityFacts $facts, Collection $types): array
    {
        $out = [];
        foreach ($types as $type) {
            $out['type:'.$type->id] = PaymentAvailabilityEvaluation::evaluateType($facts, $type)->value;
            foreach ($type->accounts as $account) {
                $this->bindAccountType($account, $type);
                $out['account:'.$account->id] = PaymentAvailabilityEvaluation::evaluateAccount($facts, $account)->value;
            }
        }
        ksort($out);

        return $out;
    }

    private function bindAccountType(PaymentAccount $account, PaymentType $type): void
    {
        if ($account->relationLoaded('paymentType') && $account->paymentType !== null) {
            return;
        }

        $account->setRelation('paymentType', $type);
    }
}
