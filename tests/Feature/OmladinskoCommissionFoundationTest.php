<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CommissionCanonicalSeat;
use App\Support\CommissionProfileConfig;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmladinskoCommissionFoundationTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_profile_config_and_provides_commission_for_omladinsko(): void
    {
        $config = CommissionProfileConfig::for('omladinsko');
        $this->assertTrue($config->providesCommission);
        $this->assertSame(3, $config->seatCount);
        $this->assertSame([1, 2, 3], $config->allowedSeats);
        $this->assertSame(2, $config->firstSessionQuorum);
        $this->assertSame(3, $config->allMembersRequiredCount);
        $this->assertTrue($config->usesExplicitCanonicalSeats);
        $this->assertFalse($config->usesMemberTypeCatalog);

        $zensko = CommissionProfileConfig::for('zensko');
        $this->assertTrue($zensko->providesCommission);
        $this->assertSame(5, $zensko->seatCount);
        $this->assertSame([1, 2, 3, 4, 5], $zensko->allowedSeats);
        $this->assertNull($zensko->firstSessionQuorum);

        $this->assertFalse(CommissionProfileConfig::for('ostalo')->providesCommission);

        $competition = $this->makeOmladinskoDraft();
        $this->assertTrue($competition->profileProvidesCommission());
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );
    }

    public function test_youth_commission_requires_three_seats_president_and_rejects_duplicates_and_seats_four_five(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();

        $incomplete = $this->actingAs($admin)->post(route('admin.commissions.store'), $this->youthCommissionPayload($competition, [
            1 => ['name' => 'Ana Predsjednik', 'email' => 'ana.pred.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Marko Clan', 'email' => 'marko.clan.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]));
        $incomplete->assertRedirect();
        $incomplete->assertSessionHasNoErrors();
        $partial = Commission::query()->latest('id')->first();
        $competition->refresh();
        $this->assertSame($partial->id, $competition->commission_id);
        $this->assertFalse($competition->hasCompleteValidCommission());

        $duplicateSeat = $this->actingAs($admin)->post(route('admin.commissions.store'), [
            'name' => 'Duplikat '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$this->makeOmladinskoDraft('Drugi poziv '.uniqid())->id],
            'members' => [
                0 => $this->youthMember(1, 'A', 'predsjednik', 'a.'.uniqid().'@kotor.me'),
                1 => $this->youthMember(1, 'B', 'clan', 'b.'.uniqid().'@kotor.me'),
                2 => $this->youthMember(3, 'C', 'clan', 'c.'.uniqid().'@kotor.me'),
            ],
        ]);
        $duplicateSeat->assertRedirect();
        $duplicateSeat->assertSessionHasErrors('members.1.canonical_seat_no');

        $seatFour = $this->actingAs($admin)->post(route('admin.commissions.store'), [
            'name' => 'Seat4 '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$this->makeOmladinskoDraft('Treci '.uniqid())->id],
            'members' => [
                0 => $this->youthMember(1, 'A', 'predsjednik', 'a4.'.uniqid().'@kotor.me'),
                1 => $this->youthMember(2, 'B', 'clan', 'b4.'.uniqid().'@kotor.me'),
                2 => [
                    'name' => 'Nedozvoljeno',
                    'email' => 'c4.'.uniqid().'@kotor.me',
                    'password' => 'password12',
                    'position' => 'clan',
                    'canonical_seat_no' => 4,
                ],
            ],
        ]);
        $seatFour->assertRedirect();
        $seatFour->assertSessionHasErrors('members.2.canonical_seat_no');

        $completeCompetition = $this->makeOmladinskoDraft('Kompletna '.uniqid());
        $complete = $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($completeCompetition)
        );
        $complete->assertRedirect();
        $complete->assertSessionHasNoErrors();
        $completeCompetition->refresh();
        $this->assertTrue($completeCompetition->hasCompleteValidCommission());
        $this->assertSame(3, $completeCompetition->commission->activeMembers()->count());
        $this->assertSame(1, $completeCompetition->commission->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertTrue(
            $completeCompetition->commission->activeMembers()->whereNull('member_type')->count() === 3
        );
        $this->assertEqualsCanonicalizing(
            [1, 2, 3],
            $completeCompetition->commission->activeMembers->pluck('canonical_seat_no')->all()
        );
    }

    public function test_youth_president_may_occupy_any_of_seats_one_two_three(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $formCompetition = $this->makeOmladinskoDraft('Forma predsjednik '.uniqid());
        $form = $this->actingAs($admin)->get(route('admin.commissions.create', [
            'competition_id' => $formCompetition->id,
        ]));
        $form->assertOk();
        $formHtml = $form->getContent();
        $this->assertStringContainsString('name="members[0][position]"', $formHtml);
        $this->assertStringContainsString('name="members[1][position]"', $formHtml);
        $this->assertStringContainsString('name="members[2][position]"', $formHtml);
        $this->assertEquals(3, substr_count($formHtml, 'value="predsjednik"'));
        $this->assertStringNotContainsString('name="members[3]', $formHtml);

        foreach ([1, 2, 3] as $presidentSeat) {
            $competition = $this->makeOmladinskoDraft('Predsjednik mjesto '.$presidentSeat.' '.uniqid());
            $payload = $this->youthCommissionPayload($competition, $this->youthSeatsWithPresidentOn($presidentSeat));
            $this->assertCount(3, $payload['members']);

            $response = $this->actingAs($admin)->post(route('admin.commissions.store'), $payload);
            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $competition->refresh();
            $this->assertYouthPresidentRemainsOnSeat($competition, $presidentSeat);

            CommissionCanonicalSeat::persistForCommission($competition->commission->fresh('members'));
            $this->assertYouthPresidentRemainsOnSeat($competition->fresh(), $presidentSeat);
        }

        $zensko = Competition::create([
            'title' => 'Zensko raspored '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => 'zensko',
            'status' => 'draft',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
        ]);
        UpNumber::create(['competition_id' => $zensko->id, 'number' => 'UP-Z-'.uniqid()]);
        $this->actingAs($admin)->post(route('admin.commissions.store'), [
            'name' => 'Zenska '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$zensko->id],
            'members' => [
                0 => ['name' => 'Predsjednik Z', 'email' => 'z.pred.'.uniqid().'@kotor.me', 'password' => 'password12', 'position' => 'predsjednik', 'member_type' => 'opstina'],
                1 => ['name' => 'Clan 2 Z', 'email' => 'z.c2.'.uniqid().'@kotor.me', 'password' => 'password12', 'position' => 'clan', 'member_type' => 'opstina'],
                2 => ['name' => 'Clan 3 Z', 'email' => 'z.c3.'.uniqid().'@kotor.me', 'password' => 'password12', 'position' => 'clan', 'member_type' => 'opstina'],
                3 => ['name' => 'Clan 4 Z', 'email' => 'z.c4.'.uniqid().'@kotor.me', 'password' => 'password12', 'position' => 'clan', 'member_type' => 'udruzenje', 'organization' => 'Udruženje'],
                4 => ['name' => 'Clan 5 Z', 'email' => 'z.c5.'.uniqid().'@kotor.me', 'password' => 'password12', 'position' => 'clan', 'member_type' => 'zene_mreza'],
            ],
        ])->assertSessionHasNoErrors();

        $zensko->refresh();
        $this->assertTrue($zensko->hasCompleteValidCommission());
        $this->assertSame(5, $zensko->commission->activeMembers()->count());
        $this->assertSame('predsjednik', $zensko->commission->activeMembers->firstWhere('member_type', 'opstina')->position);
        $this->assertSame(
            1,
            $zensko->commission->activeMembers()->where('position', 'predsjednik')->count()
        );
        $this->assertEqualsCanonicalizing(
            ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'],
            $zensko->commission->activeMembers->pluck('member_type')->all()
        );
        $this->assertEqualsCanonicalizing(
            [1, 2, 3, 4, 5],
            $zensko->commission->activeMembers->map(fn (CommissionMember $member) => $member->canonicalSeatNumber())->all()
        );
    }

    public function test_omladinsko_publish_without_commission_succeeds_with_warning(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();

        $response = $this->actingAs($admin)->post(route('admin.competitions.publish', $competition));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas(
            'commission_incomplete_warning',
            CommissionProfileConfig::OMLADINSKO_INCOMPLETE_PUBLISH_WARNING
        );

        $competition->refresh();
        $this->assertSame('published', $competition->status);
        $this->assertNull($competition->commission_id);
        $this->assertFalse($competition->hasCompleteValidCommission());
    }

    public function test_commission_cannot_be_shared_between_zensko_and_omladinsko(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))->assertSessionHasNoErrors();
        $youth->refresh();
        $commissionId = $youth->commission_id;

        $zensko = Competition::create([
            'title' => 'Zensko '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => 'zensko',
            'status' => 'draft',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
        ]);
        UpNumber::create(['competition_id' => $zensko->id, 'number' => 'UP-Z-'.uniqid()]);

        $response = $this->actingAs($admin)->from(route('admin.competitions.edit', $zensko))->put(
            route('admin.competitions.update', $zensko),
            [
                'title' => $zensko->title,
                'description' => $zensko->description,
                'type' => 'zensko',
                'up_number' => $zensko->upNumber->number,
                'year' => $zensko->year,
                'budget' => $zensko->budget,
                'start_date' => $zensko->start_date?->toDateString(),
                'status' => 'draft',
                'commission_id' => $commissionId,
            ]
        );

        $response->assertRedirect(route('admin.competitions.edit', $zensko));
        $response->assertSessionHasErrors('commission_id');
        $this->assertSame(
            CommissionProfileConfig::MIXED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('commission_id')
        );
        $this->assertNull($zensko->fresh()->commission_id);
    }

    public function test_youth_replacement_keeps_seat_and_history(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $this->actingAs($admin)->post(route('admin.commissions.store'), $this->youthCommissionPayload($competition))->assertSessionHasNoErrors();
        $commission = $competition->fresh()->commission;
        $replaced = $commission->activeMembers()->where('canonical_seat_no', 2)->first();
        $this->assertNotNull($replaced);

        $response = $this->actingAs($admin)->post(route('admin.commissions.members.add', $commission), [
            'name' => 'Zamjena Mjesto Dva',
            'email' => 'zamjena.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 2,
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('inactive', $replaced->fresh()->status);
        $substitute = CommissionMember::query()
            ->where('commission_id', $commission->id)
            ->where('is_substitute', true)
            ->first();
        $this->assertNotNull($substitute);
        $this->assertSame(2, (int) $substitute->replaces_member_number);
        $this->assertSame(2, (int) $substitute->canonical_seat_no);
        $this->assertNull($substitute->member_type);
        $this->assertSame('active', $substitute->status);
        $this->assertNotNull(CommissionMember::query()->find($replaced->id));

        $seatFive = $this->actingAs($admin)->post(route('admin.commissions.members.add', $commission), [
            'name' => 'Nedozvoljena zamjena',
            'email' => 'seat5.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 5,
        ]);
        $seatFive->assertSessionHasErrors('replaces_member_number');
    }

    public function test_zensko_five_member_completeness_and_publish_without_commission_remain(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = Competition::create([
            'title' => 'Zensko publish '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => 'zensko',
            'status' => 'draft',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-'.uniqid()]);

        $publish = $this->actingAs($admin)->post(route('admin.competitions.publish', $competition));
        $publish->assertRedirect();
        $publish->assertSessionHas('success');
        $publish->assertSessionMissing('commission_incomplete_warning');

        $commission = Commission::create([
            'name' => 'Zenska '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        foreach ($types as $i => $type) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $i === 0 ? 'predsjednik' : 'clan',
                'member_type' => $type,
                'status' => 'active',
            ]);
        }
        $competition->update(['commission_id' => $commission->id]);
        $this->assertTrue($competition->fresh()->hasCompleteValidCommission());
        $this->assertSame(5, $commission->activeMembers()->count());
    }

    /**
     * @return array<int, array{name: string, email: string, position: string}>
     */
    private function youthSeatsWithPresidentOn(int $presidentSeat): array
    {
        $seats = [];
        foreach ([1, 2, 3] as $seat) {
            $seats[$seat] = [
                'name' => $seat === $presidentSeat ? 'Predsjednik Mjesto '.$seat : 'Clan Mjesto '.$seat,
                'email' => ($seat === $presidentSeat ? 'pred.' : 'clan.').$seat.'.'.uniqid().'@kotor.me',
                'position' => $seat === $presidentSeat ? 'predsjednik' : 'clan',
            ];
        }

        return $seats;
    }

    private function assertYouthPresidentRemainsOnSeat(Competition $competition, int $presidentSeat): void
    {
        $commission = $competition->commission()->with('activeMembers')->first();
        $this->assertNotNull($commission);
        $active = $commission->activeMembers;
        $this->assertSame(3, $active->count());
        $this->assertTrue($competition->hasCompleteValidCommission());
        $this->assertEqualsCanonicalizing([1, 2, 3], $active->pluck('canonical_seat_no')->all());
        $this->assertSame(1, $active->where('position', 'predsjednik')->count());
        $president = $active->firstWhere('position', 'predsjednik');
        $this->assertNotNull($president);
        $this->assertSame($presidentSeat, (int) $president->canonical_seat_no);
        $this->assertSame($presidentSeat, (int) $president->canonicalSeatNumber());
        $this->assertNull($president->member_type);
    }

    private function makeOmladinskoDraft(?string $title = null): Competition
    {
        $year = $this->yearSerial++;
        $competition = Competition::create([
            'title' => $title ?? 'Omladinski nacrt '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '80000.00',
            'deadline_days' => 20,
            'competition_number' => '01-'.uniqid(),
        ]);
        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $competition->competition_number,
        ]);

        return $competition->fresh();
    }

    /**
     * @param  array<int, array<string, string>>  $overridesBySeat
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(Competition $competition, array $overridesBySeat = []): array
    {
        $defaults = [
            1 => ['name' => 'Predsjednik Tri', 'email' => 'p.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Dva', 'email' => 'c2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Tri', 'email' => 'c3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ];

        $seats = $overridesBySeat === [] ? $defaults : $overridesBySeat;
        $members = [];
        foreach ($seats as $seat => $row) {
            $members[$seat - 1] = $this->youthMember(
                $seat,
                $row['name'],
                $row['position'],
                $row['email']
            );
        }

        return [
            'name' => 'Komisija mladih '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function youthMember(int $seat, string $name, string $position, string $email): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'password' => 'password12',
            'position' => $position,
            'canonical_seat_no' => $seat,
        ];
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
