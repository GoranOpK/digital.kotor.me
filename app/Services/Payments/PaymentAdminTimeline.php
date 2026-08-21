<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;

final class PaymentAdminTimeline
{
    /**
     * @return list<array{event_type: string, label: string, occurred_at: string, metadata: array<string, string>}>
     */
    public function forTransaction(PaymentTransaction $transaction): array
    {
        $labels = $this->labels();

        return $transaction->events
            ->sortBy('id')
            ->values()
            ->map(function (PaymentTransactionEvent $event) use ($labels): array {
                return [
                    'event_type' => $event->event_type,
                    'label' => $labels[$event->event_type] ?? $event->event_type,
                    'occurred_at' => $event->occurred_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') ?? '',
                    'metadata' => $this->safeMetadata($event),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function labels(): array
    {
        return [
            PaymentTransactionEventType::STARTED => 'Plaćanje pokrenuto',
            PaymentTransactionEventType::GATEWAY_REDIRECTED => 'Preusmjereno na servis plaćanja',
            PaymentTransactionEventType::GATEWAY_START_FAILED => 'Pokretanje servisa plaćanja nije uspjelo',
            PaymentTransactionEventType::GATEWAY_VERIFICATION_FAILED => 'Provjera rezultata nije uspjela',
            PaymentTransactionEventType::GATEWAY_CONTRADICTORY_RESULT => 'Kontradiktorni rezultat ignorisan',
            PaymentTransactionEventType::GATEWAY_INQUIRY => 'Provjera statusa kod provajdera',
            PaymentTransactionEventType::SUCCESSFUL => \App\Enums\PaymentStatus::Successful->label(),
            PaymentTransactionEventType::FAILED => \App\Enums\PaymentStatus::Failed->label(),
            PaymentTransactionEventType::CANCELLED => \App\Enums\PaymentStatus::Cancelled->label(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function safeMetadata(PaymentTransactionEvent $event): array
    {
        $payload = is_array($event->payload) ? $event->payload : [];
        $allowed = ['provider', 'inquiry_outcome', 'reason', 'current_status', 'incoming_status'];
        $safe = [];

        foreach ($allowed as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
