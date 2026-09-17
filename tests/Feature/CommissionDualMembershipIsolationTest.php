<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CommissionProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionDualMembershipIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_same_users_can_be_active_on_separate_youth_and_womens_commissions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youthCompetition = $this->makeOmladinskoDraft(2026);
        $shared = [
            1 => ['name' => 'Predsjednik Zajednicki', 'email' => 'dual.pred.'.uniqid().'@kotor.me'],
            2 => ['name' => 'Clan Dva Zajednicki', 'email' => 'dual.c2.'.uniqid().'@kotor.me'],
            3 => ['name' => 'Clan Tri Zajednicki', 'email' => 'dual.c3.'.uniqid().'@kotor.me'],
        ];

        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($youthCompetition, $shared)
        )->assertSessionHasNoErrors();

        $youthCompetition->refresh();
        $youthCommission = $youthCompetition->commission;
        $this->assertNotNull($youthCommission);
        $this->assertSame(3, $youthCommission->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            [1, 2, 3],
            $youthCommission->activeMembers->pluck('canonical_seat_no')->all()
        );

        $sharedUsers = [];
        foreach ($shared as $seat => $row) {
            $sharedUsers[$seat] = User::where('email', $row['email'])->firstOrFail();
        }

        $zenskoCompetition = $this->makeZenskoDraft(2026);
        $extra = [
            4 => ['name' => 'Clan Cetiri Zensko', 'email' => 'zensko.c4.'.uniqid().'@kotor.me'],
            5 => ['name' => 'Clan Pet Zensko', 'email' => 'zensko.c5.'.uniqid().'@kotor.me'],
        ];

        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->womensCommissionPayload($zenskoCompetition, $shared, $extra)
        )->assertSessionHasNoErrors();

        $zenskoCompetition->refresh();
        $zenskoCommission = $zenskoCompetition->commission;
        $this->assertNotNull($zenskoCommission);
        $this->assertNotSame($youthCommission->id, $zenskoCommission->id);
        $this->assertSame(5, $zenskoCommission->activeMembers()->count());
        $this->assertTrue($zenskoCompetition->hasCompleteValidCommission());
        $this->assertTrue($youthCompetition->fresh()->hasCompleteValidCommission());
        $this->assertEqualsCanonicalizing(
            [1, 2, 3, 4, 5],
            $zenskoCommission->fresh('activeMembers')->activeMembers
                ->map(fn (CommissionMember $member) => $member->canonicalSeatNumber())
                ->all()
        );

        foreach ($sharedUsers as $user) {
            $this->assertSame(2, CommissionMember::where('user_id', $user->id)->where('status', 'active')->count());
            $this->assertNotNull(CommissionMember::activeForCommission($user->id, $youthCommission->id));
            $this->assertNotNull(CommissionMember::activeForCommission($user->id, $zenskoCommission->id));
        }

        $incompleteYouth = $this->makeOmladinskoDraft(2028);
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($incompleteYouth, [
                1 => ['name' => 'Samo Predsjednik', 'email' => 'dup.pred.'.uniqid().'@kotor.me'],
                2 => ['name' => 'Samo Clan Dva', 'email' => 'dup.c2.'.uniqid().'@kotor.me'],
            ])
        )->assertSessionHasNoErrors();
        $incompleteCommission = $incompleteYouth->fresh()->commission;
        $existingEmail = $incompleteCommission->activeMembers()->where('canonical_seat_no', 1)->first()->user->email;

        $duplicateOnSameCommission = $this->actingAs($admin)->post(
            route('admin.commissions.members.add', $incompleteCommission),
            [
                'name' => 'Ponovljeni',
                'email' => $existingEmail,
                'position' => 'clan',
                'canonical_seat_no' => 3,
            ]
        );
        $duplicateOnSameCommission->assertRedirect();
        $duplicateOnSameCommission->assertSessionHasErrors('error');
        $this->assertSame('Ovaj korisnik je već član komisije.', session('errors')->first('error'));
        $this->assertSame(2, $incompleteCommission->fresh()->activeMembers()->count());
    }

    public function test_membership_access_sessions_and_scores_stay_scoped_to_each_commission(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youthCompetition = $this->makeOmladinskoPublished(2026);
        $zenskoCompetition = $this->makeZenskoPublished(2026);
        $shared = [
            1 => ['name' => 'Predsjednik Dual', 'email' => 'scope.pred.'.uniqid().'@kotor.me'],
            2 => ['name' => 'Clan Dva Dual', 'email' => 'scope.c2.'.uniqid().'@kotor.me'],
            3 => ['name' => 'Clan Tri Dual', 'email' => 'scope.c3.'.uniqid().'@kotor.me'],
        ];
        $extra = [
            4 => ['name' => 'Clan Cetiri Samo Zensko', 'email' => 'scope.c4.'.uniqid().'@kotor.me'],
            5 => ['name' => 'Clan Pet Samo Zensko', 'email' => 'scope.c5.'.uniqid().'@kotor.me'],
        ];

        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($youthCompetition, $shared)
        )->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->womensCommissionPayload($zenskoCompetition, $shared, $extra)
        )->assertSessionHasNoErrors();

        $youthCompetition->refresh();
        $zenskoCompetition->refresh();
        $youthCommissionId = (int) $youthCompetition->commission_id;
        $zenskoCommissionId = (int) $zenskoCompetition->commission_id;
        $this->assertNotSame($youthCommissionId, $zenskoCommissionId);

        $this->markCommissionEmailsVerified(array_merge($shared, $extra));
        $president = User::where('email', $shared[1]['email'])->firstOrFail();
        $youthOnly = $this->makeYouthOnlyCompetition(2027);
        $womenOnly = User::where('email', $extra[4]['email'])->firstOrFail();

        $youthMembership = CommissionMember::activeForCompetition($president->id, $youthCompetition);
        $zenskoMembership = CommissionMember::activeForCompetition($president->id, $zenskoCompetition);
        $this->assertNotNull($youthMembership);
        $this->assertNotNull($zenskoMembership);
        $this->assertNotSame($youthMembership->id, $zenskoMembership->id);
        $this->assertSame($youthCommissionId, (int) $youthMembership->commission_id);
        $this->assertSame($zenskoCommissionId, (int) $zenskoMembership->commission_id);
        $this->assertNull(CommissionMember::activeForCompetition($womenOnly->id, $youthCompetition));
        $this->assertNull(CommissionMember::activeForCompetition($youthOnly['user']->id, $zenskoCompetition));

        $youthApplication = $this->submittedApplication($youthCompetition);
        $zenskoApplication = $this->submittedApplication($zenskoCompetition);

        $this->actingAs($president)->get(route('admin.competitions.show', $youthCompetition))->assertOk();
        $this->actingAs($president)->get(route('admin.competitions.show', $zenskoCompetition))->assertOk();
        $this->actingAs($womenOnly)->get(route('admin.competitions.show', $youthCompetition))->assertForbidden();
        $this->actingAs($womenOnly)->get(route('admin.competitions.show', $zenskoCompetition))->assertOk();
        $this->actingAs($youthOnly['user'])->get(route('admin.competitions.show', $zenskoCompetition))->assertForbidden();
        $this->actingAs($youthOnly['user'])->get(route('admin.competitions.show', $youthOnly['competition']))->assertOk();

        $this->actingAs($womenOnly)->post(
            route('commission-sessions.first.store', $youthCompetition),
            $this->sessionPayload([$youthMembership->id])
        )->assertForbidden();

        $this->actingAs($president)->post(
            route('commission-sessions.first.store', $youthCompetition),
            $this->sessionPayload([
                $youthMembership->id,
                CommissionMember::activeForCompetition(
                    User::where('email', $shared[2]['email'])->firstOrFail()->id,
                    $youthCompetition
                )->id,
                CommissionMember::activeForCompetition($womenOnly->id, $zenskoCompetition)->id,
            ])
        )->assertSessionHasErrors('present_member_ids');

        $this->actingAs($president)->post(
            route('commission-sessions.first.store', $youthCompetition),
            $this->sessionPayload([
                $youthMembership->id,
                CommissionMember::activeForCompetition(
                    User::where('email', $shared[2]['email'])->firstOrFail()->id,
                    $youthCompetition
                )->id,
            ])
        )->assertSessionHasNoErrors();

        $this->actingAs($president)
            ->post(route('commission-sessions.first.confirm', $youthCompetition))
            ->assertSessionHasNoErrors();

        $youthSession = $youthCompetition->fresh()->firstCommissionSession();
        $this->assertNotNull($youthSession);
        $this->assertSame($youthCompetition->id, $youthSession->competition_id);
        $this->assertSame($youthCommissionId, (int) $youthSession->commission_id);
        $this->assertNull($zenskoCompetition->fresh()->firstCommissionSession());
        $this->assertSame(0, CommissionSession::query()->where('competition_id', $zenskoCompetition->id)->count());
        $this->assertSame(
            0,
            $youthSession->attendances()->whereIn(
                'commission_member_id',
                CommissionMember::where('commission_id', $zenskoCommissionId)->pluck('id')
            )->count()
        );

        $this->actingAs($president)->get(route('evaluation.create', $youthApplication))->assertOk();
        $this->actingAs($president)->get(route('evaluation.create', $zenskoApplication))->assertOk();
        $this->actingAs($womenOnly)->get(route('evaluation.create', $youthApplication))->assertForbidden();
        $this->actingAs($womenOnly)->get(route('evaluation.create', $zenskoApplication))->assertOk();
        $this->actingAs($youthOnly['user'])->get(route('evaluation.create', $zenskoApplication))->assertForbidden();

        EvaluationScore::create([
            'application_id' => $youthApplication->id,
            'commission_member_id' => $youthMembership->id,
            'criterion_1' => 5,
        ]);
        EvaluationScore::create([
            'application_id' => $zenskoApplication->id,
            'commission_member_id' => $zenskoMembership->id,
            'criterion_1' => 3,
        ]);

        $this->assertSame(
            [$youthMembership->id],
            EvaluationScore::where('application_id', $youthApplication->id)->pluck('commission_member_id')->all()
        );
        $this->assertSame(
            [$zenskoMembership->id],
            EvaluationScore::where('application_id', $zenskoApplication->id)->pluck('commission_member_id')->all()
        );
        $this->assertNotSame(
            EvaluationScore::where('application_id', $youthApplication->id)->value('commission_member_id'),
            EvaluationScore::where('application_id', $zenskoApplication->id)->value('commission_member_id')
        );
    }

    public function test_womens_membership_created_first_does_not_change_active_for_competition(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youthCompetition = $this->makeOmladinskoPublished(2026);
        $zenskoCompetition = $this->makeZenskoPublished(2026);
        $shared = [
            1 => ['name' => 'Predsjednik Prvo Zensko', 'email' => 'first.pred.'.uniqid().'@kotor.me'],
            2 => ['name' => 'Clan Dva Prvo Zensko', 'email' => 'first.c2.'.uniqid().'@kotor.me'],
            3 => ['name' => 'Clan Tri Prvo Zensko', 'email' => 'first.c3.'.uniqid().'@kotor.me'],
        ];
        $extra = [
            4 => ['name' => 'Clan Cetiri', 'email' => 'first.c4.'.uniqid().'@kotor.me'],
            5 => ['name' => 'Clan Pet', 'email' => 'first.c5.'.uniqid().'@kotor.me'],
        ];

        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->womensCommissionPayload($zenskoCompetition, $shared, $extra)
        )->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($youthCompetition, $shared)
        )->assertSessionHasNoErrors();

        $youthCompetition->refresh();
        $zenskoCompetition->refresh();
        $this->assertNotSame($youthCompetition->commission_id, $zenskoCompetition->commission_id);
        $this->assertTrue($youthCompetition->hasCompleteValidCommission());
        $this->assertTrue($zenskoCompetition->hasCompleteValidCommission());
        $this->assertFalse(
            Competition::commissionIsCompleteAndValidForType($youthCompetition->commission, 'zensko')
        );

        $this->markCommissionEmailsVerified(array_merge($shared, $extra));
        $president = User::where('email', $shared[1]['email'])->firstOrFail();
        $globalFirst = CommissionMember::activeMembershipForUser($president->id);
        $youthMembership = CommissionMember::activeForCompetition($president->id, $youthCompetition);
        $zenskoMembership = CommissionMember::activeForCompetition($president->id, $zenskoCompetition);

        $this->assertNotNull($globalFirst);
        $this->assertNotNull($youthMembership);
        $this->assertNotNull($zenskoMembership);
        $this->assertSame($president->id, $youthMembership->user_id);
        $this->assertSame($president->id, $zenskoMembership->user_id);
        $this->assertNotSame($youthMembership->id, $zenskoMembership->id);
        $this->assertSame((int) $youthCompetition->commission_id, (int) $youthMembership->commission_id);
        $this->assertSame((int) $zenskoCompetition->commission_id, (int) $zenskoMembership->commission_id);
        $this->assertSame((int) $zenskoMembership->id, (int) $globalFirst->id);
        $this->assertNotSame((int) $globalFirst->id, (int) $youthMembership->id);
        $this->assertContains((int) $youthMembership->canonicalSeatNumber(), [1, 2, 3]);
        $this->assertContains((int) $zenskoMembership->canonicalSeatNumber(), [1, 2, 3, 4, 5]);

        $this->actingAs($president)->get(route('admin.competitions.show', $youthCompetition))->assertOk();
        $this->actingAs($president)->get(route('admin.competitions.show', $zenskoCompetition))->assertOk();
        $this->actingAs($president)->get(route('commission-sessions.first.edit', $youthCompetition))->assertOk();
    }

    public function test_deactivation_and_replacement_do_not_cross_commissions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youthCompetition = $this->makeOmladinskoPublished(2026);
        $zenskoCompetition = $this->makeZenskoPublished(2026);
        $shared = [
            1 => ['name' => 'Predsjednik Isolacija', 'email' => 'iso.pred.'.uniqid().'@kotor.me'],
            2 => ['name' => 'Clan Dva Isolacija', 'email' => 'iso.c2.'.uniqid().'@kotor.me'],
            3 => ['name' => 'Clan Tri Isolacija', 'email' => 'iso.c3.'.uniqid().'@kotor.me'],
        ];
        $extra = [
            4 => ['name' => 'Clan Cetiri Isolacija', 'email' => 'iso.c4.'.uniqid().'@kotor.me'],
            5 => ['name' => 'Clan Pet Isolacija', 'email' => 'iso.c5.'.uniqid().'@kotor.me'],
        ];

        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($youthCompetition, $shared)
        )->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->womensCommissionPayload($zenskoCompetition, $shared, $extra)
        )->assertSessionHasNoErrors();

        $youthCompetition->refresh();
        $zenskoCompetition->refresh();
        $userTwo = User::where('email', $shared[2]['email'])->firstOrFail();
        $youthSeatTwo = CommissionMember::activeForCompetition($userTwo->id, $youthCompetition);
        $zenskoSeatTwo = CommissionMember::activeForCompetition($userTwo->id, $zenskoCompetition);
        $this->assertNotNull($youthSeatTwo);
        $this->assertNotNull($zenskoSeatTwo);
        $this->assertNotSame($youthSeatTwo->id, $zenskoSeatTwo->id);

        $youthSeatTwo->update(['status' => 'inactive']);
        $this->assertNull(CommissionMember::activeForCompetition($userTwo->id, $youthCompetition));
        $this->assertNotNull(CommissionMember::activeForCompetition($userTwo->id, $zenskoCompetition));
        $this->assertSame('active', $zenskoSeatTwo->fresh()->status);
        $this->assertSame($zenskoSeatTwo->id, CommissionMember::activeForCompetition($userTwo->id, $zenskoCompetition)->id);

        $youthSeatTwo->update(['status' => 'active']);
        $youthCommission = $youthCompetition->commission;
        $this->actingAs($admin)->post(route('admin.commissions.members.add', $youthCommission), [
            'name' => 'Zamjena Mjesto Dva',
            'email' => 'iso.zamjena.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame('inactive', $youthSeatTwo->fresh()->status);
        $this->assertSame('active', $zenskoSeatTwo->fresh()->status);
        $this->assertNull(CommissionMember::activeForCompetition($userTwo->id, $youthCompetition));
        $replacedWomens = CommissionMember::activeForCompetition($userTwo->id, $zenskoCompetition);
        $this->assertNotNull($replacedWomens);
        $this->assertSame($zenskoSeatTwo->id, $replacedWomens->id);
        $this->assertSame((int) $zenskoCompetition->commission_id, (int) $replacedWomens->commission_id);
    }

    public function test_same_commission_id_cannot_be_shared_but_overlapping_users_are_allowed(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youthCompetition = $this->makeOmladinskoDraft(2026);
        $shared = [
            1 => ['name' => 'Predsjednik Mix', 'email' => 'mix.pred.'.uniqid().'@kotor.me'],
            2 => ['name' => 'Clan Dva Mix', 'email' => 'mix.c2.'.uniqid().'@kotor.me'],
            3 => ['name' => 'Clan Tri Mix', 'email' => 'mix.c3.'.uniqid().'@kotor.me'],
        ];
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->youthCommissionPayload($youthCompetition, $shared)
        )->assertSessionHasNoErrors();
        $youthCompetition->refresh();
        $youthCommissionId = $youthCompetition->commission_id;
        $this->assertNotNull($youthCommissionId);

        $zenskoCompetition = $this->makeZenskoDraft(2026);
        $response = $this->actingAs($admin)->from(route('admin.competitions.edit', $zenskoCompetition))->put(
            route('admin.competitions.update', $zenskoCompetition),
            [
                'title' => $zenskoCompetition->title,
                'description' => $zenskoCompetition->description,
                'type' => 'zensko',
                'up_number' => $zenskoCompetition->upNumber->number,
                'year' => $zenskoCompetition->year,
                'budget' => $zenskoCompetition->budget,
                'start_date' => $zenskoCompetition->start_date?->toDateString(),
                'status' => 'draft',
                'commission_id' => $youthCommissionId,
            ]
        );
        $response->assertRedirect(route('admin.competitions.edit', $zenskoCompetition));
        $response->assertSessionHasErrors('commission_id');
        $this->assertSame(
            CommissionProfileConfig::MIXED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('commission_id')
        );
        $this->assertNull($zenskoCompetition->fresh()->commission_id);

        $extra = [
            4 => ['name' => 'Clan Cetiri Mix', 'email' => 'mix.c4.'.uniqid().'@kotor.me'],
            5 => ['name' => 'Clan Pet Mix', 'email' => 'mix.c5.'.uniqid().'@kotor.me'],
        ];
        $this->actingAs($admin)->post(
            route('admin.commissions.store'),
            $this->womensCommissionPayload($zenskoCompetition, $shared, $extra)
        )->assertSessionHasNoErrors();
        $zenskoCompetition->refresh();
        $this->assertNotNull($zenskoCompetition->commission_id);
        $this->assertNotSame($youthCommissionId, $zenskoCompetition->commission_id);
        foreach ($shared as $row) {
            $user = User::where('email', $row['email'])->firstOrFail();
            $this->assertSame(2, CommissionMember::where('user_id', $user->id)->where('status', 'active')->count());
        }
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $sharedBySeat
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(Competition $competition, array $sharedBySeat): array
    {
        $members = [];
        foreach ($sharedBySeat as $seat => $row) {
            $members[$seat - 1] = [
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => 'password12',
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'canonical_seat_no' => $seat,
            ];
        }

        return [
            'name' => 'Komisija mladih '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $sharedBySeat
     * @param  array<int, array{name: string, email: string}>  $extraBySeat
     * @return array<string, mixed>
     */
    private function womensCommissionPayload(Competition $competition, array $sharedBySeat, array $extraBySeat): array
    {
        $types = [1 => 'opstina', 2 => 'opstina', 3 => 'opstina', 4 => 'udruzenje', 5 => 'zene_mreza'];
        $rows = $sharedBySeat + $extraBySeat;
        $members = [];
        foreach ($rows as $seat => $row) {
            $members[$seat - 1] = [
                'name' => $row['name'],
                'email' => $row['email'],
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => $types[$seat],
                'organization' => $seat === 4 ? 'Udruženje Kotor' : null,
            ];
            if (! User::where('email', $row['email'])->exists()) {
                $members[$seat - 1]['password'] = 'password12';
            }
        }

        return [
            'name' => 'Zenska komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_ids' => [$competition->id],
            'members' => $members,
        ];
    }

    /**
     * @param  list<int>  $presentIds
     * @return array<string, mixed>
     */
    private function sessionPayload(array $presentIds): array
    {
        return [
            'held_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'notes' => 'Prva sjednica',
            'present_member_ids' => $presentIds,
        ];
    }

    private function makeOmladinskoDraft(int $year): Competition
    {
        return $this->makeOmladinsko($year, published: false);
    }

    private function makeOmladinskoPublished(int $year): Competition
    {
        return $this->makeOmladinsko($year, published: true);
    }

    private function makeOmladinsko(int $year, bool $published): Competition
    {
        $competition = Competition::create([
            'title' => 'Omladinsko '.$year.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => $published ? now()->subDays(30)->toDateString() : now()->toDateString(),
            'end_date' => $published ? now()->subDays(5)->toDateString() : now()->addDays(20)->toDateString(),
            'type' => 'omladinsko',
            'status' => $published ? 'published' : 'draft',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => $published ? now()->subDays(30) : null,
            'competition_number' => 'UP-O-'.$year.'-'.uniqid(),
        ]);
        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $competition->competition_number,
        ]);

        return $competition->fresh();
    }

    private function makeZenskoDraft(int $year): Competition
    {
        return $this->makeZensko($year, published: false);
    }

    private function makeZenskoPublished(int $year): Competition
    {
        return $this->makeZensko($year, published: true);
    }

    private function makeZensko(int $year, bool $published): Competition
    {
        $competition = Competition::create([
            'title' => 'Zensko '.$year.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => $published ? now()->subDays(30)->toDateString() : now()->toDateString(),
            'end_date' => $published ? now()->subDays(5)->toDateString() : now()->addDays(20)->toDateString(),
            'type' => 'zensko',
            'status' => $published ? 'published' : 'draft',
            'year' => $year,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => $published ? now()->subDays(30) : null,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => 'UP-Z-'.$year.'-'.uniqid()]);

        return $competition->fresh();
    }

    /**
     * @return array{competition: Competition, user: User}
     */
    private function makeYouthOnlyCompetition(int $year): array
    {
        $competition = $this->makeOmladinskoPublished($year);
        $commission = Commission::create([
            'name' => 'Samo mladi '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $user = $this->userWithRole('komisija');
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);
        $competition->update(['commission_id' => $commission->id]);

        return [
            'competition' => $competition->fresh(),
            'user' => $user,
        ];
    }

    private function submittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan '.$competition->type,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $rows
     */
    private function markCommissionEmailsVerified(array $rows): void
    {
        User::whereIn('email', collect($rows)->pluck('email'))->update([
            'email_verified_at' => now(),
        ]);
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
