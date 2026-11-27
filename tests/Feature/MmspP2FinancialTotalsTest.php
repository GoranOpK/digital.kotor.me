<?php

namespace Tests\Feature;

use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\CanonicalInvestmentPlanService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Kn\Concerns\BuildsCanonicalApplicantDraft;
use Tests\TestCase;

class MmspP2FinancialTotalsTest extends TestCase
{
    use BuildsCanonicalApplicantDraft;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_form_calculates_totals_and_reopens_the_saved_draft(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);

        $first = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk()
            ->assertSee('data-mmsp-expense-amount', false)
            ->assertSee('js/mmsp-p2-financials.js', false)
            ->assertSee('id="total_investment"', false)
            ->assertSee('id="expected_subsidy"', false)
            ->assertSee('name="total_investment"', false)
            ->assertSee('name="expected_subsidy"', false);
        $html = $first->getContent();
        $this->assertMatchesRegularExpression('/id="total_investment"[^>]*\sreadonly(?:=|\s|>)/', $html);
        $this->assertMatchesRegularExpression('/id="expected_subsidy"[^>]*\sreadonly(?:=|\s|>)/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="own_contribution"[^>]*\sreadonly(?:=|\s|>)/', $html);

        $lines = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10.50'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '20.25'],
            ['cost_type' => 'Materijal', 'description' => 'Ambalaža', 'amount' => '1.00'],
            ['cost_type' => 'Usluga', 'description' => 'Montaža', 'amount' => '0.01'],
            ['cost_type' => 'Obuka', 'description' => 'Obuka osoblja', 'amount' => '100.00'],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '131.76', '31.76', '100.00'))
            ->assertSessionHasNoErrors();

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame('131.76', $plan->total_investment);
        $this->assertSame('31.76', $plan->own_contribution);
        $this->assertSame('100.00', $plan->expected_subsidy);
        $this->assertCount(5, $plan->expenseLines());

        $reopened = $this->actingAs($owner)
            ->get(route('applications.canonical-p2.edit', $application))
            ->assertOk();
        $reopened->assertSee('value="131.76"', false);
        $reopened->assertSee('value="31.76"', false);
        $reopened->assertSee('value="100.00"', false);
        foreach (['10.50', '20.25', '1.00', '0.01', '100.00'] as $amount) {
            $reopened->assertSee('value="'.$amount.'"', false);
        }

        $changed = $lines;
        $changed[1]['amount'] = '22.25';
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($changed, '133.76', '40.00', '93.76'))
            ->assertSessionHasNoErrors();
        $plan->refresh();
        $this->assertSame('133.76', $plan->total_investment);
        $this->assertSame('40.00', $plan->own_contribution);
        $this->assertSame('93.76', $plan->expected_subsidy);

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '131.76', '131.76', '0.00'))
            ->assertSessionHasErrors('expected_subsidy');
        $plan->refresh();
        $this->assertSame('93.76', $plan->expected_subsidy);
    }

    public function test_expense_table_starts_with_one_row_and_keeps_submitted_rows(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);
        $edit = route('applications.canonical-p2.edit', $application);

        $first = $this->actingAs($owner)->get($edit)->assertOk();
        $first->assertSee('data-mmsp-add-expense-row', false)
            ->assertSee('+ Dodaj red', false)
            ->assertSee('mmsp-p2-expense-print-hide', false)
            ->assertDontSee('name="expense_lines[1][cost_type]"', false);
        $this->assertSame(1, $this->expenseRowCount($first->getContent()));
        $script = file_get_contents(public_path('js/mmsp-p2-financials.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString("window.addEventListener('beforeprint'", $script);
        $this->assertStringContainsString("window.addEventListener('afterprint'", $script);
        $this->assertStringNotContainsString("document.addEventListener('beforeprint'", $script);
        $this->assertStringNotContainsString("document.addEventListener('afterprint'", $script);
        $this->assertStringContainsString("querySelectorAll('[data-mmsp-expense-amount]')", $script);
        $this->assertStringContainsString('data-mmsp-add-expense-row', $script);

        $lines = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10.00'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '5.00'],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '15.00', '5.00', '10.00'))
            ->assertSessionHasNoErrors();

        $reopened = $this->actingAs($owner)->get($edit)->assertOk();
        $reopened->assertSee('value="Oprema"', false)
            ->assertSee('value="Alat"', false)
            ->assertDontSee('name="expense_lines[2][cost_type]"', false);
        $this->assertSame(2, $this->expenseRowCount($reopened->getContent()));

        $invalid = $lines;
        $invalid[] = ['cost_type' => 'Prevoz', 'description' => 'Dostava', 'amount' => 'nije iznos'];
        $this->actingAs($owner)
            ->from($edit)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($invalid, '15.00', '5.00', '10.00'))
            ->assertRedirect($edit)
            ->assertSessionHasErrors('expense_lines.2.amount');

        $kept = $this->actingAs($owner)->get($edit)->assertOk();
        $kept->assertSee('value="Oprema"', false)
            ->assertSee('value="Alat"', false)
            ->assertSee('value="Prevoz"', false)
            ->assertSee('value="nije iznos"', false);
        $this->assertSame(3, $this->expenseRowCount($kept->getContent()));

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $plan->forceFill(['frozen_at' => now()])->save();
        $locked = $this->actingAs($owner)->get($edit)->assertOk();
        $locked->assertDontSee('data-mmsp-add-expense-row', false)
            ->assertDontSee('+ Dodaj red', false)
            ->assertSee('value="Oprema"', false)
            ->assertSee('value="Alat"', false)
            ->assertDontSee('value="Prevoz"', false);
        $this->assertSame(2, $this->expenseRowCount($locked->getContent()));
        $lockedTable = $this->expenseTable($locked->getContent());
        $this->assertSame(3, preg_match_all('/<th\b/', $lockedTable));
        $this->assertStringNotContainsString('Ukloni', $lockedTable);
        $this->assertStringNotContainsString('mmsp-p2-expense-remove', $lockedTable);
    }

    public function test_removed_expense_rows_are_reindexed_and_do_not_return(): void
    {
        $owner = $this->owner();
        $application = $this->canonicalDraft($owner, $this->competition(), [
            'with_p2' => false,
            'with_documents' => false,
        ]);
        $edit = route('applications.canonical-p2.edit', $application);

        $first = $this->actingAs($owner)->get($edit)->assertOk();
        $html = $first->getContent();
        $table = $this->expenseTable($html);
        $this->assertSame(4, preg_match_all('/<th\b/', $table));
        $this->assertSame(1, substr_count($table, 'data-mmsp-remove-expense-row'));
        $this->assertStringContainsString('>Ukloni<', $table);
        $this->assertMatchesRegularExpression(
            '/<th class="mmsp-p2-expense-remove"><\/th>/',
            $table
        );
        $this->assertStringContainsString('data-mmsp-max-expense-lines="50"', $html);
        $this->assertMatchesRegularExpression(
            '/\.mmsp-p2-expense-remove \{ display:none !important; \}/',
            $html
        );
        $this->assertStringContainsString('width: 1%;', $html);
        $this->assertStringContainsString('.mmsp-p2-remove-row {', $html);
        $this->assertStringContainsString('padding: 8px 16px;', $html);
        $this->assertStringContainsString('border-radius: 6px;', $html);
        $script = file_get_contents(public_path('js/mmsp-p2-financials.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString('function reindexExpenseRows', $script);
        $this->assertStringContainsString('function removeExpenseRow', $script);
        $this->assertStringContainsString("target.closest('[data-mmsp-remove-expense-row]')", $script);
        $this->assertStringContainsString("form.dispatchEvent(new Event('input'", $script);
        $this->assertStringContainsString("|| '50'", $script);
        $this->assertStringNotContainsString('confirm(', $script);
        $this->assertStringNotContainsString('alert(', $script);

        $lines = [
            ['cost_type' => 'Oprema', 'description' => 'Mašina', 'amount' => '10.00'],
            ['cost_type' => 'Alat', 'description' => 'Set alata', 'amount' => '20.00'],
            ['cost_type' => 'Prevoz', 'description' => 'Dostava', 'amount' => '30.00'],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($lines, '60.00', '10.00', '50.00'))
            ->assertSessionHasNoErrors();

        $kept = [
            $lines[0],
            $lines[2],
        ];
        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($kept, '40.00', '10.00', '30.00'))
            ->assertSessionHasNoErrors();

        $reopened = $this->actingAs($owner)->get($edit)->assertOk();
        $reopened->assertSee('value="Oprema"', false)
            ->assertSee('value="Prevoz"', false)
            ->assertDontSee('value="Alat"', false)
            ->assertDontSee('name="expense_lines[2][cost_type]"', false);
        $this->assertSame(2, $this->expenseRowCount($reopened->getContent()));
        $this->assertSame(2, substr_count($this->expenseTable($reopened->getContent()), 'data-mmsp-remove-expense-row'));

        $invalid = [
            $lines[0],
            ['cost_type' => 'Materijal', 'description' => 'Ambalaža', 'amount' => 'nije iznos'],
        ];
        $this->actingAs($owner)
            ->from($edit)
            ->put(route('applications.canonical-p2.update', $application), $this->payload($invalid, '40.00', '10.00', '30.00'))
            ->assertRedirect($edit)
            ->assertSessionHasErrors('expense_lines.1.amount');

        $keptInput = $this->actingAs($owner)->get($edit)->assertOk();
        $keptInput->assertSee('value="Oprema"', false)
            ->assertSee('value="Materijal"', false)
            ->assertSee('value="nije iznos"', false)
            ->assertDontSee('value="Alat"', false)
            ->assertDontSee('value="Prevoz"', false);
        $this->assertSame(2, $this->expenseRowCount($keptInput->getContent()));

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame(['Oprema', 'Prevoz'], array_column($plan->expenseLines(), 'cost_type'));

        $this->actingAs($owner)
            ->from($edit)
            ->put(route('applications.canonical-p2.update', $application), $this->payload([
                ['cost_type' => '', 'description' => '', 'amount' => ''],
            ], '0.00', '10.00', '-5.00'))
            ->assertRedirect($edit)
            ->assertSessionHasErrors('expected_subsidy');

        $emptied = $this->actingAs($owner)->get($edit)->assertOk();
        $emptied->assertDontSee('value="Oprema"', false)
            ->assertDontSee('value="Materijal"', false)
            ->assertDontSee('value="Prevoz"', false)
            ->assertDontSee('value="Alat"', false);
        $this->assertSame(1, $this->expenseRowCount($emptied->getContent()));
        $plan->refresh();
        $this->assertSame(['Oprema', 'Prevoz'], array_column($plan->expenseLines(), 'cost_type'));

        $this->actingAs($owner)
            ->put(route('applications.canonical-p2.update', $application), $this->payload([
                $lines[0],
            ], '10.00', '4.00', '6.00'))
            ->assertSessionHasNoErrors();

        $final = $this->actingAs($owner)->get($edit)->assertOk();
        $final->assertSee('value="Oprema"', false)
            ->assertDontSee('value="Prevoz"', false);
        $this->assertSame(1, $this->expenseRowCount($final->getContent()));

        $plan->refresh();
        $plan->forceFill(['frozen_at' => now()])->save();
        $locked = $this->actingAs($owner)->get($edit)->assertOk();
        $lockedTable = $this->expenseTable($locked->getContent());
        $this->assertSame(3, preg_match_all('/<th\b/', $lockedTable));
        $this->assertStringNotContainsString('Ukloni', $lockedTable);
        $this->assertStringNotContainsString('data-mmsp-remove-expense-row', $locked->getContent());
        $locked->assertDontSee('js/mmsp-p2-financials.js', false)
            ->assertSee('value="Oprema"', false)
            ->assertDontSee('value="Prevoz"', false);
    }

    private function expenseTable(string $html): string
    {
        $this->assertSame(1, preg_match('/<table[^>]*data-mmsp-expense-table[\s\S]*?<\/table>/', $html, $matches));

        return $matches[0];
    }

    private function expenseRowCount(string $html): int
    {
        return preg_match_all('/name="expense_lines\[\d+\]\[cost_type\]"/', $html) ?: 0;
    }

    private function owner(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'name' => 'P2 Podnosilac',
            'jmb' => '0101990123456',
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
            'phone' => '067000000',
        ]);
    }

    private function competition(): Competition
    {
        return Competition::create([
            'title' => 'MMSP P2 zbir',
            'description' => 'Test',
            'type' => CompetitionProfileResolver::TYPE_CANONICAL,
            'status' => 'published',
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * @param  list<array{cost_type: string, description: string, amount: string}>  $lines
     * @return array<string, mixed>
     */
    private function payload(array $lines, string $total, string $own, string $subsidy): array
    {
        return [
            'subsidy_type' => CanonicalSubsidyType::INNOVATIVE,
            'total_investment' => $total,
            'own_contribution' => $own,
            'expected_subsidy' => $subsidy,
            'expense_lines' => $lines,
            'narrative_sections' => array_fill_keys(array_keys(CanonicalInvestmentPlanService::NARRATIVE_FIELDS), 'Testni opis plana ulaganja.'),
        ];
    }
}
