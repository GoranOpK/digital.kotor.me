<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\ArchivedTestCompetitionRemoval;
use App\Services\Competitions\Lifecycle\PublishedCallLifecycleLock;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ArchivedMmspTestCompetitionRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        config(['archived_test_competitions.mark_ids' => '']);
    }

    public function test_completed_mmsp_without_test_flag_cannot_be_removed(): void
    {
        $superadmin = $this->user('superadmin');
        $competition = $this->competition('completed', 'MMSP bez oznake', false, true);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.preview'), [
                'competition_id' => $competition->id,
            ])
            ->assertOk()
            ->assertSee('dozvoljeno samo za konkurs koji je izričito označen kao testni')
            ->assertDontSee('Trajno ukloni ovaj konkurs', false);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $competition->id,
                'confirmation' => 'UKLONI '.$competition->id,
            ])
            ->assertStatus(422);

        $this->assertNotNull(Competition::query()->find($competition->id));
        $this->assertFalse((bool) $competition->fresh()->is_test);
        $this->assertTrue((bool) $competition->fresh()->is_test_preview);
    }

    public function test_empty_allowlist_rejects_marking_and_exact_id_keeps_preview_flag(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('completed', 'MMSP za oznaku', false, true);
        $other = $this->competition('completed', 'Drugi MMSP', false, true);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.preview'), [
                'competition_id' => $target->id,
            ])
            ->assertOk()
            ->assertSee('nije na spisku odobrenom za naknadno označavanje')
            ->assertDontSee('Označi samo ovaj konkurs', false);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertStatus(422);

        $this->assertFalse((bool) $target->fresh()->is_test);
        $this->assertTrue((bool) $target->fresh()->is_test_preview);

        config(['archived_test_competitions.mark_ids' => (string) $target->id]);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-mark.show'));

        $target->refresh();
        $other->refresh();
        $this->assertTrue((bool) $target->is_test);
        $this->assertTrue((bool) $target->is_test_preview);
        $this->assertSame('MMSP za oznaku', $target->title);
        $this->assertSame('completed', $target->status);
        $this->assertFalse((bool) $other->is_test);
        $this->assertTrue((bool) $other->is_test_preview);

        $log = DB::table('logs')
            ->where('action', 'archived_test_competition_marked')
            ->get()
            ->first(fn ($row): bool => str_contains((string) $row->details, '"result":"marked"'));
        $this->assertNotNull($log);
        $this->assertStringContainsString('"competition_id":'.$target->id, (string) $log->details);
    }

    public function test_completed_test_mmsp_removes_only_its_own_records_and_files(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('completed', 'TEST', true, true);
        $other = $this->competition('completed', 'Stvarni MMSP', false, true);
        $graph = $this->canonicalGraph($target, $other, $superadmin);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-competition.show'))
            ->assertSessionHas('success');

        $this->assertNull(Competition::query()->find($target->id));
        $this->assertNull(Application::query()->find($graph['target_application']));
        foreach ([
            'canonical_formal_decisions',
            'canonical_administrative_events',
            'canonical_secretariat_handoffs',
            'canonical_predlogs',
            'canonical_outcomes',
            'canonical_tie_resolutions',
            'canonical_final_rankings',
            'competition_official_decision_copies',
            'competition_official_decision_lifecycle_events',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->where('competition_id', $target->id)->count(), $table);
        }
        $this->assertSame(0, DB::table('notices')->where('source_id', $target->id)->where('source_type', 'competition_decision')->count());
        $this->assertSame(0, DB::table('canonical_tie_votes')->where('tie_resolution_id', $graph['tie'])->count());
        $this->assertSame(0, DB::table('canonical_final_ranking_signatures')->where('final_ranking_id', $graph['ranking'])->count());
        $this->assertSame(0, DB::table('evaluation_scores')->where('application_id', $graph['target_application'])->count());

        $this->assertNotNull(Competition::query()->find($other->id));
        $this->assertTrue((bool) $other->fresh()->is_test_preview);
        $this->assertFalse((bool) $other->fresh()->is_test);
        $this->assertNotNull(Application::query()->find($graph['other_application']));
        $this->assertSame(1, DB::table('notices')->where('id', $graph['other_notice'])->count());
        $this->assertSame(1, DB::table('competition_official_decision_copies')->where('competition_id', $other->id)->count());
        $this->assertNotNull(User::query()->find($superadmin->id));
        $this->assertNotNull(User::query()->find($graph['applicant']));
        $this->assertNotNull(DB::table('commission_members')->where('id', $graph['member'])->first());
        $this->assertNotNull(DB::table('commissions')->where('id', $graph['commission'])->first());

        Storage::disk('local')->assertMissing($graph['target_pdf']);
        Storage::disk('local')->assertMissing($graph['exclusive']);
        Storage::disk('local')->assertExists($graph['shared']);
        Storage::disk('local')->assertExists($graph['other_pdf']);
        Storage::disk('local')->assertExists($graph['other_file']);
    }

    public function test_notice_of_another_competition_stops_removal_without_deleting_either_side(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('completed', 'MMSP koji se ne dira', true, false);
        $other = $this->competition('completed', 'MMSP koji drži vezu', false, true);
        $applicant = $this->user('korisnik');
        $application = $this->application($target, $applicant);
        $pdf = 'competitions/'.$target->id.'/official-decisions/odluka.pdf';
        Storage::disk('local')->put($pdf, 'pdf');
        $copyId = DB::table('competition_official_decision_copies')->insertGetId([
            'competition_id' => $target->id,
            'storage_path' => $pdf,
            'uploaded_by' => $superadmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ownNotice = $this->notice($target->id, 'Obavještenje cilja', null, $copyId);
        $foreignNotice = $this->notice($other->id, 'Obavještenje drugog konkursa', null, $copyId);
        $otherPdf = 'competitions/'.$other->id.'/official-decisions/ostaje.pdf';
        Storage::disk('local')->put($otherPdf, 'ostaje');

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.preview'), [
                'competition_id' => $target->id,
            ])
            ->assertOk()
            ->assertSee('Uklanjanje je zaustavljeno jer obavještenje ili dokument drugog konkursa zavisi')
            ->assertDontSee('Trajno ukloni ovaj konkurs', false);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $target->id,
                'confirmation' => 'UKLONI '.$target->id,
            ])
            ->assertStatus(422);

        $this->assertNotNull(Competition::query()->find($target->id));
        $this->assertNotNull(Competition::query()->find($other->id));
        $this->assertNotNull(Application::query()->find($application->id));
        $this->assertSame(1, DB::table('notices')->where('id', $ownNotice)->count());
        $this->assertSame(1, DB::table('notices')->where('id', $foreignNotice)->count());
        $this->assertSame(1, DB::table('competition_official_decision_copies')->where('id', $copyId)->count());
        $this->assertTrue((bool) $other->fresh()->is_test_preview);
        Storage::disk('local')->assertExists($pdf);
        Storage::disk('local')->assertExists($otherPdf);
    }

    public function test_database_failure_restores_mmsp_rows_and_keeps_the_file(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('completed', 'MMSP za prekid', true, true);
        $other = $this->competition('completed', 'MMSP pored prekida', false, false);
        $graph = $this->canonicalGraph($target, $other, $superadmin);
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
        $this->assertSame(1, DB::table('canonical_formal_decisions')->where('competition_id', $target->id)->count());
        $this->assertSame(2, DB::table('notices')->where('source_id', $target->id)->where('source_type', 'competition_decision')->count());
        $this->assertNotNull(Application::query()->find($graph['target_application']));
        $this->assertNotNull(Competition::query()->find($other->id));
        Storage::disk('local')->assertExists($graph['target_pdf']);
        Storage::disk('local')->assertExists($graph['exclusive']);
        Storage::disk('local')->assertExists($graph['other_pdf']);
    }

    public function test_published_real_mmsp_stays_protected(): void
    {
        $admin = $this->user('admin');
        $superadmin = $this->user('superadmin');
        $published = $this->competition('published', 'Objavljeni MMSP', false, false);

        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $published))
            ->assertForbidden()
            ->assertSee(PublishedCallLifecycleLock::DELETE_PUBLISHED_MESSAGE, false);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $published->id,
                'confirmation' => 'UKLONI '.$published->id,
            ])
            ->assertStatus(422);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $published->id,
                'confirmation' => 'OZNACI '.$published->id,
            ])
            ->assertStatus(422);

        $published->refresh();
        $this->assertFalse((bool) $published->is_test);
        $this->assertFalse((bool) $published->is_test_preview);
        $this->assertSame('published', $published->status);
        $this->assertSame('Objavljeni MMSP', $published->title);
    }

    public function test_archive_pages_name_pm_zp_and_mmsp(): void
    {
        $superadmin = $this->user('superadmin');

        $this->actingAs($superadmin)
            ->get(route('admin.archive-test-mark.show'))
            ->assertOk()
            ->assertSee('preduzetništvu mladih')
            ->assertSee('ženskom preduzetništvu')
            ->assertSee('mikro, malim i srednjim preduzećima');
        $this->actingAs($superadmin)
            ->get(route('admin.archive-test-competition.show'))
            ->assertOk()
            ->assertSee('preduzetništvu mladih')
            ->assertSee('ženskom preduzetništvu')
            ->assertSee('mikro, malim i srednjim preduzećima');
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $status, string $title, bool $isTest, bool $isTestPreview): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => $status,
            'is_test' => $isTest,
            'is_test_preview' => $isTestPreview,
            'year' => (int) now()->year,
            'budget' => 1000,
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

    /**
     * @return array<string, int|string>
     */
    private function canonicalGraph(Competition $target, Competition $other, User $actor): array
    {
        $applicant = $this->user('korisnik');
        $targetApplication = $this->application($target, $applicant);
        $otherApplication = $this->application($other, $applicant);
        $commissionId = DB::table('commissions')->insertGetId([
            'name' => 'Komisija MMSP',
            'year' => (int) now()->year,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $memberId = DB::table('commission_members')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $actor->id,
            'name' => 'Ana Anić',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('evaluation_scores')->insert([
            'application_id' => $targetApplication->id,
            'commission_member_id' => $memberId,
            'criterion_1' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exclusive = 'applications/'.$targetApplication->id.'/sopstveni.pdf';
        $shared = 'shared/mmsp-isti.pdf';
        $otherFile = 'applications/'.$otherApplication->id.'/ostaje.pdf';
        Storage::disk('local')->put($exclusive, 'sopstveni');
        Storage::disk('local')->put($shared, 'dijeljeni');
        Storage::disk('local')->put($otherFile, 'drugi');
        foreach ([
            [$targetApplication->id, $exclusive, 'sopstveni.pdf'],
            [$targetApplication->id, $shared, 'isti.pdf'],
            [$otherApplication->id, $shared, 'isti.pdf'],
            [$otherApplication->id, $otherFile, 'ostaje.pdf'],
        ] as [$applicationId, $path, $name]) {
            ApplicationDocument::create([
                'application_id' => $applicationId,
                'name' => $name,
                'file_path' => $path,
                'document_type' => 'ostalo',
                'is_required' => false,
            ]);
        }

        $sessionId = DB::table('commission_sessions')->insertGetId([
            'competition_id' => $target->id,
            'commission_id' => $commissionId,
            'session_type' => 'final',
            'held_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rankingId = DB::table('canonical_final_rankings')->insertGetId([
            'competition_id' => $target->id,
            'commission_id' => $commissionId,
            'final_decision_session_id' => $sessionId,
            'confirmed_by_member_id' => $memberId,
            'budget' => 1000,
            'total_approved' => 500,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_final_ranking_signatures')->insert([
            'final_ranking_id' => $rankingId,
            'commission_member_id' => $memberId,
            'user_id' => $actor->id,
            'canonical_seat_no' => 1,
            'canonical_seat' => 'predsjednik',
            'signer_name' => 'Ana Anić',
            'signed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_outcomes')->insert([
            'competition_id' => $target->id,
            'application_id' => $targetApplication->id,
            'decision' => 'approved',
            'approved_amount' => 500,
            'recorded_by_member_id' => $memberId,
            'final_ranking_id' => $rankingId,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tieId = DB::table('canonical_tie_resolutions')->insertGetId([
            'competition_id' => $target->id,
            'shared_rank' => 1,
            'tied_application_ids' => json_encode([$targetApplication->id]),
            'selected_application_ids' => json_encode([$targetApplication->id]),
            'status' => 'locked',
            'proposed_by_member_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_tie_votes')->insert([
            'tie_resolution_id' => $tieId,
            'commission_member_id' => $memberId,
            'user_id' => $actor->id,
            'canonical_seat_no' => 1,
            'in_favour' => true,
            'voted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $predlogId = DB::table('canonical_predlogs')->insertGetId([
            'competition_id' => $target->id,
            'final_ranking_id' => $rankingId,
            'payload' => json_encode(['title' => 'Predlog']),
            'fingerprint' => hash('sha256', 'predlog '.$target->id),
            'prepared_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $handoffId = DB::table('canonical_secretariat_handoffs')->insertGetId([
            'competition_id' => $target->id,
            'predlog_id' => $predlogId,
            'recipient' => 'Sekretarijat',
            'handed_off_by_user_id' => $actor->id,
            'handed_off_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetPdf = 'competitions/'.$target->id.'/official-decisions/odluka.pdf';
        $otherPdf = 'competitions/'.$other->id.'/official-decisions/odluka.pdf';
        Storage::disk('local')->put($targetPdf, 'odluka '.$target->id);
        Storage::disk('local')->put($otherPdf, 'odluka '.$other->id);
        $copyId = DB::table('competition_official_decision_copies')->insertGetId([
            'competition_id' => $target->id,
            'storage_path' => $targetPdf,
            'uploaded_by' => $actor->id,
            'business_title' => 'Odluka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('competition_official_decision_copies')->insert([
            'competition_id' => $other->id,
            'storage_path' => $otherPdf,
            'uploaded_by' => $actor->id,
            'business_title' => 'Odluka drugog',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('competition_official_decision_lifecycle_events')->insert([
            'competition_official_decision_copy_id' => $copyId,
            'competition_id' => $target->id,
            'action' => 'uploaded',
            'actor_user_id' => $actor->id,
            'created_at' => now(),
        ]);
        $olderNotice = $this->notice($target->id, 'Starije obavještenje', null, null);
        $newerNotice = $this->notice($target->id, 'Novije obavještenje', $olderNotice, $copyId);
        $otherNotice = $this->notice($other->id, 'Obavještenje drugog MMSP', null, null);
        DB::table('canonical_formal_decisions')->insert([
            'competition_id' => $target->id,
            'publication_slot' => 'formal_decision',
            'predlog_id' => $predlogId,
            'handoff_id' => $handoffId,
            'decision_number' => 'MMSP-'.$target->id,
            'decision_date' => now()->toDateString(),
            'recorded_by_user_id' => $actor->id,
            'recorded_at' => now(),
            'official_decision_copy_id' => $copyId,
            'published_notice_id' => $olderNotice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_administrative_events')->insert([
            'competition_id' => $target->id,
            'action' => 'recorded',
            'actor_user_id' => $actor->id,
            'subject_type' => 'canonical_formal_decision',
            'subject_id' => $target->id,
            'created_at' => now(),
        ]);

        return [
            'target_application' => $targetApplication->id,
            'other_application' => $otherApplication->id,
            'applicant' => $applicant->id,
            'commission' => $commissionId,
            'member' => $memberId,
            'ranking' => $rankingId,
            'tie' => $tieId,
            'other_notice' => $otherNotice,
            'newer_notice' => $newerNotice,
            'target_pdf' => $targetPdf,
            'other_pdf' => $otherPdf,
            'exclusive' => $exclusive,
            'shared' => $shared,
            'other_file' => $otherFile,
        ];
    }

    private function notice(int $competitionId, string $title, ?int $supersededId, ?int $copyId): int
    {
        return DB::table('notices')->insertGetId([
            'title' => $title,
            'visible_in_active_panel' => true,
            'source_type' => 'competition_decision',
            'source_id' => $competitionId,
            'content_delivery' => 'competition_decision_signed_copy',
            'published_at' => now(),
            'superseded_notice_id' => $supersededId,
            'source_object_id' => $copyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
