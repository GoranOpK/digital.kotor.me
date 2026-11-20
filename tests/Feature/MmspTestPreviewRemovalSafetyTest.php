<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\Competitions\MmspTestPreviewRemoval;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MmspTestPreviewRemovalSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_database_failure_keeps_the_competition_applications_and_files(): void
    {
        $admin = $this->user('konkurs_admin');
        $preview = $this->preview('TEST 5 prekid');
        $application = $this->application($preview, $this->user('korisnik'));
        $path = 'mmsp-test/'.$application->id.'/dokaz.pdf';
        Storage::disk('local')->put($path, 'dokaz');
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Dokaz',
            'file_path' => $path,
            'type' => 'pdf',
            'document_type' => 'licna_karta',
        ]);
        $this->app->bind(MmspTestPreviewRemoval::class, function () {
            return new class extends MmspTestPreviewRemoval
            {
                protected function beforeCommit(): void
                {
                    throw new RuntimeException('forced rollback');
                }
            };
        });

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $preview))
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'UKLONI TEST '.$preview->id,
            ])
            ->assertRedirect(route('admin.competitions.show', $preview))
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(Competition::query()->find($preview->id));
        $this->assertNotNull(Application::query()->find($application->id));
        $this->assertSame(1, ApplicationDocument::query()->where('application_id', $application->id)->count());
        Storage::disk('local')->assertExists($path);
        $log = DB::table('logs')->where('action', 'mmsp_test_preview_removal')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('"result":"aborted"', (string) $log->details);
    }

    public function test_file_deletion_failure_is_recorded_after_the_database_commit(): void
    {
        $admin = $this->user('konkurs_admin');
        $preview = $this->preview('TEST 5 fajl');
        $application = $this->application($preview, $this->user('korisnik'));
        $path = 'mmsp-test/'.$application->id.'/ostaje.pdf';
        Storage::disk('local')->put($path, 'ostaje');
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Ostaje na disku',
            'file_path' => $path,
            'type' => 'pdf',
            'document_type' => 'licna_karta',
        ]);
        $this->app->bind(MmspTestPreviewRemoval::class, function () {
            return new class extends MmspTestPreviewRemoval
            {
                protected function deleteStoredFile(string $path): void
                {
                    throw new RuntimeException('disk unavailable');
                }
            };
        });

        $this->actingAs($admin)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'UKLONI TEST '.$preview->id,
            ])
            ->assertRedirect(route('admin.competitions.index'))
            ->assertSessionHas('success');

        $this->assertNull(Competition::query()->find($preview->id));
        $this->assertNull(Application::query()->find($application->id));
        Storage::disk('local')->assertExists($path);
        $log = DB::table('logs')
            ->where('action', 'mmsp_test_preview_removal')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($log);
        $details = json_decode((string) $log->details, true);
        $this->assertSame('files_partial', $details['result']);
        $this->assertSame([$path], $details['failed']);
    }

    public function test_completed_cycle_removes_only_its_rows_and_keeps_shared_documents(): void
    {
        $admin = $this->user('konkurs_admin');
        $owner = $this->user('korisnik');
        $cycle = $this->completedCycle($admin, $owner);

        $this->actingAs($admin)
            ->post(route('admin.competitions.mmsp-test-preview.remove', $cycle['preview']), [
                'confirmation' => 'UKLONI TEST '.$cycle['preview'],
            ])
            ->assertRedirect(route('admin.competitions.index'));

        $this->assertNull(Competition::query()->find($cycle['preview']));
        $this->assertNull(Application::query()->find($cycle['application']));
        foreach ([
            'canonical_applicant_forms',
            'canonical_investment_plans',
            'canonical_document_evidence',
            'evaluation_scores',
            'application_documents',
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
            'canonical_outcomes',
            'application_prigovors',
            'application_eliminatory_checks',
            'application_oral_presentations',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->where('application_id', $cycle['application'])->count(), $table);
        }
        foreach ([
            'canonical_final_rankings',
            'canonical_tie_resolutions',
            'canonical_predlogs',
            'canonical_secretariat_handoffs',
            'canonical_formal_decisions',
            'canonical_administrative_events',
            'mmsp_decision_documents',
            'competition_official_decision_copies',
            'competition_official_decision_lifecycle_events',
            'commission_sessions',
            'up_number',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->where('competition_id', $cycle['preview'])->count(), $table);
        }
        $this->assertSame(0, DB::table('notices')->where('source_id', $cycle['preview'])->count());
        $this->assertSame(0, DB::table('commission_governance_conflicts')->where('competition_id', $cycle['preview'])->count());

        $this->assertNotNull(Competition::query()->find($cycle['official']));
        $this->assertNotNull(Competition::query()->find($cycle['women']));
        $this->assertNotNull(Competition::query()->find($cycle['youth']));
        $this->assertNotNull(DB::table('commissions')->find($cycle['commission']));
        $this->assertSame(1, DB::table('commission_members')->where('commission_id', $cycle['commission'])->count());
        $this->assertSame(1, DB::table('commission_member_replacements')->where('commission_id', $cycle['commission'])->count());
        $this->assertSame(1, DB::table('commission_sessions')->where('competition_id', $cycle['official'])->count());
        $this->assertNotNull(DB::table('commission_governance_conflicts')->find($cycle['other_conflict']));
        $this->assertSame(1, DB::table('canonical_final_rankings')->where('competition_id', $cycle['official'])->count());
        $this->assertNotNull(UserDocument::query()->find($cycle['library']));
        $this->assertNotNull(Application::query()->find($cycle['other_application']));
        $this->assertSame(1, ApplicationDocument::query()->where('application_id', $cycle['other_application'])->count());

        Storage::disk('local')->assertMissing($cycle['exclusive']);
        Storage::disk('local')->assertMissing($cycle['decision']);
        Storage::disk('local')->assertExists($cycle['shared']);
        Storage::disk('local')->assertExists($cycle['library_path']);
        Storage::disk('local')->assertExists($cycle['official_decision']);
        Storage::disk('local')->assertExists($cycle['women_file']);
    }

    public function test_foreign_competition_conflict_that_references_the_test_application_stops_the_removal(): void
    {
        $admin = $this->user('konkurs_admin');
        $owner = $this->user('korisnik');
        $commissionId = DB::table('commissions')->insertGetId([
            'name' => 'Zajednička komisija',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $memberId = DB::table('commission_members')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $admin->id,
            'name' => 'Ana Anić',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('commission_member_replacements')->insert([
            'commission_id' => $commissionId,
            'seat_no' => 1,
            'canonical_seat' => 'predsjednik',
            'vacated_member_id' => $memberId,
            'vacancy_reason' => 'test',
            'vacancy_triggered_at' => now(),
            'due_at' => now()->addDays(15),
            'recorded_by_user_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $preview = $this->preview('TEST 5 tuđi sukob', ['commission_id' => $commissionId]);
        $official = $this->competition('Zvanični MMSP ostaje', 'published', ['commission_id' => $commissionId]);
        $application = $this->application($preview, $owner);
        $path = 'mmsp-test/'.$application->id.'/ostaje.pdf';
        Storage::disk('local')->put($path, 'ostaje');
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'Ostaje',
            'file_path' => $path,
            'type' => 'pdf',
            'document_type' => 'licna_karta',
        ]);
        $ownedConflict = DB::table('commission_governance_conflicts')->insertGetId([
            'commission_id' => $commissionId,
            'competition_id' => $preview->id,
            'application_id' => $application->id,
            'conflict_type' => 'applicant',
            'normalized_value' => '01019901',
            'reason' => 'Sukob testnog nacrta',
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $foreignConflict = DB::table('commission_governance_conflicts')->insertGetId([
            'commission_id' => $commissionId,
            'competition_id' => $official->id,
            'application_id' => $application->id,
            'conflict_type' => 'cross_reference',
            'normalized_value' => '01019901',
            'reason' => 'Zvanični konkurs referencira testnu prijavu',
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assessmentId = DB::table('application_completeness_assessments')->insertGetId([
            'application_id' => $application->id,
            'competition_id' => $official->id,
            'outcome' => 'complete',
            'evidence_snapshot' => json_encode(['documents' => 1]),
            'evidence_fingerprint' => hash('sha256', 'tudi'),
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $preview))
            ->post(route('admin.competitions.mmsp-test-preview.remove', $preview), [
                'confirmation' => 'UKLONI TEST '.$preview->id,
            ])
            ->assertRedirect(route('admin.competitions.show', $preview))
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull(Competition::query()->find($preview->id));
        $this->assertNotNull(Competition::query()->find($official->id));
        $this->assertNotNull(Application::query()->find($application->id));
        $this->assertSame(1, ApplicationDocument::query()->where('application_id', $application->id)->count());
        Storage::disk('local')->assertExists($path);
        $this->assertNotNull(DB::table('commissions')->find($commissionId));
        $this->assertSame(1, DB::table('commission_members')->where('commission_id', $commissionId)->count());
        $this->assertSame(1, DB::table('commission_member_replacements')->where('commission_id', $commissionId)->count());
        $this->assertSame($preview->id, (int) DB::table('commission_governance_conflicts')->where('id', $ownedConflict)->value('competition_id'));
        $this->assertSame($application->id, (int) DB::table('commission_governance_conflicts')->where('id', $ownedConflict)->value('application_id'));
        $this->assertSame($official->id, (int) DB::table('commission_governance_conflicts')->where('id', $foreignConflict)->value('competition_id'));
        $this->assertSame($application->id, (int) DB::table('commission_governance_conflicts')->where('id', $foreignConflict)->value('application_id'));
        $this->assertSame($official->id, (int) DB::table('application_completeness_assessments')->where('id', $assessmentId)->value('competition_id'));
        $this->assertSame($application->id, (int) DB::table('application_completeness_assessments')->where('id', $assessmentId)->value('application_id'));
    }

    public function test_removal_refuses_official_women_and_youth_competitions(): void
    {
        $admin = $this->user('konkurs_admin');
        $official = $this->competition('Zvanični MMSP', 'published', [
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
        ]);
        $women = $this->competition('Ženski poziv', 'draft', ['type' => 'zensko', 'is_test_preview' => true]);
        $youth = $this->competition('Omladinski poziv', 'published', ['type' => 'omladinsko']);

        foreach ([$official, $women, $youth] as $competition) {
            $this->actingAs($admin)
                ->from(route('admin.competitions.show', $competition))
                ->post(route('admin.competitions.mmsp-test-preview.remove', $competition), [
                    'confirmation' => 'UKLONI TEST '.$competition->id,
                ])
                ->assertSessionHasErrors('confirmation');
            $this->assertNotNull(Competition::query()->find($competition->id));
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $title, string $status, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => $title,
            'description' => 'Opis',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(19)->toDateString(),
            'published_at' => $status === 'published' ? now()->subDay() : null,
            'is_test_preview' => false,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function preview(string $title, array $overrides = []): Competition
    {
        return $this->competition($title, 'draft', array_merge([
            'is_test_preview' => true,
        ], $overrides));
    }

    private function application(Competition $competition, User $owner): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'applicant_type' => 'preduzetnik',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * @return array<string, int|string>
     */
    private function completedCycle(User $admin, User $owner): array
    {
        $commissionId = DB::table('commissions')->insertGetId([
            'name' => 'Zajednička komisija',
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $memberId = DB::table('commission_members')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $admin->id,
            'name' => 'Ana Anić',
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('commission_member_replacements')->insert([
            'commission_id' => $commissionId,
            'seat_no' => 1,
            'canonical_seat' => 'predsjednik',
            'vacated_member_id' => $memberId,
            'vacancy_reason' => 'test',
            'vacancy_triggered_at' => now(),
            'due_at' => now()->addDays(15),
            'recorded_by_user_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $preview = $this->preview('TEST 5 kompletan', ['commission_id' => $commissionId]);
        $official = $this->competition('Zvanični MMSP ostaje', 'published', ['commission_id' => $commissionId]);
        $women = $this->competition('Ženski poziv ostaje', 'published', ['type' => 'zensko']);
        $youth = $this->competition('Omladinski poziv ostaje', 'published', ['type' => 'omladinsko']);
        $application = $this->application($preview, $owner);
        $otherApplication = $this->application($official, $owner);

        DB::table('canonical_applicant_forms')->insert([
            'application_id' => $application->id,
            'legal_form' => 'preduzetnik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_investment_plans')->insert([
            'application_id' => $application->id,
            'total_investment' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('final_application_claims')->insert([
            'user_id' => $owner->id,
            'competition_id' => $preview->id,
            'application_id' => $application->id,
            'claimed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assessmentId = DB::table('application_completeness_assessments')->insertGetId([
            'application_id' => $application->id,
            'competition_id' => $preview->id,
            'outcome' => 'complete',
            'recorded_by_user_id' => $admin->id,
            'evidence_snapshot' => json_encode(['documents' => 1]),
            'evidence_fingerprint' => hash('sha256', 'dokaz'),
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('application_complaints')->insert([
            'application_id' => $application->id,
            'competition_id' => $preview->id,
            'completeness_assessment_id' => $assessmentId,
            'submitted_by_user_id' => $owner->id,
            'body' => 'Prigovor ostaje u testnom ciklusu.',
            'submitted_at' => now(),
            'decision_due_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('evaluation_scores')->insert([
            'application_id' => $application->id,
            'commission_member_id' => $memberId,
            'criterion_1' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('application_eliminatory_checks')->insert([
            'application_id' => $application->id,
            'criterion_1' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('application_prigovors')->insert([
            'application_id' => $application->id,
            'obrazlozenje' => 'Testni prigovor',
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by_user_id' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exclusive = 'applications/'.$application->id.'/sopstveni.pdf';
        $shared = 'shared/mmsp-isti.pdf';
        $libraryPath = 'library/licni-'.$owner->id.'.pdf';
        $womenFile = 'applications/zensko/ostaje.pdf';
        Storage::disk('local')->put($exclusive, 'sopstveni');
        Storage::disk('local')->put($shared, 'dijeljeni');
        Storage::disk('local')->put($libraryPath, 'arhiva');
        Storage::disk('local')->put($womenFile, 'zenski');
        $library = UserDocument::query()->create([
            'user_id' => $owner->id,
            'category' => 'Ostali dokumenti',
            'name' => 'Lični dokument',
            'file_path' => $libraryPath,
            'file_size' => 12,
            'status' => 'active',
        ]);
        $exclusiveDocument = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'sopstveni.pdf',
            'file_path' => $exclusive,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'isti.pdf',
            'file_path' => $shared,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'arhiva.pdf',
            'file_path' => $libraryPath,
            'document_type' => 'licna_karta',
            'user_document_id' => $library->id,
            'is_required' => false,
        ]);
        ApplicationDocument::create([
            'application_id' => $otherApplication->id,
            'name' => 'isti.pdf',
            'file_path' => $shared,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        $womenApplication = $this->application($women, $owner);
        ApplicationDocument::create([
            'application_id' => $womenApplication->id,
            'name' => 'zenski.pdf',
            'file_path' => $womenFile,
            'document_type' => 'ostalo',
            'is_required' => false,
        ]);
        DB::table('canonical_document_evidence')->insert([
            'application_document_id' => $exclusiveDocument->id,
            'application_id' => $application->id,
            'document_type' => 'ostalo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionId = DB::table('commission_sessions')->insertGetId([
            'competition_id' => $preview->id,
            'commission_id' => $commissionId,
            'session_type' => 'final',
            'held_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('commission_sessions')->insert([
            'competition_id' => $official->id,
            'commission_id' => $commissionId,
            'session_type' => 'final',
            'held_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('application_oral_presentations')->insert([
            'commission_session_id' => $sessionId,
            'application_id' => $application->id,
            'held_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('up_number')->insert([
            'competition_id' => $preview->id,
            'number' => 'UP-TEST-'.$preview->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('commission_governance_conflicts')->insert([
            'commission_id' => $commissionId,
            'user_id' => $owner->id,
            'commission_member_id' => $memberId,
            'competition_id' => $preview->id,
            'application_id' => $application->id,
            'conflict_type' => 'applicant',
            'normalized_value' => '01019901',
            'reason' => 'Testni sukob',
            'detected_at' => now(),
            'detected_by_user_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherConflict = DB::table('commission_governance_conflicts')->insertGetId([
            'commission_id' => $commissionId,
            'user_id' => $admin->id,
            'competition_id' => $official->id,
            'conflict_type' => 'member',
            'normalized_value' => '02029902',
            'reason' => 'Sukob zvaničnog konkursa',
            'detected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rankingId = DB::table('canonical_final_rankings')->insertGetId([
            'competition_id' => $preview->id,
            'commission_id' => $commissionId,
            'final_decision_session_id' => $sessionId,
            'confirmed_by_member_id' => $memberId,
            'budget' => 1000,
            'total_approved' => 500,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $officialSessionId = DB::table('commission_sessions')->where('competition_id', $official->id)->value('id');
        DB::table('canonical_final_rankings')->insert([
            'competition_id' => $official->id,
            'commission_id' => $commissionId,
            'final_decision_session_id' => $officialSessionId,
            'confirmed_by_member_id' => $memberId,
            'budget' => 2000,
            'total_approved' => 0,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_final_ranking_signatures')->insert([
            'final_ranking_id' => $rankingId,
            'commission_member_id' => $memberId,
            'user_id' => $admin->id,
            'canonical_seat_no' => 1,
            'canonical_seat' => 'predsjednik',
            'signer_name' => 'Ana Anić',
            'signed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_outcomes')->insert([
            'competition_id' => $preview->id,
            'application_id' => $application->id,
            'decision' => 'approved',
            'approved_amount' => 500,
            'recorded_by_member_id' => $memberId,
            'final_ranking_id' => $rankingId,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tieId = DB::table('canonical_tie_resolutions')->insertGetId([
            'competition_id' => $preview->id,
            'shared_rank' => 1,
            'tied_application_ids' => json_encode([$application->id]),
            'selected_application_ids' => json_encode([$application->id]),
            'status' => 'locked',
            'proposed_by_member_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_tie_votes')->insert([
            'tie_resolution_id' => $tieId,
            'commission_member_id' => $memberId,
            'user_id' => $admin->id,
            'canonical_seat_no' => 1,
            'in_favour' => true,
            'voted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $predlogId = DB::table('canonical_predlogs')->insertGetId([
            'competition_id' => $preview->id,
            'final_ranking_id' => $rankingId,
            'payload' => json_encode(['title' => 'Predlog']),
            'fingerprint' => hash('sha256', 'predlog '.$preview->id),
            'prepared_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $handoffId = DB::table('canonical_secretariat_handoffs')->insertGetId([
            'competition_id' => $preview->id,
            'predlog_id' => $predlogId,
            'recipient' => 'Sekretarijat',
            'handed_off_by_user_id' => $admin->id,
            'handed_off_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $decision = 'mmsp-decision-documents/'.$preview->id.'/odluka.docx';
        $officialDecision = 'mmsp-decision-documents/'.$official->id.'/odluka.docx';
        Storage::disk('local')->put($decision, 'odluka');
        Storage::disk('local')->put($officialDecision, 'zvanicna');
        DB::table('mmsp_decision_documents')->insert([
            'competition_id' => $preview->id,
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'storage_path' => $decision,
            'content_sha256' => hash('sha256', 'odluka'),
            'ranking_sha256' => hash('sha256', 'rang'),
        ]);
        DB::table('mmsp_decision_documents')->insert([
            'competition_id' => $official->id,
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'storage_path' => $officialDecision,
            'content_sha256' => hash('sha256', 'zvanicna'),
            'ranking_sha256' => hash('sha256', 'rang-2'),
        ]);
        $copyPath = 'competitions/'.$preview->id.'/official-decisions/odluka.pdf';
        Storage::disk('local')->put($copyPath, 'kopija');
        $copyId = DB::table('competition_official_decision_copies')->insertGetId([
            'competition_id' => $preview->id,
            'storage_path' => $copyPath,
            'uploaded_by' => $admin->id,
            'business_title' => 'Odluka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('competition_official_decision_lifecycle_events')->insert([
            'competition_official_decision_copy_id' => $copyId,
            'competition_id' => $preview->id,
            'action' => 'uploaded',
            'actor_user_id' => $admin->id,
            'created_at' => now(),
        ]);
        $noticeId = DB::table('notices')->insertGetId([
            'title' => 'Obavještenje testnog ciklusa',
            'visible_in_active_panel' => true,
            'source_type' => 'competition_decision',
            'source_id' => $preview->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_formal_decisions')->insert([
            'competition_id' => $preview->id,
            'publication_slot' => 'formal_decision',
            'predlog_id' => $predlogId,
            'handoff_id' => $handoffId,
            'decision_number' => 'MMSP-'.$preview->id,
            'decision_date' => now()->toDateString(),
            'recorded_by_user_id' => $admin->id,
            'recorded_at' => now(),
            'official_decision_copy_id' => $copyId,
            'published_notice_id' => $noticeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('canonical_administrative_events')->insert([
            'competition_id' => $preview->id,
            'action' => 'recorded',
            'actor_user_id' => $admin->id,
            'subject_type' => 'canonical_formal_decision',
            'subject_id' => $preview->id,
            'created_at' => now(),
        ]);

        return [
            'preview' => $preview->id,
            'official' => $official->id,
            'women' => $women->id,
            'youth' => $youth->id,
            'application' => $application->id,
            'other_application' => $otherApplication->id,
            'commission' => $commissionId,
            'library' => $library->id,
            'other_conflict' => $otherConflict,
            'exclusive' => $exclusive,
            'shared' => $shared,
            'library_path' => $libraryPath,
            'decision' => $decision,
            'official_decision' => $officialDecision,
            'women_file' => $womenFile,
        ];
    }
}
