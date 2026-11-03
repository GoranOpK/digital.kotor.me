<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\Competitions\ArchivedTestCompetitionRemoval;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ArchivedTestCompetitionRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_superadmin_previews_and_removes_only_the_confirmed_competition(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->closedCompetition('omladinsko', 'Testni PM konkurs', 'closed', true);
        $other = $this->closedCompetition('omladinsko', 'Drugi PM konkurs');
        $this->attachYouthDecision($target, $superadmin);
        $this->attachYouthDecision($other, $superadmin);
        $targetApplication = $this->application($target, $superadmin);
        $otherApplication = $this->application($other, $superadmin);
        $exclusive = 'applications/'.$targetApplication->id.'/sopstveni.pdf';
        $shared = 'shared/isti.pdf';
        $otherFile = 'applications/'.$otherApplication->id.'/ostaje.pdf';
        Storage::disk('local')->put($exclusive, 'sopstveni');
        Storage::disk('local')->put($shared, 'dijeljeni');
        Storage::disk('local')->put($otherFile, 'drugi');
        ApplicationDocument::create([
            'application_id' => $targetApplication->id,
            'name' => 'sopstveni.pdf',
            'file_path' => $exclusive,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $targetApplication->id,
            'name' => 'isti.pdf',
            'file_path' => $shared,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $otherApplication->id,
            'name' => 'isti.pdf',
            'file_path' => $shared,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $otherApplication->id,
            'name' => 'ostaje.pdf',
            'file_path' => $otherFile,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        $member = $this->commissionMember($superadmin);
        $this->equalScoreLink($target, $targetApplication, $superadmin, $member);
        $otherGroup = $this->equalScoreLink($other, $otherApplication, $superadmin, $member);

        $preview = $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.preview'), [
                'competition_id' => $target->id,
            ])
            ->assertOk();
        $preview->assertSee('Testni PM konkurs');
        $preview->assertSee('youth-decision-documents/'.$target->id.'/odluka.docx');
        $preview->assertSee($exclusive);
        $preview->assertSee($shared);
        $preview->assertSee('zadržati');
        $preview->assertSee('UKLONI '.$target->id);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-competition.show'))
            ->assertSessionHas('success');

        $this->assertNull(Competition::query()->find($target->id));
        $this->assertNull(Application::query()->find($targetApplication->id));
        $this->assertSame(0, DB::table('youth_decision_documents')->where('competition_id', $target->id)->count());
        $this->assertSame(0, DB::table('youth_equal_score_groups')->where('competition_id', $target->id)->count());
        $this->assertSame(0, DB::table('application_documents')->where('application_id', $targetApplication->id)->count());
        $this->assertNotNull(Competition::query()->find($other->id));
        $this->assertNotNull(Application::query()->find($otherApplication->id));
        $this->assertSame(1, DB::table('youth_decision_documents')->where('competition_id', $other->id)->count());
        $this->assertSame(1, DB::table('youth_equal_score_groups')->where('id', $otherGroup)->count());
        $this->assertNotNull(User::query()->find($superadmin->id));
        $this->assertNotNull(DB::table('commission_members')->where('id', $member)->first());
        Storage::disk('local')->assertMissing($exclusive);
        Storage::disk('local')->assertMissing('youth-decision-documents/'.$target->id.'/odluka.docx');
        Storage::disk('local')->assertExists($shared);
        Storage::disk('local')->assertExists($otherFile);
        Storage::disk('local')->assertExists('youth-decision-documents/'.$other->id.'/odluka.docx');

        $log = DB::table('logs')
            ->where('user_id', $superadmin->id)
            ->where('action', 'archived_test_competition_removal')
            ->get()
            ->first(fn ($row): bool => str_contains((string) $row->details, '"result":"database_removed"'));
        $this->assertNotNull($log);
        $this->assertStringContainsString('"competition_id":'.$target->id, (string) $log->details);
        $this->assertStringNotContainsString('"competition_id":'.$other->id, (string) $log->details);
    }

    public function test_other_roles_and_direct_requests_cannot_start_removal(): void
    {
        $target = $this->closedCompetition('omladinsko', 'Zatvoreni konkurs');

        foreach (['admin', 'konkurs_admin', 'komisija', 'korisnik'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)
                ->get(route('admin.archive-test-competition.show'))
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('admin.archive-test-competition.preview'), [
                    'competition_id' => $target->id,
                ])
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('admin.archive-test-competition.destroy'), [
                    'competition_id' => $target->id,
                    'confirmation' => 'UKLONI '.$target->id,
                ])
                ->assertForbidden();
        }

        $this->post(route('admin.archive-test-competition.destroy'), [
            'competition_id' => $target->id,
            'confirmation' => 'UKLONI '.$target->id,
        ])->assertForbidden();

        $this->assertNotNull(Competition::query()->find($target->id));
    }

    public function test_confirmation_status_type_and_mass_id_are_rejected(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->closedCompetition('omladinsko', 'Zatvoreni konkurs');
        $published = $this->closedCompetition('omladinsko', 'Otvoreni konkurs', 'published');
        $mmsp = $this->closedCompetition(
            CompetitionProfileResolver::TYPE_CANONICAL,
            'MMSP konkurs'
        );

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI 999999',
            ])
            ->assertStatus(422);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => [$target->id, $published->id],
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertStatus(422);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $published->id,
                'confirmation' => 'UKLONI '.$published->id,
            ])
            ->assertStatus(422);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.preview'), [
                'competition_id' => $mmsp->id,
            ])
            ->assertOk()
            ->assertSee('dozvoljeno samo za konkurs koji je izričito označen kao testni');
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $mmsp->id,
                'confirmation' => 'UKLONI '.$mmsp->id,
            ])
            ->assertStatus(422);

        $this->assertNotNull(Competition::query()->find($target->id));
        $this->assertNotNull(Competition::query()->find($published->id));
        $this->assertNotNull(Competition::query()->find($mmsp->id));
    }

    public function test_database_failure_does_not_leave_partial_rows_or_delete_files(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->closedCompetition('omladinsko', 'Konkurs za prekid', 'closed', true);
        $this->attachYouthDecision($target, $superadmin);
        $this->app->bind(ArchivedTestCompetitionRemoval::class, function () {
            return new class extends ArchivedTestCompetitionRemoval
            {
                protected function beforeCommit(): void
                {
                    throw new RuntimeException('forced rollback');
                }
            };
        });

        $this->actingAs($superadmin)
            ->from(route('admin.archive-test-competition.show'))
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-competition.show'))
            ->assertSessionHasErrors('competition_id');

        $this->assertNotNull(Competition::query()->find($target->id));
        $this->assertSame(1, DB::table('youth_decision_documents')->where('competition_id', $target->id)->count());
        Storage::disk('local')->assertExists('youth-decision-documents/'.$target->id.'/odluka.docx');
        $log = DB::table('logs')->where('action', 'archived_test_competition_removal')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('"result":"aborted"', (string) $log->details);
    }

    public function test_zensko_snapshot_is_removed_without_touching_the_other_competition(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->closedCompetition('zensko', 'Testni ŽP konkurs', 'closed', true);
        $other = $this->closedCompetition('zensko', 'Drugi ŽP konkurs');
        $this->attachZenskoSnapshot($target, $superadmin);
        $this->attachZenskoSnapshot($other, $superadmin);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertRedirect();

        $this->assertNull(Competition::query()->find($target->id));
        $this->assertSame(0, DB::table('zensko_decision_proposal_snapshots')->where('competition_id', $target->id)->count());
        $this->assertNotNull(Competition::query()->find($other->id));
        $this->assertSame(1, DB::table('zensko_decision_proposal_snapshots')->where('competition_id', $other->id)->count());
        Storage::disk('local')->assertMissing('zensko-decision-proposals/'.$target->id.'/predlog.html');
        Storage::disk('local')->assertExists('zensko-decision-proposals/'.$other->id.'/predlog.html');
    }

    public function test_regular_archive_stays_visible_and_draft_delete_still_works(): void
    {
        $admin = $this->user('admin');
        $konkursAdmin = $this->user('konkurs_admin');
        $superadmin = $this->user('superadmin');
        $plain = $this->closedCompetition('zensko', 'Običan zatvoreni ŽP');
        $kept = $this->closedCompetition('omladinsko', 'PM koji ostaje');
        $draft = $this->closedCompetition('zensko', 'Nacrt ŽP', 'draft');

        $this->actingAs($konkursAdmin)
            ->get(route('competitions.archive'))
            ->assertOk()
            ->assertSee('Običan zatvoreni ŽP')
            ->assertSee('PM koji ostaje')
            ->assertDontSee('obrišete ovaj konkurs iz arhive', false);

        foreach ([$admin, $konkursAdmin, $superadmin] as $actor) {
            $this->actingAs($actor)
                ->delete(route('admin.competitions.destroy', $plain))
                ->assertForbidden()
                ->assertSee('Arhivirani konkurs ne može da se obriše redovnim brisanjem.', false);
        }

        $this->assertNotNull(Competition::query()->find($plain->id));
        $this->assertNotNull(Competition::query()->find($kept->id));

        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $draft))
            ->assertRedirect(route('admin.competitions.index'));
        $this->assertNull(Competition::query()->find($draft->id));
    }

    public function test_superadmin_dashboard_links_to_the_separate_procedure(): void
    {
        $this->actingAs($this->user('superadmin'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Uklanjanje testnog konkursa iz arhive');
        $this->actingAs($this->user('admin'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Uklanjanje testnog konkursa iz arhive');
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function closedCompetition(string $type, string $title, string $status = 'closed', bool $isTest = false): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'type' => $type,
            'status' => $status,
            'is_test' => $isTest,
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
        ]);
    }

    private function application(Competition $competition, User $user): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan '.$competition->id,
            'applicant_type' => 'fizicko_lice',
            'company_legal_form' => null,
            'business_stage' => 'započinjanje',
            'business_area' => 'Usluge',
            'is_registered' => false,
            'status' => 'submitted',
        ]);
    }

    private function attachYouthDecision(Competition $competition, User $actor): void
    {
        $path = 'youth-decision-documents/'.$competition->id.'/odluka.docx';
        Storage::disk('local')->put($path, 'PK odluka '.$competition->id);
        YouthDecisionDocument::query()->create([
            'competition_id' => $competition->id,
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'storage_path' => $path,
            'content_sha256' => hash('sha256', 'odluka '.$competition->id),
            'ranking_sha256' => hash('sha256', 'rang '.$competition->id),
        ]);
    }

    private function attachZenskoSnapshot(Competition $competition, User $actor): void
    {
        $path = 'zensko-decision-proposals/'.$competition->id.'/predlog.html';
        Storage::disk('local')->put($path, '<html>'.$competition->id.'</html>');
        $snapshotId = DB::table('zensko_decision_proposal_snapshots')->insertGetId([
            'competition_id' => $competition->id,
            'storage_path' => $path,
            'content_sha256' => hash('sha256', 'predlog '.$competition->id),
            'closed_by' => $actor->id,
            'created_at' => now(),
        ]);
        DB::table('competitions')
            ->where('id', $competition->id)
            ->update(['zensko_decision_proposal_snapshot_id' => $snapshotId]);
    }

    private function commissionMember(User $user): int
    {
        $commissionId = DB::table('commissions')->insertGetId([
            'name' => 'Komisija za test',
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('commission_members')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $user->id,
            'name' => 'Ana Anić',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function equalScoreLink(Competition $competition, Application $application, User $user, int $memberId): int
    {
        $groupId = DB::table('youth_equal_score_groups')->insertGetId([
            'competition_id' => $competition->id,
            'group_key' => 'grupa-'.$competition->id,
            'full_score' => 10,
            'ranking_position' => 1,
            'applied_rule' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roundId = DB::table('youth_equal_score_rounds')->insertGetId([
            'group_id' => $groupId,
            'round_no' => 1,
            'justification' => 'Test',
            'budget_before' => 1000,
            'proposal_total' => 500,
            'created_by_user_id' => $user->id,
            'created_by_member_id' => $memberId,
            'created_by_name' => 'Ana Anić',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('youth_equal_score_round_applications')->insert([
            'round_id' => $roundId,
            'application_id' => $application->id,
            'business_stage' => 'započinjanje',
            'draft_amount' => 100,
            'selected' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $groupId;
    }
}
