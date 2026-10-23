<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YouthAllocationListConfirmationMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_22_100000_add_youth_allocation_list_confirmation_audit_to_competitions_table.php';

    private const COLUMNS = [
        'youth_allocation_list_confirmed_at',
        'youth_allocation_list_confirmed_by_user_id',
        'youth_allocation_list_confirmed_by_commission_member_id',
        'youth_allocation_list_confirmed_by_name',
    ];

    private const USER_FK = 'comp_yalc_user_fk';

    private const MEMBER_FK = 'comp_yalc_member_fk';

    private const VOTING_TABLES = [
        'youth_equal_score_groups',
        'youth_equal_score_rounds',
        'youth_equal_score_round_applications',
        'youth_equal_score_votes',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_fresh_schema_is_nullable_with_named_foreign_keys(): void
    {
        foreach (self::COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('competitions', $column));
            $this->assertSame('YES', $this->column($column)->IS_NULLABLE);
        }

        $this->assertNotNull($this->foreign(self::USER_FK));
        $this->assertNotNull($this->foreign(self::MEMBER_FK));
        foreach (self::VOTING_TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    public function test_womens_and_existing_rows_stay_null_and_down_then_up_keeps_voting_tables(): void
    {
        $womensId = $this->insertCompetition('zensko');
        $youthId = $this->insertCompetition('omladinsko');

        $womens = DB::table('competitions')->where('id', $womensId)->first();
        $youth = DB::table('competitions')->where('id', $youthId)->first();
        foreach (self::COLUMNS as $column) {
            $this->assertNull($womens->{$column});
            $this->assertNull($youth->{$column});
        }

        $this->migration()->down();

        foreach (self::COLUMNS as $column) {
            $this->assertFalse(Schema::hasColumn('competitions', $column));
        }
        $this->assertNull($this->foreign(self::USER_FK));
        $this->assertNull($this->foreign(self::MEMBER_FK));
        foreach (self::VOTING_TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertNotNull(DB::table('competitions')->where('id', $womensId)->first());
        $this->assertNotNull(DB::table('competitions')->where('id', $youthId)->first());

        $this->migration()->up();

        foreach (self::COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('competitions', $column));
            $this->assertSame('YES', $this->column($column)->IS_NULLABLE);
        }
        $this->assertNotNull($this->foreign(self::USER_FK));
        $this->assertNotNull($this->foreign(self::MEMBER_FK));
        foreach (self::VOTING_TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $afterWomens = DB::table('competitions')->where('id', $womensId)->first();
        $afterYouth = DB::table('competitions')->where('id', $youthId)->first();
        foreach (self::COLUMNS as $column) {
            $this->assertNull($afterWomens->{$column});
            $this->assertNull($afterYouth->{$column});
        }
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function column(string $name): object
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['competitions', $name]
        );
        $this->assertNotNull($row);

        return $row;
    }

    private function foreign(string $name): ?object
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['competitions', $name, 'FOREIGN KEY']
        );
    }

    private function insertCompetition(string $type): int
    {
        return DB::table('competitions')->insertGetId([
            'title' => 'Migracija yalc '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => '10000.00',
            'deadline_days' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
