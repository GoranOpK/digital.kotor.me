<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proveri da li je hash validan
        if (!hash_equals((string) $hash, sha1($user->email))) {
            abort(403, 'Invalid verification link.');
        }

        // Proveri da li je signed URL validan
        if (!URL::hasValidSignature($request)) {
            abort(403, 'Invalid or expired verification link.');
        }

        // Proveri da li je email već verifikovan
        if ($user->hasVerifiedEmail()) {
            // Automatski prijavi korisnika i preusmeri ga
            auth()->login($user);
            return redirect()->intended(route('dashboard', absolute: false))->with('verified', true);
        }

        // Verifikuj email
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Automatski prijavi korisnika nakon verifikacije
        auth()->login($user);

        return redirect()->intended(route('dashboard', absolute: false))->with('verified', true);
    }
}
