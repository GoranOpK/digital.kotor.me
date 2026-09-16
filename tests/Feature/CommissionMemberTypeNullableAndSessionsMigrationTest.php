<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class CommissionMemberTypeNullableAndSessionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MEMBER_TYPE_MIGRATION = 'database/migrations/2026_09_15_140000_allow_null_member_type_on_commission_members.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_member_type_is_nullable_and_sessions_tables_exist(): void
    {
        $nullable = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['commission_members', 'member_type']
        );
        $this->assertSame('YES', $nullable->IS_NULLABLE);

        $this->assertTrue(Schema::hasTable('commission_sessions'));
        $this->assertTrue(Schema::hasTable('commission_session_attendances'));
        $this->assertTrue(Schema::hasColumn('commission_sessions', 'session_type'));
        $this->assertTrue(Schema::hasColumn('commission_sessions', 'held_at'));
        $this->assertTrue(Schema::hasColumn('commission_sessions', 'completed_at'));
        $this->assertTrue(Schema::hasColumn('commission_session_attendances', 'present'));
    }

    public function test_null_member_type_can_be_stored_without_rewriting_zensko_rows(): void
    {
        $commission = $this->makeCommission();
        $youth = CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $this->komisijaUser()->id,
            'name' => 'Mladi član',
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        $zensko = CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $this->komisijaUser()->id,
            'name' => 'Ženski član',
            'position' => 'clan',
            'member_type' => 'zene_mreza',
            'status' => 'active',
        ]);

        $this->assertNull($youth->fresh()->member_type);
        $this->assertSame('zene_mreza', $zensko->fresh()->member_type);
    }

    public function test_rollback_refuses_when_null_member_type_rows_exist(): void
    {
        $commission = $this->makeCommission();
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $this->komisijaUser()->id,
            'name' => 'Null tip',
            'position' => 'clan',
            'member_type' => null,
            'canonical_seat_no' => 2,
            'status' => 'active',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot restore member_type NOT NULL');
        $this->memberTypeMigration()->down();
    }

    public function test_rollback_succeeds_when_no_null_member_type_rows_exist(): void
    {
        $before = $this->memberTypeNullable();
        $this->assertSame('YES', $before);

        $this->memberTypeMigration()->down();
        $this->assertSame('NO', $this->memberTypeNullable());

        $this->memberTypeMigration()->up();
        $this->assertSame('YES', $this->memberTypeNullable());
    }

    private function memberTypeMigration(): object
    {
        return require base_path(self::MEMBER_TYPE_MIGRATION);
    }

    private function memberTypeNullable(): string
    {
        return DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['commission_members', 'member_type']
        )->IS_NULLABLE;
    }

    private function makeCommission(): Commission
    {
        return Commission::create([
            'name' => 'Migracija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function komisijaUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
