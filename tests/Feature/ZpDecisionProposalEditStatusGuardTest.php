<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Models\ZenskoDecisionProposalSnapshot;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZpDecisionProposalEditStatusGuardTest extends TestCase
{
    use RefreshDatabase;

    private const ZENSKO_CLOSE_MESSAGE = 'Konkurs ženskog preduzetništva zatvara se kroz radnju za zatvaranje konkursa.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_edit_form_does_not_offer_closing_a_womens_competition(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $competition = $this->openCompetition('zensko');

        $this->actingAs($this->editor())
            ->get(route('admin.competitions.edit', $competition))
            ->assertOk()
            ->assertDontSee('value="completed"', false)
            ->assertDontSee('value="closed"', false)
            ->assertSee('value="published"', false);
    }

    public function test_direct_completed_status_is_rejected(): void
    {
        $competition = $this->openCompetition('zensko');

        $this->actingAs($this->editor())
            ->from(route('admin.competitions.edit', $competition))
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'status' => 'completed',
            ]))
            ->assertRedirect(route('admin.competitions.edit', $competition))
            ->assertSessionHasErrors(['status' => self::ZENSKO_CLOSE_MESSAGE]);

        $this->assertSame('published', $competition->fresh()->status);
    }

    public function test_direct_closed_status_is_rejected(): void
    {
        $competition = $this->openCompetition('zensko');

        $this->actingAs($this->editor())
            ->from(route('admin.competitions.edit', $competition))
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'status' => 'closed',
            ]))
            ->assertRedirect(route('admin.competitions.edit', $competition))
            ->assertSessionHasErrors(['status' => self::ZENSKO_CLOSE_MESSAGE]);

        $this->assertSame('published', $competition->fresh()->status);
    }

    public function test_rejected_status_change_does_not_write_competition_fields(): void
    {
        $competition = $this->openCompetition('zensko');
        $before = $competition->fresh()->only(['title', 'status', 'budget', 'year', 'type', 'closed_at']);

        $this->actingAs($this->editor())
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'title' => 'Pokušaj zaobilaženja',
                'status' => 'completed',
                'budget' => 1,
            ]))
            ->assertSessionHasErrors('status');

        $fresh = $competition->fresh();
        $this->assertSame($before['title'], $fresh->title);
        $this->assertSame('published', $fresh->status);
        $this->assertSame($before['year'], (int) $fresh->year);
        $this->assertSame('zensko', $fresh->type);
        $this->assertNull($fresh->closed_at);
        $this->assertSame(0, bccomp((string) $before['budget'], (string) $fresh->budget, 2));
        $this->assertNull($fresh->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_allowed_edit_of_open_womens_competition_still_works(): void
    {
        $competition = $this->openCompetition('zensko');

        $this->actingAs($this->editor())
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'title' => 'Ženski konkurs izmijenjen',
                'status' => 'published',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $this->assertSame('Ženski konkurs izmijenjen', $fresh->title);
        $this->assertSame('published', $fresh->status);
        $this->assertNull($fresh->zensko_decision_proposal_snapshot_id);
    }

    public function test_chairman_close_still_stores_one_linked_snapshot(): void
    {
        [$competition, $president] = $this->closeableCompetition();

        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $snapshot = ZenskoDecisionProposalSnapshot::query()->findOrFail($fresh->zensko_decision_proposal_snapshot_id);
        $this->assertSame('completed', $fresh->status);
        $this->assertSame($competition->id, $snapshot->competition_id);
        $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([$snapshot->storage_path], Storage::disk('local')->allFiles());
    }

    public function test_youth_edit_can_still_mark_completed(): void
    {
        $competition = $this->openCompetition('omladinsko', [
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($this->editor())
            ->get(route('admin.competitions.edit', $competition))
            ->assertOk()
            ->assertSee('value="completed"', false);

        $this->actingAs($this->editor())
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'status' => 'completed',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_canonical_edit_keeps_its_own_close_block(): void
    {
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->actingAs($this->editor())
            ->get(route('admin.competitions.edit', $competition))
            ->assertOk()
            ->assertSee('value="completed"', false);

        $this->actingAs($this->editor())
            ->put(route('admin.competitions.update', $competition), $this->payload($competition, [
                'status' => 'completed',
            ]))
            ->assertSessionHasErrors([
                'status' => 'Kanonski Poziv se zatvara samo nakon objave potpisane formalne Odluke, kroz kanonsko zatvaranje.',
            ]);

        $this->assertSame('published', $competition->fresh()->status);
    }

    public function test_saved_snapshot_is_unchanged_when_edit_is_attempted(): void
    {
        [$competition, $president] = $this->closeableCompetition();
        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $snapshot = ZenskoDecisionProposalSnapshot::query()->findOrFail($fresh->zensko_decision_proposal_snapshot_id);
        $bytes = Storage::disk('local')->get($snapshot->storage_path);
        $hash = $snapshot->content_sha256;

        $this->actingAs($this->editor())
            ->put(route('admin.competitions.update', $fresh), $this->payload($fresh, [
                'title' => 'Pokušaj izmjene arhiva',
                'status' => 'completed',
            ]))
            ->assertForbidden();

        $again = $fresh->fresh();
        $stored = ZenskoDecisionProposalSnapshot::query()->findOrFail($again->zensko_decision_proposal_snapshot_id);
        $this->assertSame($snapshot->id, $stored->id);
        $this->assertSame($hash, $stored->content_sha256);
        $this->assertSame($bytes, Storage::disk('local')->get($stored->storage_path));
        $this->assertSame($fresh->title, $again->title);
        $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
    }

    private function editor(): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function openCompetition(string $type, array $overrides = []): Competition
    {
        $competition = Competition::query()->create(array_merge([
            'title' => 'Konkurs '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => 100000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(10),
        ], $overrides));
        UpNumber::query()->create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        return $competition->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => $competition->upNumber()->value('number'),
            'year' => $competition->year,
            'budget' => $competition->budget,
            'start_date' => $competition->start_date?->toDateString(),
            'status' => $competition->status,
        ], $overrides);
    }

    /**
     * @return array{0: Competition, 1: CommissionMember}
     */
    private function closeableCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'zensko',
        ]);
        $competition = Competition::query()->create([
            'title' => 'Žensko preduzetništvo — zatvaranje',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 100000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'commission_id' => $commission->id,
        ]);
        UpNumber::query()->create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        $roleId = Role::query()->where('name', 'komisija')->firstOrFail()->id;
        $president = null;
        for ($seat = 1; $seat <= 5; $seat++) {
            $user = User::factory()->create([
                'role_id' => $roleId,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $member = CommissionMember::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => 'opstina',
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
            if ($seat === 1) {
                $president = $member;
            }
        }

        $owner = User::factory()->create();
        $application = Application::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Plan Ana',
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => 'Ana Anić',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'evaluated',
            'submitted_at' => now()->subDays(20),
            'final_score' => 45,
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 1000,
            'requested_amount' => 1000,
        ]);
        ApplicationEliminatoryCheck::query()->create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDay(),
            'confirmed_by_commission_member_id' => $president->id,
            'confirmed_by_user_id' => $president->user_id,
            'confirmed_by_name' => $president->name,
        ]);
        foreach ($competition->commission->activeMembers()->orderBy('canonical_seat_no')->get() as $member) {
            $score = [
                'application_id' => $application->id,
                'commission_member_id' => $member->id,
                'canonical_seat_no' => $member->canonical_seat_no,
                'documents_complete' => true,
                'completed_at' => now()->subDay(),
                'final_score' => 45,
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $score['criterion_'.$criterion] = 5;
            }
            EvaluationScore::query()->create($score);
        }

        return [$competition->fresh(), $president];
    }
}
