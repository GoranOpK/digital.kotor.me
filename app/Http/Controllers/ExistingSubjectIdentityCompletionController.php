<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteExistingSubjectIdentityRequest;
use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CountryCatalog;
use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\Runtime\ExistingSubjectIdentityCompletionService;
use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityPrefill;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use App\Identity\Runtime\IdentityMutationDeniedException;
use App\Identity\Runtime\IdentityUseGateException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExistingSubjectIdentityCompletionController extends Controller
{
    public function create(
        ExistingSubjectIdentityEligibility $eligibility,
        ExistingSubjectIdentityPrefill $prefill,
        ExistingSubjectIdentityReturnTo $returnTo,
    ): View|RedirectResponse {
        $user = Auth::user();
        $result = $eligibility->inspect($user);

        if ($result->isCurrent()) {
            return redirect($returnTo->consume());
        }

        if (! $result->eligible || $result->branch === null) {
            Log::info('identity.completion', [
                'user_id' => $user?->id,
                'branch' => $result->branch,
                'outcome' => 'ineligible',
                'reason_code' => $result->denyReason,
            ]);

            abort(403, 'Dopuna identiteta subjekta nije dozvoljena.');
        }

        Log::info('identity.completion', [
            'user_id' => $user->id,
            'branch' => $result->branch,
            'outcome' => 'shown',
            'reason_code' => 'form',
        ]);

        return view('identity.completion', [
            'branch' => $result->branch,
            'prefill' => $prefill->forUser($user, $result->branch),
            'callingCodes' => PhoneCallingCodeCatalog::pickerEntries(),
            'countryEntries' => CountryCatalog::entries(),
        ]);
    }

    public function store(
        CompleteExistingSubjectIdentityRequest $request,
        ExistingSubjectIdentityCompletionService $service,
        ExistingSubjectIdentityReturnTo $returnTo,
    ): RedirectResponse {
        $user = $request->user();

        try {
            $service->complete($user, $request->validated());
        } catch (IdentityUseGateException|IdentityMutationDeniedException|CanonicalIdentityWriteException $e) {
            abort(403, $e->getMessage());
        }

        return redirect($returnTo->consume());
    }
}
