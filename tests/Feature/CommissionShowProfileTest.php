<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CommissionProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionShowProfileTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_null_profile_show_displays_undetermined_warning(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->makeLegacyCommission([
            ['Legacy A', 'predsjednik', 'opstina'],
            ['Legacy B', 'clan', 'udruzenje'],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            CommissionProfileConfig::UNDETERMINED_COMMISSION_SHOW_MESSAGE,
            $html
        );
        $this->assertNull($commission->fresh()->profile_type);
    }

    public function test_null_profile_show_lists_only_saved_members(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->makeLegacyCommission([
            ['Legacy Ana', 'predsjednik', 'opstina'],
            ['Legacy Bojan', 'clan', 'udruzenje'],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Legacy Ana', $html);
        $this->assertStringContainsString('Legacy Bojan', $html);
        $this->assertSame(2, substr_count($html, 'class="member-item"'));
        $this->assertStringContainsString('Broj članova:', $html);
        $this->assertStringNotContainsString('/ 5', $html);
        $this->assertStringNotContainsString('/ 3', $html);
    }

    public function test_null_profile_show_does_not_present_womens_or_youth_as_determined(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->makeLegacyCommission([
            ['Legacy Ana', 'predsjednik', 'opstina'],
            ['Legacy Bojan', 'clan', 'zene_mreza'],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Predstavnik Opštine Kotor', $html);
        $this->assertStringNotContainsString('Ženske političke mreže', $html);
        $this->assertStringNotContainsString('udruženja preduzetnica', $html);
        $this->assertStringNotContainsString('zene_mreza', $html);
        $this->assertStringNotContainsString('1. Mjesto Komisije', $html);
        $this->assertStringNotContainsString('2. Mjesto Komisije', $html);
        $this->assertStringNotContainsString('3. Mjesto Komisije', $html);
        $this->assertStringNotContainsString('Kanonsko mjesto', $html);
        $this->assertStringNotContainsString('Tip člana', $html);
    }

    public function test_null_profile_show_does_not_pad_empty_seats(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->makeLegacyCommission([
            ['Samo jedan', 'predsjednik', 'opstina'],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, substr_count($html, 'Nije dodijeljen'));
        $this->assertSame(0, substr_count($html, 'composition-slot-title">'));
        $this->assertStringContainsString('Samo jedan', $html);
        $this->assertSame(1, $commission->fresh()->members()->count());
        $this->assertNull($commission->fresh()->profile_type);
    }

    public function test_null_profile_edit_and_call_link_remain_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $commission = $this->makeLegacyCommission([
            ['Legacy Ana', 'predsjednik', 'opstina'],
        ]);
        $zensko = $this->makeDraftCompetition('zensko', 'Zenski show '.uniqid());

        $showHtml = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString(route('admin.commissions.edit', $commission), $showHtml);
        $this->assertStringNotContainsString('Dodaj novog člana', $showHtml);

        $this->actingAs($admin)
            ->get(route('admin.commissions.edit', $commission))
            ->assertOk()
            ->assertSee(CommissionProfileConfig::UNDETERMINED_COMMISSION_PROFILE_MESSAGE, false);

        $this->actingAs($admin)
            ->from(route('admin.commissions.edit', $commission))
            ->put(route('admin.commissions.update', $commission), [
                'name' => $commission->name,
                'year' => $commission->year,
                'start_date' => $commission->start_date->toDateString(),
                'end_date' => $commission->end_date->toDateString(),
                'status' => $commission->status,
                'competition_ids' => [$zensko->id],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('competition_type');
        $this->assertSame(
            CommissionProfileConfig::UNDETERMINED_COMMISSION_PROFILE_MESSAGE,
            session('errors')->first('competition_type')
        );

        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $zensko), [
                'title' => $zensko->title,
                'description' => $zensko->description,
                'type' => 'zensko',
                'up_number' => $zensko->upNumber->number,
                'year' => $zensko->year,
                'budget' => $zensko->budget,
                'start_date' => $zensko->start_date?->toDateString(),
                'status' => $zensko->status,
                'commission_id' => $commission->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_id');

        $this->assertNull($zensko->fresh()->commission_id);
        $this->assertNull($commission->fresh()->profile_type);
        $this->assertSame(0, $commission->fresh()->competitions()->count());
    }

    public function test_womens_show_keeps_five_category_seats(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeDraftCompetition('zensko', 'Zenski show '.uniqid());
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($competition))
            ->assertSessionHasNoErrors();
        $commission = $competition->fresh()->commission;

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertSame(5, substr_count($html, 'composition-slot-title">'));
        $this->assertStringContainsString('1. Predsjednik — Predstavnik Opštine Kotor', $html);
        $this->assertStringContainsString('Predstavnica Ženske političke mreže', $html);
        $this->assertStringContainsString('/ 5', $html);
        $this->assertStringNotContainsString('1. Mjesto Komisije', $html);
        $this->assertStringNotContainsString(
            CommissionProfileConfig::UNDETERMINED_COMMISSION_SHOW_MESSAGE,
            $html
        );
        $this->assertStringContainsString(route('admin.commissions.edit', $commission), $html);
    }

    public function test_youth_show_keeps_three_seats_without_womens_categories(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeDraftCompetition('omladinsko', 'Omladinski show '.uniqid());
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($competition))
            ->assertSessionHasNoErrors();
        $commission = $competition->fresh()->commission;

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();

        $this->assertSame(3, substr_count($html, 'composition-slot-title">'));
        $this->assertStringContainsString('1. Mjesto Komisije', $html);
        $this->assertStringContainsString('2. Mjesto Komisije', $html);
        $this->assertStringContainsString('3. Mjesto Komisije', $html);
        $this->assertStringContainsString('/ 3', $html);
        $this->assertStringNotContainsString('Predstavnik Opštine Kotor', $html);
        $this->assertStringNotContainsString('Ženske političke mreže', $html);
        $this->assertStringNotContainsString('zene_mreza', $html);
        $this->assertStringNotContainsString(
            CommissionProfileConfig::UNDETERMINED_COMMISSION_SHOW_MESSAGE,
            $html
        );
        $this->assertStringContainsString(route('admin.commissions.edit', $commission), $html);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $members
     */
    private function makeLegacyCommission(array $members): Commission
    {
        $commission = Commission::create([
            'name' => 'Legacy NULL show '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $this->assertNull($commission->profile_type);

        foreach ($members as $index => $row) {
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $this->userWithRole('komisija')->id,
                'name' => $row[0],
                'position' => $row[1],
                'member_type' => $row[2],
                'canonical_seat_no' => $index + 1,
                'status' => 'active',
            ]);
        }

        return $commission->fresh(['members.user']);
    }

    /**
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(Competition $competition): array
    {
        $members = [];
        foreach ([
            1 => ['name' => 'Predsjednik Show', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Show 2', 'position' => 'clan'],
            3 => ['name' => 'Clan Show 3', 'position' => 'clan'],
        ] as $seat => $row) {
            $members[$seat - 1] = [
                'name' => $row['name'],
                'email' => 'show.y.'.$seat.'.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => $row['position'],
                'canonical_seat_no' => $seat,
            ];
        }

        return [
            'name' => 'Komisija mladih show '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_type' => 'omladinsko',
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function zenskoCommissionPayload(Competition $competition): array
    {
        $rows = [
            ['Predsjednik Z Show', 'predsjednik', 'opstina', null],
            ['Clan 2 Z Show', 'clan', 'opstina', null],
            ['Clan 3 Z Show', 'clan', 'opstina', null],
            ['Clan 4 Z Show', 'clan', 'udruzenje', 'Udruženje'],
            ['Clan 5 Z Show', 'clan', 'zene_mreza', null],
        ];
        $members = [];
        foreach ($rows as $index => $row) {
            $members[$index] = [
                'name' => $row[0],
                'email' => 'show.z.'.$index.'.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => $row[1],
                'member_type' => $row[2],
            ];
            if ($row[3] !== null) {
                $members[$index]['organization'] = $row[3];
            }
        }

        return [
            'name' => 'Zenska komisija show '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_type' => 'zensko',
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    private function makeDraftCompetition(string $type, string $title): Competition
    {
        $year = $this->yearSerial++;
        $attributes = [
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => $type,
            'status' => 'draft',
            'year' => $year,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'competition_number' => '01-'.uniqid(),
        ];
        if ($type === 'omladinsko') {
            $attributes['call_number'] = 1;
            $attributes['annual_budget'] = '100000.00';
        }

        $competition = Competition::create($attributes);
        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $competition->competition_number,
        ]);

        return $competition->fresh();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }
}
