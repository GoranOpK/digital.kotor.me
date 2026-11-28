<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\CanonicalApplicantForm;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\CanonicalReviewState;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

class MmspEvaluationListAlignmentTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-09-10 10:00:00', 'Europe/Belgrade'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_mmsp_evaluation_list_matches_the_youth_columns_without_changing_other_programs(): void
    {
        [$commission, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK];
        $open = $this->competition($commission, 'MMSP otvoreno bodovanje');
        $decided = $this->competition($commission, 'MMSP konačna odluka');
        $decided->forceFill(['canonical_ranking_formed_at' => now()])->save();

        $entrepreneur = $this->owner('Stari Nalog Petra');
        $olive = $this->application($open, $entrepreneur, 'Masline u Kotoru', CanonicalLegalForm::PREDUZETNIK, 'Petar Petrović');
        $cheese = $this->application($open, $entrepreneur, 'Bokeljski sir', CanonicalLegalForm::PREDUZETNIK, 'Petar Petrović', null, 'submitted', null, null, CanonicalSubsidyType::DIGITALIZATION);
        $company = $this->application($open, $this->owner('Nalog Društva'), 'Digitalni vodič', CanonicalLegalForm::DRUSTVO, 'Odgovorno Lice', 'Kotor Turist DOO');
        $nameless = $this->application($open, $this->owner('Nalog Bez Naziva'), null, CanonicalLegalForm::PREDUZETNIK, 'Ana BezPlana');
        $review = $this->application($open, $this->owner('Nalog Provjere'), 'Čeka provjeru');
        $ready = $this->application($open, $this->owner('Nalog Spreman'), 'Spreman za bodovanje');
        $partial = $this->application($open, $this->owner('Nalog Djelimican'), 'Djelimično ocijenjen');
        $approved = $this->application($decided, $this->owner('Nalog Odobren'), 'Odobreni plan', CanonicalLegalForm::PREDUZETNIK, 'Jovan Odobren', null, 'approved', '50.00', '1500.00');
        $rejected = $this->application($decided, $this->owner('Nalog Odbijen'), 'Odbijeni plan', CanonicalLegalForm::PREDUZETNIK, 'Mika Odbijen', null, 'rejected', '20.00');

        foreach ([$olive, $cheese, $company, $nameless, $ready, $partial, $approved, $rejected] as $application) {
            $this->completeAssessment($application, $chair);
        }
        $this->scoreMember($chair, $partial, 4);
        $this->scoreAll($members, $approved, 5);
        $this->scoreAll($members, $rejected, 2);

        $youth = $this->otherProgram($chair->user, 'omladinsko', 'Plan mladih', 'Marko Mladi', 'Nalog Mladi');
        $women = $this->otherProgram($chair->user, 'zensko', 'Plan žena', null, 'Jelena Ženska', 'published');

        $entrepreneur->update(['name' => 'Novi Nalog Petra']);
        $before = $this->snapshot([$approved, $rejected]);

        $mixed = $this->actingAs($chair->user)->get(route('evaluation.index'))->assertOk()->getContent();
        $this->assertSame([
            'Naziv biznis plana',
            'Podnosilac',
            'Konkurs',
            'Status',
            'Ocjena',
            'Akcije',
        ], $this->headers($mixed));
        $this->assertStringNotContainsString('Naziv plana ulaganja', $mixed);
        $this->assertStringNotContainsString('Vrsta subvencije', $mixed);

        $mmsp = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'competition_id' => $open->id,
        ]))->assertOk()->getContent();
        $this->assertSame([
            'Naziv plana ulaganja',
            'Podnosilac',
            'Konkurs',
            'Status',
            'Ocjena',
            'Akcije',
        ], $this->headers($mmsp));
        $this->assertStringNotContainsString('Naziv biznis plana', $mmsp);
        $this->assertStringNotContainsString('Vrsta subvencije', $mmsp);
        $this->assertStringNotContainsString(CanonicalSubsidyType::label(CanonicalSubsidyType::AUTHENTIC_LOCAL), $mmsp);

        $oliveRow = $this->row($mmsp, 'Masline u Kotoru');
        $cheeseRow = $this->row($mmsp, 'Bokeljski sir');
        $this->assertStringContainsString('Petar Petrović', $oliveRow);
        $this->assertStringContainsString('Petar Petrović', $cheeseRow);
        $this->assertStringNotContainsString('Novi Nalog Petra', $oliveRow);
        $this->assertStringNotContainsString('Novi Nalog Petra', $cheeseRow);
        $this->assertStringNotContainsString('Stari Nalog Petra', $mmsp);

        $companyRow = $this->row($mmsp, 'Digitalni vodič');
        $this->assertStringContainsString('Kotor Turist DOO', $companyRow);
        $this->assertStringNotContainsString('Odgovorno Lice', $companyRow);
        $this->assertStringNotContainsString('Nalog Društva', $companyRow);

        $namelessRow = $this->row($mmsp, 'Ana BezPlana');
        $this->assertStringContainsString('—', $namelessRow);
        $this->assertStringNotContainsString('Nalog Bez Naziva', $namelessRow);

        $reviewRow = $this->row($mmsp, 'Čeka provjeru');
        $this->assertStringContainsString(CanonicalReviewState::LABELS[CanonicalReviewState::PENDING_REVIEW], $reviewRow);
        $this->assertStringContainsString('Obrazac P3', $reviewRow);
        $this->assertStringNotContainsString('Ocjeni', $reviewRow);

        $readyRow = $this->row($mmsp, 'Spreman za bodovanje');
        $this->assertStringContainsString('U ocjenjivanju', $readyRow);
        $this->assertStringContainsString('Obrazac P3', $readyRow);
        $this->assertStringNotContainsString('Ocjeni', $readyRow);
        $this->assertStringNotContainsString('Pregledaj ocjenu', $readyRow);

        $partialRow = $this->row($mmsp, 'Djelimično ocijenjen');
        $this->assertStringContainsString('Vaša ocjena je unesena', $partialRow);
        $this->assertStringContainsString('btn-sm evaluated', $partialRow);
        $this->assertStringContainsString('Obrazac P3', $partialRow);
        $this->assertStringNotContainsString('Pregledaj ocjenu', $partialRow);

        $decidedList = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'competition_id' => $decided->id,
        ]))->assertOk()->getContent();
        $approvedRow = $this->row($decidedList, 'Odobreni plan');
        $this->assertStringContainsString('Odobrena prijava', $approvedRow);
        $this->assertStringContainsString('status-evaluated', $approvedRow);
        $this->assertStringContainsString('50.00 / 50', $approvedRow);
        $this->assertStringContainsString('Jovan Odobren', $approvedRow);
        $this->assertStringContainsString('Ocijenjena prijava', $approvedRow);
        $this->assertStringContainsString('#10b981', $approvedRow);
        $rejectedRow = $this->row($decidedList, 'Odbijeni plan');
        $this->assertStringContainsString('Odbijena prijava', $rejectedRow);
        $this->assertStringContainsString('status-rejected', $rejectedRow);
        $this->assertStringContainsString('Mika Odbijen', $rejectedRow);
        $this->assertStringContainsString('20.00 / 50', $rejectedRow);
        $this->assertStringContainsString('Pregledaj', $rejectedRow);
        $this->assertStringNotContainsString('Odobrena prijava', $rejectedRow);

        $evaluated = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'filter' => 'evaluated',
            'competition_id' => $decided->id,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('Odobreni plan', $evaluated);
        $this->assertStringContainsString('Odbijeni plan', $evaluated);
        $rejectedFilter = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'filter' => 'rejected',
            'competition_id' => $decided->id,
        ]))->assertOk()->getContent();
        $this->assertStringNotContainsString('Odobreni plan', $rejectedFilter);
        $this->assertStringContainsString('Odbijeni plan', $rejectedFilter);
        $pending = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'filter' => 'pending',
            'competition_id' => $decided->id,
        ]))->assertOk()->getContent();
        $this->assertStringNotContainsString('Odobreni plan', $pending);
        $this->assertStringNotContainsString('Odbijeni plan', $pending);

        $youthList = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'competition_id' => $youth->id,
        ]))->assertOk()->getContent();
        $this->assertSame('Naziv biznis plana', $this->headers($youthList)[0]);
        $youthRow = $this->row($youthList, 'Plan mladih');
        $this->assertStringContainsString('Marko Mladi', $youthRow);
        $this->assertStringNotContainsString('Nalog Mladi', $youthRow);
        $this->assertStringContainsString('Obrazac M3', $youthRow);
        $this->assertStringNotContainsString('Obrazac P3', $youthRow);
        $this->assertStringNotContainsString('Naziv plana ulaganja', $youthList);

        $womenList = $this->actingAs($chair->user)->get(route('evaluation.index', [
            'competition_id' => $women->id,
        ]))->assertOk()->getContent();
        $this->assertSame('Naziv biznis plana', $this->headers($womenList)[0]);
        $womenRow = $this->row($womenList, 'Plan žena');
        $this->assertStringContainsString('Jelena Ženska', $womenRow);
        $this->assertStringContainsString('Ocjeni', $womenRow);
        $this->assertStringNotContainsString('Obrazac P3', $womenRow);
        $this->assertStringNotContainsString('Obrazac M3', $womenRow);

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($chair->user)->post(route('evaluation.store', $approved), $this->rescorePayload());
            $this->fail('Ponovni upis ocjene na odobrenoj prijavi mora biti odbijen.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(CanonicalIndividualScoringService::CANONICAL_RANKING_FROZEN_MESSAGE, $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot([$approved, $rejected]));
        $this->assertSame(5, (int) EvaluationScore::query()->where('application_id', $approved->id)->value('criterion_1'));
    }

    /**
     * @return list<string>
     */
    private function headers(string $html): array
    {
        $this->assertSame(1, preg_match('/<thead>.*?<\/thead>/s', $html, $head));
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $head[0], $cells);

        return array_map(fn (string $cell): string => trim(strip_tags($cell)), $cells[1]);
    }

    private function row(string $html, string $needle): string
    {
        preg_match_all('/<tr\b[^>]*>.*?<\/tr>/s', $html, $rows);
        foreach ($rows[0] as $row) {
            if (str_contains($row, $needle)) {
                return $row;
            }
        }

        $this->fail($needle);
    }

    private function competition(Commission $commission, string $title): Competition
    {
        return Competition::create([
            'title' => $title.' '.uniqid(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'closed',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
    }

    private function owner(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function application(
        Competition $competition,
        User $owner,
        ?string $planName,
        string $legalForm = CanonicalLegalForm::PREDUZETNIK,
        ?string $personName = null,
        ?string $companyName = null,
        string $status = 'submitted',
        ?string $score = null,
        ?string $amount = null,
        string $subsidy = CanonicalSubsidyType::AUTHENTIC_LOCAL,
    ): Application {
        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => CanonicalLegalForm::applicantType($legalForm),
            'status' => $status,
            'business_plan_name' => $planName,
            'submitted_at' => now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);
        $application->forceFill([
            'final_score' => $score,
            'approved_amount' => $amount,
        ])->save();
        CanonicalApplicantForm::query()->create([
            'application_id' => $application->id,
            'legal_form' => $legalForm,
            'person_name' => $personName,
            'company_name' => $companyName,
            'subsidy_type' => $subsidy,
        ])->forceFill(['frozen_at' => now()])->save();
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'subsidy_type' => $subsidy,
            'claimed_at' => now(),
        ]);

        return $application->fresh();
    }

    private function completeAssessment(Application $application, CommissionMember $chair): void
    {
        ApplicationCompletenessAssessment::query()->create([
            'application_id' => $application->id,
            'competition_id' => $application->competition_id,
            'outcome' => ApplicationCompletenessAssessment::COMPLETE,
            'recorded_by_user_id' => $chair->user_id,
            'evidence_snapshot' => [],
            'evidence_fingerprint' => 'potpuna-'.$application->id,
            'recorded_at' => now()->subDays(20),
        ]);
    }

    private function scoreMember(CommissionMember $member, Application $application, int $mark): void
    {
        $attributes = [
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'canonical_seat_no' => $member->canonical_seat_no,
            'completed_at' => now(),
        ];
        for ($i = 1; $i <= 10; $i++) {
            $attributes['criterion_'.$i] = $mark;
        }
        EvaluationScore::query()->create($attributes);
    }

    /**
     * @param  array<string, CommissionMember>  $members
     */
    private function scoreAll(array $members, Application $application, int $mark): void
    {
        foreach ($members as $member) {
            $this->scoreMember($member, $application, $mark);
        }
    }

    private function otherProgram(User $viewer, string $type, string $planName, ?string $historicalName, string $accountName, string $status = 'closed'): Competition
    {
        $commission = Commission::create([
            'name' => $type.' '.$planName,
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $seatCount = $type === 'zensko' ? 5 : 3;
        for ($seat = 1; $seat <= $seatCount; $seat++) {
            $user = $seat === 1 ? $viewer : User::factory()->create([
                'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => $type === 'zensko' && $seat > 3 ? 'udruzenje' : 'opstina',
                'canonical_seat_no' => $type === 'omladinsko' ? $seat : null,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Konkurs '.$type.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'published_at' => '2026-08-01 09:00:00',
        ]);
        $owner = User::factory()->create([
            'name' => $accountName,
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'submitted',
            'business_plan_name' => $planName,
            'preduzetnik_name' => $historicalName,
            'submitted_at' => now(),
            'redni_broj' => 1,
        ]);

        return $competition;
    }

    /**
     * @param  list<Application>  $applications
     * @return array<int, array{status: string, score: ?string, amount: ?string}>
     */
    private function snapshot(array $applications): array
    {
        $snapshot = [];
        foreach ($applications as $application) {
            $fresh = $application->fresh();
            $snapshot[$fresh->id] = [
                'status' => (string) $fresh->status,
                'score' => $fresh->final_score === null ? null : (string) $fresh->final_score,
                'amount' => $fresh->approved_amount === null ? null : (string) $fresh->approved_amount,
            ];
        }

        return $snapshot;
    }

    /**
     * @return array<string, int|string>
     */
    private function rescorePayload(): array
    {
        $payload = ['notes' => 'Pokusaj izmjene', 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload['criterion_'.$i] = 1;
        }

        return $payload;
    }
}
