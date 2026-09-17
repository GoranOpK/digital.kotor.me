<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\ApplicationPrigovorService;
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

        if ($application->competition?->isOmladinskoProfile()) {
            $validated = $request->validate([
                'contested' => 'required|array|min:1',
                'contested.*' => 'integer|in:1,2,3',
                'criterion_obrazlozenja' => 'nullable|array',
                'criterion_obrazlozenja.1' => 'nullable|string|max:5000',
                'criterion_obrazlozenja.2' => 'nullable|string|max:5000',
                'criterion_obrazlozenja.3' => 'nullable|string|max:5000',
            ]);

            $this->prigovors->submit(
                $application,
                $user,
                '',
                $validated['contested'],
                $validated['criterion_obrazlozenja'] ?? [],
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
