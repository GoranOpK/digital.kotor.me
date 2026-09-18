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

class YouthBonusConfirmationAuditMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_18_100000_add_youth_bonus_confirmation_audit_to_applications_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_audit_columns_are_nullable_with_foreign_keys(): void
    {
        foreach ([
            'bonuses_confirmed_at',
            'bonuses_confirmed_by_user_id',
            'bonuses_confirmed_by_commission_member_id',
            'bonuses_confirmed_by_name',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('applications', $column));
            $this->assertSame('YES', $this->column($column)->IS_NULLABLE);
        }

        $this->assertNotNull($this->foreign('app_bonuses_conf_by_user_fk'));
        $this->assertNotNull($this->foreign('app_bonuses_conf_by_member_fk'));
    }

    public function test_existing_womens_row_stays_unchanged_and_down_drops_only_new_objects(): void
    {
        $applicationId = $this->insertSubmittedApplication();
        DB::table('applications')->where('id', $applicationId)->update([
            'bonus_info_day' => 1,
            'bonus_new_business' => 1,
            'bonus_zavod_nezaposleni' => 1,
            'bonus_green_innovative' => 1,
        ]);

        $before = DB::table('applications')->where('id', $applicationId)->first();
        $this->assertSame(1, (int) $before->bonus_info_day);
        $this->assertSame(1, (int) $before->bonus_new_business);
        $this->assertSame(1, (int) $before->bonus_zavod_nezaposleni);
        $this->assertSame(1, (int) $before->bonus_green_innovative);
        $this->assertNull($before->bonuses_confirmed_at);
        $this->assertNull($before->bonuses_confirmed_by_user_id);
        $this->assertNull($before->bonuses_confirmed_by_commission_member_id);
        $this->assertNull($before->bonuses_confirmed_by_name);

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('applications', 'bonuses_confirmed_at'));
        $this->assertFalse(Schema::hasColumn('applications', 'bonuses_confirmed_by_user_id'));
        $this->assertFalse(Schema::hasColumn('applications', 'bonuses_confirmed_by_commission_member_id'));
        $this->assertFalse(Schema::hasColumn('applications', 'bonuses_confirmed_by_name'));
        $this->assertNull($this->foreign('app_bonuses_conf_by_user_fk'));
        $this->assertNull($this->foreign('app_bonuses_conf_by_member_fk'));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_info_day'));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_training'));
        $this->assertTrue(Schema::hasColumn('applications', 'bonus_zavod_nezaposleni'));

        $afterDown = DB::table('applications')->where('id', $applicationId)->first();
        $this->assertSame(1, (int) $afterDown->bonus_info_day);
        $this->assertSame(1, (int) $afterDown->bonus_new_business);
        $this->assertSame(1, (int) $afterDown->bonus_zavod_nezaposleni);
        $this->assertSame(1, (int) $afterDown->bonus_green_innovative);

        $this->migration()->up();

        $afterUp = DB::table('applications')->where('id', $applicationId)->first();
        $this->assertSame(1, (int) $afterUp->bonus_info_day);
        $this->assertSame(1, (int) $afterUp->bonus_new_business);
        $this->assertSame(1, (int) $afterUp->bonus_zavod_nezaposleni);
        $this->assertSame(1, (int) $afterUp->bonus_green_innovative);
        $this->assertNull($afterUp->bonuses_confirmed_at);
        $this->assertNull($afterUp->bonuses_confirmed_by_user_id);
        $this->assertNull($afterUp->bonuses_confirmed_by_commission_member_id);
        $this->assertNull($afterUp->bonuses_confirmed_by_name);
        $this->assertNotNull($this->foreign('app_bonuses_conf_by_user_fk'));
        $this->assertNotNull($this->foreign('app_bonuses_conf_by_member_fk'));
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

    private function insertSubmittedApplication(): int
    {
        $competitionId = DB::table('competitions')->insertGetId([
            'title' => 'Migracija bonus audit '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
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
            'business_plan_name' => 'Plan migracija',
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
