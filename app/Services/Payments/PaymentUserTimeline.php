<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;

final class PaymentUserTimeline
{
    /**
     * @return list<array{label: string, occurred_at: string}>
     */
    public function forTransaction(PaymentTransaction $transaction): array
    {
        $labels = $this->userFacingLabels();

        return $transaction->events
            ->sortBy('id')
            ->values()
            ->filter(fn (PaymentTransactionEvent $event): bool => isset($labels[$event->event_type]))
            ->map(function (PaymentTransactionEvent $event) use ($labels): array {
                return [
                    'label' => $labels[$event->event_type],
                    'occurred_at' => $event->occurred_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') ?? '',
                ];
            })
            ->all();
    }

    public function successfulAtLabel(PaymentTransaction $transaction): ?string
    {
        $event = $transaction->events
            ->firstWhere('event_type', PaymentTransactionEventType::SUCCESSFUL);

        if ($event?->occurred_at === null) {
            return null;
        }

        return $event->occurred_at->timezone(config('app.timezone'))->format('d.m.Y. H:i');
    }

    /**
     * @return array<string, string>
     */
    private function userFacingLabels(): array
    {
        return [
            PaymentTransactionEventType::STARTED => 'Plaćanje pokrenuto',
            PaymentTransactionEventType::GATEWAY_REDIRECTED => 'Preusmjereno na servis plaćanja',
            PaymentTransactionEventType::SUCCESSFUL => \App\Enums\PaymentStatus::Successful->label(),
            PaymentTransactionEventType::FAILED => \App\Enums\PaymentStatus::Failed->label(),
            PaymentTransactionEventType::CANCELLED => \App\Enums\PaymentStatus::Cancelled->label(),
        ];
    }
}
