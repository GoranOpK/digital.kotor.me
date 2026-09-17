<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationOralPresentation;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApplicationOralPresentationsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_17_130000_create_application_oral_presentations_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_oral_table_exists_with_expected_columns_and_does_not_alter_sessions(): void
    {
        $this->assertTrue(Schema::hasTable('application_oral_presentations'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'commission_session_id'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'application_id'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'scheduled_at'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'held_at'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'applicant_attended'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'notes'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'completed_at'));
        $this->assertTrue(Schema::hasColumn('application_oral_presentations', 'recorded_by_user_id'));
        $this->assertFalse(Schema::hasColumn('commission_sessions', 'application_id'));

        $unique = DB::selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'application_oral_presentations'
               AND COLUMN_NAME = 'application_id'
               AND NON_UNIQUE = 0"
        );
        $this->assertSame(ApplicationOralPresentation::APPLICATION_UNIQUE_INDEX, $unique->INDEX_NAME);

        $sessionUnique = DB::selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commission_sessions'
               AND INDEX_NAME = 'commission_sessions_competition_type_unique'"
        );
        $this->assertNotNull($sessionUnique);
    }

    public function test_down_drops_only_oral_table_and_leaves_first_session_row(): void
    {
        $ctx = $this->makeYouthSessionRows();
        $firstId = $ctx['first']->id;
        $secondId = $ctx['second']->id;
        $oralId = $ctx['oral']->id;

        $this->assertNotNull(CommissionSession::query()->find($firstId));
        $this->assertNotNull(CommissionSession::query()->find($secondId));
        $this->assertNotNull(ApplicationOralPresentation::query()->find($oralId));

        $this->oralMigration()->down();

        $this->assertFalse(Schema::hasTable('application_oral_presentations'));
        $this->assertTrue(Schema::hasTable('commission_sessions'));
        $this->assertTrue(Schema::hasTable('commission_session_attendances'));
        $this->assertNotNull(CommissionSession::query()->find($firstId));
        $this->assertNotNull(CommissionSession::query()->find($secondId));
        $this->assertSame(CommissionSession::TYPE_FIRST, CommissionSession::query()->find($firstId)->session_type);
        $this->assertFalse(Schema::hasColumn('commission_sessions', 'application_id'));

        $this->oralMigration()->up();

        $this->assertTrue(Schema::hasTable('application_oral_presentations'));
        $this->assertNotNull(CommissionSession::query()->find($firstId));
        $this->assertNotNull(CommissionSession::query()->find($secondId));
        $this->assertSame(0, ApplicationOralPresentation::query()->count());
    }

    private function oralMigration(): object
    {
        return require base_path(self::MIGRATION);
    }

    /**
     * @return array{first: CommissionSession, second: CommissionSession, oral: ApplicationOralPresentation}
     */
    private function makeYouthSessionRows(): array
    {
        $commission = Commission::create([
            'name' => 'Migracija oral '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        $competition = Competition::create([
            'title' => 'Oral migracija',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-M-2026',
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan migracija',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
        $first = CommissionSession::create([
            'competition_id' => $competition->id,
            'commission_id' => $commission->id,
            'session_type' => CommissionSession::TYPE_FIRST,
            'held_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
            'recorded_by_user_id' => $user->id,
            'notes' => 'Prva ostaje',
        ]);
        $second = CommissionSession::create([
            'competition_id' => $competition->id,
            'commission_id' => $commission->id,
            'session_type' => CommissionSession::TYPE_SECOND,
            'held_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'recorded_by_user_id' => $user->id,
            'notes' => 'Druga ostaje',
        ]);
        $oral = ApplicationOralPresentation::create([
            'commission_session_id' => $second->id,
            'application_id' => $application->id,
            'scheduled_at' => now()->subDay(),
            'held_at' => now()->subDay(),
            'applicant_attended' => true,
            'notes' => 'Usmeno',
            'completed_at' => now()->subDay(),
            'recorded_by_user_id' => $user->id,
        ]);

        return compact('first', 'second', 'oral');
    }
}
