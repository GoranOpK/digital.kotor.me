<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\PaymentAccount;
use App\Models\PaymentInitiation;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionEvent;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class PaymentDomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_tables_and_required_columns_exist(): void
    {
        foreach ([
            'payment_types',
            'payment_accounts',
            'payment_initiations',
            'payment_transactions',
            'payment_transaction_events',
            'ep_catalog_audits',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->assertTrue(Schema::hasColumns('payment_types', [
            'id', 'code', 'name', 'description', 'is_active', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_accounts', [
            'id', 'payment_type_id', 'account_number', 'name', 'is_active', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_initiations', [
            'id', 'uuid', 'user_id', 'payment_type_id', 'payment_account_id', 'amount', 'currency',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_transactions', [
            'id', 'uuid', 'payment_initiation_id', 'user_id', 'payment_type_id', 'payment_account_id',
            'status', 'amount', 'currency', 'merchant_transaction_id', 'gateway_reference', 'provider', 'snapshot',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_transaction_events', [
            'id', 'payment_transaction_id', 'event_type', 'provider_event_id', 'payload',
            'occurred_at', 'received_at',
        ]));
        $this->assertTrue(Schema::hasColumns('ep_catalog_audits', [
            'id', 'actor_user_id', 'action', 'entity_type', 'entity_id', 'changes', 'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('ep_catalog_audits', 'updated_at'));

        $this->assertTrue(Schema::hasTable('payments'));
    }

    public function test_relationships_and_casts_work(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => PaymentStatus::Processing,
        ]);

        $this->assertTrue($transaction->paymentType->is($transaction->paymentAccount->paymentType));
        $this->assertTrue($transaction->initiation->transaction->is($transaction));
        $this->assertTrue($transaction->user->paymentTransactions->contains($transaction));
        $this->assertInstanceOf(PaymentStatus::class, $transaction->status);
        $this->assertSame(PaymentStatus::Processing, $transaction->status);
        $this->assertSame('12.50', $transaction->amount);
        $this->assertSame('EUR', $transaction->currency);
        $this->assertIsArray($transaction->snapshot);
        $this->assertTrue($transaction->paymentType->is_active);
        $this->assertTrue($transaction->paymentAccount->is_active);
    }

    public function test_initiation_is_not_a_business_transaction_and_has_unique_uuid(): void
    {
        $initiation = PaymentInitiation::factory()->create();

        $this->assertNull($initiation->transaction);
        $this->assertArrayNotHasKey('status', $initiation->getAttributes());
        $this->assertNotSame('', $initiation->uuid);

        $this->expectException(QueryException::class);
        PaymentInitiation::factory()->create(['uuid' => $initiation->uuid]);
    }

    public function test_transaction_identifiers_and_one_to_one_initiation_are_unique(): void
    {
        $first = PaymentTransaction::factory()->create([
            'merchant_transaction_id' => 'syn-merchant-1',
        ]);

        $this->expectException(QueryException::class);
        PaymentTransaction::factory()->create([
            'uuid' => $first->uuid,
        ]);
    }

    public function test_one_transaction_per_initiation(): void
    {
        $first = PaymentTransaction::factory()->create();

        $this->expectException(QueryException::class);
        PaymentTransaction::query()->create([
            'payment_initiation_id' => $first->payment_initiation_id,
            'user_id' => $first->user_id,
            'payment_type_id' => $first->payment_type_id,
            'payment_account_id' => $first->payment_account_id,
            'status' => PaymentStatus::Processing,
            'amount' => '1.00',
            'currency' => 'EUR',
        ]);
    }

    public function test_merchant_transaction_id_is_unique_when_present_and_allows_multiple_nulls(): void
    {
        PaymentTransaction::factory()->create(['merchant_transaction_id' => null]);
        PaymentTransaction::factory()->create(['merchant_transaction_id' => null]);

        PaymentTransaction::factory()->create(['merchant_transaction_id' => 'syn-merchant-unique']);

        $this->expectException(QueryException::class);
        PaymentTransaction::factory()->create(['merchant_transaction_id' => 'syn-merchant-unique']);
    }

    public function test_amount_is_stored_as_decimal_and_account_number_as_string(): void
    {
        $transaction = PaymentTransaction::factory()->create(['amount' => '100.50']);
        $row = DB::table('payment_transactions')->where('id', $transaction->id)->first();

        $this->assertSame('100.50', $transaction->fresh()->amount);
        $this->assertIsString($transaction->paymentAccount->account_number);
        $this->assertContains(
            Schema::getColumnType('payment_accounts', 'account_number'),
            ['string', 'varchar']
        );
        $this->assertContains(Schema::getColumnType('payment_transactions', 'amount'), ['decimal', 'numeric']);
        $this->assertSame('EUR', $row->currency);
        $this->assertSame('processing', $row->status);
    }

    public function test_snapshot_and_event_payload_json_storage(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'snapshot' => [
                'amount' => '20.00',
                'currency' => 'EUR',
                'payment_type_name' => 'Synthetic label',
            ],
        ]);
        $event = PaymentTransactionEvent::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'payload' => ['reason' => 'synthetic-event', 'email' => 'do-not-store@example.com'],
        ]);

        $this->assertSame('Synthetic label', $transaction->fresh()->snapshot['payment_type_name']);
        $this->assertSame('synthetic-event', $event->fresh()->payload['reason']);
        $this->assertArrayNotHasKey('email', $event->fresh()->payload);
    }

    public function test_inactive_catalog_rows_keep_transaction_history_and_snapshot(): void
    {
        $type = PaymentType::factory()->create(['name' => 'Original type label']);
        $account = PaymentAccount::factory()->create([
            'payment_type_id' => $type->id,
            'account_number' => 'SYN-11111111111111111111',
        ]);
        $initiation = PaymentInitiation::factory()->create([
            'payment_type_id' => $type->id,
            'payment_account_id' => $account->id,
        ]);
        $transaction = PaymentTransaction::factory()->create([
            'payment_initiation_id' => $initiation->id,
            'user_id' => $initiation->user_id,
            'payment_type_id' => $type->id,
            'payment_account_id' => $account->id,
            'snapshot' => [
                'payment_type_name' => 'Original type label',
                'account_number' => 'SYN-11111111111111111111',
                'amount' => '12.50',
                'currency' => 'EUR',
            ],
        ]);

        $type->update(['name' => 'Changed live label', 'is_active' => false]);
        $account->update(['is_active' => false]);

        $transaction->refresh();

        $this->assertFalse($type->fresh()->is_active);
        $this->assertFalse($account->fresh()->is_active);
        $this->assertTrue($transaction->paymentType->is($type));
        $this->assertTrue($transaction->paymentAccount->is($account));
        $this->assertSame('Original type label', $transaction->snapshot['payment_type_name']);
        $this->assertSame('Changed live label', $type->fresh()->name);
    }

    public function test_restrict_delete_keeps_financial_history(): void
    {
        $transaction = PaymentTransaction::factory()->create();

        $this->expectException(QueryException::class);
        PaymentType::query()->whereKey($transaction->payment_type_id)->delete();
    }

    public function test_events_are_append_only(): void
    {
        $event = PaymentTransactionEvent::factory()->create();

        $this->expectException(LogicException::class);
        $event->update(['event_type' => 'changed']);
    }

    public function test_events_and_transactions_cannot_be_deleted_through_eloquent(): void
    {
        $event = PaymentTransactionEvent::factory()->create();
        $transaction = $event->transaction;

        try {
            $event->delete();
            $this->fail('Event delete must be rejected.');
        } catch (LogicException $e) {
            $this->assertSame('Payment transaction events are append-only.', $e->getMessage());
        }

        $this->expectException(LogicException::class);
        $transaction->delete();
    }

    public function test_stub_payments_table_is_untouched_by_foundation_models(): void
    {
        $this->assertTrue(Schema::hasColumn('payments', 'status'));

        $user = User::factory()->create();
        DB::table('payments')->insert([
            'user_id' => $user->id,
            'payment_type' => 'stub',
            'amount' => '1.00',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        $this->assertSame(0, PaymentTransaction::query()->count());
    }
}
