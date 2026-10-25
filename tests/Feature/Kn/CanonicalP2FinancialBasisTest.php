<?php

namespace Tests\Feature\Kn;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalBeneficiaryApplications;
use App\Services\Competitions\CanonicalReviewBasis;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use App\Support\Pib;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\TestCase;

/**
 * KN-SYNC-008A — canonical P2 subsidy type + financial basis, FINAL freeze,
 * and čl.19 two-applications-per-beneficiary (normalized PIB) submission integrity.
 */
class CanonicalP2FinancialBasisTest extends TestCase
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

    // ---- A. Subsidy type -------------------------------------------------

    public function test_subsidy_type_catalog_codes_labels_order_and_cap_metadata(): void
    {
        $this->assertSame([
            'autenticni_lokalni_proizvodi_usluge',
            'inovativni_proizvodi_usluge',
            'digitalizacija',
        ], CanonicalSubsidyType::codes());

        $this->assertSame([
            'autenticni_lokalni_proizvodi_usluge' => 'Subvencije za razvoj autentičnih lokalnih proizvoda/usluga',
            'inovativni_proizvodi_usluge' => 'Subvencije za razvoj inovativnih proizvoda/usluga',
            'digitalizacija' => 'Subvencije za digitalizaciju',
        ], CanonicalSubsidyType::labels());

        $this->assertSame(1, CanonicalSubsidyType::order(CanonicalSubsidyType::AUTHENTIC_LOCAL));
        $this->assertSame(2, CanonicalSubsidyType::order(CanonicalSubsidyType::INNOVATIVE));
        $this->assertSame(3, CanonicalSubsidyType::order(CanonicalSubsidyType::DIGITALIZATION));
        $this->assertSame('čl.12 tačka 1', CanonicalSubsidyType::articleReference(CanonicalSubsidyType::AUTHENTIC_LOCAL));

        $this->assertSame(80, CanonicalSubsidyType::capPercentage(CanonicalSubsidyType::AUTHENTIC_LOCAL));
        $this->assertSame(50, CanonicalSubsidyType::capPercentage(CanonicalSubsidyType::INNOVATIVE));
        $this->assertSame(50, CanonicalSubsidyType::capPercentage(CanonicalSubsidyType::DIGITALIZATION));
        $this->assertSame(20, CanonicalSubsidyType::BUDGET_CAP_PERCENTAGE);
        $this->assertSame(2, CanonicalSubsidyType::MAX_APPLICATIONS_PER_BENEFICIARY);

        $this->assertFalse(CanonicalSubsidyType::isValid('1'));
        $this->assertFalse(CanonicalSubsidyType::isValid(1));
        $this->expectException(\InvalidArgumentException::class);
        CanonicalSubsidyType::capPercentage('nepoznato');
    }

    public function test_all_three_subsidy_types_accepted_on_canonical_p2(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), p1Typed: false);

        foreach (CanonicalSubsidyType::codes() as $code) {
            $this->actingAs($owner)
                ->put(route('applications.canonical-p2.update', $application), $this->p2Payload($code))
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('applications.canonical-p2.edit', $application));

            $this->assertSame($code, $application->canonicalInvestmentPlan()->value('subsidy_type'));
        }

        $this->assertSame(1, CanonicalInvestmentPlan::query()->count());
    }

    public function test_invalid_subsidy_type_rejected(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), withP2: false);

        foreach (['1', 'subvencija', 'zensko'] as $invalid) {
            $this->actingAs($owner)
                ->from(route('applications.canonical-p2.edit', $application))
                ->put(route('applications.canonical-p2.update', $application), $this->p2Payload($invalid))
                ->assertSessionHasErrors('subsidy_type');
        }

        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    public function test_subsidy_type_required_before_final(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL));
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), array_merge($this->p2Payload(), ['subsidy_type' => '']))
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => 'Plan ulaganja (Obrazac P2) nije kompletan: Vrsta subvencije je obavezna.']);

        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_women_youth_and_ostalo_cannot_reach_canonical_p2(): void
    {
        $owner = $this->makeOwner();
        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH, 'ostalo'] as $type) {
            $application = $this->draft($owner, $this->openCompetition($type), $type);

            $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))->assertNotFound();
            $this->actingAs($owner)
                ->put(route('applications.canonical-p2.update', $application), $this->p2Payload())
                ->assertNotFound();
        }

        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    public function test_other_user_cannot_read_or_write_canonical_p2(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), withP2: false);
        $stranger = $this->makeOwner();

        $this->actingAs($stranger)->get(route('applications.canonical-p2.edit', $application))->assertForbidden();
        $this->actingAs($stranger)
            ->put(route('applications.canonical-p2.update', $application), $this->p2Payload())
            ->assertForbidden();
        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    // ---- B. P2 financial basis ------------------------------------------

    public function test_p2_financial_frame_persisted_decimal_safe(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), p1Typed: false);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), [
                'subsidy_type' => CanonicalSubsidyType::DIGITALIZATION,
                'total_investment' => '10000,10',
                'own_contribution' => '0',
                'expected_subsidy' => '5000.05',
                'expense_lines' => [
                    ['cost_type' => 'Softver', 'description' => 'ERP licenca', 'amount' => '3333,33'],
                    ['cost_type' => '', 'description' => '', 'amount' => ''],
                    ['cost_type' => 'Oprema', 'description' => 'Server', 'amount' => '6666.77'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $plan = $application->canonicalInvestmentPlan()->firstOrFail();
        $this->assertSame(CanonicalSubsidyType::DIGITALIZATION, $plan->subsidy_type);
        $this->assertSame('10000.10', $plan->total_investment);
        $this->assertSame('0.00', $plan->own_contribution);
        $this->assertSame('5000.05', $plan->expected_subsidy);
        $this->assertSame([
            ['cost_type' => 'Softver', 'description' => 'ERP licenca', 'amount' => '3333.33'],
            ['cost_type' => 'Oprema', 'description' => 'Server', 'amount' => '6666.77'],
        ], $plan->expenseLines());
        $this->assertNull($plan->frozen_at);
    }

    public function test_expense_line_sum_must_match_total_to_the_cent(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), withP2: false);

        $payload = $this->p2Payload();
        $payload['expense_lines'] = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '600.00'],
            ['cost_type' => 'Obuka', 'description' => 'Kurs', 'amount' => '399.99'],
        ];

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $payload)
            ->assertSessionHasErrors(['expense_lines' => 'Zbir stavki troškova (999.99 EUR) mora biti jednak ukupnoj vrijednosti ulaganja (1000.00 EUR).']);
        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());

        $payload['expense_lines'][1]['amount'] = '400';
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame(1, CanonicalInvestmentPlan::query()->count());
    }

    public function test_amount_bounds_are_validated(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), withP2: false);
        $route = route('applications.canonical-p2.update', $application);

        $cases = [
            ['total_investment', '0'],
            ['total_investment', '-5'],
            ['total_investment', '10.005'],
            ['own_contribution', '1000.01'],
            ['own_contribution', '-1'],
            ['expected_subsidy', '0'],
            ['expected_subsidy', '1000.01'],
        ];
        foreach ($cases as [$field, $value]) {
            $this->actingAs($owner)
                ->from(route('applications.canonical-p2.edit', $application))
                ->put($route, array_merge($this->p2Payload(), [$field => $value]))
                ->assertSessionHasErrors($field);
        }

        $payload = $this->p2Payload();
        $payload['expense_lines'][] = ['cost_type' => 'Nula', 'description' => 'Nula', 'amount' => '0'];
        $this->actingAs($owner)->put($route, $payload)->assertSessionHasErrors('expense_lines.1.amount');

        $payload = $this->p2Payload();
        $payload['expense_lines'][0]['description'] = '';
        $this->actingAs($owner)->put($route, $payload)->assertSessionHasErrors('expense_lines.0.description');

        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    public function test_final_has_no_fallback_to_shared_required_amount_or_expense_projection(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'NO-P2', withP2: false);
        $application->update(['total_budget_needed' => 9000, 'requested_amount' => 4000]);
        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Zaostali plan',
            'required_amount' => 9000,
            'requested_amount' => 4000,
            'funding_sources_table' => [['type' => 'Laptop', 'price' => '900']],
        ]);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => 'Morate popuniti plan ulaganja (Obrazac P2): vrstu subvencije i finansijski okvir ulaganja prije konačne predaje.']);

        $this->assertSame('draft', $application->fresh()->status);
        $this->assertSame(0, FinalApplicationClaim::query()->count());
        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
    }

    public function test_final_blocked_when_explicit_total_missing_even_if_lines_exist(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'NO-TOTAL', withP2: false);
        CanonicalInvestmentPlan::create([
            'application_id' => $application->id,
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => null,
            'own_contribution' => '0.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [['cost_type' => 'Oprema', 'description' => 'Alat', 'amount' => '1000.00']],
        ]);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors(['error' => 'Plan ulaganja (Obrazac P2) nije kompletan: Ukupna vrijednost ulaganja je obavezna.']);

        $this->assertNull($application->canonicalInvestmentPlan()->value('total_investment'));
        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_final_revalidates_inconsistent_stored_p2(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'BAD-SUM', withP2: false);
        CanonicalInvestmentPlan::create([
            'application_id' => $application->id,
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => '1000.00',
            'own_contribution' => '0.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [['cost_type' => 'Oprema', 'description' => 'Alat', 'amount' => '999.99']],
        ]);

        $this->actingAs($owner)
            ->from(route('applications.show', $application))
            ->post(route('applications.final-submit', $application))
            ->assertSessionHasErrors('error');

        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    // ---- C. Freeze -------------------------------------------------------

    public function test_draft_p2_is_editable(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'EDIT', withP2: false, p1Typed: false);
        $route = route('applications.canonical-p2.update', $application);

        $this->actingAs($owner)->put($route, $this->p2Payload(CanonicalSubsidyType::INNOVATIVE))->assertSessionHasNoErrors();
        $this->actingAs($owner)->put($route, $this->p2Payload(CanonicalSubsidyType::DIGITALIZATION, '2000.00'))->assertSessionHasNoErrors();

        $plan = $application->canonicalInvestmentPlan()->firstOrFail();
        $this->assertSame(CanonicalSubsidyType::DIGITALIZATION, $plan->subsidy_type);
        $this->assertSame('2000.00', $plan->total_investment);
        $this->assertSame('2000.00', $plan->expenseLines()[0]['amount']);
        $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))
            ->assertOk()
            ->assertSee('Plan ulaganja — Obrazac P2')
            ->assertSee('Subvencije za razvoj autentičnih lokalnih proizvoda/usluga')
            ->assertDontSee('biznis ideja');
    }

    public function test_final_freezes_all_p2_facts_and_blocks_every_mutation_path(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'FREEZE', withP2: false);
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->p2Payload(CanonicalSubsidyType::INNOVATIVE))
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)->post(route('applications.final-submit', $application))
            ->assertRedirect(route('applications.show', $application));

        $plan = $application->canonicalInvestmentPlan()->firstOrFail();
        $this->assertTrue($plan->isFrozen());
        $frozen = $plan->basis();

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $application))
            ->put(route('applications.canonical-p2.update', $application), $this->p2Payload(CanonicalSubsidyType::DIGITALIZATION, '5000.00'))
            ->assertSessionHasErrors('error');

        try {
            $plan->update(['subsidy_type' => CanonicalSubsidyType::AUTHENTIC_LOCAL]);
            $this->fail('Frozen P2 update must fail closed.');
        } catch (LogicException $exception) {
            $this->assertSame(CanonicalInvestmentPlan::FROZEN_MESSAGE, $exception->getMessage());
        }
        foreach ([
            ['total_investment' => '9999.00'],
            ['own_contribution' => '1.00'],
            ['expected_subsidy' => '1.00'],
            ['expense_lines' => [['cost_type' => 'X', 'description' => 'Y', 'amount' => '1.00']]],
        ] as $mutation) {
            try {
                $plan->fresh()->update($mutation);
                $this->fail('Frozen P2 update must fail closed.');
            } catch (LogicException) {
            }
        }
        try {
            $plan->fresh()->delete();
            $this->fail('Frozen P2 delete must fail closed.');
        } catch (LogicException) {
        }

        $this->assertSame($frozen, $plan->fresh()->basis());
        $this->assertSame(CanonicalSubsidyType::INNOVATIVE, $frozen['subsidy_type']);
        $this->assertSame('1000.00', $frozen['total_investment']);
        $this->assertSame('200.00', $frozen['own_contribution']);
        $this->assertSame('500.00', $frozen['expected_subsidy']);
        $this->assertSame([['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => '1000.00']], $frozen['expense_lines']);

        $this->actingAs($owner)->get(route('applications.canonical-p2.edit', $application))
            ->assertOk()
            ->assertSee(CanonicalInvestmentPlan::FROZEN_MESSAGE);
    }

    public function test_canonical_review_basis_and_claim_read_frozen_p2_only(): void
    {
        $owner = $this->makeOwner();
        $application = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'BASIS', subsidyType: CanonicalSubsidyType::DIGITALIZATION);
        $basis = app(CanonicalReviewBasis::class);

        $this->assertNull($basis->frozenP2($application));

        $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertRedirect();
        $application->refresh();

        $frozen = $basis->frozenP2($application);
        $this->assertSame(CanonicalSubsidyType::DIGITALIZATION, $frozen['subsidy_type']);
        $this->assertSame('1000.00', $frozen['total_investment']);
        $this->assertSame('200.00', $frozen['own_contribution']);
        $this->assertSame('500.00', $frozen['expected_subsidy']);
        $this->assertCount(1, $frozen['expense_lines']);
        $this->assertNotNull($frozen['frozen_at']);
        $this->assertSame($frozen, $basis->snapshot($application)['canonical_p2']);

        $claim = FinalApplicationClaim::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame(CanonicalSubsidyType::DIGITALIZATION, $claim->subsidy_type);
        $this->assertSame($this->pibFor($owner), $claim->beneficiary_pib);
        $this->assertSame(1, (int) $claim->beneficiary_slot);
    }

    // ---- D. Two applications / beneficiary ------------------------------

    public function test_same_pib_two_different_types_allowed_same_type_and_third_blocked(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        $first = $this->draft($owner, $competition, 'A1', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL);
        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertSessionHasNoErrors();

        $duplicate = $this->draft($owner, $competition, 'A2', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL);
        $this->actingAs($owner)
            ->from(route('applications.show', $duplicate))
            ->post(route('applications.final-submit', $duplicate))
            ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);
        $this->assertSame('draft', $duplicate->fresh()->status);

        $second = $this->draft($owner, $competition, 'B1', subsidyType: CanonicalSubsidyType::INNOVATIVE);
        $this->actingAs($owner)->post(route('applications.final-submit', $second))->assertSessionHasNoErrors();
        $this->assertSame('submitted', $second->fresh()->status);

        $third = $this->draft($owner, $competition, 'C1', subsidyType: CanonicalSubsidyType::DIGITALIZATION);
        $this->actingAs($owner)
            ->from(route('applications.show', $third))
            ->post(route('applications.final-submit', $third))
            ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::MAX_TWO_MESSAGE]);
        $this->assertSame('draft', $third->fresh()->status);
        $this->assertFalse($third->canonicalInvestmentPlan()->firstOrFail()->isFrozen());

        $claims = FinalApplicationClaim::query()->where('competition_id', $competition->id)->orderBy('id')->get();
        $this->assertCount(2, $claims);
        $this->assertSame([1, 2], $claims->pluck('beneficiary_slot')->map(fn ($slot) => (int) $slot)->all());
        $this->assertSame([CanonicalSubsidyType::AUTHENTIC_LOCAL, CanonicalSubsidyType::INNOVATIVE], $claims->pluck('subsidy_type')->all());
        $this->assertSame([1, 2], [(int) $first->fresh()->redni_broj, (int) $second->fresh()->redni_broj]);
    }

    public function test_second_application_keeps_first_immutable_and_each_p2_frozen_independently(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        $first = $this->draft($owner, $competition, 'FIRST', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL);
        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertSessionHasNoErrors();
        $first->refresh();
        $firstSnapshot = [
            $first->status,
            $first->submitted_at->toIso8601String(),
            $first->redni_broj,
            app(CanonicalReviewBasis::class)->frozenP2($first),
        ];

        $second = $this->draft($owner, $competition, 'SECOND', withP2: false, p1Typed: false);
        $this->assertSame($second->id, app(CanonicalBeneficiaryApplications::class)->userApplicationForCompetition($competition, $owner->id)?->id);

        $this->actingAs($owner)
            ->from(route('applications.canonical-p2.edit', $second))
            ->put(route('applications.canonical-p2.update', $second), $this->p2Payload(CanonicalSubsidyType::AUTHENTIC_LOCAL))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $second), $this->p2Payload(CanonicalSubsidyType::DIGITALIZATION, '2500.00'))
            ->assertSessionHasNoErrors();
        $this->actingAs($owner)
            ->put(route('applications.canonical-p1.update', $second), $this->canonicalP1Payload($second, ['subsidy_type' => CanonicalSubsidyType::DIGITALIZATION]))
            ->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('applications.final-submit', $second))->assertSessionHasNoErrors();

        $first->refresh();
        $this->assertSame($firstSnapshot, [
            $first->status,
            $first->submitted_at->toIso8601String(),
            $first->redni_broj,
            app(CanonicalReviewBasis::class)->frozenP2($first),
        ]);

        $secondP2 = app(CanonicalReviewBasis::class)->frozenP2($second->fresh());
        $this->assertSame(CanonicalSubsidyType::DIGITALIZATION, $secondP2['subsidy_type']);
        $this->assertSame('2500.00', $secondP2['total_investment']);
        $this->assertSame(CanonicalSubsidyType::AUTHENTIC_LOCAL, $firstSnapshot[3]['subsidy_type']);
        $this->assertSame('1000.00', $firstSnapshot[3]['total_investment']);

        $claims = FinalApplicationClaim::query()->where('user_id', $owner->id)->orderBy('id')->get();
        $this->assertSame([$first->id, $second->id], $claims->pluck('application_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, CanonicalInvestmentPlan::query()->whereNotNull('frozen_at')->count());
    }

    public function test_untyped_historical_claims_keep_one_per_user_competition_in_database(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $legacy = fn (string $suffix) => FinalApplicationClaim::query()->create([
            'user_id' => $owner->id,
            'competition_id' => $competition->id,
            'application_id' => $this->draft($owner, $competition, $suffix, withP2: false)->id,
            'claimed_at' => now(),
        ]);

        $legacy('LEGACY-1');
        $this->assertSame('*', FinalApplicationClaim::query()->value('claim_scope'));
        $this->assertQueryRejected(fn () => $legacy('LEGACY-2'));

        $next = $this->draft($owner, $competition, 'AFTER-LEGACY', subsidyType: CanonicalSubsidyType::INNOVATIVE);
        $this->actingAs($owner)
            ->from(route('applications.show', $next))
            ->post(route('applications.final-submit', $next))
            ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::LEGACY_CLAIM_MESSAGE]);
        $this->assertSame(1, FinalApplicationClaim::query()->count());
    }

    public function test_normalized_pib_is_one_beneficiary_across_user_accounts(): void
    {
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $pib = '02345678';

        $this->assertSame($pib, Pib::normalize('02-345.678'));
        $this->assertSame($pib, Pib::normalize(' 0234 5678 '));

        $userA = $this->makeOwner();
        $a = $this->draft($userA, $competition, 'U1', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL, pib: $pib);
        $this->actingAs($userA)->post(route('applications.final-submit', $a))->assertSessionHasNoErrors();

        $userB = $this->makeOwner();
        $sameType = $this->draft($userB, $competition, 'U2', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL, pib: $pib);
        $this->actingAs($userB)
            ->from(route('applications.show', $sameType))
            ->post(route('applications.final-submit', $sameType))
            ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);

        $this->actingAs($userB)
            ->put(route('applications.canonical-p2.update', $sameType), $this->p2Payload(CanonicalSubsidyType::AUTHENTIC_LOCAL))
            ->assertSessionHasErrors(['subsidy_type' => CanonicalBeneficiaryApplications::DUPLICATE_TYPE_MESSAGE]);

        $b = $this->draft($userB, $competition, 'U2B', subsidyType: CanonicalSubsidyType::DIGITALIZATION, pib: $pib);
        $this->actingAs($userB)->post(route('applications.final-submit', $b))->assertSessionHasNoErrors();

        $userC = $this->makeOwner();
        $c = $this->draft($userC, $competition, 'U3', subsidyType: CanonicalSubsidyType::INNOVATIVE, pib: $pib);
        $this->actingAs($userC)
            ->from(route('applications.show', $c))
            ->post(route('applications.final-submit', $c))
            ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::MAX_TWO_MESSAGE]);

        $this->assertSame(2, FinalApplicationClaim::query()->where('beneficiary_pib', $pib)->count());
    }

    public function test_different_pib_is_a_different_beneficiary(): void
    {
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        foreach (['11111111', '22222222', '33333333'] as $index => $pib) {
            $owner = $this->makeOwner();
            $application = $this->draft($owner, $competition, 'P'.$index, subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL, pib: $pib);
            $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertSessionHasNoErrors();
        }

        $this->assertSame(3, FinalApplicationClaim::query()->where('subsidy_type', CanonicalSubsidyType::AUTHENTIC_LOCAL)->count());
    }

    public function test_final_requires_valid_normalized_pib(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);

        foreach ([null, '1234567', '1234567X'] as $index => $pib) {
            $application = $this->draft($owner, $competition, 'NOPIB'.$index, subsidyType: CanonicalSubsidyType::codes()[$index]);
            $application->forceFill(['pib' => $pib])->save();

            $this->actingAs($owner)
                ->from(route('applications.show', $application))
                ->post(route('applications.final-submit', $application))
                ->assertSessionHasErrors(['error' => CanonicalBeneficiaryApplications::PIB_REQUIRED_MESSAGE]);
            $this->assertSame('draft', $application->fresh()->status);
        }

        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_women_and_youth_applications_are_not_counted(): void
    {
        $owner = $this->makeOwner();
        $pib = $this->pibFor($owner);
        foreach ([CompetitionProfileResolver::TYPE_WOMEN, CompetitionProfileResolver::TYPE_YOUTH] as $type) {
            $other = $this->draft($owner, $this->openCompetition($type), $type);
            $other->forceFill(['pib' => $pib, 'status' => 'submitted', 'submitted_at' => now()])->save();
        }

        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        foreach ([CanonicalSubsidyType::AUTHENTIC_LOCAL, CanonicalSubsidyType::INNOVATIVE] as $type) {
            $application = $this->draft($owner, $competition, $type, subsidyType: $type);
            $this->actingAs($owner)->post(route('applications.final-submit', $application))->assertSessionHasNoErrors();
            $this->assertSame('submitted', $application->fresh()->status);
        }

        $this->assertSame(2, FinalApplicationClaim::query()->count());
        $this->assertSame(2, FinalApplicationClaim::query()->where('competition_id', $competition->id)->count());
    }

    public function test_draft_lookup_allows_second_canonical_application_and_stops_at_two(): void
    {
        $owner = $this->makeOwner();
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $lookup = app(CanonicalBeneficiaryApplications::class);

        $this->assertNull($lookup->userApplicationForCompetition($competition, $owner->id));

        $first = $this->draft($owner, $competition, 'L1', subsidyType: CanonicalSubsidyType::AUTHENTIC_LOCAL);
        $this->assertSame($first->id, $lookup->userApplicationForCompetition($competition, $owner->id)?->id);

        $this->actingAs($owner)->post(route('applications.final-submit', $first))->assertSessionHasNoErrors();
        $this->assertNull($lookup->userApplicationForCompetition($competition, $owner->id));

        $second = $this->draft($owner, $competition, 'L2', subsidyType: CanonicalSubsidyType::DIGITALIZATION);
        $this->assertSame($second->id, $lookup->userApplicationForCompetition($competition, $owner->id)?->id);

        $this->actingAs($owner)->post(route('applications.final-submit', $second))->assertSessionHasNoErrors();
        $this->assertSame($first->id, $lookup->userApplicationForCompetition($competition, $owner->id)?->id);

        $women = $this->openCompetition(CompetitionProfileResolver::TYPE_WOMEN);
        $womenApplication = $this->draft($owner, $women, 'W');
        $womenApplication->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
        $this->assertSame($womenApplication->id, $lookup->userApplicationForCompetition($women, $owner->id)?->id);
    }

    public function test_database_rejects_same_type_and_third_slot_for_one_pib(): void
    {
        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $owner = $this->makeOwner();
        $pib = '44444444';
        $claim = fn (Application $application, string $type, int $slot) => FinalApplicationClaim::query()->create([
            'user_id' => $application->user_id,
            'competition_id' => $competition->id,
            'application_id' => $application->id,
            'subsidy_type' => $type,
            'beneficiary_pib' => $pib,
            'beneficiary_slot' => $slot,
            'claimed_at' => now(),
        ]);

        $claim($this->draft($owner, $competition, 'D1', withP2: false), CanonicalSubsidyType::AUTHENTIC_LOCAL, 1);

        $other = $this->makeOwner();
        $this->assertQueryRejected(fn () => $claim($this->draft($other, $competition, 'D2', withP2: false), CanonicalSubsidyType::AUTHENTIC_LOCAL, 2));
        $this->assertQueryRejected(fn () => $claim($this->draft($other, $competition, 'D3', withP2: false), CanonicalSubsidyType::INNOVATIVE, 1));
        $this->assertQueryRejected(fn () => $claim($this->draft($other, $competition, 'D4', withP2: false), CanonicalSubsidyType::DIGITALIZATION, 3));

        $claim($this->draft($other, $competition, 'D5', withP2: false), CanonicalSubsidyType::INNOVATIVE, 2);
        $this->assertSame(2, FinalApplicationClaim::query()->where('beneficiary_pib', $pib)->count());
    }

    // ---- E. Profile isolation -------------------------------------------

    public function test_women_and_youth_final_need_no_p2_or_pib_and_create_no_plan(): void
    {
        $owner = $this->makeOwner();
        $women = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_WOMEN), 'W', withP2: false);

        $this->actingAs($owner)->post(route('applications.final-submit', $women))
            ->assertRedirect(route('applications.show', $women));

        $this->assertSame('submitted', $women->fresh()->status);
        $this->assertNull($women->fresh()->pib);
        $this->assertSame(0, CanonicalInvestmentPlan::query()->count());
        $this->assertSame(0, FinalApplicationClaim::query()->count());
    }

    public function test_show_links_canonical_p2_only_for_canonical(): void
    {
        $owner = $this->makeOwner();
        $canonical = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL), 'SHOW-C');
        $women = $this->draft($owner, $this->openCompetition(CompetitionProfileResolver::TYPE_WOMEN), 'SHOW-W');

        $this->actingAs($owner)->get(route('applications.show', $canonical))
            ->assertOk()
            ->assertSee(route('applications.canonical-p2.edit', $canonical), false);
        $this->actingAs($owner)->get(route('applications.show', $women))
            ->assertOk()
            ->assertDontSee('Plan ulaganja (P2)');
    }

    // ---- fixtures ---------------------------------------------------------

    private function assertQueryRejected(callable $write): void
    {
        try {
            $write();
            $this->fail('Database must reject this claim.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function p2Payload(string $subsidyType = CanonicalSubsidyType::INNOVATIVE, string $total = '1000.00'): array
    {
        return [
            'subsidy_type' => $subsidyType,
            'total_investment' => $total,
            'own_contribution' => '200.00',
            'expected_subsidy' => '500.00',
            'expense_lines' => [
                ['cost_type' => 'Oprema', 'description' => 'Mašina za preradu', 'amount' => $total],
            ],
            'narrative_sections' => array_fill_keys(array_keys(\App\Services\Competitions\CanonicalInvestmentPlanService::NARRATIVE_FIELDS), 'Testni opis plana ulaganja.'),
        ];
    }

    private function openCompetition(string $type): Competition
    {
        return Competition::create([
            'title' => 'KN-SYNC-008A-'.$type.'-'.uniqid(),
            'description' => 'Canonical P2',
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

    private function makeOwner(): User
    {
        $this->jmbSequence++;

        return User::factory()->create([
            'email' => 'p2-'.uniqid().'@example.com',
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Podnosilac',
            'jmb' => '0101990'.str_pad((string) $this->jmbSequence, 6, '0', STR_PAD_LEFT),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
            'user_type' => 'Fizičko lice',
        ]);
    }

    private function pibFor(User $owner): string
    {
        return '7'.str_pad((string) $owner->id, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Canonical drafts are FINAL-ready canonical P1a + P2 + čl.14 prilozi (KN-SYNC-015);
     * $p1Typed = false leaves Obrazac P1 without a subsidy type so P2 may switch freely.
     */
    private function draft(
        User $owner,
        Competition $competition,
        string $suffix = 'DRAFT',
        bool $withP2 = true,
        string $subsidyType = CanonicalSubsidyType::INNOVATIVE,
        ?string $pib = null,
        bool $p1Typed = true,
    ): Application {
        $isCanonical = $competition->type === CompetitionProfileResolver::TYPE_CANONICAL;
        if ($isCanonical) {
            return $this->canonicalDraft($owner, $competition, [
                'subsidy_type' => $subsidyType,
                'p1_subsidy_type' => $p1Typed ? $subsidyType : null,
                'with_p2' => $withP2,
                'pib' => $pib ?? $this->pibFor($owner),
            ]);
        }

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'KN-SYNC-008A-'.$suffix,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'business_area' => 'usluge',
            'status' => 'draft',
            'is_registered' => false,
            'physical_person_name' => $owner->name,
            'physical_person_phone' => '067000000',
            'physical_person_email' => $owner->email,
            'physical_person_address' => 'Njegoševa 1, 85330 Kotor',
            'physical_person_jmbg' => $owner->jmb,
            'accuracy_declaration' => true,
            'pib' => null,
        ]);

        BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Plan ulaganja',
            'applicant_name' => $owner->name,
            'applicant_jmbg' => $owner->jmb,
            'applicant_address' => 'Njegoševa 1, 85330 Kotor',
            'applicant_phone' => '067000000',
            'applicant_email' => $owner->email,
            'finances_notice_confirmed' => true,
        ]);

        return $application->fresh(['businessPlan', 'competition']);
    }
}
