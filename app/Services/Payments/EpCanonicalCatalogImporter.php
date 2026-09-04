<?php

namespace App\Services\Payments;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Explicit, idempotent F11 catalog load. Not invoked from DatabaseSeeder.
 */
class EpCanonicalCatalogImporter
{
    public function __construct(
        private readonly PaymentCatalogAuditService $audits,
    ) {}

    public function import(User $actor): EpCanonicalCatalogImportReport
    {
        EpCanonicalCatalog::assertConsistent();

        $report = new EpCanonicalCatalogImportReport;

        foreach (EpCanonicalCatalog::types() as $typeSpec) {
            DB::transaction(function () use ($actor, $typeSpec, $report): void {
                $type = $this->ensureType($actor, $typeSpec, $report);
                if ($type === null) {
                    return;
                }

                $this->ensureTypeRules($actor, $type, $typeSpec['type_set'], $report);

                foreach ($typeSpec['accounts'] as $accountSpec) {
                    $account = $this->ensureAccount($actor, $type, $accountSpec, $report);
                    if ($account === null) {
                        continue;
                    }
                    $this->ensureAccountRules($actor, $account, $accountSpec['set'], $report);
                }
            });
        }

        return $report;
    }

    /**
     * @param  array{code: string, name: string, type_set: string, accounts: list<array{number: string, name: string, set: string}>}  $typeSpec
     */
    private function ensureType(User $actor, array $typeSpec, EpCanonicalCatalogImportReport $report): ?PaymentType
    {
        $existing = PaymentType::query()->where('code', $typeSpec['code'])->first();
        if ($existing === null) {
            $type = PaymentType::query()->create([
                'code' => $typeSpec['code'],
                'name' => $typeSpec['name'],
                'description' => null,
                'is_active' => false,
            ]);
            $this->audits->typeCreated($actor, $type);
            $report->typesCreated++;

            return $type;
        }

        if ((string) $existing->name !== $typeSpec['name']) {
            $report->conflicts[] = 'type.code='.$typeSpec['code'].' exists with different name; left unchanged.';
            $report->typesSkipped++;

            return null;
        }

        $report->typesSkipped++;

        return $existing;
    }

    /**
     * @param  array{number: string, name: string, set: string}  $accountSpec
     */
    private function ensureAccount(
        User $actor,
        PaymentType $type,
        array $accountSpec,
        EpCanonicalCatalogImportReport $report
    ): ?PaymentAccount {
        $existing = PaymentAccount::query()->where('account_number', $accountSpec['number'])->first();
        if ($existing === null) {
            $account = PaymentAccount::query()->create([
                'payment_type_id' => $type->id,
                'account_number' => $accountSpec['number'],
                'name' => $accountSpec['name'],
                'is_active' => false,
            ]);
            $this->audits->accountCreated($actor, $account);
            $report->accountsCreated++;

            return $account;
        }

        if ((int) $existing->payment_type_id !== (int) $type->id) {
            $report->conflicts[] = 'account='.$accountSpec['number'].' exists on another type; account_number not rewritten.';
            $report->accountsSkipped++;

            return null;
        }

        if ((string) ($existing->name ?? '') !== $accountSpec['name']) {
            $report->conflicts[] = 'account='.$accountSpec['number'].' exists with different name; left unchanged.';
            $report->accountsSkipped++;

            return null;
        }

        $report->accountsSkipped++;

        return $existing;
    }

    private function ensureTypeRules(
        User $actor,
        PaymentType $type,
        string $set,
        EpCanonicalCatalogImportReport $report
    ): void {
        foreach (EpCanonicalCatalog::availabilityRows($set) as $row) {
            $existing = $this->findTypeRule($type, $row['user_type'], $row['residential_status']);
            if ($existing !== null) {
                continue;
            }

            $rule = PaymentTypeAvailability::query()->create([
                'payment_type_id' => $type->id,
                'user_type' => $row['user_type'],
                'residential_status' => $row['residential_status'],
                'is_active' => true,
            ]);
            $this->audits->typeAvailabilityAdded($actor, $rule);
            $report->typeRulesCreated++;
        }
    }

    private function ensureAccountRules(
        User $actor,
        PaymentAccount $account,
        string $set,
        EpCanonicalCatalogImportReport $report
    ): void {
        foreach (EpCanonicalCatalog::availabilityRows($set) as $row) {
            $existing = $this->findAccountRule($account, $row['user_type'], $row['residential_status']);
            if ($existing !== null) {
                continue;
            }

            $rule = PaymentAccountAvailability::query()->create([
                'payment_account_id' => $account->id,
                'user_type' => $row['user_type'],
                'residential_status' => $row['residential_status'],
                'is_active' => true,
            ]);
            $this->audits->accountAvailabilityAdded($actor, $rule);
            $report->accountRulesCreated++;
        }
    }

    private function findTypeRule(PaymentType $type, string $userType, ?string $residential): ?PaymentTypeAvailability
    {
        return PaymentTypeAvailability::query()
            ->where('payment_type_id', $type->id)
            ->where('user_type', $userType)
            ->when(
                $residential === null,
                fn ($query) => $query->whereNull('residential_status'),
                fn ($query) => $query->where('residential_status', $residential)
            )
            ->first();
    }

    private function findAccountRule(PaymentAccount $account, string $userType, ?string $residential): ?PaymentAccountAvailability
    {
        return PaymentAccountAvailability::query()
            ->where('payment_account_id', $account->id)
            ->when(
                $residential === null,
                fn ($query) => $query->whereNull('residential_status'),
                fn ($query) => $query->where('residential_status', $residential)
            )
            ->where('user_type', $userType)
            ->first();
    }
}
