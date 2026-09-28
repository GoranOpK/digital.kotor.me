<?php

namespace Tests\Feature;

use App\Mail\CommissionAppointmentMailCopy;
use App\Mail\CommissionAssignedToCompetition;
use App\Mail\CommissionMemberAdded;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommissionAppointmentMailProfileTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2026;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_member_added_youth_before_assignment_link(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $payload = $this->youthCommissionPayload($competition);
        $catalogBefore = CompetitionProgramCatalog::definitions();

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($competition->fresh()->published_at);
        $this->assertSame($catalogBefore, CompetitionProgramCatalog::definitions());
        Mail::assertSent(CommissionMemberAdded::class, 3);

        foreach (collect($payload['members'])->pluck('email') as $email) {
            Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($email, $payload) {
                $html = $mail->render();

                return $mail->hasTo($email)
                    && $mail->hasSubject('Imenovanje u Komisiju za podršku preduzetništvu mladih')
                    && str_contains($html, 'Obavještavamo Vas da ste imenovani za člana')
                    && str_contains($html, 'podršku preduzetništvu mladih')
                    && str_contains($html, url('/'))
                    && $this->youthBodyIsClean($html)
                    && ! str_contains($html, $payload['members'][0]['password']);
            });
        }
    }

    public function test_member_added_youth_after_existing_assignment(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft();
        $payload = $this->youthCommissionPayload($competition, [
            1 => ['name' => 'Predsjednik Dva', 'email' => 'pred.dva.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Dva', 'email' => 'clan.dva.'.uniqid().'@kotor.me', 'position' => 'clan'],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        $commission = $competition->fresh()->commission;
        $this->assertSame($commission->id, $competition->fresh()->commission_id);
        $newEmail = 'clan.tri.'.uniqid().'@kotor.me';

        $this->actingAs($admin)->post(route('admin.commissions.members.add', $commission), [
            'name' => 'Clan Tri Naknadno',
            'email' => $newEmail,
            'password' => 'password12',
            'position' => 'clan',
            'canonical_seat_no' => 3,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($newEmail) {
            $html = $mail->render();

            return $mail->hasTo($newEmail)
                && $mail->hasSubject('Imenovanje u Komisiju za podršku preduzetništvu mladih')
                && str_contains($html, 'podršku preduzetništvu mladih')
                && $this->youthBodyIsClean($html);
        });
    }

    public function test_member_added_zensko_keeps_existing_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeZenskoDraft();
        $payload = $this->zenskoCommissionPayload($competition);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 5);
        foreach (collect($payload['members'])->pluck('email') as $email) {
            Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($email) {
                $html = $mail->render();

                return $mail->hasTo($email)
                    && $mail->hasSubject('Imenovanje u Komisiju za podršku ženskom preduzetništvu')
                    && str_contains($html, 'Komisije za raspodjelu sredstava za podršku ženskom preduzetništvu')
                    && str_contains($html, 'sistem za podršku ženskom preduzetništvu Opštine Kotor')
                    && str_contains($html, 'Sistem za podršku ženskom preduzetništvu')
                    && str_contains($html, url('/'))
                    && $this->zenskoBodyIsUnchanged($html);
            });
        }
    }

    public function test_member_added_ostalo_is_neutral(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeDraftCompetition('ostalo', 'Ostalo MemberAdded '.uniqid());
        $payload = $this->zenskoCommissionPayload($competition);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 5);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za sprovođenje konkursa')
                && str_contains($html, 'Obavještavamo Vas da ste imenovani u Komisiju za sprovođenje konkursa.')
                && str_contains($html, url('/'))
                && $this->neutralBodyIsClean($html);
        });
    }

    public function test_member_added_without_competition_is_neutral(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $payload = $this->zenskoCommissionPayload(null);
        $email = $payload['members'][0]['email'];

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 5);
        Mail::assertNotSent(CommissionAssignedToCompetition::class);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($email) {
            $html = $mail->render();

            return $mail->hasTo($email)
                && $mail->hasSubject('Imenovanje u Komisiju za sprovođenje konkursa')
                && str_contains($html, 'Obavještavamo Vas da ste imenovani u Komisiju za sprovođenje konkursa.')
                && $this->neutralBodyIsClean($html);
        });
    }

    public function test_member_added_mixed_types_is_neutral(): void
    {
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['zensko', 'omladinsko'])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['omladinsko', 'ostalo'])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['ostalo'])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes([])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes([null, ''])->isNeutral());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['zensko'])->isZensko());
        $this->assertTrue(CommissionAppointmentMailCopy::fromCompetitionTypes(['omladinsko', 'omladinsko'])->isOmladinsko());

        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $ostalo = $this->makeDraftCompetition('ostalo', 'Ostalo mix '.uniqid());
        $payload = $this->youthCommissionPayload($youth);
        $payload['competition_ids'] = [$youth->id, $ostalo->id];

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za sprovođenje konkursa')
                && str_contains($html, 'Obavještavamo Vas da ste imenovani u Komisiju za sprovođenje konkursa.')
                && $this->neutralBodyIsClean($html);
        });
    }

    public function test_legacy_null_member_type_and_youth_seat_without_youth_call_is_neutral(): void
    {
        $user = $this->userWithRole('komisija');
        $commission = Commission::create([
            'name' => 'Legacy bez Poziva '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);
        $member = CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => 'Legacy Clan',
            'position' => 'predsjednik',
            'member_type' => null,
            'canonical_seat_no' => 1,
            'status' => 'active',
        ]);

        $copy = CommissionAppointmentMailCopy::fromCompetitionTypes(
            $commission->competitions()->pluck('type')
        );
        $this->assertTrue($copy->isNeutral());

        Mail::to($user->email)->send(new CommissionMemberAdded($member, $copy));

        Mail::assertSent(CommissionMemberAdded::class, 1);
        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($user) {
            $html = $mail->render();

            return $mail->hasTo($user->email)
                && $mail->hasSubject('Imenovanje u Komisiju za sprovođenje konkursa')
                && str_contains($html, 'Obavještavamo Vas da ste imenovani u Komisiju za sprovođenje konkursa.')
                && $this->neutralBodyIsClean($html)
                && ! str_contains($html, 'preduzetništvu mladih');
        });
    }

    public function test_assigned_youth_uses_competition_type(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeOmladinskoDraft('Omladinski Assigned '.uniqid());
        $payload = $this->youthCommissionPayload($competition);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionAssignedToCompetition::class, 3);
        foreach (collect($payload['members'])->pluck('email') as $email) {
            Mail::assertSent(CommissionAssignedToCompetition::class, function (CommissionAssignedToCompetition $mail) use ($email, $competition) {
                $html = $mail->render();

                return $mail->hasTo($email)
                    && $mail->hasSubject('Imenovanje u Komisiju za podršku preduzetništvu mladih')
                    && str_contains($html, 'podršku preduzetništvu mladih')
                    && str_contains($html, 'omladinski Poziv')
                    && str_contains($html, $competition->title)
                    && str_contains($html, url('/'))
                    && $this->youthBodyIsClean($html);
            });
        }
    }

    public function test_assigned_zensko_keeps_existing_copy(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeZenskoDraft('Zenski Assigned '.uniqid());
        $payload = $this->zenskoCommissionPayload($competition);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionAssignedToCompetition::class, 5);
        foreach (collect($payload['members'])->pluck('email') as $email) {
            Mail::assertSent(CommissionAssignedToCompetition::class, function (CommissionAssignedToCompetition $mail) use ($email, $competition) {
                $html = $mail->render();

                return $mail->hasTo($email)
                    && $mail->hasSubject('Imenovanje u Komisiju za podršku ženskom preduzetništvu')
                    && str_contains($html, 'Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu')
                    && str_contains($html, 'za sljedeći konkurs:')
                    && str_contains($html, $competition->title)
                    && str_contains($html, url('/'))
                    && $this->zenskoBodyIsUnchanged($html);
            });
        }
    }

    public function test_assigned_ostalo_is_neutral(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $competition = $this->makeDraftCompetition('ostalo', 'Ostalo Assigned '.uniqid());
        $payload = $this->zenskoCommissionPayload($competition);

        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionAssignedToCompetition::class, 5);
        Mail::assertSent(CommissionAssignedToCompetition::class, function (CommissionAssignedToCompetition $mail) use ($competition) {
            $html = $mail->render();

            return $mail->hasSubject('Imenovanje u Komisiju za sprovođenje konkursa')
                && str_contains($html, 'za sljedeći Poziv:')
                && str_contains($html, $competition->title)
                && str_contains($html, url('/'))
                && $this->neutralBodyIsClean($html)
                && ! str_contains($html, (string) $competition->type);
        });
    }

    public function test_substitute_keeps_profile_of_assigned_competition(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $youth = $this->makeOmladinskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->youthCommissionPayload($youth))
            ->assertSessionHasNoErrors();

        $commission = $youth->fresh()->commission;
        $substituteEmail = 'zamjena.mladi.'.uniqid().'@kotor.me';
        $this->actingAs($admin)->post(route('admin.commissions.members.add', $commission), [
            'name' => 'Zamjena Mladi',
            'email' => $substituteEmail,
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($substituteEmail) {
            $html = $mail->render();

            return $mail->hasTo($substituteEmail)
                && $mail->hasSubject('Imenovanje za zamjenskog člana Komisije za podršku preduzetništvu mladih')
                && str_contains($html, 'podršku preduzetništvu mladih')
                && $this->youthBodyIsClean($html);
        });

        $zensko = $this->makeZenskoDraft();
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $this->zenskoCommissionPayload($zensko))
            ->assertSessionHasNoErrors();
        $zenskoCommission = $zensko->fresh()->commission;
        $zenskoSubEmail = 'zamjena.zensko.'.uniqid().'@kotor.me';
        $this->actingAs($admin)->post(route('admin.commissions.members.add', $zenskoCommission), [
            'name' => 'Zamjena Zensko',
            'email' => $zenskoSubEmail,
            'password' => 'password12',
            'position' => 'clan',
            'member_type' => 'zamjenski',
            'replaces_member_number' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, function (CommissionMemberAdded $mail) use ($zenskoSubEmail) {
            $html = $mail->render();

            return $mail->hasTo($zenskoSubEmail)
                && $mail->hasSubject('Imenovanje za zamjenskog člana Komisije za podršku ženskom preduzetništvu')
                && $this->zenskoBodyIsUnchanged($html);
        });
    }

    public function test_mail_does_not_change_authorization_or_send_duplicate_appointment_mail(): void
    {
        $applicant = $this->userWithRole('korisnik');
        $competition = $this->makeOmladinskoDraft();
        $payload = $this->youthCommissionPayload($competition);

        $this->actingAs($applicant)
            ->post(route('admin.commissions.store'), $payload)
            ->assertForbidden();

        $this->assertNull($competition->fresh()->commission_id);
        Mail::assertNotSent(CommissionMemberAdded::class);
        Mail::assertNotSent(CommissionAssignedToCompetition::class);

        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)
            ->post(route('admin.commissions.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(CommissionMemberAdded::class, 3);
        Mail::assertSent(CommissionAssignedToCompetition::class, 3);
        $this->assertSame(3, $competition->fresh()->commission->activeMembers()->count());
        $this->assertSame('draft', $competition->fresh()->status);
    }

    /**
     * @return array<int, string>
     */
    private function forbiddenYouthPhrases(): array
    {
        return [
            'žensko preduzetništvo',
            'ženskom preduzetništvu',
            'preduzetnice',
        ];
    }

    private function youthBodyIsClean(string $html): bool
    {
        foreach ($this->forbiddenYouthPhrases() as $phrase) {
            if (str_contains($html, $phrase)) {
                return false;
            }
        }

        return ! str_contains(strtolower($html), 'youth')
            && ! str_contains($html, 'password12')
            && ! str_contains($html, 'omladinsko')
            && ! str_contains($html, 'APP_KEY');
    }

    private function zenskoBodyIsUnchanged(string $html): bool
    {
        return str_contains($html, 'ženskom preduzetništvu')
            && ! str_contains($html, 'preduzetništvu mladih')
            && ! str_contains($html, 'omladinski Poziv')
            && ! str_contains($html, 'sprovođenje konkursa')
            && ! str_contains(strtolower($html), 'youth');
    }

    private function neutralBodyIsClean(string $html): bool
    {
        return str_contains($html, 'sprovođenje konkursa')
            && ! str_contains($html, 'žensko preduzetništvo')
            && ! str_contains($html, 'ženskom preduzetništvu')
            && ! str_contains($html, 'preduzetništvu mladih')
            && ! str_contains($html, 'omladinski Poziv')
            && ! str_contains(strtolower($html), 'youth')
            && ! str_contains($html, 'omladinsko')
            && ! str_contains($html, 'zensko');
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

    /**
     * @param  array<int, array<string, string>>  $overridesBySeat
     * @return array<string, mixed>
     */
    private function youthCommissionPayload(Competition $competition, array $overridesBySeat = []): array
    {
        $defaults = [
            1 => ['name' => 'Predsjednik Mail', 'email' => 'mladi.mail.1.'.uniqid().'@kotor.me', 'position' => 'predsjednik'],
            2 => ['name' => 'Clan Mail 2', 'email' => 'mladi.mail.2.'.uniqid().'@kotor.me', 'position' => 'clan'],
            3 => ['name' => 'Clan Mail 3', 'email' => 'mladi.mail.3.'.uniqid().'@kotor.me', 'position' => 'clan'],
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
            'name' => 'Komisija mladih mail '.uniqid(),
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
    private function zenskoCommissionPayload(?Competition $competition): array
    {
        $rows = [
            ['Predsjednik Z Mail', 'predsjednik', 'opstina', null],
            ['Clan 2 Z Mail', 'clan', 'opstina', null],
            ['Clan 3 Z Mail', 'clan', 'opstina', null],
            ['Clan 4 Z Mail', 'clan', 'udruzenje', 'Udruženje'],
            ['Clan 5 Z Mail', 'clan', 'zene_mreza', null],
        ];
        $members = [];
        foreach ($rows as $index => $row) {
            $members[$index] = [
                'name' => $row[0],
                'email' => 'zensko.mail.'.$index.'.'.uniqid().'@kotor.me',
                'password' => 'password12',
                'position' => $row[1],
                'member_type' => $row[2],
            ];
            if ($row[3] !== null) {
                $members[$index]['organization'] = $row[3];
            }
        }

        $payload = [
            'name' => 'Zenska komisija mail '.uniqid(),
            'year' => 2026,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'members' => $members,
        ];
        if ($competition !== null) {
            $payload['competition_ids'] = [$competition->id];
        }

        return $payload;
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
