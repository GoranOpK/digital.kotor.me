<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CanonicalInvestmentPlan;
use App\Models\CommissionMember;
use App\Models\User;
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
        $readOnlyVisitor = $this->authorizeCanonicalFormRead($request, $application);

        $plan = CanonicalInvestmentPlan::query()->where('application_id', $application->id)->first();
        $application->loadMissing('canonicalApplicantForm');

        return view('applications.canonical-p2', [
            'application' => $application,
            'plan' => $plan,
            'subsidyTypes' => CanonicalSubsidyType::labels(),
            'readOnly' => $readOnlyVisitor || ($plan?->isFrozen() ?? false) || $application->isApplicantContentWriteLocked(),
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
            'narrative_sections',
        ]));

        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error])->withInput();
        }

        return redirect()->route('applications.show', $application)
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

    private function authorizeCanonicalFormRead(Request $request, Application $application): bool
    {
        $application->loadMissing(['competition', 'finalClaim']);
        if ($application->competition?->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            abort(404);
        }

        $user = $request->user();
        if ($user instanceof User && (int) $application->user_id === (int) $user->id) {
            return false;
        }

        $competition = $application->competition;
        $submitted = $application->status !== 'draft';
        $phaseOpen = $competition !== null && (
            $competition->isCanonicalTestPreview()
            || $competition->isApplicationDeadlinePassed()
            || in_array($competition->status, ['closed', 'completed'], true)
        );
        $role = $user?->role?->name;
        $staff = in_array($role, ['admin', 'superadmin', 'konkurs_admin', 'kk_admin'], true);
        $member = $user instanceof User
            && $competition?->commission_id
            && CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id) !== null;
        if ($submitted && $phaseOpen && ($staff || $member)) {
            return true;
        }

        abort(403, 'Nemate pristup ovoj prijavi.');
    }
}
