<?php

namespace Tests\Feature;

use App\Exceptions\CulturalActivityRecordException;
use App\Models\CulturalActivityRecord;
use App\Models\NewsletterDeliveryLedger;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalActivity\CulturalActivityActor;
use App\Services\CulturalActivity\CulturalActivityRecordInput;
use App\Services\CulturalActivity\CulturalActivityRecorder;
use App\Services\CulturalActivity\CulturalActivityRecordWriteResult;
use App\Services\CulturalActivity\CulturalActivitySourceModule;
use App\Services\CulturalActivity\CulturalActivityStore;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * F8-02 — TS-012 central store foundation (bez emitera, bez Admin UI).
 */
class CulturalActivityFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CulturalActivityStore $store;

    private CulturalActivityRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->store = app(CulturalActivityStore::class);
        $this->recorder = app(CulturalActivityRecorder::class);
    }

    public function test_valid_event_inserts_one_row_with_preserved_occurred_at(): void
    {
        $occurredAt = now()->subMinutes(7);
        $input = $this->userInput(['occurred_at' => $occurredAt, 'context' => ['request_id' => 12]]);

        $result = $this->store->write($input);

        $this->assertTrue($result->wasInserted());
        $this->assertDatabaseCount('cultural_activity_records', 1);
        $this->assertNotSame('newsletter_delivery_ledger', (new CulturalActivityRecord)->getTable());
        $record = $result->record;
        $this->assertNotNull($record);
        $this->assertSame(CulturalActivitySourceModule::TS_001, $record->source_module);
        $this->assertSame('org.request.submit:1', $record->event_id);
        $this->assertSame('org.request.submit', $record->event_type);
        $this->assertSame(
            $occurredAt->format('Y-m-d H:i:s'),
            $record->occurred_at->format('Y-m-d H:i:s')
        );
        $this->assertTrue($record->created_at->greaterThan($occurredAt));
        $this->assertSame(['request_id' => 12], $record->context);
        $this->assertNull($record->updated_at ?? null);
        $this->assertFalse(Schema::hasColumn('cultural_activity_records', 'updated_at'));
    }

    public function test_created_at_column_has_current_timestamp_default(): void
    {
        $row = DB::selectOne(
            "SELECT COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'cultural_activity_records'
               AND COLUMN_NAME = 'created_at'"
        );

        $this->assertNotNull($row);
        $this->assertSame('NO', $row->IS_NULLABLE);
        $this->assertNotNull($row->COLUMN_DEFAULT);
        $this->assertTrue(
            str_contains(strtoupper((string) $row->COLUMN_DEFAULT), 'CURRENT_TIMESTAMP'),
            'created_at default must be CURRENT_TIMESTAMP, got: '.$row->COLUMN_DEFAULT
        );

        $fk = DB::selectOne(
            "SELECT DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = 'car_actor_user_fk'"
        );
        $this->assertNotNull($fk);
        $this->assertSame('RESTRICT', $fk->DELETE_RULE);
    }

    public function test_idempotent_retry_does_not_insert_second_row(): void
    {
        $input = $this->userInput();
        $first = $this->store->write($input);
        $second = $this->store->write($input);

        $this->assertTrue($first->wasInserted());
        $this->assertSame(CulturalActivityRecordWriteResult::DUPLICATE, $second->status);
        $this->assertTrue($second->alreadyExists());
        $this->assertDatabaseCount('cultural_activity_records', 1);
        $this->assertTrue($first->record->is($second->record));
    }

    public function test_database_unique_constraint_on_source_module_and_event_id(): void
    {
        $this->store->write($this->userInput());

        $this->expectException(QueryException::class);

        DB::table('cultural_activity_records')->insert([
            'source_module' => CulturalActivitySourceModule::TS_001,
            'event_id' => 'org.request.submit:1',
            'event_type' => 'other.type',
            'occurred_at' => now(),
            'actor_type' => CulturalActivityRecord::ACTOR_USER,
            'actor_user_id' => $this->user->id,
            'target_type' => 'organizer_request',
            'target_id' => 1,
            'created_at' => now(),
        ]);
    }

    public function test_mismatch_duplicate_does_not_overwrite(): void
    {
        Event::fake([MessageLogged::class]);
        $this->store->write($this->userInput(['event_type' => 'org.request.submit']));

        $mismatch = $this->store->write($this->userInput([
            'event_type' => 'org.request.reject',
            'context' => ['request_id' => 99],
        ]));

        $this->assertSame(CulturalActivityRecordWriteResult::MISMATCH, $mismatch->status);
        $this->assertDatabaseCount('cultural_activity_records', 1);
        $this->assertSame('org.request.submit', CulturalActivityRecord::query()->first()->event_type);
        $this->assertNull(CulturalActivityRecord::query()->first()->context);
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && ($event->message === 'cultural_activity.duplicate_mismatch'
                    || str_contains($event->message, 'duplicate_mismatch'));
        });
    }

    public function test_user_and_system_actors_persist(): void
    {
        $userResult = $this->store->write($this->userInput(['event_id' => 'user-action-1']));
        $systemResult = $this->store->write($this->systemInput(['event_id' => 'system-action-1']));

        $this->assertSame(CulturalActivityRecord::ACTOR_USER, $userResult->record->actor_type);
        $this->assertSame((int) $this->user->id, (int) $userResult->record->actor_user_id);
        $this->assertSame(CulturalActivityRecord::ACTOR_SYSTEM, $systemResult->record->actor_type);
        $this->assertNull($systemResult->record->actor_user_id);
    }

    public function test_invalid_source_module_is_rejected(): void
    {
        $this->expectException(CulturalActivityRecordException::class);

        $this->userInput(['source_module' => 'TS-010']);
    }

    public function test_writer_does_not_generate_event_id(): void
    {
        $this->expectException(CulturalActivityRecordException::class);

        $this->userInput(['event_id' => '']);
    }

    public function test_model_update_and_delete_are_prohibited(): void
    {
        $record = $this->store->write($this->userInput())->record;

        try {
            $record->event_type = 'tampered';
            $record->save();
            $this->fail('Update must not succeed.');
        } catch (CulturalActivityRecordException $e) {
            $this->assertSame('Audit zapis je nepromjenjiv.', $e->getMessage());
        }

        try {
            $record->delete();
            $this->fail('Delete must not succeed.');
        } catch (CulturalActivityRecordException $e) {
            $this->assertSame('Audit zapis je nepromjenjiv.', $e->getMessage());
        }

        try {
            CulturalActivityRecord::query()->whereKey($record->id)->update(['event_type' => 'tampered']);
            $this->fail('Query update must not succeed.');
        } catch (CulturalActivityRecordException $e) {
            $this->assertSame('Audit zapis je nepromjenjiv.', $e->getMessage());
        }

        $fresh = $record->fresh();
        $this->assertSame('org.request.submit', $fresh->event_type);
        $this->assertDatabaseCount('cultural_activity_records', 1);
    }

    public function test_context_rejects_denied_keys_and_nested_payload(): void
    {
        try {
            $this->userInput(['context' => ['password' => 'secret']]);
            $this->fail('Denied key must be rejected.');
        } catch (CulturalActivityRecordException) {
        }

        try {
            $this->userInput(['context' => ['unsubscribe_token' => 'abc']]);
            $this->fail('Token key must be rejected.');
        } catch (CulturalActivityRecordException) {
        }

        try {
            $this->userInput(['context' => ['snapshot' => ['id' => 1]]]);
            $this->fail('Nested context must be rejected.');
        } catch (CulturalActivityRecordException) {
        }

        $this->assertDatabaseCount('cultural_activity_records', 0);
    }

    public function test_safe_facade_swallows_store_failure_and_logs(): void
    {
        Event::fake([MessageLogged::class]);

        $store = $this->createMock(CulturalActivityStore::class);
        $store->expects($this->once())
            ->method('write')
            ->willThrowException(new QueryException('mysql', 'insert', [], new \Exception('forced')));

        $recorder = new CulturalActivityRecorder($store);
        $result = $recorder->record($this->userInput());

        $this->assertTrue($result->wasFailed());
        $this->assertNotNull($result->error);
        $this->assertDatabaseCount('cultural_activity_records', 0);
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'error'
                && ($event->message === 'cultural_activity.store_failed'
                    || str_contains($event->message, 'store_failed'));
        });
    }

    public function test_newsletter_ledger_is_not_the_audit_store(): void
    {
        $this->assertSame('newsletter_delivery_ledger', (new NewsletterDeliveryLedger)->getTable());
        $this->assertSame('cultural_activity_records', (new CulturalActivityRecord)->getTable());
        $this->assertTrue(Schema::hasTable('cultural_activity_records'));
        $this->assertTrue(Schema::hasTable('newsletter_delivery_ledger'));
    }

    public function test_existing_workflows_are_not_wired_to_central_audit(): void
    {
        $paths = [
            app_path('Services/CulturalEventDomain/EventLifecycle.php'),
            app_path('Services/CulturalEventDomain/OccurrenceLifecycle.php'),
            app_path('Services/CulturalManifestationDomain/ManifestationLifecycle.php'),
            app_path('Services/Newsletter/NewsletterFirstIncludeDeliveryService.php'),
            app_path('Services/Newsletter/NewsletterPriorityDeliveryService.php'),
            app_path('Services/CulturalOrganizer/OrganizerCreationDecisionService.php'),
            app_path('Services/CulturalOrganizer/ModeratorRequestDecisionService.php'),
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);
            $this->assertStringNotContainsString('CulturalActivityRecorder', $contents);
            $this->assertStringNotContainsString('CulturalActivityStore', $contents);
            $this->assertStringNotContainsString('CulturalActivityRecordInput', $contents);
        }

        $this->assertDatabaseCount('cultural_activity_records', 0);
    }

    public function test_restrict_user_delete_keeps_historical_actor(): void
    {
        $this->store->write($this->userInput());

        $this->expectException(QueryException::class);
        $this->user->delete();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function userInput(array $overrides = []): CulturalActivityRecordInput
    {
        return new CulturalActivityRecordInput(
            sourceModule: $overrides['source_module'] ?? CulturalActivitySourceModule::TS_001,
            eventId: $overrides['event_id'] ?? 'org.request.submit:1',
            eventType: $overrides['event_type'] ?? 'org.request.submit',
            occurredAt: $overrides['occurred_at'] ?? now()->subMinute(),
            actor: CulturalActivityActor::user($this->user),
            targetType: 'organizer_request',
            targetId: 1,
            organizerContextId: null,
            context: $overrides['context'] ?? [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function systemInput(array $overrides = []): CulturalActivityRecordInput
    {
        return new CulturalActivityRecordInput(
            sourceModule: CulturalActivitySourceModule::TS_004,
            eventId: $overrides['event_id'] ?? 'occ.auto_finish:1',
            eventType: 'occ.auto_finish',
            occurredAt: now()->subMinute(),
            actor: CulturalActivityActor::system(),
            targetType: 'occurrence',
            targetId: 1,
        );
    }
}
