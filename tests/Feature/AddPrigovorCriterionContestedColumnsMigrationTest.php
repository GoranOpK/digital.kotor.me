<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddPrigovorCriterionContestedColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'database/migrations/2026_09_16_120000_add_criterion_contested_columns_to_application_prigovors.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_contested_columns_exist_and_are_nullable(): void
    {
        foreach (['criterion_1_contested', 'criterion_2_contested', 'criterion_3_contested'] as $column) {
            $this->assertTrue(Schema::hasColumn('application_prigovors', $column));
            $this->assertSame('YES', $this->column($column)->IS_NULLABLE);
        }
    }

    public function test_existing_womens_row_stays_null_and_down_drops_only_new_columns(): void
    {
        $applicationId = $this->insertSubmittedApplication();
        $prigovorId = DB::table('application_prigovors')->insertGetId([
            'application_id' => $applicationId,
            'obrazlozenje' => 'Ženski plain-text prigovor',
            'status' => ApplicationPrigovor::STATUS_PODNESEN,
            'submitted_at' => now(),
            'submitted_by_user_id' => Application::query()->findOrFail($applicationId)->user_id,
            'eliminatory_reason_remaining' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('application_prigovors')->where('id', $prigovorId)->first();
        $this->assertSame('Ženski plain-text prigovor', $before->obrazlozenje);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $before->status);
        $this->assertNull($before->criterion_1_contested);
        $this->assertNull($before->criterion_2_contested);
        $this->assertNull($before->criterion_3_contested);

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('application_prigovors', 'criterion_1_contested'));
        $this->assertFalse(Schema::hasColumn('application_prigovors', 'criterion_2_contested'));
        $this->assertFalse(Schema::hasColumn('application_prigovors', 'criterion_3_contested'));
        $this->assertTrue(Schema::hasColumn('application_prigovors', 'obrazlozenje'));
        $this->assertTrue(Schema::hasColumn('application_prigovors', 'criterion_1_remaining'));

        $afterDown = DB::table('application_prigovors')->where('id', $prigovorId)->first();
        $this->assertSame('Ženski plain-text prigovor', $afterDown->obrazlozenje);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $afterDown->status);
        $this->assertTrue($afterDown->eliminatory_reason_remaining == 1);

        $this->migration()->up();

        $afterUp = DB::table('application_prigovors')->where('id', $prigovorId)->first();
        $this->assertSame('Ženski plain-text prigovor', $afterUp->obrazlozenje);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $afterUp->status);
        $this->assertNull($afterUp->criterion_1_contested);
        $this->assertNull($afterUp->criterion_2_contested);
        $this->assertNull($afterUp->criterion_3_contested);
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION_PATH);
    }

    private function column(string $name): object
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['application_prigovors', $name]
        );
        $this->assertNotNull($row);

        return $row;
    }

    private function insertSubmittedApplication(): int
    {
        $competitionId = DB::table('competitions')->insertGetId([
            'title' => 'Migracija prigovor '.uniqid(),
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
