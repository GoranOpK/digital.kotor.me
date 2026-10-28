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
use App\Services\CanonicalIndividualScoringService;
use App\Services\Competitions\CompetitionDecisionDocumentBuilder;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\Competitions\YouthAllocationDraftService;
use App\Services\Competitions\ZenskoDecisionProposalHtmlReader;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ZpDecisionProposalArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_open_competition_uses_the_live_decision(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        [$competition, $president, $application] = $this->readyCompetition();
        $application->update(['preduzetnik_name' => 'Mira Mirić']);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Preduzetnica Mira Mirić.', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee(ZenskoDecisionProposalHtmlReader::LEGACY_NOTICE, false);
        $response->assertSee('Štampaj Odluku', false);
        $response->assertSee('Nazad na rang listu', false);
        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_closed_competition_shows_the_stored_html(): void
    {
        [$competition, $president] = $this->closeCompetition();

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Preduzetnica Ana Anić.', false);
        $response->assertSee('45,00', false);
        $response->assertSee('1.000,00', false);
        $response->assertSee('Štampaj Odluku', false);
        $response->assertSee('Nazad na rang listu', false);
        $response->assertDontSee(ZenskoDecisionProposalHtmlReader::LEGACY_NOTICE, false);
        $response->assertDontSee('zensko-decision-proposals', false);
    }

    public function test_later_name_change_does_not_change_the_archive(): void
    {
        [$competition, $president, $application] = $this->closeCompetition();
        $application->update(['preduzetnik_name' => 'Mira Mirić']);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee('Preduzetnica Mira Mirić.', false);
    }

    public function test_later_score_change_does_not_change_the_archive(): void
    {
        [$competition, $president, $application] = $this->closeCompetition();
        $application->update(['final_score' => 12.34]);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('45,00', false);
        $response->assertDontSee('12,34', false);
    }

    public function test_later_amount_change_does_not_change_the_archive(): void
    {
        [$competition, $president, $application] = $this->closeCompetition();
        $application->update(['approved_amount' => 50]);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('1.000,00', false);
        $response->assertDontSee('50,00', false);
    }

    public function test_archive_view_does_not_call_build(): void
    {
        [$competition, $president] = $this->closeCompetition();
        $this->forbidDecisionBuild();

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk()
            ->assertSee('Preduzetnica Ana Anić.', false);
    }

    public function test_missing_file_blocks_the_archive(): void
    {
        [$competition, $president, , $snapshot] = $this->closeCompetition();
        $this->forbidDecisionBuild();
        Storage::disk('local')->delete($snapshot->storage_path);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Sačuvani Predlog odluke nije moguće prikazati jer provjera integriteta dokumenta nije uspjela.', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee('zensko-decision-proposals', false);
        $response->assertDontSee($snapshot->storage_path, false);
    }

    public function test_changed_bytes_block_the_archive(): void
    {
        [$competition, $president, , $snapshot] = $this->closeCompetition();
        $this->forbidDecisionBuild();
        Storage::disk('local')->put($snapshot->storage_path, 'TAMPERED-MARKER');

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Sačuvani Predlog odluke nije moguće prikazati jer provjera integriteta dokumenta nije uspjela.', false);
        $response->assertDontSee('TAMPERED-MARKER', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee($snapshot->storage_path, false);
    }

    public function test_competition_id_mismatch_blocks_the_archive(): void
    {
        [$competition, $president, , $snapshot] = $this->closeCompetition();
        $this->forbidDecisionBuild();
        $other = Competition::query()->create([
            'title' => 'Drugi konkurs',
            'description' => 'Opis',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 1000,
            'deadline_days' => 20,
        ]);
        DB::table('zensko_decision_proposal_snapshots')
            ->where('id', $snapshot->id)
            ->update(['competition_id' => $other->id]);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Sačuvani Predlog odluke nije moguće prikazati jer provjera integriteta dokumenta nije uspjela.', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee($snapshot->storage_path, false);
    }

    public function test_missing_snapshot_row_blocks_the_archive(): void
    {
        [$competition, $president, , $snapshot] = $this->closeCompetition();
        $this->forbidDecisionBuild();
        Schema::disableForeignKeyConstraints();
        try {
            DB::table('zensko_decision_proposal_snapshots')->where('id', $snapshot->id)->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition->fresh()));

        $response->assertOk();
        $response->assertSee('Sačuvani Predlog odluke nije moguće prikazati jer provjera integriteta dokumenta nije uspjela.', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $response->assertDontSee($snapshot->storage_path, false);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_legacy_closed_competition_shows_the_live_notice_without_creating_a_snapshot(): void
    {
        [$competition, $president, $application] = $this->readyCompetition();
        $competition->forceFill([
            'status' => 'completed',
            'closed_at' => now(),
        ])->save();
        $application->update(['preduzetnik_name' => 'Mira Mirić']);

        $response = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $response->assertOk();
        $response->assertSee('Ovo nije trajno sačuvan Predlog odluke. Prikaz je izrađen iz trenutnih podataka.', false);
        $response->assertSee('Preduzetnica Mira Mirić.', false);
        $response->assertDontSee('Preduzetnica Ana Anić.', false);
        $this->assertNull($competition->fresh()->zensko_decision_proposal_snapshot_id);
        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unauthorized_user_cannot_open_the_stored_document(): void
    {
        [$competition, $president, , $snapshot] = $this->closeCompetition();
        $outsider = User::factory()->create([
            'role_id' => Role::query()->where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $citizen = User::factory()->create([
            'role_id' => Role::query()->where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->get(route('admin.competitions.decision', $competition))
            ->assertForbidden()
            ->assertDontSee('Preduzetnica Ana Anić.', false)
            ->assertDontSee($snapshot->storage_path, false);

        $this->actingAs($citizen)
            ->get(route('admin.competitions.decision', $competition))
            ->assertForbidden()
            ->assertDontSee('Preduzetnica Ana Anić.', false)
            ->assertDontSee($snapshot->storage_path, false);

        $this->assertNotSame($outsider->id, $president->user_id);
    }

    public function test_youth_and_canonical_competitions_keep_their_existing_decision_block(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::query()->where('name', 'admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
        $youth = $this->bareCompetition('omladinsko');
        $canonical = $this->bareCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        $this->actingAs($admin)
            ->get(route('admin.competitions.decision', $youth))
            ->assertForbidden()
            ->assertSee(YouthAllocationDraftService::WOMEN_PREDLOG_CLOSED_MESSAGE, false);

        $this->actingAs($admin)
            ->get(route('admin.competitions.decision', $canonical))
            ->assertForbidden()
            ->assertSee(CanonicalIndividualScoringService::CANONICAL_OUTCOME_NOT_ACTIVE_MESSAGE, false);

        $this->assertSame(0, ZenskoDecisionProposalSnapshot::query()->count());
    }

    public function test_public_html_notice_stays_on_the_live_document(): void
    {
        [$competition, $president, $application, $snapshot] = $this->closeCompetition();
        $application->update(['preduzetnik_name' => 'Mira Mirić']);
        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'content_delivery' => 'competition_decision_html',
            'publicly_available' => true,
        ]);

        $public = $this->get(route('notices.public-content', $notice));
        $archive = $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition));

        $public->assertOk();
        $public->assertSee('Preduzetnica Mira Mirić.', false);
        $public->assertDontSee('Preduzetnica Ana Anić.', false);
        $public->assertDontSee($snapshot->storage_path, false);
        $public->assertDontSee('Nazad na rang listu', false);
        $archive->assertOk();
        $archive->assertSee('Preduzetnica Ana Anić.', false);
        $archive->assertDontSee('Preduzetnica Mira Mirić.', false);
    }

    public function test_signed_pdf_copy_is_unchanged_by_the_archive_view(): void
    {
        [$competition, $president] = $this->closeCompetition();
        $pdfPath = 'competitions/decisions/signed.pdf';
        $pdf = "%PDF-1.4\nunchanged-signed-copy";
        Storage::disk('local')->put($pdfPath, $pdf);
        $copy = CompetitionOfficialDecisionCopy::query()->create([
            'competition_id' => $competition->id,
            'storage_path' => $pdfPath,
            'business_title' => 'Potpisana odluka',
        ]);
        $notice = Notice::factory()->create([
            'source_type' => 'competition_decision',
            'source_id' => $competition->id,
            'source_object_id' => $copy->id,
            'content_delivery' => 'competition_decision_signed_copy',
            'publicly_available' => true,
        ]);

        $this->actingAs($president->user)
            ->get(route('admin.competitions.decision', $competition))
            ->assertOk()
            ->assertSee('Preduzetnica Ana Anić.', false);

        $download = $this->get(route('notices.public-content', $notice));
        $download->assertOk();
        $this->assertSame($pdf, Storage::disk('local')->get($pdfPath));
        $this->assertSame($pdfPath, $copy->fresh()->storage_path);
        $this->assertSame('Potpisana odluka', $copy->fresh()->business_title);
        $this->assertSame('competition_decision_signed_copy', $notice->fresh()->content_delivery);
    }

    private function forbidDecisionBuild(): void
    {
        $builder = Mockery::mock(CompetitionDecisionDocumentBuilder::class);
        $builder->shouldNotReceive('build');
        $this->app->instance(CompetitionDecisionDocumentBuilder::class, $builder);
    }

    /**
     * @return array{0: Competition, 1: CommissionMember, 2: Application, 3: ZenskoDecisionProposalSnapshot}
     */
    private function closeCompetition(): array
    {
        [$competition, $president, $application] = $this->readyCompetition();

        $this->actingAs($president->user)
            ->post(route('admin.competitions.close', $competition))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $snapshot = ZenskoDecisionProposalSnapshot::query()->findOrFail($fresh->zensko_decision_proposal_snapshot_id);

        return [$fresh, $president, $application->fresh(), $snapshot];
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
            'title' => 'Žensko preduzetništvo — arhiva',
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

        return [$competition->fresh(), $president, $application];
    }

    private function bareCompetition(string $type): Competition
    {
        return Competition::query()->create([
            'title' => 'Konkurs '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => 100000,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
        ]);
    }
}
