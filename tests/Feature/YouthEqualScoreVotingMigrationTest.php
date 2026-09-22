<?php

namespace Tests\Feature;

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

class YouthEqualScoreVotingMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_21_160000_create_youth_equal_score_voting_tables.php';

    private const TABLES = [
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

    public function test_fresh_schema_has_named_foreign_keys_and_uniques(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertNotNull($this->foreign('youth_equal_score_groups', 'yesg_competition_fk'));
        $this->assertNotNull($this->unique('youth_equal_score_groups', 'yesg_comp_key_uq'));

        $this->assertNotNull($this->foreign('youth_equal_score_rounds', 'yesr_group_fk'));
        $this->assertNotNull($this->foreign('youth_equal_score_rounds', 'yesr_created_user_fk'));
        $this->assertNotNull($this->foreign('youth_equal_score_rounds', 'yesr_created_member_fk'));
        $this->assertNotNull($this->unique('youth_equal_score_rounds', 'yesr_group_round_uq'));

        $this->assertNotNull($this->foreign('youth_equal_score_round_applications', 'yesra_round_fk'));
        $this->assertNotNull($this->foreign('youth_equal_score_round_applications', 'yesra_application_fk'));
        $this->assertNotNull($this->unique('youth_equal_score_round_applications', 'yesra_round_app_uq'));

        $this->assertNotNull($this->foreign('youth_equal_score_votes', 'yesv_round_fk'));
        $this->assertNotNull($this->foreign('youth_equal_score_votes', 'yesv_member_fk'));
        $this->assertNotNull($this->foreign('youth_equal_score_votes', 'yesv_user_fk'));
        $this->assertNotNull($this->unique('youth_equal_score_votes', 'yesv_round_seat_uq'));

        $this->assertTrue(Schema::hasColumn('youth_equal_score_groups', 'group_key'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_groups', 'full_score'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_groups', 'ranking_position'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_groups', 'applied_rule'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_rounds', 'locked_at'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_rounds', 'outcome'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_round_applications', 'selected'));
        $this->assertTrue(Schema::hasColumn('youth_equal_score_votes', 'canonical_seat_no'));
    }

    public function test_down_then_up_leaves_sessions_and_womens_rows_untouched(): void
    {
        $womensId = $this->insertSubmittedApplication('zensko', 'preduzetnica');
        $youthId = $this->insertSubmittedApplication('omladinsko', 'fizicko_lice');
        $sessions = $this->insertYouthSessionsFor($youthId);

        DB::table('applications')->where('id', $womensId)->update([
            'bonus_green_innovative' => 1,
        ]);
        $this->assertSame(1, (int) DB::table('applications')->where('id', $womensId)->value('bonus_green_innovative'));
        $this->assertNotNull(CommissionSession::query()->find($sessions['first']));
        $this->assertNotNull(CommissionSession::query()->find($sessions['second']));
        $this->assertSame(CommissionSession::TYPE_FIRST, CommissionSession::query()->find($sessions['first'])->session_type);
        $this->assertSame(CommissionSession::TYPE_SECOND, CommissionSession::query()->find($sessions['second'])->session_type);

        $this->migration()->down();

        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertTrue(Schema::hasTable('commission_sessions'));
        $this->assertTrue(Schema::hasTable('commission_session_attendances'));
        $this->assertTrue(Schema::hasTable('applications'));
        $this->assertNotNull(CommissionSession::query()->find($sessions['first']));
        $this->assertNotNull(CommissionSession::query()->find($sessions['second']));
        $this->assertSame(1, (int) DB::table('applications')->where('id', $womensId)->value('bonus_green_innovative'));
        $this->assertNotNull(DB::table('applications')->where('id', $youthId)->first());
        $this->assertNull($this->foreign('youth_equal_score_groups', 'yesg_competition_fk'));

        $this->migration()->up();

        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertNotNull($this->foreign('youth_equal_score_groups', 'yesg_competition_fk'));
        $this->assertNotNull($this->unique('youth_equal_score_groups', 'yesg_comp_key_uq'));
        $this->assertSame(0, DB::table('youth_equal_score_groups')->count());
        $this->assertSame(0, DB::table('youth_equal_score_rounds')->count());
        $this->assertSame(0, DB::table('youth_equal_score_round_applications')->count());
        $this->assertSame(0, DB::table('youth_equal_score_votes')->count());
        $this->assertNotNull(CommissionSession::query()->find($sessions['first']));
        $this->assertNotNull(CommissionSession::query()->find($sessions['second']));
        $this->assertSame(1, (int) DB::table('applications')->where('id', $womensId)->value('bonus_green_innovative'));
        $this->assertSame(CommissionSession::TYPE_FIRST, CommissionSession::query()->find($sessions['first'])->session_type);
        $this->assertSame(CommissionSession::TYPE_SECOND, CommissionSession::query()->find($sessions['second'])->session_type);
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function foreign(string $table, string $name): ?object
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );
    }

    private function unique(string $table, string $name): ?object
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'UNIQUE']
        );
    }

    /**
     * @return array{first: int, second: int}
     */
    private function insertYouthSessionsFor(int $youthApplicationId): array
    {
        $application = DB::table('applications')->where('id', $youthApplicationId)->first();
        $competition = DB::table('competitions')->where('id', $application->competition_id)->first();
        $userId = $application->user_id;
        $commission = Commission::create([
            'name' => 'Migracija yesv '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $userId,
            'name' => 'Predsjednik migracija',
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        DB::table('competitions')->where('id', $competition->id)->update([
            'commission_id' => $commission->id,
        ]);

        $first = CommissionSession::create([
            'competition_id' => $competition->id,
            'commission_id' => $commission->id,
            'session_type' => CommissionSession::TYPE_FIRST,
            'held_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
            'recorded_by_user_id' => $userId,
            'notes' => 'Prva ostaje',
        ]);
        $second = CommissionSession::create([
            'competition_id' => $competition->id,
            'commission_id' => $commission->id,
            'session_type' => CommissionSession::TYPE_SECOND,
            'held_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'recorded_by_user_id' => $userId,
            'notes' => 'Druga ostaje',
        ]);

        return ['first' => $first->id, 'second' => $second->id];
    }

    private function insertSubmittedApplication(string $type, string $applicantType): int
    {
        $competitionId = DB::table('competitions')->insertGetId([
            'title' => 'Migracija yesv '.$type.' '.uniqid(),
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
