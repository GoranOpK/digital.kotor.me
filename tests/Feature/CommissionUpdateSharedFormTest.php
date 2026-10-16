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
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommissionUpdateSharedFormTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_update_rejects_empty_ostalo_unknown_mixed_and_missing_ids(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();
        $youthCommission = $youth->fresh()->commission;

        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($zensko))
            ->assertSessionHasNoErrors();
        $zenskoCommission = $zensko->fresh()->commission;

        $ostalo = $this->makeDraftCompetition('ostalo', 'Ostalo update '.uniqid());
        $otherYouth = $this->makeOmladinskoDraft();
        $otherZensko = $this->makeZenskoDraft();

        $this->assertUpdateRejected($admin, $youthCommission, [$ostalo->id], 'competition_ids', $youth, $ostalo);
        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            CommissionProfileConfig::canonicalFormTypeFromTypes([null])['error']
        );
        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            CommissionProfileConfig::canonicalFormTypeFromTypes(['nepoznat'])['error']
        );

        $this->assertUpdateRejected(
            $admin,
            $youthCommission,
            [$youth->id, $zensko->id],
            'competition_ids',
            $youth,
            $zensko
        );
        $this->assertSame(
            CommissionProfileConfig::MIXED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $this->assertUpdateRejected(
            $admin,
            $zenskoCommission,
            [$zensko->id, $ostalo->id],
            'competition_ids',
            $zensko,
            $ostalo
        );
        $this->assertSame(
            CommissionProfileConfig::MIXED_COMPETITION_TYPES_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $this->assertUpdateRejected(
            $admin,
            $youthCommission,
            [$youth->id, $ostalo->id],
            'competition_ids',
            $youth,
            $ostalo
        );
        $this->assertSame(
            CommissionProfileConfig::MIXED_COMPETITION_TYPES_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $this->assertUpdateRejected($admin, $youthCommission, [999999], 'competition_ids', $youth);
        $this->assertUpdateRejected($admin, $youthCommission, [$youth->id, 999999], 'competition_ids', $youth);
        $this->assertSame(
            CommissionProfileConfig::UNKNOWN_COMPETITION_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );

        Mail::fake();
        $forged = $this->updatePayload($youthCommission, [$ostalo->id]);
        $forged['competition_type'] = 'omladinsko';
        $this->actingAs($admin)
            ->from(route('admin.commissions.edit', $youthCommission))
            ->put(route('admin.commissions.update', $youthCommission), $forged)
            ->assertRedirect()
            ->assertSessionHasErrors('competition_ids');
        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );
        $this->assertSame($youthCommission->id, $youth->fresh()->commission_id);
        $this->assertNull($ostalo->fresh()->commission_id);
        Mail::assertNothingSent();

        $this->assertSame($otherYouth->id, $otherYouth->fresh()->id);
        $this->assertNull($otherYouth->fresh()->commission_id);
        $this->assertNull($otherZensko->fresh()->commission_id);
    }

    public function test_homogeneous_womens_update_keeps_five_seats_and_categories(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeZenskoDraft();
        $second = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($first))
            ->assertSessionHasNoErrors();
        Mail::fake();

        $commission = $first->fresh()->commission;
        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, [$first->id, $second->id]))
            ->assertRedirect(route('admin.commissions.show', $commission))
            ->assertSessionHasNoErrors();

        $commission->refresh()->load('activeMembers');
        $this->assertTrue($first->fresh()->hasCompleteValidCommission());
        $this->assertSame($commission->id, $second->fresh()->commission_id);
        $this->assertSame(5, $commission->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'],
            $commission->activeMembers->pluck('member_type')->all()
        );
        $this->assertSame(1, $commission->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertSame('zensko', $commission->profile_type);
    }

    public function test_homogeneous_youth_update_keeps_three_seats_and_one_president(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoDraft();
        $second = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($first))
            ->assertSessionHasNoErrors();
        Mail::fake();

        $commission = $first->fresh()->commission;
        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, [$first->id, $second->id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $commission->refresh()->load('activeMembers');
        $this->assertSame($commission->id, $first->fresh()->commission_id);
        $this->assertSame($commission->id, $second->fresh()->commission_id);
        $this->assertSame(3, $commission->activeMembers()->count());
        $this->assertEqualsCanonicalizing([1, 2, 3], $commission->activeMembers->pluck('canonical_seat_no')->all());
        $this->assertSame(1, $commission->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertTrue($commission->activeMembers->every(fn (CommissionMember $member) => $member->member_type === null));
        $this->assertSame('omladinsko', $commission->profile_type);
    }

    public function test_update_can_remove_all_calls_and_keep_stored_profile(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();
        $commission = $youth->fresh()->commission;
        Mail::fake();

        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, []))
            ->assertRedirect(route('admin.commissions.show', $commission))
            ->assertSessionHasNoErrors();

        $this->assertSame('omladinsko', $commission->fresh()->profile_type);
        $this->assertNull($youth->fresh()->commission_id);
        $this->assertSame(3, $commission->fresh()->activeMembers()->count());
        $this->assertSame(0, $commission->fresh()->competitions()->count());
        Mail::assertNothingSent();
    }

    public function test_update_can_reattach_same_profile_call_after_unlink(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoDraft();
        $second = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($first))
            ->assertSessionHasNoErrors();
        $commission = $first->fresh()->commission;
        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, []))
            ->assertSessionHasNoErrors();

        Mail::fake();
        $this->actingAs($admin)
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, [$second->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame('omladinsko', $commission->fresh()->profile_type);
        $this->assertNull($first->fresh()->commission_id);
        $this->assertSame($commission->id, $second->fresh()->commission_id);
        $this->assertSame(3, $commission->fresh()->activeMembers()->count());
    }

    public function test_womens_commission_cannot_be_rebound_to_youth_call(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($zensko))
            ->assertSessionHasNoErrors();
        $commission = $zensko->fresh()->commission;

        $this->assertUpdateRejected($admin, $commission, [$youth->id], 'competition_ids', $zensko, $youth);
        $this->assertSame(
            CommissionProfileConfig::COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE,
            session('errors')->first('competition_ids')
        );
        $this->assertSame(5, $commission->fresh()->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'],
            $commission->fresh()->activeMembers->pluck('member_type')->all()
        );
        $this->assertSame(1, $commission->fresh()->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertSame($commission->id, $zensko->fresh()->commission_id);
        $this->assertNull($youth->fresh()->commission_id);
    }

    public function test_youth_commission_cannot_be_rebound_to_womens_call(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();
        $commission = $youth->fresh()->commission;

        $this->assertUpdateRejected($admin, $commission, [$zensko->id], 'competition_ids', $youth, $zensko);
        $this->assertSame(
            CommissionProfileConfig::COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE,
            session('errors')->first('competition_ids')
        );
        $this->assertSame(3, $commission->fresh()->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            [1, 2, 3],
            $commission->fresh()->activeMembers->pluck('canonical_seat_no')->all()
        );
        $this->assertSame(1, $commission->fresh()->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertSame($commission->id, $youth->fresh()->commission_id);
        $this->assertNull($zensko->fresh()->commission_id);
    }

    public function test_commission_without_assigned_call_is_rejected_fail_closed(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $orphan = Commission::create([
            'name' => 'Komisija bez Poziva '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        foreach ([
            ['Predsjednik orphan', 'predsjednik', 'opstina', 1],
            ['Clan 2 orphan', 'clan', 'opstina', 2],
            ['Clan 3 orphan', 'clan', 'opstina', 3],
            ['Clan 4 orphan', 'clan', 'udruzenje', 4],
            ['Clan 5 orphan', 'clan', 'zene_mreza', 5],
        ] as $row) {
            CommissionMember::create([
                'commission_id' => $orphan->id,
                'user_id' => $this->userWithRole('komisija')->id,
                'name' => $row[0],
                'position' => $row[1],
                'member_type' => $row[2],
                'canonical_seat_no' => $row[3],
                'status' => 'active',
            ]);
        }

        $this->assertUpdateRejected($admin, $orphan, [$zensko->id], 'competition_type', $zensko);
        $this->assertSame(
            CommissionProfileConfig::UNDETERMINED_COMMISSION_PROFILE_MESSAGE,
            session('errors')->first('competition_type')
        );
        $this->assertSame(5, $orphan->fresh()->activeMembers()->count());
        $this->assertNull($zensko->fresh()->commission_id);
        $this->assertSame(0, $orphan->fresh()->competitions()->count());
    }

    public function test_forged_competition_type_cannot_switch_profile(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();
        $commission = $youth->fresh()->commission;

        Mail::fake();
        $forged = $this->updatePayload($commission, [$youth->id]);
        $forged['competition_type'] = 'zensko';
        $this->actingAs($admin)
            ->from(route('admin.commissions.edit', $commission))
            ->put(route('admin.commissions.update', $commission), $forged)
            ->assertRedirect()
            ->assertSessionHasErrors('competition_type');
        $this->assertSame(
            CommissionProfileConfig::PROFILE_CHANGE_REJECTED_MESSAGE,
            session('errors')->first('competition_type')
        );
        $this->assertSame($commission->id, $youth->fresh()->commission_id);
        $this->assertNull($zensko->fresh()->commission_id);
        $this->assertSame(3, $commission->fresh()->activeMembers()->count());
        Mail::assertNothingSent();
    }

    public function test_womens_edit_form_does_not_offer_youth_calls(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($zensko))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.edit', $zensko->fresh()->commission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($zensko->title, $html);
        $this->assertStringNotContainsString($youth->title, $html);
    }

    public function test_youth_edit_form_does_not_offer_womens_calls(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.edit', $youth->fresh()->commission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($youth->title, $html);
        $this->assertStringNotContainsString($zensko->title, $html);
    }

    public function test_competition_paths_cannot_attach_commission_to_ostalo(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($zensko))
            ->assertSessionHasNoErrors();
        $commission = $zensko->fresh()->commission;
        $ostalo = $this->makeDraftCompetition('ostalo', 'Ostalo putanja '.uniqid());
        Mail::fake();

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $ostalo))
            ->put(route('admin.competitions.update', $ostalo), [
                'title' => $ostalo->title,
                'description' => $ostalo->description,
                'type' => 'ostalo',
                'up_number' => $ostalo->upNumber->number,
                'year' => $ostalo->year,
                'budget' => $ostalo->budget,
                'start_date' => $ostalo->start_date?->toDateString(),
                'status' => $ostalo->status,
                'commission_id' => $commission->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_id');
        $this->assertNull($ostalo->fresh()->commission_id);
        $this->assertSame($commission->id, $zensko->fresh()->commission_id);
        Mail::assertNothingSent();

        $this->actingAs($admin)
            ->post(route('admin.competitions.store'), [
                'title' => 'Ostalo sa Komisijom '.uniqid(),
                'description' => 'Opis',
                'type' => 'ostalo',
                'up_number' => '01-ostalo-'.uniqid(),
                'year' => 2035,
                'budget' => '1000.00',
                'start_date' => now()->toDateString(),
                'commission_id' => $commission->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_id');
        $this->assertSame($commission->id, $zensko->fresh()->commission_id);
        $this->assertSame(1, $commission->fresh()->competitions()->count());
        Mail::assertNothingSent();

        $this->actingAs($admin)
            ->post(route('admin.competitions.store'), [
                'title' => 'Omladinski uz zensku Komisiju '.uniqid(),
                'description' => 'Opis',
                'type' => 'omladinsko',
                'up_number' => '01-oml-'.uniqid(),
                'year' => 2036,
                'budget' => '1000.00',
                'start_date' => now()->toDateString(),
                'commission_id' => $commission->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_id');
        $this->assertSame(1, $commission->fresh()->competitions()->count());

        $legacy = Commission::create([
            'name' => 'Legacy NULL '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
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
                'commission_id' => $legacy->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_id');
        $this->assertSame($commission->id, $zensko->fresh()->commission_id);
        Mail::assertNothingSent();
    }

    public function test_edit_form_does_not_offer_ostalo(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $ostalo = $this->makeDraftCompetition('ostalo', 'Ostalo na formi '.uniqid());
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.edit', $youth->fresh()->commission))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($youth->title, $html);
        $this->assertStringNotContainsString($ostalo->title, $html);
        $this->assertStringNotContainsString('value="ostalo"', $html);
        $this->assertStringContainsString('profil Komisije ostaje nepromjenjiv', $html);
    }

    /**
     * @param  list<int>  $competitionIds
     */
    private function assertUpdateRejected(
        User $admin,
        Commission $commission,
        array $competitionIds,
        string $errorKey,
        Competition ...$watched
    ): void {
        Mail::fake();
        $commission->refresh();
        $snapshot = [
            'name' => $commission->name,
            'year' => $commission->year,
            'status' => $commission->status,
            'start_date' => $commission->start_date?->toDateString(),
            'end_date' => $commission->end_date?->toDateString(),
        ];
        $members = $commission->members()->orderBy('id')->get([
            'id', 'position', 'member_type', 'canonical_seat_no', 'status', 'user_id',
        ])->toArray();
        $links = [];
        foreach ($watched as $competition) {
            $links[$competition->id] = $competition->fresh()->commission_id;
        }

        $this->actingAs($admin)
            ->from(route('admin.commissions.edit', $commission))
            ->put(route('admin.commissions.update', $commission), $this->updatePayload($commission, $competitionIds))
            ->assertRedirect()
            ->assertSessionHasErrors($errorKey);

        $commission->refresh();
        $this->assertSame($snapshot['name'], $commission->name);
        $this->assertSame($snapshot['year'], $commission->year);
        $this->assertSame($snapshot['status'], $commission->status);
        $this->assertSame($snapshot['start_date'], $commission->start_date?->toDateString());
        $this->assertSame($snapshot['end_date'], $commission->end_date?->toDateString());
        $this->assertSame($members, $commission->members()->orderBy('id')->get([
            'id', 'position', 'member_type', 'canonical_seat_no', 'status', 'user_id',
        ])->toArray());
        foreach ($watched as $competition) {
            $this->assertSame($links[$competition->id], $competition->fresh()->commission_id);
        }
        Mail::assertNothingSent();
    }

    /**
     * @param  list<int>  $competitionIds
     * @return array<string, mixed>
     */
    private function updatePayload(Commission $commission, array $competitionIds): array
    {
        return [
            'name' => $commission->name,
            'year' => $commission->year,
            'start_date' => $commission->start_date->toDateString(),
            'end_date' => $commission->end_date->toDateString(),
            'status' => $commission->status,
            'competition_ids' => $competitionIds,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $overridesBySeat
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(Competition $competition, array $overridesBySeat = []): array
    {
        $defaults = [
            1 => ['name' => 'Predsjednik Update', 'email' => 'upd.p.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Update 2', 'email' => 'upd.c2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Update 3', 'email' => 'upd.c3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ];
        $seats = $overridesBySeat === [] ? $defaults : $overridesBySeat;
        $members = [];
        foreach ($seats as $seat => $row) {
            $members[$seat - 1] = [
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => 'password12',
                'position' => $row['position'],
                'canonical_seat_no' => $seat,
            ];
        }

        return [
            'name' => 'Komisija mladih update '.uniqid(),
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
            ['Predsjednik Z Update', 'predsjednik', 'opstina', null],
            ['Clan 2 Z Update', 'clan', 'opstina', null],
            ['Clan 3 Z Update', 'clan', 'opstina', null],
            ['Clan 4 Z Update', 'clan', 'udruzenje', 'Udruženje'],
            ['Clan 5 Z Update', 'clan', 'zene_mreza', null],
        ];
        $members = [];
        foreach ($rows as $index => $row) {
            $members[$index] = [
                'name' => $row[0],
                'email' => 'upd.z.'.$index.'.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => $row[1],
                'member_type' => $row[2],
            ];
            if ($row[3] !== null) {
                $members[$index]['organization'] = $row[3];
            }
        }

        return [
            'name' => 'Zenska komisija update '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_type' => 'zensko',
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    private function makeOmladinskoDraft(?string $title = null): Competition
    {
        return $this->makeDraftCompetition('omladinsko', $title ?? 'Omladinski update '.uniqid());
    }

    private function makeZenskoDraft(?string $title = null): Competition
    {
        return $this->makeDraftCompetition('zensko', $title ?? 'Zenski update '.uniqid());
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
