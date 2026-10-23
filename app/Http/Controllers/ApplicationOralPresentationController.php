<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Support\CommissionProfileConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationOralPresentationController extends Controller
{
    public function edit(Competition $competition, Application $application): RedirectResponse
    {
        return $this->rejectYouthOralRecording($competition, $application);
    }

    public function store(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        return $this->rejectYouthOralRecording($competition, $application);
    }

    public function update(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        return $this->rejectYouthOralRecording($competition, $application);
    }

    public function complete(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        return $this->rejectYouthOralRecording($competition, $application);
    }

    protected function rejectYouthOralRecording(Competition $competition, Application $application): RedirectResponse
    {
        $this->authorizeOralAccess($competition, $application);

        return redirect()->route('admin.competitions.show', $competition);
    }

    protected function authorizeOralAccess(Competition $competition, Application $application): CommissionMember
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE);
        }

        if ((int) $application->competition_id !== (int) $competition->id) {
            abort(404);
        }

        $user = Auth::user();
        $member = CommissionMember::activeForCompetition($user->id, $competition);

        if (! $member || $member->position !== 'predsjednik') {
            abort(403, CommissionProfileConfig::SESSION_NOT_CHAIRMAN_MESSAGE);
        }

        if ((int) $member->commission_id !== (int) $competition->commission_id) {
            abort(403, CommissionProfileConfig::SESSION_NOT_CHAIRMAN_MESSAGE);
        }

        if (! in_array($competition->status, ['closed', 'completed'], true)
            && ! $competition->isApplicationDeadlinePassed()) {
            abort(403, 'Evidencija sjednice dostupna je tek nakon isteka roka za prijave.');
        }

        return $member;
    }
}
