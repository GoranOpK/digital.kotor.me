<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommissionCompetitionTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_16_100000_add_competition_type_to_commissions_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_backfill_sets_only_homogeneous_zensko_and_omladinsko_null_rows(): void
    {
        $this->assertTrue(Schema::hasColumn('commissions', 'profile_type'));
        $this->assertFalse(Schema::hasColumn('commissions', 'competition_type'));

        $zenskoId = $this->insertCommission('Homogena zenska');
        $youthId = $this->insertCommission('Homogena omladinska');
        $emptyId = $this->insertCommission('Bez Poziva');
        $ostaloId = $this->insertCommission('Ostalo legacy');
        $mixedId = $this->insertCommission('Mjesovita legacy');
        $canonicalId = $this->insertCommission('Kanonska MMSP', CompetitionProfileResolver::TYPE_CANONICAL);
        $presetId = $this->insertCommission('Vec zensko', 'zensko');

        $zenskoMember = $this->insertMember($zenskoId, 'Zenska clanica', 'opstina', null);
        $youthMember = $this->insertMember($youthId, 'Omladinski clan', null, 1);

        $this->insertCompetition($zenskoId, 'zensko', 2026);
        $this->insertCompetition($youthId, 'omladinsko', 2027);
        $this->insertCompetition($ostaloId, 'ostalo', 2028);
        $this->insertCompetition($mixedId, 'zensko', 2029);
        $this->insertCompetition($mixedId, 'omladinsko', 2030);
        $this->insertCompetition($canonicalId, CompetitionProfileResolver::TYPE_CANONICAL, 2031);
        $this->insertCompetition($presetId, 'omladinsko', 2032);

        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('commissions', 'profile_type'));
        $this->assertFalse(Schema::hasColumn('commissions', 'competition_type'));

        $this->assertSame('zensko', DB::table('commissions')->where('id', $zenskoId)->value('profile_type'));
        $this->assertSame('omladinsko', DB::table('commissions')->where('id', $youthId)->value('profile_type'));
        $this->assertNull(DB::table('commissions')->where('id', $emptyId)->value('profile_type'));
        $this->assertNull(DB::table('commissions')->where('id', $ostaloId)->value('profile_type'));
        $this->assertNull(DB::table('commissions')->where('id', $mixedId)->value('profile_type'));
        $this->assertSame(
            CompetitionProfileResolver::TYPE_CANONICAL,
            DB::table('commissions')->where('id', $canonicalId)->value('profile_type')
        );
        $this->assertSame('zensko', DB::table('commissions')->where('id', $presetId)->value('profile_type'));

        $this->assertSame('opstina', DB::table('commission_members')->where('id', $zenskoMember)->value('member_type'));
        $this->assertNull(DB::table('commission_members')->where('id', $youthMember)->value('member_type'));
        $this->assertSame(1, (int) DB::table('commission_members')->where('id', $youthMember)->value('canonical_seat_no'));
        $this->assertSame(2, (int) DB::table('competitions')->where('commission_id', $mixedId)->count());
    }

    public function test_down_does_not_drop_profile_type_and_up_is_idempotent(): void
    {
        $this->assertTrue(Schema::hasColumn('commissions', 'profile_type'));

        $commissionId = $this->insertCommission('Ponovni up');
        $canonicalId = $this->insertCommission('Kanonska ostaje', CompetitionProfileResolver::TYPE_CANONICAL);
        $memberId = $this->insertMember($commissionId, 'Clan ostaje', 'zene_mreza', null);
        $this->insertCompetition($commissionId, 'zensko', 2033);

        $this->migration()->up();
        $this->assertSame('zensko', DB::table('commissions')->where('id', $commissionId)->value('profile_type'));

        $this->migration()->down();
        $this->assertTrue(Schema::hasColumn('commissions', 'profile_type'));
        $this->assertFalse(Schema::hasColumn('commissions', 'competition_type'));
        $this->assertTrue(Schema::hasTable('commissions'));
        $this->assertTrue(Schema::hasTable('commission_members'));
        $this->assertSame('zensko', DB::table('commissions')->where('id', $commissionId)->value('profile_type'));
        $this->assertSame(
            CompetitionProfileResolver::TYPE_CANONICAL,
            DB::table('commissions')->where('id', $canonicalId)->value('profile_type')
        );
        $this->assertSame('Clan ostaje', DB::table('commission_members')->where('id', $memberId)->value('name'));
        $this->assertSame('zene_mreza', DB::table('commission_members')->where('id', $memberId)->value('member_type'));

        $this->migration()->up();
        $this->assertSame('zensko', DB::table('commissions')->where('id', $commissionId)->value('profile_type'));
        $this->assertSame(
            CompetitionProfileResolver::TYPE_CANONICAL,
            DB::table('commissions')->where('id', $canonicalId)->value('profile_type')
        );
        $this->assertSame('Clan ostaje', DB::table('commission_members')->where('id', $memberId)->value('name'));
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function insertCommission(string $name, ?string $profileType = null): int
    {
        return (int) DB::table('commissions')->insertGetId([
            'name' => $name.' '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $profileType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertMember(int $commissionId, string $name, ?string $memberType, ?int $seat): int
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        return (int) DB::table('commission_members')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $user->id,
            'name' => $name,
            'position' => 'predsjednik',
            'member_type' => $memberType,
            'canonical_seat_no' => $seat,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertCompetition(int $commissionId, string $type, int $year): void
    {
        $attributes = [
            'title' => $type.' '.$year.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => $type,
            'status' => 'draft',
            'year' => $year,
            'budget' => '10000.00',
            'deadline_days' => 20,
            'commission_id' => $commissionId,
            'competition_number' => '01-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if ($type === 'omladinsko') {
            $attributes['call_number'] = 1;
            $attributes['annual_budget'] = '10000.00';
        }

        $id = (int) DB::table('competitions')->insertGetId($attributes);
        UpNumber::create([
            'competition_id' => $id,
            'number' => $attributes['competition_number'],
        ]);
    }
};
