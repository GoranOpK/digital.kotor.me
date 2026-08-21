<?php

namespace Tests\Support;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\User;

trait MakesSyntheticPaymentCatalog
{
    /**
     * @return array{0: PaymentType, 1: PaymentAccount}
     */
    protected function syntheticUsablePair(
        User $user,
        string $typeCode = 'syn-user-flow',
        string $accountNumber = 'SYN-FLOW-00000000000001'
    ): array {
        $type = PaymentType::factory()->create([
            'code' => $typeCode,
            'name' => 'Synthetic user-flow type',
            'is_active' => true,
        ]);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => $accountNumber,
            'name' => 'Synthetic flow account',
            'is_active' => true,
        ]);
        $this->grantTypeAvailability($type, (string) $user->user_type, $user->residential_status);
        $this->grantAccountAvailability($account, (string) $user->user_type, $user->residential_status);

        return [$type, $account];
    }

    protected function grantTypeAvailability(PaymentType $type, string $userType, ?string $residential): void
    {
        PaymentTypeAvailability::factory()->create([
            'payment_type_id' => $type->id,
            'user_type' => $userType,
            'residential_status' => $residential,
            'is_active' => true,
        ]);
    }

    protected function grantAccountAvailability(
        PaymentAccount $account,
        string $userType,
        ?string $residential
    ): void {
        PaymentAccountAvailability::factory()->create([
            'payment_account_id' => $account->id,
            'user_type' => $userType,
            'residential_status' => $residential,
            'is_active' => true,
        ]);
    }

    protected function grantAvailability(
        PaymentType $type,
        PaymentAccount $account,
        string $userType,
        ?string $residential
    ): void {
        $this->grantTypeAvailability($type, $userType, $residential);
        $this->grantAccountAvailability($account, $userType, $residential);
    }
}
