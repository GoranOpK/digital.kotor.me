<?php

namespace Tests\Feature;

use App\Mail\ApplicationPrigovorSubmittedMail;
use App\Models\Application;
use App\Models\ApplicationPrigovor;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EliminatoryPrigovorChairmanNoticeTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2030;

    private int $callSerial = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_pm_submission_emails_the_single_active_president_and_links_the_open_review(): void
    {
        $ctx = $this->confirmedYouthFailure();
        $applicant = $this->markApplicant($ctx['application']);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $ctx['application']), $this->youthPrigovorPayload())
            ->assertRedirect(route('applications.show', $ctx['application']));

        $prigovor = $ctx['application']->fresh()->prigovor;
        $this->assertNotNull($prigovor);
        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $prigovor->status);

        $reviewUrl = route('evaluation.create', $ctx['application']);
        $this->actingAs($ctx['chairman']->user)
            ->get($reviewUrl)
            ->assertOk()
            ->assertSee('Stanje Prigovora', false);

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, function (ApplicationPrigovorSubmittedMail $mail) use ($ctx, $applicant, $reviewUrl, $prigovor) {
            $html = $mail->render();

            return $mail->hasTo($ctx['chairman']->user->email)
                && str_contains($html, 'Podnesen je prigovor na eliminatorno odbijenu prijavu')
                && str_contains($html, $ctx['competition']->title)
                && str_contains($html, 'UP-N-'.$ctx['competition']->year.'/7')
                && str_contains($html, $prigovor->submitted_at->format('d.m.Y. H:i'))
                && str_contains($html, $reviewUrl)
                && ! str_contains($html, $applicant->email)
                && ! str_contains($html, 'Tajna')
                && ! str_contains($html, '0101990123456');
        });
    }

    public function test_zp_submission_emails_the_single_active_president(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $this->markApplicant($application);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Molim preispitivanje eliminatornog odbijanja.',
            ])
            ->assertRedirect();

        $reviewUrl = route('evaluation.create', $application);
        $this->actingAs($president->user)->get($reviewUrl)->assertOk();

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, function (ApplicationPrigovorSubmittedMail $mail) use ($president, $application, $reviewUrl) {
            $html = $mail->render();

            return $mail->hasTo($president->user->email)
                && str_contains($html, $application->competition->title)
                && str_contains($html, $reviewUrl);
        });
    }

    public function test_zp_uses_the_application_review_when_evaluation_page_is_already_closed(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $application->competition->update(['closed_at' => now()->subDays(50)]);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Prigovor poslije roka za ocjenjivanje.',
            ])
            ->assertRedirect();

        $this->assertNotNull($application->fresh()->prigovor);
        $this->actingAs($president->user)
            ->get(route('evaluation.create', $application))
            ->assertForbidden();

        $fallback = route('admin.applications.show', $application);
        $this->actingAs($president->user)
            ->get($fallback)
            ->assertOk()
            ->assertSee('Stanje Prigovora', false);

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, function (ApplicationPrigovorSubmittedMail $mail) use ($president, $fallback) {
            $html = $mail->render();

            return $mail->hasTo($president->user->email)
                && str_contains($html, $fallback)
                && ! str_contains($html, '/evaluation/applications/');
        });
    }

    public function test_email_omits_the_link_when_both_existing_review_pages_are_forbidden(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $application->competition->update([
            'start_date' => now()->addDay()->toDateString(),
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Prigovor dok su obje stranice pregleda zatvorene.',
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $application->fresh()->prigovor->status);

        $evaluationUrl = route('evaluation.create', $application);
        $adminUrl = route('admin.applications.show', $application);

        $this->actingAs($president->user)->get($evaluationUrl)->assertForbidden();
        $this->actingAs($president->user)->get($adminUrl)->assertForbidden();

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, function (ApplicationPrigovorSubmittedMail $mail) use ($president, $evaluationUrl, $adminUrl) {
            $html = $mail->render();

            return $mail->hasTo($president->user->email)
                && str_contains($html, 'Podnesen je prigovor na eliminatorno odbijenu prijavu')
                && ! str_contains($html, '<a ')
                && ! str_contains($html, $evaluationUrl)
                && ! str_contains($html, $adminUrl);
        });

        $this->actingAs($president->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pregled trenutno nije dozvoljen', false)
            ->assertDontSee($evaluationUrl, false)
            ->assertDontSee($adminUrl, false);
    }

    public function test_another_commission_president_is_not_notified(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        [$otherApplication, $otherPresident] = $this->confirmedWomenFailure();

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Samo ova komisija.',
            ])
            ->assertRedirect();

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, fn ($mail) => $mail->hasTo($president->user->email));
        Mail::assertNotSent(ApplicationPrigovorSubmittedMail::class, fn ($mail) => $mail->hasTo($otherPresident->user->email));
        $this->assertNull($otherApplication->fresh()->prigovor);
    }

    public function test_ordinary_commission_member_is_not_notified(): void
    {
        $ctx = $this->confirmedYouthFailure();

        $this->actingAs($ctx['application']->user)
            ->post(route('applications.prigovor.store', $ctx['application']), $this->youthPrigovorPayload())
            ->assertRedirect();

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, fn ($mail) => $mail->hasTo($ctx['chairman']->user->email));
        Mail::assertNotSent(ApplicationPrigovorSubmittedMail::class, fn ($mail) => $mail->hasTo($ctx['member']->user->email));
    }

    public function test_missing_active_president_keeps_the_prigovor_and_sends_no_mail(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $president->update(['status' => 'inactive']);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Nema aktivnog predsjednika.',
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $application->fresh()->prigovor->status);
        Mail::assertNotSent(ApplicationPrigovorSubmittedMail::class);
    }

    public function test_multiple_active_presidents_keep_the_prigovor_and_send_no_mail(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $this->addPresident($president->commission);

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Dva predsjednika.',
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationPrigovor::STATUS_PODNESEN, $application->fresh()->prigovor->status);
        Mail::assertNotSent(ApplicationPrigovorSubmittedMail::class);
    }

    public function test_mail_failure_does_not_roll_back_the_prigovor(): void
    {
        [$application] = $this->confirmedWomenFailure();

        Mail::swap(new class
        {
            public function to($users)
            {
                throw new \RuntimeException('smtp failure');
            }
        });

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Mail ne smije poništiti prigovor.',
            ])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame(
            ApplicationPrigovor::STATUS_PODNESEN,
            $application->fresh()->prigovor->status,
        );
    }

    public function test_repeated_submission_does_not_send_another_mail(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Prvo podnošenje.',
            ])
            ->assertRedirect();

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Drugo podnošenje.',
            ])
            ->assertForbidden();

        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, 1);
        Mail::assertSent(ApplicationPrigovorSubmittedMail::class, fn ($mail) => $mail->hasTo($president->user->email));
        $this->assertSame(1, ApplicationPrigovor::query()->where('application_id', $application->id)->count());
    }

    public function test_chairman_dashboard_lists_pending_prigovori_and_drops_resolved_ones(): void
    {
        [$application, $president] = $this->confirmedWomenFailure();
        $application->update(['redni_broj' => 4]);
        $number = $application->competition->upNumber->number.'/4';

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Za kontrolnu tablu.',
            ])
            ->assertRedirect();

        $reviewUrl = route('evaluation.create', $application);
        $this->actingAs($president->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Prigovori na eliminatorne odluke', false)
            ->assertSee('Čeka odluku:', false)
            ->assertSee('>1<', false)
            ->assertSee($number, false)
            ->assertSee($application->competition->title, false)
            ->assertSee('Podnesen', false)
            ->assertSee($reviewUrl, false);

        $application->prigovor->update([
            'status' => ApplicationPrigovor::STATUS_PRIHVACEN,
            'decided_at' => now(),
        ]);

        $this->actingAs($president->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Čeka odluku:', false)
            ->assertSee('>0<', false)
            ->assertSee('Nema prigovora koji čekaju odluku.', false)
            ->assertDontSee($number, false)
            ->assertDontSee($reviewUrl, false);
    }

    public function test_users_without_that_chair_cannot_see_another_commissions_prigovori(): void
    {
        [$application, $president, $member] = $this->confirmedWomenFailure(true);
        [$otherApplication, $otherPresident] = $this->confirmedWomenFailure();
        $number = $application->competition->upNumber->number.'/9';

        $this->actingAs($application->user)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Samo nadležni predsjednik.',
            ])
            ->assertRedirect();

        $this->actingAs($member->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Prigovori na eliminatorne odluke', false);

        $this->actingAs($application->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Prigovori na eliminatorne odluke', false);

        $this->actingAs($otherPresident->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($number, false);

        $this->actingAs($president->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($number, false);

        $this->assertNull($otherApplication->fresh()->prigovor);
    }

    public function test_mmsp_prigovor_route_stays_forbidden_and_is_not_notified_or_listed(): void
    {
        $applicant = $this->userWithRole('korisnik');
        $competition = Competition::create([
            'title' => 'MMSP bez ovog prigovora '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => $this->yearSerial++,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $applicant->id,
            'business_plan_name' => 'MMSP plan',
            'applicant_type' => 'preduzetnik',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDay(),
            'redni_broj' => 3,
        ]);

        $this->actingAs($applicant)
            ->post(route('applications.prigovor.store', $application), [
                'obrazlozenje' => 'Kanonski prigovor nije ovaj tok.',
            ])
            ->assertForbidden()
            ->assertSee(LegacyCommissionFlowGuard::BLOCKED_MESSAGE, false);

        Mail::assertNotSent(ApplicationPrigovorSubmittedMail::class);
        $this->assertSame(0, ApplicationPrigovor::query()->count());

        $commission = $this->createCommission(CompetitionProfileResolver::TYPE_CANONICAL, 1, true);
        $competition->update(['commission_id' => $commission->id]);
        $canonicalPresident = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        ApplicationPrigovor::create([
            'application_id' => $application->id,
            'obrazlozenje' => 'Ručno ubačen zapis ne smije ući u PM/ŽP brojač.',
            'status' => ApplicationPrigovor::STATUS_PODNESEN,
            'submitted_at' => now(),
            'submitted_by_user_id' => $applicant->id,
            'eliminatory_reason_remaining' => true,
        ]);

        $this->actingAs($canonicalPresident->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Prigovori na eliminatorne odluke', false);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, member: CommissionMember, application: Application}
     */
    private function confirmedYouthFailure(): array
    {
        $year = $this->yearSerial++;
        $commission = $this->createCommission(CompetitionProfileResolver::TYPE_YOUTH, 3, true, true);
        $competition = $this->createCompetition($commission, CompetitionProfileResolver::TYPE_YOUTH, $year, 'UP-N-'.$year);
        $application = $this->createSubmittedApplication($competition, 7);
        $chairman = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        $member = $commission->activeMembers()->where('position', 'clan')->firstOrFail();

        $this->actingAs($chairman->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->youthFailPayload())
            ->assertRedirect();

        return [
            'competition' => $competition->fresh('upNumber'),
            'chairman' => $chairman->fresh('user'),
            'member' => $member->fresh('user'),
            'application' => $application->fresh(),
        ];
    }

    /**
     * @return array{0: Application, 1: CommissionMember, 2?: CommissionMember}
     */
    private function confirmedWomenFailure(bool $withMember = false): array
    {
        $commission = $this->createCommission(CompetitionProfileResolver::TYPE_WOMEN, 5, true);
        $competition = $this->createCompetition($commission, CompetitionProfileResolver::TYPE_WOMEN, 2026, 'UP-'.uniqid());
        $application = $this->createSubmittedApplication($competition, 9);
        $president = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        $member = $commission->activeMembers()->where('position', 'clan')->firstOrFail();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), [
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'note' => 'Ne prolazi kriterijum 2',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect();

        $application = $application->fresh(['competition.upNumber', 'user']);
        $president = $president->fresh('user');
        $member = $member->fresh('user');

        if ($withMember) {
            return [$application, $president, $member];
        }

        return [$application, $president];
    }

    private function markApplicant(Application $application): User
    {
        $application->user->forceFill([
            'first_name' => 'Tajna',
            'last_name' => '0101990123456',
            'name' => 'Tajna 0101990123456',
            'email' => 'tajna.'.$application->id.'@example.test',
        ])->save();

        return $application->user->fresh();
    }

    private function addPresident(Commission $commission): CommissionMember
    {
        $user = $this->userWithRole('komisija');

        return CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);
    }

    private function createSubmittedApplication(Competition $competition, int $redniBroj): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Biznis plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
            'redni_broj' => $redniBroj,
        ]);
    }

    private function createCompetition(Commission $commission, string $type, int $year, string $number): Competition
    {
        $competition = Competition::create([
            'title' => 'Konkurs '.$type.' '.$number,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => $year,
            'call_number' => $this->callSerial++,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => $number,
        ]);

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $number,
        ]);

        return $competition->fresh('upNumber');
    }

    private function createCommission(string $profile, int $seats, bool $withPresident, bool $numberedSeats = false): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $profile,
        ]);

        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        for ($seat = 1; $seat <= $seats; $seat++) {
            $user = $this->userWithRole('komisija');
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ($withPresident && $seat === 1) ? 'predsjednik' : 'clan',
                'member_type' => $profile === CompetitionProfileResolver::TYPE_YOUTH ? null : ($types[$seat - 1] ?? 'opstina'),
                'canonical_seat_no' => $numberedSeats ? $seat : null,
                'status' => 'active',
            ]);
        }

        return $commission->fresh('activeMembers.user');
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

    /**
     * @return array<string, mixed>
     */
    private function youthFailPayload(): array
    {
        return [
            'criterion_1' => '0',
            'criterion_2' => '0',
            'criterion_3' => '1',
            'criterion_notes' => [
                1 => 'Nedostaje izvod',
                2 => 'Nema M4',
                3 => '',
            ],
            'confirmation_acknowledged' => '1',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function youthPrigovorPayload(): array
    {
        return [
            'obrazlozenje' => 'Izvod je već bio u prijavi. M4 je dostavljen uz prijavu.',
        ];
    }
}
