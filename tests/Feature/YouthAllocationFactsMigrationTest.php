<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YouthAllocationFactsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_21_120000_add_youth_allocation_facts_to_applications_table.php';

    private const COLUMNS = [
        'youth_innovative_tech_startup',
        'youth_innovative_tech_startup_confirmed_at',
        'youth_innovative_tech_startup_confirmed_by_user_id',
        'youth_prior_municipal_youth_funding',
        'youth_prior_municipal_youth_funding_confirmed_at',
        'youth_prior_municipal_youth_funding_confirmed_by_user_id',
        'youth_applied_cap_percent',
    ];

    private const ITS_USER_FK = 'app_youth_its_conf_by_user_fk';

    private const PRIOR_USER_FK = 'app_youth_prior_fund_conf_by_user_fk';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_fresh_schema_is_nullable_with_named_foreign_keys(): void
    {
        foreach (self::COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('applications', $column));
            $this->assertSame('YES', $this->column($column)->IS_NULLABLE);
        }

        $this->assertNotNull($this->foreign(self::ITS_USER_FK));
        $this->assertNotNull($this->foreign(self::PRIOR_USER_FK));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_green_innovative'));
        $this->assertTrue(Schema::hasColumn('competitions', 'max_support_percentage'));
    }

    public function test_womens_and_existing_rows_stay_null_and_down_then_up_restores_only_new_objects(): void
    {
        $womensId = $this->insertSubmittedApplication('zensko', 'preduzetnica');
        $youthId = $this->insertSubmittedApplication('omladinsko', 'fizicko_lice');
        DB::table('applications')->where('id', $womensId)->update([
            'bonus_green_innovative' => 1,
        ]);

        $womens = DB::table('applications')->where('id', $womensId)->first();
        $youth = DB::table('applications')->where('id', $youthId)->first();
        foreach (self::COLUMNS as $column) {
            $this->assertNull($womens->{$column});
            $this->assertNull($youth->{$column});
        }
        $this->assertSame(1, (int) $womens->bonus_green_innovative);

        $this->migration()->down();

        foreach (self::COLUMNS as $column) {
            $this->assertFalse(Schema::hasColumn('applications', $column));
        }
        $this->assertNull($this->foreign(self::ITS_USER_FK));
        $this->assertNull($this->foreign(self::PRIOR_USER_FK));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_green_innovative'));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_info_day'));
        $this->assertTrue(Schema::hasColumn('competitions', 'max_support_percentage'));

        $afterDown = DB::table('applications')->where('id', $womensId)->first();
        $this->assertSame(1, (int) $afterDown->bonus_green_innovative);

        $this->migration()->up();

        foreach (self::COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('applications', $column));
        }
        $this->assertNotNull($this->foreign(self::ITS_USER_FK));
        $this->assertNotNull($this->foreign(self::PRIOR_USER_FK));

        $afterUpWomens = DB::table('applications')->where('id', $womensId)->first();
        $afterUpYouth = DB::table('applications')->where('id', $youthId)->first();
        foreach (self::COLUMNS as $column) {
            $this->assertNull($afterUpWomens->{$column});
            $this->assertNull($afterUpYouth->{$column});
        }
        $this->assertSame(1, (int) $afterUpWomens->bonus_green_innovative);
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
            ['applications', $name]
        );
        $this->assertNotNull($row);

        return $row;
    }

    private function foreign(string $name): ?object
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['applications', $name, 'FOREIGN KEY']
        );
    }

    private function insertSubmittedApplication(string $type, string $applicantType): int
    {
        $competitionId = DB::table('competitions')->insertGetId([
            'title' => 'Migracija youth facts '.$type.' '.uniqid(),
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

        $userId = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ])->id;

        return DB::table('applications')->insertGetId([
            'competition_id' => $competitionId,
            'user_id' => $userId,
            'business_plan_name' => 'Plan migracija '.$type,
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
