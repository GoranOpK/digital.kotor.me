<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictKkAdminAccess
{
    /**
     * Limit kk_admin korisnika samo na kalendar kulture i profil.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->role || $user->role->name !== 'kk_admin') {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        $allowedRouteNames = [
            'dashboard',
            'logout',
            'profile.edit',
            'profile.update',
            'profile.password.update',
            'cultural-calendar.index',
            'cultural-calendar.events',
        ];

        if ($routeName && in_array($routeName, $allowedRouteNames, true)) {
            return $next($request);
        }

        if ($routeName && str_starts_with($routeName, 'cultural-events.')) {
            return $next($request);
        }

        if ($request->is('kalendar-kulture*') || $request->is('profile*')) {
            return $next($request);
        }

        return redirect()->route('cultural-calendar.index');
    }
}
