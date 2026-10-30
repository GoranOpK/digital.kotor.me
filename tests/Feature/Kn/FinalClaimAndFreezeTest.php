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
use App\Services\Competitions\ApplicationEvidenceFreezer;
use App\Services\Competitions\CanonicalReviewBasis;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\TestCase;

/**
 * KN-SYNC-004 — canonical FINAL claim + OTD-002 hybrid evidence freeze.
 */
class FinalClaimAndFreezeTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use RefreshDatabase;

    private int $jmbSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_draft_creates_no_claim(): void
    {
        $owner = $this->makeOwner();
        $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));

        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_first_canonical_final_creates_claim_and_freezes_evidence(): void
    {
        $owner = $this->makeOwner(['jmb' => '0101990123456', 'name' => 'Zamrznuto Ime']);
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));

        $application->refresh()->load('businessPlan');
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $this->assertSame(1, $application->redni_broj);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
        $this->assertDatabaseHas('final_application_claims', [
            'user_id' => $owner->id,
            'competition_id' => $application->competition_id,
            'application_id' => $application->id,
        ]);
        $this->assertSame('0101990123456', $application->applicantJmbgForRead());
        $p1 = app(CanonicalReviewBasis::class)->frozenP1($application);
        $this->assertSame(CanonicalLegalForm::PREDUZETNIK, $p1['legal_form']);
        $this->assertSame('Marko Marković', $p1['person_name']);
        $this->assertNotNull($p1['frozen_at']);
        $this->assertNull($application->businessPlan);
        $this->assertTrue($application->isApplicantContentWriteLocked());
    }

    public function test_duplicate_final_same_user_competition_same_subsidy_type_blocked(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $first = $this->readyDraft($owner, $competition);
        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertRedirect();

        $second = $this->readyDraft($owner, $competition, 'SECOND');
        $this->actingAs($owner)
            ->from(route('applications.show', $second))
            ->post(route('applications.final-submit', $second))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $second->fresh()->status);
        $this->assertNull($second->fresh()->submitted_at);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
    }

    public function test_second_application_row_cannot_bypass_uniqueness(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $holder = $this->readyDraft($owner, $competition, 'HOLDER');
        FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $holder->id,
            'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
            'beneficiary_pib' => $this->ownerPib($owner),
            'beneficiary_slot' => 1,
            'claimed_at' => now(),
        ]);
        $holder->update(['status' => 'submitted', 'submitted_at' => now()->subHour(), 'redni_broj' => 1]);

        $intruder = $this->readyDraft($owner, $competition, 'INTRUDER');
        $this->actingAs($owner)
            ->from(route('applications.show', $intruder))
            ->post(route('applications.final-submit', $intruder))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $intruder->fresh()->status);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
    }

    public function test_claim_survives_rejected_and_later_status(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $application = $this->readyDraft($owner, $competition);
        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();

        $application->update(['status' => 'rejected', 'rejection_reason' => 'Test odbijanje']);
        $this->assertDatabaseHas('final_application_claims', [
            'application_id' => $application->id,
            'user_id' => $owner->id,
        ]);

        $application->update(['status' => 'evaluated']);
        $this->assertSame(1, FinalApplicationClaim::query()->count());

        $another = $this->readyDraft($owner, $competition, 'AFTER-STATUS');
        $this->actingAs($owner)
            ->from(route('applications.show', $another))
            ->post(route('applications.final-submit', $another))
            ->assertSessionHasErrors('error');
        $this->assertSame('draft', $another->fresh()->status);
    }

    public function test_failed_final_rolls_back_claim_status_and_freeze(): void
    {
        $this->withoutExceptionHandling();
        $this->app->instance(ApplicationEvidenceFreezer::class, new class extends ApplicationEvidenceFreezer
        {
            public function freeze(Application $application): void
            {
                throw new RuntimeException('freeze failed');
            }
        });

        $owner = $this->makeOwner(['jmb' => '0101990123456', 'name' => 'Prije Freeza']);
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'FAIL-FREEZE');

        try {
            $this->actingAs($owner)->post(route('applications.final-submit', $application));
            $this->fail('Freeze failure should surface.');
        } catch (RuntimeException $exception) {
            $this->assertSame('freeze failed', $exception->getMessage());
        }

        $fresh = $application->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertNull($fresh->submitted_at);
        $this->assertNull($fresh->redni_broj);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_validation_failure_creates_no_claim(): void
    {
        $owner = $this->makeOwner();
        $application = Application::create([
            'competition_id' => $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL)->id,
            'user_id' => $owner->id,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');

        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_retry_is_idempotent_for_submitted_at_redni_and_claim(): void
    {
        $owner = $this->makeOwner(['jmb' => '0101990123456', 'name' => 'Prvo Ime', 'email' => 'prvo@example.com']);
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();
        $application->refresh();
        $submittedAt = $application->submitted_at->toDateTimeString();
        $redni = $application->redni_broj;
        $frozenP1 = app(CanonicalReviewBasis::class)->frozenP1($application);

        $owner->update(['name' => 'Drugo Ime', 'jmb' => '0202990123456', 'email' => 'drugo-routing@example.com']);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertSame('submitted', $application->status);
        $this->assertSame($submittedAt, $application->submitted_at->toDateTimeString());
        $this->assertSame($redni, $application->redni_broj);
        $this->assertSame($frozenP1, app(CanonicalReviewBasis::class)->frozenP1($application));
        $this->assertSame('0101990123456', $application->applicantJmbgForRead());
        $this->assertSame(1, FinalApplicationClaim::query()->count());
        $this->assertSame('drugo-routing@example.com', $owner->fresh()->email);
    }

    public function test_live_routing_email_change_does_not_alter_freeze(): void
    {
        $owner = $this->makeOwner([
            'jmb' => '0101990123456',
            'name' => 'Zamrznuto Ime',
            'email' => 'freeze-contact@example.com',
        ]);
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));
        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();

        $application->refresh();
        $frozenP1 = app(CanonicalReviewBasis::class)->frozenP1($application);
        $frozenJmb = $application->applicantJmbgForRead();

        $owner->update(['email' => 'new-routing@example.com', 'name' => 'Živo Ime', 'jmb' => '9999999999999']);

        $application->refresh()->load('user');
        $this->assertSame('new-routing@example.com', $application->user->email);
        $this->assertSame($frozenP1, app(CanonicalReviewBasis::class)->frozenP1($application));
        $this->assertNotSame('new-routing@example.com', $frozenP1['email']);
        $this->assertSame($frozenJmb, $application->applicantJmbgForRead());
        $this->assertSame('0101990123456', $application->resolvedApplicantJmbg());
    }

    public function test_document_replacement_blocked_after_final_and_freeze_stable(): void
    {
        $owner = $this->makeOwner();
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));
        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();
        $application->refresh();

        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'dokaz.pdf',
            'file_path' => 'applications/dokaz.pdf',
            'document_type' => 'ostalo',
        ]);
        $pathBefore = $document->file_path;

        $this->actingAs($owner)
            ->delete(route('applications.document.destroy', [$application, $document]))
            ->assertSessionHasErrors('error');
        $this->assertNotNull($document->fresh());
        $this->assertSame($pathBefore, $document->fresh()->file_path);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.store', $application->competition), [
                'application_id' => $application->id,
                'physical_person_name' => 'Izmijenjeno',
            ])
            ->assertSessionHasErrors('error');
        $this->assertNotSame('Izmijenjeno', $application->fresh()->physical_person_name);

        $this->actingAs($owner)
            ->from(route('applications.business-plan.create', $application))
            ->post(route('applications.business-plan.store', $application), [
                'applicant_name' => 'Izmijenjen plan',
                'save_as_draft' => '1',
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame(0, BusinessPlan::query()->where('application_id', $application->id)->count());

        $frozenP1 = app(CanonicalReviewBasis::class)->frozenP1($application);
        $this->actingAs($owner)
            ->from(route('applications.canonical-p1.edit', $application))
            ->put(route('applications.canonical-p1.update', $application), [
                'subsidy_type' => CanonicalSubsidyType::DIGITALIZATION,
                'person_name' => 'Izmijenjeno',
            ])
            ->assertSessionHasErrors('error');
        $this->assertSame($frozenP1, app(CanonicalReviewBasis::class)->frozenP1($application));
    }

    public function test_jmb_privacy_uses_dual_write_not_plaintext_only_path(): void
    {
        $owner = $this->makeOwner(['jmb' => '0101990123456', 'name' => 'JMB Freeze']);
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'JMB');

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();
        $application->refresh();

        $this->assertTrue($application->hasApplicantJmbgSnapshot());
        $this->assertSame('0101990123456', $application->applicantJmbgForRead());
        $this->assertNotNull($application->applicant_jmbg_encrypted);

        $snapshot = json_encode(app(CanonicalReviewBasis::class)->snapshot($application), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('0101990123456', $snapshot);
        $this->assertStringNotContainsString((string) $application->applicant_jmbg_encrypted, $snapshot);
        $this->assertStringNotContainsString('jmb', strtolower($snapshot));
    }

    public function test_women_submit_does_not_create_final_claim(): void
    {
        $owner = $this->makeOwner();
        $application = $this->readyDraft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_WOMEN));

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertLegacyJmbgStillEncrypted($application, $owner);
    }

    public function test_youth_submit_does_not_create_final_claim(): void
    {
        $owner = $this->makeOwner();
        $application = $this->readyYouthDraft($owner);

        $this->actingAs($owner)
            ->post(route('applications.final-submit', $application), [
                'confirm_incomplete_forms' => '1',
                'confirm_missing_documents' => '1',
            ])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertLegacyJmbgStillEncrypted($application, $owner);
    }

    public function test_canonical_remains_development(): void
    {
        $entry = CompetitionProgramCatalog::definitions()[CompetitionProfileResolver::TYPE_CANONICAL];
        $this->assertSame(CompetitionProgramCatalog::STATUS_DEVELOPMENT, $entry['status']);
        $this->assertFalse(CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL)->allowsWomenBonus());
        $this->assertFalse(CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL)->allowsYouthBonus());
    }

    public function test_claim_unique_indexes_are_subsidy_type_scoped(): void
    {
        $indexes = collect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM final_application_claims'))
            ->where('Non_unique', 0)
            ->groupBy('Key_name')
            ->map(fn ($rows) => $rows->sortBy('Seq_in_index')->pluck('Column_name')->values()->all());

        $this->assertNull($indexes->get('final_claims_user_competition_unique'));
        $this->assertSame(
            ['user_id', 'competition_id', 'claim_scope'],
            $indexes->get('final_claims_user_competition_scope_unique')
        );
        $this->assertSame(
            ['competition_id', 'beneficiary_pib', 'subsidy_type'],
            $indexes->get('final_claims_competition_pib_type_unique')
        );
        $this->assertSame(
            ['competition_id', 'beneficiary_pib', 'beneficiary_slot'],
            $indexes->get('final_claims_competition_pib_slot_unique')
        );
    }

    private function openCompetition(string $type): Competition
    {
        return Competition::create([
            'title' => 'KN-SYNC-004-'.$type.'-'.uniqid(),
            'description' => 'Final claim',
            'type' => $type,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    private function makeOwner(array $overrides = []): User
    {
        $jmb = $overrides['jmb'] ?? $this->uniqueTestJmb();

        return User::factory()->create(array_merge([
            'email' => 'owner-'.uniqid().'@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'Test Podnosilac',
            'jmb' => $jmb,
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => 'Fizičko lice',
        ], $overrides));
    }

    private function readyDraft(
        User $owner,
        Competition $competition,
        string $suffix = 'DRAFT',
        bool $seedIdentityOnObrazac = true,
    ): Application {
        if ($competition->type === CompetitionProfileResolver::TYPE_CANONICAL) {
            return $this->canonicalDraft($owner, $competition, [
                'pib' => $this->ownerPib($owner),
                'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
            ]);
        }

        $jmb = $owner->jmb ?? $this->uniqueTestJmb();
        $attributes = [
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'KN-SYNC-004-'.$suffix,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'draft',
            'is_registered' => false,
            'physical_person_name' => $owner->name,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'accuracy_declaration' => true,
        ];
        if ($seedIdentityOnObrazac) {
            $attributes['physical_person_jmbg'] = $jmb;
        }
        $isCanonical = $competition->type === CompetitionProfileResolver::TYPE_CANONICAL;
        if ($isCanonical) {
            $attributes['pib'] = $this->ownerPib($owner);
        }

        $application = Application::create($attributes);

        if ($isCanonical) {
            CanonicalInvestmentPlan::create([
                'application_id' => $application->id,
                'subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL,
                'total_investment' => '1000.00',
                'own_contribution' => '200.00',
                'expected_subsidy' => '800.00',
                'expense_lines' => [['cost_type' => 'Oprema', 'description' => 'Minimalna oprema', 'amount' => '1000.00']],
            ]);
        }

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Minimalna ideja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $seedIdentityOnObrazac ? $jmb : null,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => true,
        ]);

        $application->refresh()->load(['businessPlan', 'competition']);
        $this->assertTrue($application->isObrazacComplete());

        return $application;
    }

    private function readyYouthDraft(User $owner): Application
    {
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_YOUTH);
        $application = $this->readyDraft($owner, $competition, 'YOUTH');

        $application->businessPlan->update([
            'realization_type' => 'stvori_novi',
            'funding_sources_table' => [
                ['type' => 'Laptop', 'price' => '500'],
            ],
        ]);

        return $application->fresh(['businessPlan', 'competition']);
    }

    private function assertLegacyJmbgStillEncrypted(Application $application, User $owner): void
    {
        $fresh = $application->fresh(['businessPlan']);
        $this->assertNotNull($fresh->physical_person_jmbg_encrypted);
        $this->assertSame((string) $owner->jmb, $fresh->physicalPersonJmbgForRead());
        $this->assertNotNull($fresh->businessPlan->applicant_jmbg_encrypted);
        $this->assertSame((string) $owner->jmb, $fresh->businessPlan->applicantJmbgForRead());
    }

    private function ownerPib(User $owner): string
    {
        return '7'.str_pad((string) $owner->id, 7, '0', STR_PAD_LEFT);
    }

    private function uniqueTestJmb(): string
    {
        $this->jmbSequence++;

        return '0101990'.str_pad((string) $this->jmbSequence, 6, '0', STR_PAD_LEFT);
    }
}
