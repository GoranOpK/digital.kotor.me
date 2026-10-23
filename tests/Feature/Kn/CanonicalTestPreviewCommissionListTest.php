<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance as G;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * Commission visibility on the competition page before the application deadline.
 * The exception is only an MMSP draft with test preview enabled.
 */
class CanonicalTestPreviewCommissionListTest extends TestCase
{
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private const HIDDEN_UNTIL_DEADLINE = 'Prijave će biti prikazane nakon isteka roka za prijavljivanje na konkurs.';

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

    public function test_commission_sees_submitted_preview_applications_and_existing_programs_stay_hidden(): void
    {
        [, $members] = $this->canonicalCommission();
        $chair = $members[G::ROLE_PREDSJEDNIK]->user;
        $commission = $members[G::ROLE_PREDSJEDNIK]->commission;
        $preview = $this->openCompetition($commission, self::CANONICAL, 'draft', true);
        $submitted = $this->application($preview, 'Vidljivi Podnosilac MMSP');
        $this->application($preview, 'Skriveni Nacrt MMSP', 'draft');

        $this->assertTrue($preview->isCanonicalTestPreview());
        $this->assertFalse($preview->isApplicationDeadlinePassed());

        $this->actingAs($chair)
            ->get(route('admin.competitions.show', $preview))
            ->assertOk()
            ->assertSee('Vidljivi Podnosilac MMSP')
            ->assertSee('Podnesena')
            ->assertSee(route('admin.applications.show', $submitted))
            ->assertDontSee('Skriveni Nacrt MMSP')
            ->assertDontSee(self::HIDDEN_UNTIL_DEADLINE);

        $published = $this->openCompetition($commission, self::CANONICAL, 'published', false);
        $this->application($published, 'Skriveni Redovni MMSP');
        $this->assertFalse($published->isCanonicalTestPreview());
        $this->assertFalse($published->isApplicationDeadlinePassed());
        $this->actingAs($chair)
            ->get(route('admin.competitions.show', $published))
            ->assertOk()
            ->assertSee(self::HIDDEN_UNTIL_DEADLINE)
            ->assertDontSee('Skriveni Redovni MMSP');

        $flagged = $this->openCompetition($commission, self::CANONICAL, 'published', true);
        $this->application($flagged, 'Skriveni Objavljeni MMSP');
        $this->assertTrue($flagged->isCanonicalTestMode());
        $this->assertFalse($flagged->isCanonicalTestPreview());
        $this->actingAs($chair)
            ->get(route('admin.competitions.show', $flagged))
            ->assertOk()
            ->assertSee(self::HIDDEN_UNTIL_DEADLINE)
            ->assertDontSee('Skriveni Objavljeni MMSP');

        $this->assertProfileListStaysHidden('zensko', 'Skrivena Zenska Prijava');
        $this->assertProfileListStaysHidden('omladinsko', 'Skrivena Omladinska Prijava');
    }

    private function assertProfileListStaysHidden(string $type, string $applicantName): void
    {
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $user = User::factory()->create([
            'name' => 'Clan '.$type,
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'status' => 'active',
        ]);
        $competition = $this->openCompetition($commission, $type, 'published', true);
        $this->application($competition, $applicantName);

        $this->assertFalse($competition->isCanonicalTestPreview());
        $this->actingAs($user)
            ->get(route('admin.competitions.show', $competition))
            ->assertOk()
            ->assertSee(self::HIDDEN_UNTIL_DEADLINE)
            ->assertDontSee($applicantName)
            ->assertDontSee('Ocijeni');
    }

    private function openCompetition(Commission $commission, string $type, string $status, bool $testPreview): Competition
    {
        return Competition::create([
            'title' => $type.' '.$status.' '.uniqid(),
            'type' => $type,
            'status' => $status,
            'is_test_preview' => $testPreview,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'commission_id' => $commission->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-21',
            'published_at' => $status === 'published' ? '2026-09-01 09:00:00' : null,
        ]);
    }

    private function application(Competition $competition, string $applicantName, string $status = 'submitted'): Application
    {
        $owner = User::factory()->create([
            'name' => $applicantName,
            'email' => 'podnosilac-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => $status,
            'business_plan_name' => 'Plan '.$applicantName,
            'founder_name' => $applicantName,
            'submitted_at' => $status === 'draft' ? null : now(),
            'redni_broj' => Application::query()->where('competition_id', $competition->id)->count() + 1,
        ]);
    }
}
