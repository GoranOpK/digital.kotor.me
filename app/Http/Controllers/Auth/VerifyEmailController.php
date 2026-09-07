<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     * The signed link is not a login mechanism (D12).
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        $user = $request->user();

        if ((int) $user->id !== (int) $id) {
            abort(403, 'Ovaj link za verifikaciju ne pripada prijavljenom nalogu.');
        }

        if (! hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Link za verifikaciju nije ispravan.');
        }

        $intended = $request->session()->get('url.intended');
        if (is_string($intended) && str_contains($intended, '/verify-email/')) {
            $request->session()->forget('url.intended');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false))->with('verified', true);
    }
}
