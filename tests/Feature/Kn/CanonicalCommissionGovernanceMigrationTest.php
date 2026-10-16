<?php

namespace Tests\Feature\Kn;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanonicalCommissionGovernanceMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_25_100000_add_canonical_commission_governance.php';

    private const MEMBER_UNIQUES = [
        'commission_members_canonical_active_user_unique',
        'commission_members_canonical_active_seat_unique',
        'commission_members_canonical_active_seat_no_unique',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_new_columns_are_nullable_and_existing_women_youth_rows_stay_null(): void
    {
        [$commissionId, $memberIds] = $this->insertLegacyCommissionWithMembers();

        foreach (['profile_type', 'appointment_reference', 'appointed_at', 'mandate_starts_at', 'mandate_ends_at'] as $column) {
            $this->assertSame('YES', $this->nullable('commissions', $column));
        }
        foreach (['canonical_seat', 'external_category', 'represented_company_pib', 'replaced_at', 'replaced_by_member_id',
            'canonical_active_user_id', 'canonical_active_seat', 'canonical_active_seat_no'] as $column) {
            $this->assertSame('YES', $this->nullable('commission_members', $column));
        }
        $this->assertSame('YES', $this->nullable('commission_session_attendances', 'attendance_mode'));
        $this->assertSame('YES', $this->nullable('commission_sessions', 'canonical_purpose'));

        foreach (self::MEMBER_UNIQUES as $index) {
            $this->assertTrue(Schema::hasIndex('commission_members', $index));
        }
        $this->assertNull(DB::table('commissions')->where('id', $commissionId)->value('profile_type'));
        $this->assertSame(2, DB::table('commission_members')->whereIn('id', $memberIds)->whereNull('canonical_active_user_id')->count());
    }

    public function test_down_drops_only_new_objects_and_up_restores_them(): void
    {
        [$commissionId, $memberIds] = $this->insertLegacyCommissionWithMembers();

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('commission_member_replacements'));
        $this->assertFalse(Schema::hasTable('commission_governance_conflicts'));
        $this->assertFalse(Schema::hasColumn('commissions', 'profile_type'));
        $this->assertFalse(Schema::hasColumn('commission_members', 'canonical_seat'));
        $this->assertFalse(Schema::hasColumn('commission_members', 'canonical_active_user_id'));
        $this->assertFalse(Schema::hasColumn('commission_sessions', 'canonical_purpose'));
        $this->assertFalse(Schema::hasColumn('commission_session_attendances', 'attendance_mode'));
        $this->assertTrue(Schema::hasColumn('commission_members', 'canonical_seat_no'));
        $this->assertTrue(Schema::hasColumn('commission_members', 'replaces_member_number'));
        $this->assertTrue(collect(Schema::getIndexes('commission_members'))->contains(fn ($index) => $index['columns'] === ['commission_id']));
        $this->assertSame(2, DB::table('commission_members')->where('commission_id', $commissionId)->count());

        $this->migration()->up();

        foreach (self::MEMBER_UNIQUES as $index) {
            $this->assertTrue(Schema::hasIndex('commission_members', $index));
        }
        $this->assertTrue(Schema::hasTable('commission_member_replacements'));
        $this->assertTrue(Schema::hasTable('commission_governance_conflicts'));
        $this->assertSame(2, DB::table('commission_members')->whereIn('id', $memberIds)->count());
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function nullable(string $table, string $column): ?string
    {
        return DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )?->IS_NULLABLE;
    }

    /**
     * @return array{0: int, 1: list<int>}
     */
    private function insertLegacyCommissionWithMembers(): array
    {
        $commissionId = DB::table('commissions')->insertGetId([
            'name' => 'Ženska komisija',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-01',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberIds = [];
        foreach ([1, 2] as $seat) {
            $user = User::factory()->create([
                'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $memberIds[] = DB::table('commission_members')->insertGetId([
                'commission_id' => $commissionId,
                'user_id' => $user->id,
                'name' => 'Član '.$seat,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => 'opstina',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$commissionId, $memberIds];
    }
}
