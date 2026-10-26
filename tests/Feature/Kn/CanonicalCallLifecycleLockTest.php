<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\BusinessPlan;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\ApplicationPrigovorService;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use App\Services\Competitions\Lifecycle\PublishedCallLifecycleLock;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use App\Support\CompetitionProgramCatalog;
use App\Support\EliminatoryProfileConfig;
use App\Support\KnApplicationStartContext;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\Feature\Kn\Concerns\BuildsCanonicalCommission;
use Tests\TestCase;

/**
 * KN-SYNC-011 — canonical call lifecycle lock + legacy (Women/Youth) route isolation.
 */
class CanonicalCallLifecycleLockTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use BuildsCanonicalCommission;
    use RefreshDatabase;

    private const CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    private const ORIGINAL_UP = 'UP-01-011/26';

    private int $jmbSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        CompetitionProgramCatalog::overrideStatusForTests(self::CANONICAL, CompetitionProgramCatalog::STATUS_ACTIVE);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- A. Published edit lock

    public function test_each_protected_field_is_blocked_after_publish_without_partial_update(): void
    {
        $admin = $this->admin();
        $competition = $this->canonicalCall('published');
        $original = $this->snapshot($competition);

        $cases = [
            'type' => ['type' => 'zensko'],
            'year' => ['year' => 2027],
            'budget' => ['budget' => 20000],
            'up_number' => ['up_number' => 'UP-01-999/26'],
            'start_date' => ['start_date' => now()->addDays(5)->toDateString()],
            'status' => ['status' => 'draft'],
        ];

        foreach ($cases as $field => $change) {
            $this->actingAs($admin)
                ->from(route('admin.competitions.edit', $competition))
                ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, array_merge(
                    ['title' => 'Pokušaj izmjene '.$field],
                    $change,
                )))
                ->assertRedirect(route('admin.competitions.edit', $competition))
                ->assertSessionHasErrors($field);

            $this->assertSame($original, $this->snapshot($competition->fresh()), 'Partial update for '.$field);
        }
    }

    public function test_all_protected_changes_at_once_report_every_field_and_write_nothing(): void
    {
        $competition = $this->canonicalCall('published');
        $original = $this->snapshot($competition);

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'title' => 'Novi naziv',
                'type' => 'omladinsko',
                'year' => 2030,
                'budget' => 1,
                'up_number' => 'UP-X',
                'start_date' => now()->subDays(40)->toDateString(),
                'status' => 'draft',
            ]))
            ->assertSessionHasErrors(['type', 'year', 'budget', 'up_number', 'start_date', 'status']);

        $this->assertSame($original, $this->snapshot($competition->fresh()));
    }

    public function test_published_canonical_non_protected_edit_keeps_window(): void
    {
        $competition = $this->canonicalCall('published', ['deadline_days' => 25]);
        $original = $this->snapshot($competition);

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'title' => 'Ispravljen naziv',
                'description' => 'Ispravljen opis',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.competitions.show', $competition));

        $fresh = $competition->fresh();
        $this->assertSame('Ispravljen naziv', $fresh->title);
        $this->assertSame(25, (int) $fresh->deadline_days);
        $this->assertSame(array_merge($original, ['title' => 'Ispravljen naziv']), $this->snapshot($fresh));
    }

    public function test_completed_canonical_cannot_return_to_published_or_draft(): void
    {
        $admin = $this->admin();
        $competition = $this->canonicalCall('completed', ['closed_at' => now()]);

        foreach (['published', 'draft'] as $status) {
            $this->actingAs($admin)
                ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, ['status' => $status]))
                ->assertForbidden();
            $this->assertSame('completed', $competition->fresh()->status);
        }

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $competition))
            ->assertSessionHasErrors('error');
        $this->assertSame('completed', $competition->fresh()->status);
    }

    public function test_published_canonical_cannot_be_completed_through_edit_form(): void
    {
        $competition = $this->canonicalCall('published');

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, ['status' => 'completed']))
            ->assertSessionHasErrors('status');

        $this->assertSame('published', $competition->fresh()->status);
    }

    public function test_canonical_draft_remains_fully_editable(): void
    {
        $competition = $this->canonicalCall('draft', ['start_date' => null, 'published_at' => null]);
        $start = now()->addDays(3)->toDateString();

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $competition), $this->editPayload($competition, [
                'year' => 2027,
                'budget' => 15000,
                'up_number' => 'UP-01-500/27',
                'start_date' => $start,
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $competition->fresh();
        $this->assertSame(2027, (int) $fresh->year);
        $this->assertSame(0, bccomp((string) $fresh->budget, '15000', 2));
        $this->assertSame($start, $fresh->start_date->toDateString());
        $this->assertSame('UP-01-500/27', $fresh->upNumber()->value('number'));
        $this->assertSame('draft', $fresh->status);
    }

    public function test_published_women_call_cannot_be_retyped_into_canonical(): void
    {
        $women = $this->canonicalCall('published', ['type' => 'zensko']);

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $women), $this->editPayload($women, ['type' => self::CANONICAL]))
            ->assertSessionHasErrors(['type' => PublishedCallLifecycleLock::TYPE_TARGET_LOCKED_MESSAGE]);

        $this->assertSame('zensko', $women->fresh()->type);
    }

    public function test_women_published_edit_semantics_unchanged(): void
    {
        $women = $this->canonicalCall('published', ['type' => 'zensko']);

        $this->actingAs($this->admin())
            ->put(route('admin.competitions.update', $women), $this->editPayload($women, [
                'budget' => 12000,
                'up_number' => 'UP-ZP-2',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $women->fresh();
        $this->assertSame(0, bccomp((string) $fresh->budget, '12000', 2));
        $this->assertSame('UP-ZP-2', $fresh->upNumber()->value('number'));
        $this->assertFalse($women->profile()->locksPublishedCall());
        $this->assertFalse(CompetitionProfileResolver::resolve('omladinsko')->locksPublishedCall());
    }

    // ---------------------------------------------------------------- B. Applicant deadline regression

    public function test_canonical_post_deadline_writes_are_all_blocked(): void
    {
        Storage::fake('local');
        $owner = $this->makeOwner();
        $competition = $this->canonicalCall('published', ['start_date' => now()->subDays(30)->toDateString()]);
        $application = $this->readyDraft($owner, $competition);
        $this->assertFalse($competition->fresh()->is_open);
        $this->assertTrue($application->isApplicantContentWriteLocked());

        $document = $application->documents()->where('document_type', 'licna_karta')->firstOrFail();
        $documentsBefore = $application->documents()->count();
        $applicationsBefore = Application::query()->count();

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());

        $this->actingAs($owner)
            ->post(route('applications.store', $competition), [
                'application_id' => $application->id,
                'save_as_draft' => '1',
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Izmijenjen naziv',
            ])
            ->assertSessionHasErrors('error');
        $this->assertNull($application->fresh()->business_plan_name);
        $this->assertSame('preduzetnik', $application->fresh()->applicant_type);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['person_name' => 'Kasna izmjena']))
            ->assertSessionHasErrors('error');
        $this->assertSame('Marko Marković', $application->canonicalApplicantForm()->value('person_name'));

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->p2Payload(CanonicalSubsidyType::INNOVATIVE))
            ->assertSessionHasErrors('error');
        $this->assertSame(CanonicalSubsidyType::AUTHENTIC_LOCAL, $application->canonicalInvestmentPlan()->value('subsidy_type'));

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Izmijenjena ideja',
                'applicant_name' => $owner->name,
                'applicant_address' => 'Njegoševa 1, 85330 Kotor',
                'applicant_phone' => '067000000',
                'applicant_email' => $owner->email,
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, $application->businessPlan()->count());

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'ostalo',
                'files' => [UploadedFile::fake()->create('ostalo.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame($documentsBefore, $application->documents()->count());

        $this->actingAs($owner)
            ->delete(route('applications.document.destroy', [$application, $document]))
            ->assertSessionHasErrors('error');
        $this->assertDatabaseHas('application_documents', ['id' => $document->id]);

        $this->assertSame($applicationsBefore, Application::query()->count());
    }

    public function test_canonical_post_deadline_new_draft_is_blocked(): void
    {
        $newcomer = $this->makeOwner();
        $competition = $this->canonicalCall('published', ['start_date' => now()->subDays(30)->toDateString()]);

        $this->actingAs($newcomer)
            ->post(route('applications.start', $competition))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('error');

        $this->actingAs($newcomer)
            ->get(route('applications.create', $competition))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->actingAs($newcomer)
            ->post(route('applications.store', $competition), [
                'save_as_draft' => '1',
                'applicant_type' => 'fizicko_lice',
                'business_stage' => 'započinjanje',
                'business_plan_name' => 'Kasna prijava',
            ])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, Application::query()->where('user_id', $newcomer->id)->count());
    }

    public function test_canonical_pre_deadline_writes_still_work(): void
    {
        Storage::fake('local');
        $owner = $this->makeOwner();
        $competition = $this->canonicalCall('published');
        $application = $this->canonicalDraft($owner, $competition, [
            'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
            'without_documents' => ['licna_karta'],
        ]);
        $this->assertFalse($application->isApplicantContentWriteLocked());

        $this->actingAs($this->makeOwner())
            ->post(route('applications.start', $competition), [
                'planned_intent' => KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.canonical.legal-form', $competition));

        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $application), $this->canonicalP1Payload($application, ['person_name' => 'Dorađeno Ime']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Dorađeno Ime', $application->canonicalApplicantForm()->value('person_name'));

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), array_replace($this->p2Payload(CanonicalSubsidyType::AUTHENTIC_LOCAL), [
                'expected_subsidy' => '450.00',
                'narrative_sections' => array_fill_keys(
                    array_keys(\App\Services\Competitions\CanonicalInvestmentPlanService::NARRATIVE_FIELDS),
                    'Testni opis plana ulaganja.'
                ),
            ]))
            ->assertSessionHasNoErrors();
        $this->assertSame('450.00', $application->canonicalInvestmentPlan()->value('expected_subsidy'));

        $this->actingAs($owner)
            ->post(route('applications.business-plan.store', $application), [
                'save_as_draft' => '1',
                'business_idea_name' => 'Dorađena ideja',
            ])
            ->assertRedirect(route('applications.canonical-p2.edit', $application))
            ->assertSessionHasErrors('error');
        $this->assertSame(0, $application->businessPlan()->count());

        Storage::disk('local')->put('documents/licna.pdf', 'pdf');
        $libraryDocument = UserDocument::create([
            'user_id' => $owner->id,
            'category' => 'Lični dokumenti',
            'name' => 'Lična karta',
            'file_path' => 'documents/licna.pdf',
            'original_filename' => 'licna.pdf',
            'file_size' => 3,
            'status' => 'active',
            'processed_at' => now(),
        ]);
        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'user_document_id' => $libraryDocument->id,
            ])
            ->assertSessionHasNoErrors();
        $attached = $application->documents()->where('document_type', 'licna_karta')->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('applications.document.destroy', [$application, $attached]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('application_documents', ['id' => $attached->id]);

        $this->actingAs($owner)
            ->post(route('applications.upload', $application), [
                'document_type' => 'licna_karta',
                'user_document_id' => $libraryDocument->id,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
    }

    // ---------------------------------------------------------------- C. Legacy route isolation

    public function test_legacy_obrazac3_and_prigovor_decision_are_forbidden_on_canonical(): void
    {
        Mail::fake();
        $chair = $this->komisijaUser('predsjednik');
        [$commission] = $this->canonicalCommission($chair);
        [$competition, $application] = $this->submittedCanonicalApplication($commission->id);
        $counts = $this->legacyCounts();

        $this->actingAs($chair)
            ->post(route('evaluation.eliminatory.store', $application), ['criteria' => []])
            ->assertForbidden();
        $this->actingAs($chair)
            ->post(route('evaluation.eliminatory.confirm', $application))
            ->assertForbidden();
        $this->actingAs($chair)
            ->post(route('evaluation.prigovor.decide', $application), ['decision' => 'usvojen'])
            ->assertForbidden();

        $this->assertSame($counts, $this->legacyCounts());
        $this->assertSame('submitted', $application->fresh()->status);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_legacy_prigovor_submit_is_forbidden_for_canonical_applicant(): void
    {
        Mail::fake();
        [$commission] = $this->canonicalCommission();
        [, $application, $owner] = $this->submittedCanonicalApplication($commission->id);
        $counts = $this->legacyCounts();

        $this->assertFalse(app(ApplicationPrigovorService::class)->applicantCanSubmit($application, $owner));

        $this->actingAs($owner)
            ->post(route('applications.prigovor.store', $application), ['body' => 'Prigovor na Obrazac 3.'])
            ->assertForbidden();

        $this->assertSame($counts, $this->legacyCounts());
        $this->assertSame('submitted', $application->fresh()->status);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_legacy_declarations_form_cannot_overwrite_canonical_member(): void
    {
        [, $members] = $this->canonicalCommission(signDeclarations: false);
        $member = $members['predsjednik'];
        $admin = $this->canonicalActor();

        $this->actingAs($admin)
            ->get(route('admin.commissions.members.sign', $member))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.commissions.members.store-declarations', $member), [
                'confidentiality_declaration' => '1',
                'conflict_of_interest_declaration' => '1',
                'confidentiality_accepted' => '1',
                'conflict_accepted' => '1',
            ])
            ->assertForbidden();

        $fresh = $member->fresh();
        $this->assertNull($fresh->declarations_signed_at);
        $this->assertNull($fresh->confidentiality_declaration);
        $this->assertNull($fresh->conflict_of_interest_declaration);
    }

    public function test_legacy_session_and_oral_routes_are_forbidden_on_canonical(): void
    {
        $chair = $this->komisijaUser('predsjednik');
        [$commission] = $this->canonicalCommission($chair);
        [$competition, $application] = $this->submittedCanonicalApplication($commission->id);
        $counts = $this->legacyCounts();

        $this->actingAs($chair)->get(route('commission-sessions.first.edit', $competition))->assertForbidden();
        $this->actingAs($chair)->post(route('commission-sessions.first.store', $competition), [])->assertForbidden();
        $this->actingAs($chair)->post(route('commission-sessions.first.confirm', $competition))->assertForbidden();
        $this->actingAs($chair)->get(route('commission-sessions.second.edit', $competition))->assertForbidden();
        $this->actingAs($chair)->post(route('commission-sessions.second.store', $competition), [])->assertForbidden();
        $this->actingAs($chair)
            ->post(route('commission-sessions.oral.store', [$competition, $application]), [])
            ->assertForbidden();

        $this->assertSame($counts, $this->legacyCounts());
    }

    public function test_legacy_config_fails_closed_for_canonical_and_stays_open_for_women_youth(): void
    {
        foreach (['zensko', 'omladinsko', null] as $type) {
            $this->assertTrue(CompetitionProfileResolver::resolve($type)->allowsLegacyCommissionFlows());
            $this->assertInstanceOf(EliminatoryProfileConfig::class, EliminatoryProfileConfig::for($type));
        }

        $this->assertFalse(CompetitionProfileResolver::resolve(self::CANONICAL)->allowsLegacyCommissionFlows());
        $this->assertFalse(LegacyCommissionFlowGuard::allows($this->canonicalCall('published')));
        $this->assertTrue(LegacyCommissionFlowGuard::allows($this->canonicalCall('published', ['type' => 'zensko'])));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(LegacyCommissionFlowGuard::BLOCKED_MESSAGE);
        EliminatoryProfileConfig::for(self::CANONICAL);
    }

    // ---------------------------------------------------------------- E. Delete safety

    public function test_canonical_draft_without_applications_can_be_deleted(): void
    {
        $competition = $this->canonicalCall('draft', ['start_date' => null, 'published_at' => null]);

        $this->actingAs($this->admin())
            ->delete(route('admin.competitions.destroy', $competition))
            ->assertRedirect(route('admin.competitions.index'));

        $this->assertDatabaseMissing('competitions', ['id' => $competition->id]);
    }

    public function test_canonical_with_applications_or_publication_cannot_be_deleted(): void
    {
        $admin = $this->admin();

        $draft = $this->canonicalCall('draft', ['start_date' => null, 'published_at' => null]);
        Application::create([
            'competition_id' => $draft->id,
            'user_id' => $this->makeOwner()->id,
            'status' => 'draft',
            'business_plan_name' => 'Nacrt',
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
        ]);
        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $draft))
            ->assertForbidden()
            ->assertSee(PublishedCallLifecycleLock::DELETE_APPLICATIONS_MESSAGE, false);
        $this->assertDatabaseHas('competitions', ['id' => $draft->id]);

        $published = $this->canonicalCall('published');
        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $published))
            ->assertForbidden()
            ->assertSee(PublishedCallLifecycleLock::DELETE_PUBLISHED_MESSAGE, false);
        $this->assertDatabaseHas('competitions', ['id' => $published->id]);
    }

    public function test_canonical_with_final_claim_delete_is_controlled_403_not_500(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->canonicalCall('published');
        $application = $this->readyDraft($owner, $competition);
        $this->actingAs($owner)->post(route('applications.final-submit', $application));
        $this->assertSame(1, FinalApplicationClaim::query()->count());

        $this->actingAs($this->admin())
            ->delete(route('admin.competitions.destroy', $competition))
            ->assertForbidden();

        $this->assertDatabaseHas('competitions', ['id' => $competition->id]);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_women_draft_delete_unchanged(): void
    {
        $women = $this->canonicalCall('draft', ['type' => 'zensko', 'start_date' => null, 'published_at' => null]);

        $this->actingAs($this->admin())
            ->delete(route('admin.competitions.destroy', $women))
            ->assertRedirect(route('admin.competitions.index'));

        $this->assertDatabaseMissing('competitions', ['id' => $women->id]);
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'konkurs_admin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function canonicalCall(string $status, array $overrides = []): Competition
    {
        $competition = Competition::create(array_merge([
            'title' => 'KN-SYNC-011-'.uniqid(),
            'description' => 'Lifecycle lock',
            'type' => self::CANONICAL,
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'published_at' => now()->subDay(),
        ], $overrides));
        $competition->upNumber()->create(['number' => self::ORIGINAL_UP]);

        return $competition->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function editPayload(Competition $competition, array $overrides = []): array
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
     * @return array<string, mixed>
     */
    private function snapshot(Competition $competition): array
    {
        return [
            'title' => $competition->title,
            'type' => $competition->type,
            'year' => (int) $competition->year,
            'budget' => number_format((float) $competition->budget, 2, '.', ''),
            'up_number' => $competition->upNumber()->value('number'),
            'start_date' => $competition->start_date?->toDateString(),
            'deadline_days' => (int) $competition->deadline_days,
            'status' => $competition->status,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function legacyCounts(): array
    {
        $counts = [];
        foreach ([
            'application_eliminatory_checks',
            'application_eliminatory_notices',
            'application_prigovors',
            'commission_sessions',
            'commission_session_attendances',
            'application_oral_presentations',
        ] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * @return array{0: Competition, 1: Application, 2: User}
     */
    private function submittedCanonicalApplication(int $commissionId): array
    {
        $competition = $this->canonicalCall('published', [
            'start_date' => now()->subDays(30)->toDateString(),
            'commission_id' => $commissionId,
        ]);
        $owner = $this->makeOwner();
        $application = $this->readyDraft($owner, $competition);
        $application->forceFill(['status' => 'submitted', 'submitted_at' => now()->subDays(15)])->save();

        return [$competition, $application->fresh(), $owner];
    }

    private function makeOwner(): User
    {
        $this->jmbSequence++;

        return User::factory()->create([
            'email' => 'owner-'.uniqid().'@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Test Podnosilac',
            'jmb' => '0101990'.str_pad((string) $this->jmbSequence, 6, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => 'Fizičko lice',
        ]);
    }

    private function readyDraft(User $owner, Competition $competition): Application
    {
        return $this->canonicalDraft($owner, $competition, ['subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
    }

    /**
     * @return array<string, mixed>
     */
    private function p2Payload(string $subsidyType): array
    {
        return [
            'subsidy_type' => $subsidyType,
            'total_investment' => '1000.00',
            'own_contribution' => '200.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [
                ['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00'],
            ],
        ];
    }
}
