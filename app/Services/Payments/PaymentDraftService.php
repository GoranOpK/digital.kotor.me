<?php

namespace App\Services\Payments;

use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Models\User;
use App\Support\UserType;
use Illuminate\Http\Request;

/**
 * Transient pre-gateway draft. Session-scoped. Not a PaymentTransaction.
 */
class PaymentDraftService
{
    public const SESSION_KEY = 'ep_payment_draft';

    public function __construct(
        private readonly PaymentAvailabilityService $availability
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function get(Request $request): ?array
    {
        $draft = $request->session()->get(self::SESSION_KEY);
        $user = $request->user();

        if (! is_array($draft) || $user === null || (int) ($draft['user_id'] ?? 0) !== (int) $user->id) {
            $this->clear($request);

            return null;
        }

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(Request $request, array $payload): void
    {
        $user = $request->user();
        if ($user === null) {
            return;
        }

        $current = $this->get($request) ?? [];
        $request->session()->put(self::SESSION_KEY, array_merge($current, $payload, [
            'user_id' => (int) $user->id,
            'currency' => 'EUR',
        ]));
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function resolveUsableAccount(User $user, int $typeId, int $accountId): ?PaymentAccount
    {
        $type = PaymentType::query()->find($typeId);
        $account = PaymentAccount::query()->find($accountId);

        if ($type === null || $account === null) {
            return null;
        }

        if ((int) $account->payment_type_id !== (int) $type->id) {
            return null;
        }

        if (! $this->availability->isAccountAvailable($user, $account)) {
            return null;
        }

        return $account;
    }

    public function ensureMerchantTransactionId(Request $request): string
    {
        $draft = $this->get($request) ?? [];
        $existing = (string) ($draft['merchant_transaction_id'] ?? '');

        if ($existing !== '' && str_starts_with($existing, 'EPLOCAL-')) {
            return $existing;
        }

        $id = PaymentStartService::newMerchantTransactionId();
        $this->put($request, ['merchant_transaction_id' => $id]);

        return $id;
    }

    public function payerLabel(User $user): string
    {
        if (UserType::isLegalEntity($user->user_type) && filled($user->company_name)) {
            return (string) $user->company_name;
        }

        return (string) $user->name;
    }
}
