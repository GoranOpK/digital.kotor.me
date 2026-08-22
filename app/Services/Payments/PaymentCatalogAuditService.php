<?php

namespace App\Services\Payments;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentCatalogAudit;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\User;

class PaymentCatalogAuditService
{
    public function typeCreated(User $actor, PaymentType $type): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_CREATED, PaymentCatalogAudit::ENTITY_PAYMENT_TYPE, (int) $type->id, [
            'code' => ['to' => (string) $type->code],
            'name' => ['to' => (string) $type->name],
            'description' => ['to' => (string) ($type->description ?? '')],
            'is_active' => ['to' => (bool) $type->is_active],
        ]);
    }

    /**
     * @param  array<string, mixed>  $from
     */
    public function typeUpdated(User $actor, PaymentType $type, array $from): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_UPDATED, PaymentCatalogAudit::ENTITY_PAYMENT_TYPE, (int) $type->id, $this->diff($from, [
            'name' => (string) $type->name,
            'description' => (string) ($type->description ?? ''),
            'is_active' => (bool) $type->is_active,
        ]));
    }

    public function typeActivated(User $actor, PaymentType $type, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_ACTIVATED, PaymentCatalogAudit::ENTITY_PAYMENT_TYPE, (int) $type->id, [
            'is_active' => ['from' => $fromActive, 'to' => true],
        ]);
    }

    public function typeDeactivated(User $actor, PaymentType $type, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_DEACTIVATED, PaymentCatalogAudit::ENTITY_PAYMENT_TYPE, (int) $type->id, [
            'is_active' => ['from' => $fromActive, 'to' => false],
        ]);
    }

    public function accountCreated(User $actor, PaymentAccount $account): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_CREATED, PaymentCatalogAudit::ENTITY_PAYMENT_ACCOUNT, (int) $account->id, [
            'account_number' => ['to' => (string) $account->account_number],
            'name' => ['to' => (string) ($account->name ?? '')],
            'is_active' => ['to' => (bool) $account->is_active],
        ]);
    }

    /**
     * @param  array<string, mixed>  $from
     */
    public function accountUpdated(User $actor, PaymentAccount $account, array $from): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_UPDATED, PaymentCatalogAudit::ENTITY_PAYMENT_ACCOUNT, (int) $account->id, $this->diff($from, [
            'name' => (string) ($account->name ?? ''),
            'is_active' => (bool) $account->is_active,
        ]));
    }

    public function accountActivated(User $actor, PaymentAccount $account, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_ACTIVATED, PaymentCatalogAudit::ENTITY_PAYMENT_ACCOUNT, (int) $account->id, [
            'is_active' => ['from' => $fromActive, 'to' => true],
        ]);
    }

    public function accountDeactivated(User $actor, PaymentAccount $account, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_DEACTIVATED, PaymentCatalogAudit::ENTITY_PAYMENT_ACCOUNT, (int) $account->id, [
            'is_active' => ['from' => $fromActive, 'to' => false],
        ]);
    }

    public function typeAvailabilityAdded(User $actor, PaymentTypeAvailability $rule): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_AVAILABILITY_ADDED, PaymentCatalogAudit::ENTITY_TYPE_AVAILABILITY, (int) $rule->id, [
            'user_type' => ['to' => (string) $rule->user_type],
            'residential_status' => ['to' => $rule->residential_status],
            'is_active' => ['to' => true],
        ]);
    }

    public function typeAvailabilityActivated(User $actor, PaymentTypeAvailability $rule, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_AVAILABILITY_ACTIVATED, PaymentCatalogAudit::ENTITY_TYPE_AVAILABILITY, (int) $rule->id, [
            'is_active' => ['from' => $fromActive, 'to' => true],
        ]);
    }

    public function typeAvailabilityDeactivated(User $actor, PaymentTypeAvailability $rule, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::TYPE_AVAILABILITY_DEACTIVATED, PaymentCatalogAudit::ENTITY_TYPE_AVAILABILITY, (int) $rule->id, [
            'is_active' => ['from' => $fromActive, 'to' => false],
        ]);
    }

    public function accountAvailabilityAdded(User $actor, PaymentAccountAvailability $rule): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_AVAILABILITY_ADDED, PaymentCatalogAudit::ENTITY_ACCOUNT_AVAILABILITY, (int) $rule->id, [
            'user_type' => ['to' => (string) $rule->user_type],
            'residential_status' => ['to' => $rule->residential_status],
            'is_active' => ['to' => true],
        ]);
    }

    public function accountAvailabilityActivated(User $actor, PaymentAccountAvailability $rule, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_AVAILABILITY_ACTIVATED, PaymentCatalogAudit::ENTITY_ACCOUNT_AVAILABILITY, (int) $rule->id, [
            'is_active' => ['from' => $fromActive, 'to' => true],
        ]);
    }

    public function accountAvailabilityDeactivated(User $actor, PaymentAccountAvailability $rule, bool $fromActive): void
    {
        $this->write($actor, PaymentCatalogAuditAction::ACCOUNT_AVAILABILITY_DEACTIVATED, PaymentCatalogAudit::ENTITY_ACCOUNT_AVAILABILITY, (int) $rule->id, [
            'is_active' => ['from' => $fromActive, 'to' => false],
        ]);
    }

    public function moduleToggled(User $actor, bool $enabled): void
    {
        $this->write(
            $actor,
            $enabled ? PaymentCatalogAuditAction::MODULE_ENABLED : PaymentCatalogAuditAction::MODULE_DISABLED,
            PaymentCatalogAudit::ENTITY_EP_SETTING,
            null,
            ['enabled' => ['to' => $enabled]],
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function write(User $actor, string $action, string $entityType, ?int $entityId, array $changes): void
    {
        PaymentCatalogAudit::query()->create([
            'actor_user_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changes' => $this->safeChanges($changes),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $to
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function diff(array $from, array $to): array
    {
        $changes = [];
        foreach ($to as $key => $value) {
            $previous = $from[$key] ?? null;
            if ($previous === $value) {
                continue;
            }
            $changes[$key] = ['from' => $previous, 'to' => $value];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, array<string, bool|string|null>>
     */
    private function safeChanges(array $changes): array
    {
        $allowed = ['code', 'name', 'description', 'is_active', 'account_number', 'user_type', 'residential_status', 'enabled'];
        $safe = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $changes) || ! is_array($changes[$key])) {
                continue;
            }

            $entry = [];
            foreach (['from', 'to'] as $side) {
                if (! array_key_exists($side, $changes[$key])) {
                    continue;
                }
                $entry[$side] = $this->scalar($changes[$key][$side]);
            }

            if ($entry !== []) {
                $safe[$key] = $entry;
            }
        }

        return $safe;
    }

    private function scalar(mixed $value): bool|string|null
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        return mb_substr($value, 0, 191);
    }
}
