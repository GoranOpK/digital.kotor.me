<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\CompetitionDecisionDocumentBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ZpDecisionArticleOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_one_follows_the_final_ranking_and_commission_support(): void
    {
        $competition = Competition::create([
            'title' => 'ŽP odluka',
            'description' => 'Opis',
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'budget' => 100000,
            'deadline_days' => 20,
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'published_at' => now()->subDays(30),
        ]);

        $ana = $this->supported($competition, 'Ana Ranije', 45.25, 1, 1000.50, 'approved', 'Plan Ana');
        BusinessPlan::create([
            'application_id' => $ana->id,
            'required_amount' => 5000.50,
        ]);
        $ana->forceFill(['total_budget_needed' => 1])->save();

        $bora = $this->supported($competition, 'Bora Kasnije', 45.25, 1, 800, 'approved', 'Plan Bora', 2000);

        $fali = $this->supported($competition, null, 46, 2, 20, 'approved', 'Plan Bez Imena');
        $fali->user->forceFill(['name' => 'Nalog Korisnice'])->save();

        $ceca = $this->supported($competition, 'Ceca Treca', 40.10, 3, 500, 'approved', 'Plan Ceca');
        $ceca->forceFill(['total_budget_needed' => 777.25])->save();

        $prag = $this->supported($competition, 'Prag Trideset', 30, 4, 10, 'approved', 'Plan Prag');

        $dora = $this->supported($competition, 'Dora BezRanga', 50, null, 100, 'approved', 'Plan Dora');
        $ema = $this->supported($competition, 'Ema Nize', 32, null, 50, 'approved', 'Plan Ema');

        $rejected = $this->supported($competition, 'Odbijena Ostaje', 48, 1, 999, 'rejected', 'Plan Odbijena');
        $rejected->forceFill([
            'commission_decision' => 'odbija',
            'status' => 'rejected',
        ])->save();

        $rejectedStatus = $this->supported($competition, 'Status Odbijena', 47, 1, 999, 'rejected', 'Plan Status');

        $eliminated = $this->supported($competition, 'Eliminisana Ostaje', 44, 1, 999, 'approved', 'Plan Eliminisana');
        ApplicationEliminatoryCheck::create([
            'application_id' => $eliminated->id,
            'criterion_1' => false,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now(),
        ]);

        $below = $this->supported($competition, 'Ispod Praga', 29.99, 5, 999, 'approved', 'Plan Ispod');

        $builder = app(CompetitionDecisionDocumentBuilder::class);
        $winners = $builder->articleOneApplications($competition->fresh());

        $this->assertSame(
            [$ana->id, $bora->id, $fali->id, $ceca->id, $prag->id, $dora->id, $ema->id],
            $winners->pluck('id')->all()
        );
        $this->assertTrue($ana->fresh()->meetsMinimumScore());
        $this->assertFalse($below->fresh()->meetsMinimumScore());
        $this->assertTrue($eliminated->fresh()->isEliminatedFromScoring());

        $html = view('competitions.partials.decision-document', $builder->build($competition->fresh()))->render();

        $this->assertSame(
            'Preduzetnica Ana Ranije.',
            $ana->fresh()->getApplicantDisplayForDecision()
        );
        $this->assertStringContainsString('Preduzetnica Ana Ranije.', $html);
        $this->assertStringNotContainsString('Nalog Korisnice', $html);
        $this->assertStringContainsString('Ime preduzetnice nije upisano u prijavi.', $html);
        $this->assertStringContainsString('45,25', $html);
        $this->assertStringContainsString('40,10', $html);
        $this->assertStringContainsString('30,00', $html);
        $this->assertStringContainsString('50,00', $html);
        $this->assertStringNotContainsString('45,3', $html);
        $this->assertStringContainsString('1.000,50 €', $html);
        $this->assertStringContainsString('800,00 €', $html);
        $this->assertStringContainsString('5.000,50 €', $html);
        $this->assertStringContainsString('777,25 €', $html);
        $this->assertStringNotContainsString('Traženi iznos', $html);
        $this->assertStringNotContainsString('2.000,00', $html);
        $this->assertStringNotContainsString('Plan Odbijena', $html);
        $this->assertStringNotContainsString('Plan Status', $html);
        $this->assertStringNotContainsString('Plan Eliminisana', $html);
        $this->assertStringNotContainsString('Plan Ispod', $html);
        $this->assertStringNotContainsString('Odbijena Ostaje', $html);
        $this->assertStringNotContainsString('Eliminisana Ostaje', $html);
        $this->assertStringNotContainsString('Ispod Praga', $html);

        $this->assertLessThan(strpos($html, 'Preduzetnica Bora Kasnije.'), strpos($html, 'Preduzetnica Ana Ranije.'));
        $this->assertLessThan(strpos($html, 'Ime preduzetnice nije upisano u prijavi.'), strpos($html, 'Preduzetnica Bora Kasnije.'));
        $this->assertLessThan(strpos($html, 'Preduzetnica Ceca Treca.'), strpos($html, 'Ime preduzetnice nije upisano u prijavi.'));
        $this->assertLessThan(strpos($html, 'Preduzetnica Dora BezRanga.'), strpos($html, 'Preduzetnica Prag Trideset.'));
        $this->assertLessThan(strpos($html, 'Preduzetnica Ema Nize.'), strpos($html, 'Preduzetnica Dora BezRanga.'));

        $this->assertSame(5000.50, $ana->fresh()->displayRequiredFunds());
        $this->assertSame(777.25, $ceca->fresh()->displayRequiredFunds());
        $this->assertSame(800.0, (float) $bora->fresh()->approved_amount);

        $method = new ReflectionMethod(AdminController::class, 'decisionIdentityBlockMessage');
        $message = $method->invoke(app(AdminController::class), $competition->fresh());
        $this->assertStringContainsString('Plan Bez Imena', $message);
        $this->assertStringContainsString(Application::DECISION_IDENTITY_APPLICANT_NAME_MISSING, $message);
        $this->assertStringNotContainsString('Plan Ana', $message);
    }

    public function test_women_conclusion_label_is_podrzava_se_for_full_and_partial_amounts(): void
    {
        $ranking = file_get_contents(resource_path('views/admin/competitions/ranking.blade.php'));
        $show = file_get_contents(resource_path('views/evaluation/show.blade.php'));
        $youth = file_get_contents(resource_path('views/evaluation/_youth_preliminary_ranking.blade.php'));

        $this->assertStringContainsString("value=\"podrzava_potpuno\"", $ranking);
        $this->assertStringContainsString('a. Podržava se', $ranking);
        $this->assertStringContainsString('Podržava se', $ranking);
        $this->assertStringContainsString('name="approved_amount"', $ranking);
        $this->assertStringNotContainsString('Podržava u potpunosti', $ranking);
        $this->assertStringNotContainsString('Podržava se prijava', $ranking);

        $this->assertStringContainsString("'podrzava_potpuno' => 'a. Podržava se'", $show);
        $this->assertStringNotContainsString('Podržava u potpunosti', $show);
        $this->assertStringNotContainsString('Podržava se prijava', $show);

        $this->assertStringContainsString('a. Podržava u potpunosti', $youth);
        $this->assertStringNotContainsString('Podržava se', $youth);
    }

    private function supported(
        Competition $competition,
        ?string $name,
        float $score,
        ?int $position,
        float $amount,
        string $status,
        string $plan,
        ?float $requested = null,
    ): Application {
        $user = User::factory()->create();

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => $plan,
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => $name,
            'business_stage' => 'započinjanje',
            'status' => $status,
            'submitted_at' => now()->subDays(20),
            'final_score' => $score,
            'ranking_position' => $position,
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => $amount,
            'requested_amount' => $requested,
        ]);
    }
}
