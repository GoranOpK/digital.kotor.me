<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\PaymentAccount;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\PaymentType;
use App\Models\User;
use App\Support\UserType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PaymentStartService
{
    public function __construct(
        private readonly PaymentDraftService $drafts,
        private readonly PaymentAvailabilityService $availability,
        private readonly EpModuleSettings $module,
        private readonly PaymentGatewayResolver $gateways,
    ) {}

    public function launch(Request $request): PaymentTransaction
    {
        $user = $request->user();
        if ($user === null) {
            throw new PaymentResultRejectedException('Unauthenticated payment start.');
        }

        if (! $this->module->newPaymentsEnabled()) {
            throw new PaymentResultRejectedException('New payments are disabled.');
        }

        $draft = $this->drafts->get($request);
        if ($draft === null) {
            throw new PaymentResultRejectedException('Payment draft is missing or consumed.');
        }

        $merchantId = (string) ($draft['merchant_transaction_id'] ?? '');
        if ($merchantId === '') {
            throw new PaymentResultRejectedException('Payment start token is missing.');
        }

        $amount = (string) ($draft['amount'] ?? '');
        if ($amount === '') {
            throw new PaymentResultRejectedException('Payment amount is missing.');
        }

        $type = PaymentType::query()->find((int) ($draft['payment_type_id'] ?? 0));
        $account = $this->drafts->resolveUsableAccount(
            $user,
            (int) ($draft['payment_type_id'] ?? 0),
            (int) ($draft['payment_account_id'] ?? 0)
        );

        if ($type === null || $account === null || ! $this->availability->isTypeUsable($user, $type)) {
            $this->drafts->clear($request);
            throw new PaymentResultRejectedException('Payment selection is no longer available.');
        }

        try {
            $provider = $this->gateways->resolve()->name();
        } catch (PaymentGatewayNotConfiguredException|FakePaymentGatewayUnavailableException $e) {
            Log::info('ep.payment.provider_misconfigured', [
                'exception_class' => $e::class,
            ]);

            throw $e;
        }

        $lock = Cache::lock('ep-payment-start:'.$merchantId, 15);

        try {
            $lock->block(10);

            $existing = PaymentTransaction::query()
                ->where('merchant_transaction_id', $merchantId)
                ->first();

            if ($existing !== null) {
                $this->assertOwnedBy($existing, $user);
                $this->drafts->clear($request);

                return $existing;
            }

            try {
                $transaction = DB::transaction(function () use ($user, $type, $account, $amount, $merchantId, $provider) {
                    $initiation = PaymentInitiation::query()->create([
                        'user_id' => $user->id,
                        'payment_type_id' => $type->id,
                        'payment_account_id' => $account->id,
                        'amount' => $amount,
                        'currency' => 'EUR',
                    ]);

                    $transaction = PaymentTransaction::query()->create([
                        'payment_initiation_id' => $initiation->id,
                        'user_id' => $user->id,
                        'payment_type_id' => $type->id,
                        'payment_account_id' => $account->id,
                        'status' => PaymentStatus::Processing,
                        'amount' => $amount,
                        'currency' => 'EUR',
                        'merchant_transaction_id' => $merchantId,
                        'provider' => $provider,
                        'snapshot' => $this->snapshot($user, $type, $account, $amount),
                    ]);

                    PaymentTransactionEvent::query()->create([
                        'payment_transaction_id' => $transaction->id,
                        'event_type' => PaymentTransactionEventType::STARTED,
                        'payload' => [
                            'provider' => $provider,
                            'status' => PaymentStatus::Processing->value,
                        ],
                        'occurred_at' => now(),
                        'received_at' => now(),
                    ]);

                    return $transaction;
                });
            } catch (QueryException $e) {
                $existing = PaymentTransaction::query()
                    ->where('merchant_transaction_id', $merchantId)
                    ->first();

                if ($existing === null) {
                    throw $e;
                }

                $this->assertOwnedBy($existing, $user);
                $this->drafts->clear($request);

                return $existing;
            }

            $this->drafts->clear($request);

            Log::info('ep.payment.started', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'provider' => $provider,
            ]);

            return $transaction;
        } finally {
            $lock->release();
        }
    }

    public function redirectAfterStart(PaymentTransaction $transaction): string
    {
        try {
            $gateway = $this->gateways->forTransaction($transaction);
            $result = $gateway->start(PaymentGatewayStartRequest::fromTransaction($transaction));

            if (! $result->isRedirectReady()) {
                $this->recordStartFailed($transaction, $gateway->name(), $result->outcome->value);

                return route('payments.result', $transaction);
            }

            $alreadyRedirected = PaymentTransactionEvent::query()
                ->where('payment_transaction_id', $transaction->id)
                ->where('event_type', PaymentTransactionEventType::GATEWAY_REDIRECTED)
                ->exists();

            if (! $alreadyRedirected) {
                PaymentTransactionEvent::query()->create([
                    'payment_transaction_id' => $transaction->id,
                    'event_type' => PaymentTransactionEventType::GATEWAY_REDIRECTED,
                    'payload' => [
                        'provider' => $gateway->name(),
                    ],
                    'occurred_at' => now(),
                    'received_at' => now(),
                ]);
            }

            return (string) $result->redirectUrl;
        } catch (Throwable $e) {
            $provider = is_string($transaction->provider) && $transaction->provider !== ''
                ? $transaction->provider
                : 'unknown';

            Log::info('ep.payment.gateway_start_exception', [
                'transaction_uuid' => $transaction->uuid,
                'merchant_transaction_id' => $transaction->merchant_transaction_id,
                'provider' => $provider,
                'exception_class' => $e::class,
            ]);

            $this->recordStartFailed($transaction, $provider, 'start_exception');

            return route('payments.result', $transaction);
        }
    }

    private function recordStartFailed(PaymentTransaction $transaction, string $provider, string $reason): void
    {
        PaymentTransactionEvent::query()->create([
            'payment_transaction_id' => $transaction->id,
            'event_type' => PaymentTransactionEventType::GATEWAY_START_FAILED,
            'payload' => [
                'provider' => $provider,
                'reason' => $reason,
            ],
            'occurred_at' => now(),
            'received_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user, PaymentType $type, PaymentAccount $account, string $amount): array
    {
        return [
            'payer_user_id' => $user->id,
            'payer_label' => $this->drafts->payerLabel($user),
            'user_type' => $user->user_type,
            'user_type_label' => UserType::displayLabel($user->user_type),
            'payment_type_id' => $type->id,
            'payment_type_code' => $type->code,
            'payment_type_name' => $type->name,
            'payment_account_id' => $account->id,
            'account_number' => $account->account_number,
            'account_name' => $account->name,
            'amount' => $amount,
            'currency' => 'EUR',
            'catalog' => 'synthetic-local',
        ];
    }

    public static function newMerchantTransactionId(): string
    {
        return 'EPLOCAL-'.strtoupper((string) Str::ulid());
    }

    private function assertOwnedBy(PaymentTransaction $transaction, User $user): void
    {
        if ((int) $transaction->user_id !== (int) $user->id) {
            throw new PaymentResultRejectedException('Payment start token mismatch.');
        }
    }
}
