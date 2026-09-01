<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $default = route('home', absolute: false);
        $user = Auth::user();
        if ($user && $user->role && $user->role->name === 'kk_admin') {
            $default = route('cultural-calendar.index', absolute: false);
        } elseif ($user && $user->role && $user->role->name === 'konkurs_admin') {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.competitions.index', [
                'type' => 'zensko',
                'tab' => 'active',
            ]);
        }

        return $this->redirectAfterLogin($request, $default);
    }

    /**
     * Preserve intended only for safe same-app URLs (no open redirect).
     */
    private function redirectAfterLogin(Request $request, string $default): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return redirect()->to($default);
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return redirect()->to($intended);
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && (str_starts_with($intended, $appUrl.'/') || $intended === $appUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($default);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
