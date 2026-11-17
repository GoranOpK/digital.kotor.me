<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\ApplicationPrigovorService;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationPrigovorController extends Controller
{
    public function __construct(
        protected ApplicationPrigovorService $prigovors,
    ) {}

    public function store(Request $request, Application $application): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $application->loadMissing('competition');
        LegacyCommissionFlowGuard::abortUnlessAllowed($application->competition);

        if ($application->competition?->isOmladinskoProfile()) {
            $validated = $request->validate([
                'obrazlozenje' => 'required|string|max:5000',
                'contested' => 'sometimes|array',
                'contested.*' => 'integer|in:1,2,3',
            ]);

            $this->prigovors->submit(
                $application,
                $user,
                $validated['obrazlozenje'],
                $validated['contested'] ?? [],
            );
        } else {
            $validated = $request->validate([
                'obrazlozenje' => 'required|string|max:5000',
            ]);

            $this->prigovors->submit($application, $user, $validated['obrazlozenje']);
        }

        return redirect()->route('applications.show', $application)
            ->with('success', 'Prigovor je podnesen Komisiji.');
    }
}
