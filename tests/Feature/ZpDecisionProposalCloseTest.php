<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\EvaluationScore;
use App\Models\Notice;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Models\ZenskoDecisionProposalSnapshot;
use Database\Seeders\RoleSeeder;
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\ZpEqualScoreAllocationGuard;
use App\Services\Competitions\ZenskoDecisionProposalCloseService;
use App\Services\Competitions\ZenskoDecisionProposalHtmlStore;
use App\Services\Competitions\ZenskoDecisionProposalStoreException;
use App\Services\Competitions\ZenskoDecisionProposalStoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ZpDecisionProposalCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_close_stores_one_snapshot_and_completes_the_competition(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        Storage::fake('local');
        [$competition, $president, $application] = $this->readyCompetition();

        $this->actingAs($president->user)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $snapshot = ZenskoDecisionProposalSnapshot::query()->firstOrFail();
        $html = Storage::disk('local')->get($snapshot->storage_path);

        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->closed_at);
        $this->assertSame($snapshot->id, $fresh->zensko_decision_proposal_snapshot_id);
        $this->assertSame($competition->id, $snapshot->competition_id);
        $this->assertSame($president->user_id, $snapshot->closed_by);
        $this->assertTrue($fresh->zenskoDecisionProposalSnapshot->is($snapshot));
        $this->assertTrue($snapshot->competition->is($fresh));
        $this->assertSame(hash('sha256', (string) $html), $snapshot->content_sha256);
        $this->assertStringContainsString('Ana Anić', (string) $html);
        $this->assertSame(1, (int) $application->fresh()->ranking_position);
        $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([$snapshot->storage_path], Storage::disk('local')->allFiles());
    }

    public function test_repeated_close_does_not_create_a_second_snapshot(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();

        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertSame('completed', $competition->fresh()->status);
    }

    public function test_failed_html_write_leaves_the_competition_open(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $documents = Mockery::mock(ZenskoDecisionProposalHtmlStore::class);
        $documents->shouldReceive('store')->once()->andThrow(
            new ZenskoDecisionProposalStoreException('Privremeni fajl Predloga odluke nije upisan.')
        );

        $result = $this->service($documents)->close($competition, $president->user);

        $this->assertFalse($result->succeeded);
        $this->assertSame('Privremeni fajl Predloga odluke nije upisan.', $result->error);
        $this->assertSame('published', $competition->fresh()->status);
        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_exception_before_commit_removes_only_the_unreferenced_file(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        Storage::disk('local')->put('zensko-decision-proposals/keep.html', 'KEEP');
        $service = new class(
            app(ZenskoDecisionProposalHtmlStore::class),
            app(ZpEqualScoreAllocationGuard::class),
            app(CanonicalIndividualScoringService::class),
        ) extends ZenskoDecisionProposalCloseService {
            protected function beforePersist(ZenskoDecisionProposalStoredFile $file): void
            {
                throw new \RuntimeException('before commit');
            }
        };

        $result = $service->close($competition, $president->user);

        $this->assertFalse($result->succeeded);
        $this->assertStringContainsString('nije sačuvan', (string) $result->error);
        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame(['zensko-decision-proposals/keep.html'], Storage::disk('local')->allFiles());
    }

    public function test_snapshot_insert_failure_rolls_back_the_close(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $service = new class(
            app(ZenskoDecisionProposalHtmlStore::class),
            app(ZpEqualScoreAllocationGuard::class),
            app(CanonicalIndividualScoringService::class),
        ) extends ZenskoDecisionProposalCloseService {
            protected function recordSnapshot(
                Competition $competition,
                ZenskoDecisionProposalStoredFile $file,
                User $actor,
            ): ZenskoDecisionProposalSnapshot {
                throw new \RuntimeException('insert failed');
            }
        };

        $result = $service->close($competition, $president->user);

        $this->assertFalse($result->succeeded);
        $this->assertSame('published', $competition->fresh()->status);
        $this->assertNull($competition->fresh()->closed_at);
        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_status_update_failure_rolls_back_the_snapshot_row(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $service = new class(
            app(ZenskoDecisionProposalHtmlStore::class),
            app(ZpEqualScoreAllocationGuard::class),
            app(CanonicalIndividualScoringService::class),
        ) extends ZenskoDecisionProposalCloseService {
            protected function completeCompetition(Competition $competition, ZenskoDecisionProposalSnapshot $snapshot): void
            {
                parent::completeCompetition($competition, $snapshot);
                throw new \RuntimeException('status failed');
            }
        };

        $result = $service->close($competition, $president->user);

        $this->assertFalse($result->succeeded);
        $this->assertSame('published', $competition->fresh()->status);
        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_missing_identity_blocks_close(): void
    {
        Storage::fake('local');
        [$competition, $president, $application] = $this->readyCompetition();
        $application->update([
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => null,
        ]);

        $this->actingAs($president->user)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'error' => 'Plan Ana: '.Application::DECISION_IDENTITY_APPLICANT_NAME_MISSING,
            ]);

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_incomplete_commission_decisions_block_close(): void
    {
        Storage::fake('local');
        [$competition, $president, $application] = $this->readyCompetition();
        $application->update([
            'commission_decision' => null,
            'approved_amount' => null,
        ]);

        $this->actingAs($president->user)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'error' => 'Ne možete zatvoriti konkurs prije nego što donesete zaključak za sve prijave i generišete odluku o dodjeli sredstava.',
            ]);

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unresolved_equal_scores_block_close(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $competition->update(['budget' => 100]);
        $first = $competition->applications()->firstOrFail();
        $first->update([
            'final_score' => 40,
            'ranking_position' => 1,
            'business_stage' => 'razvoj',
            'requested_amount' => 1000,
            'approved_amount' => 100,
        ]);
        $second = $this->addSupportedApplication($competition, $president, 'Bora Borić');
        $second->update([
            'final_score' => 40,
            'ranking_position' => 1,
            'business_stage' => 'započinjanje',
            'requested_amount' => 1000,
            'commission_decision' => 'odbija',
            'commission_justification' => 'Odbijeno uz obrazloženje.',
            'approved_amount' => null,
        ]);

        $this->actingAs($president->user)
            ->from(route('admin.competitions.show', $competition))
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'error' => ZpEqualScoreAllocationGuard::STAGE_PRIORITY_VIOLATION_MESSAGE,
            ]);

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_non_chairman_cannot_close(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $member = $competition->commission->activeMembers()->where('position', 'clan')->firstOrFail();

        $this->actingAs($member->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_youth_close_does_not_use_the_zensko_snapshot_service(): void
    {
        $service = Mockery::mock(ZenskoDecisionProposalCloseService::class);
        $service->shouldNotReceive('close');
        $this->app->instance(ZenskoDecisionProposalCloseService::class, $service);

        $competition = Competition::query()->create([
            'title' => 'Omladinski konkurs',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
        $user = User::factory()->create([
            'role_id' => Role::query()->where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('admin.competitions.close', $competition))
            ->assertForbidden();

        $this->assertSame('published', $competition->fresh()->status);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_close_does_not_create_a_signed_pdf_or_notice(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();

        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(0, CompetitionOfficialDecisionCopy::query()->count());
        $this->assertSame(0, Notice::query()->count());
        $this->assertSame(1, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_referenced_attempt_file_is_not_deleted_and_unknown_state_is_reported(): void
    {
        Storage::fake('local');
        [$competition, $president] = $this->readyCompetition();
        $service = new class(
            app(ZenskoDecisionProposalHtmlStore::class),
            app(ZpEqualScoreAllocationGuard::class),
            app(CanonicalIndividualScoringService::class),
        ) extends ZenskoDecisionProposalCloseService {
            public function probe(?string $path): ?string
            {
                return $this->discardUnreferencedAttempt($path);
            }
        };

        $stored = app(ZenskoDecisionProposalHtmlStore::class)->store($competition);
        ZenskoDecisionProposalSnapshot::query()->create([
            'competition_id' => $competition->id,
            'storage_path' => $stored->storagePath,
            'content_sha256' => $stored->contentSha256,
            'closed_by' => $president->user_id,
        ]);

        $this->assertNull($service->probe($stored->storagePath));
        Storage::disk('local')->assertExists($stored->storagePath);

        $orphan = 'zensko-decision-proposals/'.$competition->id.'/01ARZ3NDEKTSV4RRFFQ69G5FAV.html';
        Storage::disk('local')->put($orphan, '<p>pokusaj</p>');
        $this->assertNull($service->probe($orphan));
        Storage::disk('local')->assertMissing($orphan);
        Storage::disk('local')->assertExists($stored->storagePath);
    }

    private function service(ZenskoDecisionProposalHtmlStore $documents): ZenskoDecisionProposalCloseService
    {
        return new ZenskoDecisionProposalCloseService(
            $documents,
            app(ZpEqualScoreAllocationGuard::class),
            app(CanonicalIndividualScoringService::class),
        );
    }

    /**
     * @return array{0: Competition, 1: CommissionMember, 2: Application}
     */
    private function readyCompetition(): array
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

        $application = $this->addSupportedApplication($competition, $president, 'Ana Anić');

        return [$competition->fresh(), $president, $application];
    }

    private function addSupportedApplication(Competition $competition, CommissionMember $president, string $name): Application
    {
        $owner = User::factory()->create();
        $application = Application::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => $name === 'Ana Anić' ? 'Plan Ana' : 'Plan '.$name,
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => $name,
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

        $members = $competition->commission->activeMembers()->orderBy('canonical_seat_no')->get();
        foreach ($members as $member) {
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

        return $application;
    }
}
