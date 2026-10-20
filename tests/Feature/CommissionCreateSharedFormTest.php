<?php

namespace Tests\Feature;

use App\Mail\CommissionAssignedToCompetition;
use App\Mail\CommissionMemberAdded;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Support\CommissionProfileConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommissionCreateSharedFormTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_shared_form_offers_both_supported_profiles(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $html = $this->actingAs($admin)->get(route('admin.commissions.create'))->assertOk()->getContent();

        $this->assertStringContainsString('1. Vrsta konkursa', $html);
        $this->assertStringContainsString('Žensko preduzetništvo', $html);
        $this->assertStringContainsString('Podrška preduzetništvu mladih', $html);
        $this->assertStringContainsString('Podrška mikro, malim i srednjim preduzećima', $html);
        $this->assertLessThan(
            strpos($html, 'Podrška preduzetništvu mladih'),
            strpos($html, 'Žensko preduzetništvo')
        );
        $this->assertLessThan(
            strpos($html, 'Podrška mikro, malim i srednjim preduzećima'),
            strpos($html, 'Podrška preduzetništvu mladih')
        );
        $this->assertStringContainsString('value="'.CompetitionProfileResolver::TYPE_CANONICAL.'"', $html);
        $this->assertStringNotContainsString('value="canonical"', $html);
        $this->assertStringNotContainsString('name="members[3]', $html);
        $this->assertStringNotContainsString('Ženske političke mreže', $html);
        $this->assertStringNotContainsString('youth', strtolower($html));
        $this->assertStringNotContainsString('value="ostalo"', $html);
    }

    public function test_zensko_profile_shows_five_seats_and_existing_categories(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeZenskoDraft('Ženski prikaz '.uniqid());
        $html = $this->actingAs($admin)->get(route('admin.commissions.create', [
            'competition_type' => 'zensko',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('2. Poziv', $html);
        $this->assertStringContainsString('3. Sastav Komisije', $html);
        $this->assertStringContainsString($competition->title, $html);
        $this->assertStringContainsString('Nacrt', $html);
        $this->assertStringContainsString('name="members[0][member_type]"', $html);
        $this->assertStringContainsString('name="members[4]', $html);
        $this->assertStringContainsString('value="opstina"', $html);
        $this->assertStringContainsString('value="udruzenje"', $html);
        $this->assertStringContainsString('value="zene_mreza"', $html);
        $this->assertStringContainsString('Ženske političke mreže', $html);
        $this->assertStringContainsString('Udruženja preduzetnica', $html);
        $this->assertStringContainsString('name="members[0][position]"', $html);
        $this->assertSame(0, substr_count($html, 'name="members[0][canonical_seat_no]"'));
        $this->assertStringNotContainsString('youth', strtolower($html));
        $this->assertStringContainsString('sva 5 redovna mjesta', $html);
        $this->assertStringNotContainsString('bilo kojim brojem', $html);
        $this->assertMatchesRegularExpression('/name="members\[0\]\[name\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="members\[4\]\[email\]"[^>]*required/', $html);
    }

    public function test_omladinsko_profile_shows_only_three_seats_without_womens_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft('Omladinski prikaz '.uniqid());
        $html = $this->actingAs($admin)->get(route('admin.commissions.create', [
            'competition_type' => 'omladinsko',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString($competition->title, $html);
        $this->assertStringContainsString('Poziv br. 1', $html);
        $this->assertStringContainsString('omladinski Poziv', $html);
        $this->assertStringContainsString('Poziv nije obavezan pri kreiranju', $html);
        $this->assertStringContainsString('value="omladinsko"', $html);
        $this->assertStringContainsString('name="members[0][position]"', $html);
        $this->assertStringContainsString('name="members[2][canonical_seat_no]"', $html);
        $this->assertStringNotContainsString('name="members[3]', $html);
        $this->assertStringNotContainsString('name="members[4]', $html);
        $this->assertStringNotContainsString('member_type', $html);
        $this->assertStringNotContainsString('Ženske političke mreže', $html);
        $this->assertStringNotContainsString('Udruženja preduzetnica', $html);
        $this->assertStringNotContainsString('zene_mreza', $html);
        $this->assertStringNotContainsString('youth', strtolower($html));
        $this->assertEquals(3, substr_count($html, 'value="predsjednik"'));
        $this->assertStringContainsString('sva 3 redovna mjesta', $html);
        $this->assertStringNotContainsString('Ostala mjesta možete popuniti kasnije', $html);
        $this->assertMatchesRegularExpression('/name="members\[0\]\[name\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="members\[2\]\[email\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="members\[2\]\[position\]"[^>]*required/', $html);
    }

    public function test_mmsp_profile_shows_three_seats_without_womens_categories(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeMmspDraft('MMSP prikaz '.uniqid());
        $html = $this->actingAs($admin)->get(route('admin.commissions.create', [
            'competition_type' => CompetitionProfileResolver::TYPE_CANONICAL,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString($competition->title, $html);
        $this->assertStringContainsString('Podrška mikro, malim i srednjim preduzećima', $html);
        $this->assertStringContainsString('value="'.CompetitionProfileResolver::TYPE_CANONICAL.'"', $html);
        $this->assertStringNotContainsString('value="canonical"', $html);
        $this->assertStringContainsString('name="members[0][position]"', $html);
        $this->assertStringContainsString('name="members[2][canonical_seat_no]"', $html);
        $this->assertStringNotContainsString('name="members[3]', $html);
        $this->assertStringNotContainsString('name="members[4]', $html);
        $this->assertStringNotContainsString('member_type', $html);
        $this->assertStringNotContainsString('Ženske političke mreže', $html);
        $this->assertStringNotContainsString('Udruženja preduzetnica', $html);
        $this->assertStringNotContainsString('zene_mreza', $html);
        $this->assertEquals(3, substr_count($html, 'value="predsjednik"'));
        $this->assertStringContainsString('sva 3 redovna mjesta', $html);
        $this->assertMatchesRegularExpression('/name="members\[0\]\[name\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="members\[2\]\[email\]"[^>]*required/', $html);
    }

    public function test_create_rejects_incomplete_composition_and_substitute_in_regular_seat(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $incompleteZensko = $this->zenskoCommissionPayload();
        unset($incompleteZensko['members'][4]);
        $this->assertStoreRejected($admin, $incompleteZensko, 'members');
        $this->assertSame(
            CommissionProfileConfig::INCOMPLETE_ZENSKO_CREATE_MESSAGE,
            session('errors')->first('members')
        );

        $incompleteYouth = $this->youthCommissionPayload(null, [
            1 => ['name' => 'Ana Predsjednik', 'email' => 'inc.p.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Marko Clan', 'email' => 'inc.c.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]);
        $this->assertStoreRejected($admin, $incompleteYouth, 'members');
        $this->assertSame(
            CommissionProfileConfig::INCOMPLETE_OMLADINSKO_CREATE_MESSAGE,
            session('errors')->first('members')
        );

        $substituteSeat = $this->zenskoCommissionPayload();
        $substituteSeat['members'][4]['member_type'] = 'zamjenski';
        $this->assertStoreRejected($admin, $substituteSeat, 'members.4.member_type');
        $this->assertSame(0, Commission::query()->count());
    }

    public function test_youth_president_may_be_on_seat_one_two_or_three(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        foreach ([1, 2, 3] as $presidentSeat) {
            $competition = $this->makeOmladinskoDraft('Predsjednik '.$presidentSeat.' '.uniqid());
            $this->actingAs($admin)
                ->post(route('admin.commissions.store'), $this->youthCommissionPayload(
                    $competition,
                    $this->youthSeatsWithPresidentOn($presidentSeat)
                ))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $competition->refresh();
            $president = $competition->commission->activeMembers->firstWhere('position', 'predsjednik');
            $this->assertSame($presidentSeat, (int) $president->canonical_seat_no);
            $this->assertSame(1, $competition->commission->activeMembers()->where('position', 'predsjednik')->count());
        }
    }

    public function test_youth_rejects_missing_or_two_presidents(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $withoutPresident = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->from(route('admin.commissions.create', ['competition_type' => 'omladinsko']))
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($withoutPresident, [
                1 => ['name' => 'Clan Jedan', 'email' => 'np1.'.uniqid().'@kotor.me', 'position' => 'clan'],
                2 => ['name' => 'Clan Dva', 'email' => 'np2.'.uniqid().'@kotor.me', 'position' => 'clan'],
                3 => ['name' => 'Clan Tri', 'email' => 'np3.'.uniqid().'@kotor.me', 'position' => 'clan'],
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('members');
        $this->assertSame(CommissionProfileConfig::YOUTH_PRESIDENT_COUNT_MESSAGE, session('errors')->first('members'));
        $this->assertNull($withoutPresident->fresh()->commission_id);
        $this->assertSame(0, Commission::query()->count());

        $twoPresidents = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($twoPresidents, [
                1 => ['name' => 'Predsjednik A', 'email' => 'tp1.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
                2 => ['name' => 'Predsjednik B', 'email' => 'tp2.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
                3 => ['name' => 'Clan Tri', 'email' => 'tp3.'.uniqid().'@kotor.me', 'position' => 'clan'],
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('members');
        $this->assertNull($twoPresidents->fresh()->commission_id);
        $this->assertSame(0, Commission::query()->count());
    }

    public function test_youth_rejects_seats_four_and_five_from_manual_payload(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $payload = $this->youthCommissionPayload($competition);
        $payload['members'][3] = [
            'name' => 'Nedozvoljeno Cetvrto',
            'email' => 'seat4.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'canonical_seat_no' => 4,
        ];
        $payload['members'][4] = [
            'name' => 'Nedozvoljeno Peto',
            'email' => 'seat5.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'canonical_seat_no' => 5,
        ];

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('members.3.canonical_seat_no');
        $this->assertNull($competition->fresh()->commission_id);
        $this->assertSame(0, Commission::query()->count());
        $this->assertSame(0, CommissionMember::query()->count());
    }

    public function test_youth_rejects_member_type_bypass(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $payload = $this->youthCommissionPayload($competition);
        $payload['members'][0]['member_type'] = 'opstina';

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('members.0.member_type');
        $this->assertSame(
            CommissionProfileConfig::YOUTH_MEMBER_TYPE_REJECTED_MESSAGE,
            session('errors')->first('members.0.member_type')
        );
        $this->assertNull($competition->fresh()->commission_id);
        $this->assertSame(0, CommissionMember::query()->count());
    }

    public function test_youth_rejects_duplicate_user_and_duplicate_seat(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $sharedEmail = 'dup.'.uniqid().'@kotor.me';
        $duplicateUserCompetition = $this->makeOmladinskoDraft();
        $duplicateUser = $this->youthCommissionPayload($duplicateUserCompetition);
        $duplicateUser['members'][1]['email'] = $sharedEmail;
        $duplicateUser['members'][0]['email'] = $sharedEmail;

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $duplicateUser)
            ->assertRedirect()
            ->assertSessionHasErrors('members.1.email');
        $this->assertSame(0, Commission::query()->count());

        $duplicateSeatCompetition = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), [
                'name' => 'Duplikat mjesto '.uniqid(),
                'year' => 2026,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'competition_type' => 'omladinsko',
                'competition_ids' => [$duplicateSeatCompetition->id],
                'members' => [
                    0 => $this->youthMember(1, 'A', 'predsjednik', 'ds1.'.uniqid().'@kotor.me'),
                    1 => $this->youthMember(1, 'B', 'clan', 'ds2.'.uniqid().'@kotor.me'),
                    2 => $this->youthMember(3, 'C', 'clan', 'ds3.'.uniqid().'@kotor.me'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('members.1.canonical_seat_no');
        $this->assertNull($duplicateSeatCompetition->fresh()->commission_id);
    }

    public function test_payload_type_cannot_change_call_profile(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $youthPayload = $this->youthCommissionPayload($zensko);
        $youthPayload['competition_type'] = 'omladinsko';

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $youthPayload)
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->assertNull($zensko->fresh()->commission_id);
        $this->assertSame(0, Commission::query()->count());

        $youth = $this->makeOmladinskoDraft();
        $womensPayload = $this->zenskoCommissionPayload($youth);
        $womensPayload['competition_type'] = 'zensko';

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $womensPayload)
            ->assertRedirect()
            ->assertSessionHasErrors();
        $this->assertNull($youth->fresh()->commission_id);
        $this->assertSame(0, Commission::query()->count());
    }

    public function test_mmsp_commission_is_created_with_three_members_and_one_president(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeMmspDraft('MMSP kreiranje '.uniqid());
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->mmspCommissionPayload($competition))
            ->assertSessionHasNoErrors();

        $commission = Commission::query()->latest('id')->first();
        $this->assertSame(CompetitionProfileResolver::TYPE_CANONICAL, $commission->profile_type);
        $this->assertSame(3, $commission->activeMembers()->count());
        $this->assertSame(1, $commission->activeMembers()->where('position', 'predsjednik')->count());
        $this->assertEqualsCanonicalizing([1, 2, 3], $commission->activeMembers->pluck('canonical_seat_no')->all());
        $this->assertTrue($commission->activeMembers->every(fn (CommissionMember $member) => $member->member_type === null));
        $this->assertSame($commission->id, $competition->fresh()->commission_id);

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.show', $commission))
            ->assertOk()
            ->getContent();
        foreach ($commission->activeMembers()->orderBy('canonical_seat_no')->get() as $member) {
            $this->assertStringContainsString($member->name, $html);
        }
        $this->assertStringNotContainsString('Nije dodijeljen', $html);
        $this->assertStringNotContainsString('Možete dodati još <strong>1 zamjenskog člana</strong>', $html);

        Mail::assertSent(CommissionMemberAdded::class, 3);
    }

    public function test_mmsp_shared_commission_can_be_completed_in_canonical_governance(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeMmspDraft('MMSP governance '.uniqid());

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->mmspCommissionPayload($competition))
            ->assertSessionHasNoErrors();

        $commission = Commission::query()->latest('id')->firstOrFail();
        $today = now()->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.competitions.canonical-commission.establish', $competition), [
                'name' => $commission->name,
                'appointment_reference' => 'Akt o obrazovanju TEST',
                'appointed_on' => $today,
                'mandate_starts_on' => $today,
            ])
            ->assertSessionHasNoErrors();

        $commission->refresh();
        $this->assertNotNull($commission->appointment_reference);
        $this->assertNotNull($commission->mandate_starts_at);
        $this->assertNotNull($commission->mandate_ends_at);

        $members = $commission->activeMembers()->with('user')->orderBy('canonical_seat_no')->get();
        $nonPresidentRoles = [
            CanonicalCommissionGovernance::ROLE_SEKRETAR,
            CanonicalCommissionGovernance::ROLE_SPOLJASNJI,
        ];
        $nonPresidentIndex = 0;

        foreach ($members as $member) {
            $role = $member->position === 'predsjednik'
                ? CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
                : $nonPresidentRoles[$nonPresidentIndex++];

            $payload = [
                'email' => $member->user->email,
                'seat_no' => $member->canonical_seat_no,
                'role' => $role,
                'appointment_reference' => 'Akt člana '.$member->canonical_seat_no,
                'appointed_on' => $today,
            ];
            if ($role === CanonicalCommissionGovernance::ROLE_SPOLJASNJI) {
                $payload['external_category'] = 'biznis';
            }

            $this->actingAs($admin)
                ->post(route('admin.competitions.canonical-commission.appoint', $competition), $payload)
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $commission->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            CanonicalCommissionGovernance::ROLES,
            $commission->activeMembers()->pluck('canonical_seat')->all()
        );

        $html = $this->actingAs($admin)
            ->get(route('admin.competitions.canonical-commission.show', $competition))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Komisija je strukturno potpuna.', $html);
        $this->assertStringNotContainsString('Nepoznata uloga', $html);
    }

    public function test_mmsp_commission_can_be_created_without_a_call(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->mmspCommissionPayload())
            ->assertSessionHasNoErrors();

        $commission = Commission::query()->latest('id')->first();
        $this->assertSame(CompetitionProfileResolver::TYPE_CANONICAL, $commission->profile_type);
        $this->assertSame(3, $commission->activeMembers()->count());
        $this->assertSame(0, $commission->competitions()->count());
        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertNotSent(CommissionAssignedToCompetition::class);
    }

    public function test_mmsp_rejects_seats_four_and_five_and_wrong_president_count(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeMmspDraft();
        $payload = $this->mmspCommissionPayload($competition);
        $payload['members'][3] = [
            'name' => 'Nedozvoljeno Cetvrto',
            'email' => 'mmsp4.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'canonical_seat_no' => 4,
        ];
        $payload['members'][4] = [
            'name' => 'Nedozvoljeno Peto',
            'email' => 'mmsp5.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'canonical_seat_no' => 5,
        ];
        $this->assertStoreRejected($admin, $payload, 'members.3.canonical_seat_no', $competition);
        $this->assertSame(
            CommissionProfileConfig::INVALID_CANONICAL_SEAT_MESSAGE,
            session('errors')->first('members.3.canonical_seat_no')
        );

        $withoutPresident = $this->makeMmspDraft();
        $this->assertStoreRejected($admin, $this->mmspCommissionPayload($withoutPresident, [
            1 => ['name' => 'Clan Jedan', 'email' => 'mnp1.'.uniqid().'@kotor.me', 'position' => 'clan'],
            2 => ['name' => 'Clan Dva', 'email' => 'mnp2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Tri', 'email' => 'mnp3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]), 'members', $withoutPresident);
        $this->assertSame(
            CommissionProfileConfig::CANONICAL_PRESIDENT_COUNT_MESSAGE,
            session('errors')->first('members')
        );

        $twoPresidents = $this->makeMmspDraft();
        $this->assertStoreRejected($admin, $this->mmspCommissionPayload($twoPresidents, [
            1 => ['name' => 'Predsjednik A', 'email' => 'mtp1.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Predsjednik B', 'email' => 'mtp2.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            3 => ['name' => 'Clan Tri', 'email' => 'mtp3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]), 'members', $twoPresidents);
    }

    public function test_mmsp_rejects_member_type_and_non_mmsp_calls(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $mmsp = $this->makeMmspDraft();
        $withMemberType = $this->mmspCommissionPayload($mmsp);
        $withMemberType['members'][0]['member_type'] = 'opstina';
        $this->assertStoreRejected($admin, $withMemberType, 'members.0.member_type', $mmsp);
        $this->assertSame(
            CommissionProfileConfig::CANONICAL_MEMBER_TYPE_REJECTED_MESSAGE,
            session('errors')->first('members.0.member_type')
        );

        $youth = $this->makeOmladinskoDraft();
        $zensko = $this->makeZenskoDraft();
        $mmspToYouth = $this->mmspCommissionPayload($youth);
        $this->assertStoreRejected($admin, $mmspToYouth, 'competition_ids', $youth);
        $mmspToWomen = $this->mmspCommissionPayload($zensko);
        $this->assertStoreRejected($admin, $mmspToWomen, 'competition_ids', $zensko);

        $youthToMmsp = $this->youthCommissionPayload($mmsp);
        $this->assertStoreRejected($admin, $youthToMmsp, 'competition_ids', $mmsp);
        $this->assertSame(
            CommissionProfileConfig::CANONICAL_COMMISSION_REQUIRED_MESSAGE,
            session('errors')->first('competition_ids')
        );
        $this->assertSame(0, Commission::query()->count());
    }

    public function test_store_requires_a_supported_profile_and_matching_optional_calls(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $zensko = $this->makeZenskoDraft();
        $youth = $this->makeOmladinskoDraft();
        $ostalo = $this->makeDraftCompetition('ostalo', 'Ostalo forma '.uniqid());

        $withoutType = $this->zenskoCommissionPayload($zensko);
        unset($withoutType['competition_type']);
        $this->assertStoreRejected($admin, $withoutType, 'competition_type', $zensko);
        $this->assertSame(
            CommissionProfileConfig::PROFILE_REQUIRED_MESSAGE,
            session('errors')->first('competition_type')
        );

        $unknownType = $this->zenskoCommissionPayload($zensko);
        $unknownType['competition_type'] = 'nepoznat';
        $this->assertStoreRejected($admin, $unknownType, 'competition_type', $zensko);

        $ostaloType = $this->zenskoCommissionPayload($zensko);
        $ostaloType['competition_type'] = 'ostalo';
        $this->assertStoreRejected($admin, $ostaloType, 'competition_type', $zensko);
        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_type')
        );

        $ostaloAsZensko = $this->zenskoCommissionPayload($ostalo);
        $this->assertStoreRejected($admin, $ostaloAsZensko, 'competition_ids', $ostalo);
        $this->assertSame(
            CommissionProfileConfig::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $mixedKn = $this->youthCommissionPayload($youth);
        $mixedKn['competition_ids'] = [$zensko->id, $youth->id];
        $this->assertStoreRejected($admin, $mixedKn, 'competition_ids', $zensko, $youth);
        $this->assertSame(
            CommissionProfileConfig::MIXED_PROFILE_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $mixedOstalo = $this->zenskoCommissionPayload($zensko);
        $mixedOstalo['competition_ids'] = [$zensko->id, $ostalo->id];
        $this->assertStoreRejected($admin, $mixedOstalo, 'competition_ids', $zensko, $ostalo);
        $this->assertSame(
            CommissionProfileConfig::MIXED_COMPETITION_TYPES_MESSAGE,
            session('errors')->first('competition_ids')
        );

        $missingOnly = $this->youthCommissionPayload($youth);
        $missingOnly['competition_ids'] = [999999];
        $this->assertStoreRejected($admin, $missingOnly, 'competition_ids', $youth);

        $validAndMissing = $this->youthCommissionPayload($youth);
        $validAndMissing['competition_ids'] = [$youth->id, 999999];
        $this->assertStoreRejected($admin, $validAndMissing, 'competition_ids', $youth);
        $this->assertSame(
            CommissionProfileConfig::UNKNOWN_COMPETITION_ASSIGNMENT_MESSAGE,
            session('errors')->first('competition_ids')
        );
    }

    public function test_womens_commission_can_be_created_without_a_call(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $payload = $this->zenskoCommissionPayload();
        $this->actingAs($admin)->post(route('admin.commissions.store'), $payload)->assertSessionHasNoErrors();

        $commission = Commission::query()->latest('id')->first();
        $this->assertSame('zensko', $commission->profile_type);
        $this->assertSame(5, $commission->activeMembers()->count());
        $this->assertSame(0, $commission->competitions()->count());
        Mail::assertSent(CommissionMemberAdded::class, 5);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za podršku ženskom preduzetništvu')
                && str_contains($html, 'ženskom preduzetništvu');
        });
        Mail::assertNotSent(CommissionAssignedToCompetition::class);
    }

    public function test_youth_commission_can_be_created_without_a_call(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');
        $payload = $this->youthCommissionPayload();
        $this->actingAs($admin)->post(route('admin.commissions.store'), $payload)->assertSessionHasNoErrors();

        $commission = Commission::query()->latest('id')->first();
        $this->assertSame('omladinsko', $commission->profile_type);
        $this->assertSame(3, $commission->activeMembers()->count());
        $this->assertSame(0, $commission->competitions()->count());
        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za podršku preduzetništvu mladih')
                && str_contains($html, 'preduzetništvu mladih')
                && ! str_contains($html, 'ženskom preduzetništvu');
        });
        Mail::assertNotSent(CommissionAssignedToCompetition::class);
    }

    public function test_already_assigned_call_is_not_overwritten(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $first = $this->youthCommissionPayload($competition);
        $this->actingAs($admin)->post(route('admin.commissions.store'), $first)->assertSessionHasNoErrors();
        $originalId = $competition->fresh()->commission_id;
        $this->assertNotNull($originalId);

        $secondCompetitionIgnored = $this->makeOmladinskoDraft();
        $second = $this->youthCommissionPayload($secondCompetitionIgnored);
        $second['competition_ids'] = [$competition->id];
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $second)
            ->assertRedirect()
            ->assertSessionHasErrors('competition_ids');
        $this->assertSame(
            CommissionProfileConfig::COMPETITION_ALREADY_ASSIGNED_MESSAGE,
            session('errors')->first('competition_ids')
        );
        $this->assertSame($originalId, $competition->fresh()->commission_id);
        $this->assertSame(1, Commission::query()->count());
        $this->assertSame(3, CommissionMember::query()->where('commission_id', $originalId)->count());
    }

    public function test_konkurs_admin_can_create_while_commission_member_and_applicant_cannot(): void
    {
        $konkursAdmin = $this->userWithRole('konkurs_admin');
        $ordinaryAdmin = $this->userWithRole('admin');
        $member = $this->userWithRole('komisija');
        $applicant = $this->userWithRole('korisnik');

        $this->actingAs($konkursAdmin)->get(route('admin.commissions.create'))->assertOk();
        $this->actingAs($ordinaryAdmin)->get(route('admin.commissions.create'))->assertOk();
        $this->actingAs($member)->get(route('admin.commissions.create'))->assertRedirect(route('dashboard'));
        $this->actingAs($applicant)->get(route('admin.commissions.create'))->assertForbidden();

        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($member)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($applicant)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($this->makeOmladinskoDraft()))
            ->assertForbidden();
        $this->assertSame(0, Commission::query()->count());

        $this->actingAs($konkursAdmin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($this->makeOmladinskoDraft()))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Commission::query()->count());
    }

    public function test_profile_aware_appointment_mails_use_canonical_call_type(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('konkurs_admin');

        $youth = $this->makeOmladinskoDraft('Mail mladi '.uniqid());
        $youthPayload = $this->youthCommissionPayload($youth);
        $this->actingAs($admin)->post(route('admin.commissions.store'), $youthPayload)->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertSent(CommissionAssignedToCompetition::class, 3);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za podršku preduzetništvu mladih')
                && str_contains($html, 'preduzetništvu mladih')
                && ! str_contains(strtolower($html), 'youth')
                && ! str_contains($html, 'ženskom preduzetništvu');
        });
        Mail::assertSent(CommissionAssignedToCompetition::class, function (CommissionAssignedToCompetition $mail) use ($youth) {
            $html = $mail->render();

            return str_contains($html, 'omladinski Poziv')
                && str_contains($html, $youth->title)
                && ! str_contains(strtolower($html), 'youth');
        });

        Mail::fake();
        $zensko = $this->makeZenskoDraft('Mail zensko '.uniqid());
        $zenskoPayload = $this->zenskoCommissionPayload($zensko);
        $this->actingAs($admin)->post(route('admin.commissions.store'), $zenskoPayload)->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 5);
        Mail::assertSent(CommissionAssignedToCompetition::class, 5);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za podršku ženskom preduzetništvu')
                && str_contains($html, 'ženskom preduzetništvu')
                && ! str_contains($html, 'preduzetništvu mladih');
        });
    }

    public function test_existing_womens_commission_and_substitute_remain_unchanged(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($competition))
            ->assertSessionHasNoErrors();

        $competition->refresh();
        $this->assertTrue($competition->hasCompleteValidCommission());
        $this->assertSame(5, $competition->commission->activeMembers()->count());
        $this->assertEqualsCanonicalizing(
            ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'],
            $competition->commission->activeMembers->pluck('member_type')->all()
        );

        $replaced = $competition->commission->activeMembers()->where('position', 'clan')->first();
        $this->actingAs($admin)->post(route('admin.commissions.members.add', $competition->commission), [
            'name' => 'Zamjenski član',
            'email' => 'zamjena.'.uniqid().'@kotor.me',
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame('inactive', $replaced->fresh()->status);
        $substitute = CommissionMember::query()
            ->where('commission_id', $competition->commission_id)
            ->where('is_substitute', true)
            ->first();
        $this->assertNotNull($substitute);
        $this->assertSame(2, (int) $substitute->replaces_member_number);
        $this->assertSame('active', $substitute->status);
    }

    public function test_dual_membership_isolation_still_allows_same_users_on_separate_commissions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $shared = [
            1 => ['name' => 'Predsjednik Dual', 'email' => 'form.dual.1.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Dual 2', 'email' => 'form.dual.2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Dual 3', 'email' => 'form.dual.3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ];
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth, $shared))
            ->assertSessionHasNoErrors();

        $zensko = $this->makeZenskoDraft();
        $womens = $this->zenskoCommissionPayload($zensko);
        foreach ([0, 1, 2] as $index) {
            $womens['members'][$index]['email'] = $shared[$index + 1]['email'];
            $womens['members'][$index]['name'] = $shared[$index + 1]['name'];
        }
        $this->actingAs($admin)->post(route('admin.commissions.store'), $womens)->assertSessionHasNoErrors();

        $this->assertNotSame($youth->fresh()->commission_id, $zensko->fresh()->commission_id);
        foreach ($shared as $row) {
            $user = User::where('email', $row['email'])->firstOrFail();
            $this->assertSame(2, CommissionMember::where('user_id', $user->id)->where('status', 'active')->count());
        }
    }

    public function test_validation_errors_preserve_old_input_on_shared_form(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft('Greska forma '.uniqid());
        $payload = $this->youthCommissionPayload($competition, [
            1 => ['name' => 'Clan Jedan', 'email' => 'err1.'.uniqid().'@kotor.me', 'position' => 'clan'],
            2 => ['name' => 'Clan Dva', 'email' => 'err2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Tri', 'email' => 'err3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]);
        $payload['name'] = 'Komisija sa greškom';

        $response = $this->actingAs($admin)
            ->from(route('admin.commissions.create', ['competition_type' => 'omladinsko']))
            ->post(route('admin.commissions.store'), $payload);

        $response->assertRedirect(route('admin.commissions.create', ['competition_type' => 'omladinsko']));
        $response->assertSessionHasErrors('members');

        $html = $this->actingAs($admin)
            ->get(route('admin.commissions.create', ['competition_type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Komisija sa greškom', $html);
        $this->assertStringContainsString($payload['members'][0]['name'], $html);
        $this->assertStringContainsString(CommissionProfileConfig::YOUTH_PRESIDENT_COUNT_MESSAGE, $html);
        $this->assertStringContainsString($competition->title, $html);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertStoreRejected(User $admin, array $payload, string $errorKey, Competition ...$competitions): void
    {
        Mail::fake();
        $commissionCount = Commission::query()->count();
        $memberCount = CommissionMember::query()->count();
        $assigned = [];
        foreach ($competitions as $competition) {
            $assigned[$competition->id] = $competition->fresh()->commission_id;
        }

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors($errorKey);

        $this->assertSame($commissionCount, Commission::query()->count());
        $this->assertSame($memberCount, CommissionMember::query()->count());
        foreach ($competitions as $competition) {
            $this->assertSame($assigned[$competition->id], $competition->fresh()->commission_id);
        }
        Mail::assertNothingSent();
    }

    /**
     * @param  array<int, array<string, string>>  $overridesBySeat
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(?Competition $competition = null, array $overridesBySeat = []): array
    {
        $defaults = [
            1 => ['name' => 'Predsjednik Forma', 'email' => 'form.p.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Forma 2', 'email' => 'form.c2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Forma 3', 'email' => 'form.c3.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ];
        $seats = $overridesBySeat === [] ? $defaults : $overridesBySeat;
        $members = [];
        foreach ($seats as $seat => $row) {
            $members[$seat - 1] = $this->youthMember($seat, $row['name'], $row['position'], $row['email']);
        }

        return [
            'name' => 'Komisija mladih forma '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_type' => 'omladinsko',
            'competition_ids' => $competition ? [$competition->id] : [],
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

    /**
     * @return array<string, mixed>
     */
    private function zenskoCommissionPayload(?Competition $competition = null): array
    {
        $rows = [
            ['Predsjednik Z Forma', 'predsjednik', 'opstina', null],
            ['Clan 2 Z Forma', 'clan', 'opstina', null],
            ['Clan 3 Z Forma', 'clan', 'opstina', null],
            ['Clan 4 Z Forma', 'clan', 'udruzenje', 'Udruženje'],
            ['Clan 5 Z Forma', 'clan', 'zene_mreza', null],
        ];
        $members = [];
        foreach ($rows as $index => $row) {
            $members[$index] = [
                'name' => $row[0],
                'email' => 'form.z.'.$index.'.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => $row[1],
                'member_type' => $row[2],
            ];
            if ($row[3] !== null) {
                $members[$index]['organization'] = $row[3];
            }
        }

        return [
            'name' => 'Zenska komisija forma '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'competition_type' => 'zensko',
            'competition_ids' => $competition ? [$competition->id] : [],
            'members' => $members,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $overridesBySeat
     * @return array<string, mixed>
     */
    private function mmspCommissionPayload(?Competition $competition = null, array $overridesBySeat = []): array
    {
        $payload = $this->youthCommissionPayload($competition, $overridesBySeat);
        $payload['name'] = 'Komisija MMSP forma '.uniqid();
        $payload['competition_type'] = CompetitionProfileResolver::TYPE_CANONICAL;

        return $payload;
    }

    private function makeMmspDraft(?string $title = null): Competition
    {
        return $this->makeDraftCompetition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            $title ?? 'MMSP nacrt '.uniqid()
        );
    }

    private function makeOmladinskoDraft(?string $title = null): Competition
    {
        return $this->makeDraftCompetition('omladinsko', $title ?? 'Omladinski nacrt '.uniqid());
    }

    private function makeZenskoDraft(?string $title = null): Competition
    {
        return $this->makeDraftCompetition('zensko', $title ?? 'Zenski nacrt '.uniqid());
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
