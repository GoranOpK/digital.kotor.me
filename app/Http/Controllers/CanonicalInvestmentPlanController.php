<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CanonicalInvestmentPlan;
use App\Services\Competitions\CanonicalInvestmentPlanService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin canonical Obrazac P2 surface (vrsta subvencije + finansijski okvir ulaganja).
 * Women/Youth never reach this form.
 */
class CanonicalInvestmentPlanController extends Controller
{
    public function edit(Request $request, Application $application): View
    {
        $this->authorizeOwnerOnCanonical($request, $application);

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();

        return view('applications.canonical-p2', [
            'application' => $application,
            'plan' => $plan,
            'subsidyTypes' => CanonicalSubsidyType::labels(),
            'readOnly' => ($plan?->isFrozen() ?? false) || $application->isApplicantContentWriteLocked(),
        ]);
    }

    public function update(
        Request $request,
        Application $application,
        CanonicalInvestmentPlanService $plans,
    ): RedirectResponse {
        $this->authorizeOwnerOnCanonical($request, $application);

        $result = $plans->save($application, $request->user(), $request->only([
            'subsidy_type',
            'total_investment',
            'own_contribution',
            'expected_subsidy',
            'expense_lines',
        ]));

        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error])->withInput();
        }

        return redirect()->route('applications.canonical-p2.edit', $application)
            ->with('success', 'Plan ulaganja (Obrazac P2) je sačuvan.');
    }

    private function authorizeOwnerOnCanonical(Request $request, Application $application): void
    {
        if ((int) $application->user_id !== (int) $request->user()?->id) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        $application->loadMissing('competition');
        if ($application->competition?->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            abort(404);
        }
    }
}
