<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictRoleModuleAccess
{
    /**
     * Limit kk_admin i konkurs_admin na svoje module.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->role) {
            return $next($request);
        }

        $roleName = $user->role->name;
        $routeName = $request->route()?->getName();

        $commonAllowed = [
            'dashboard',
            'logout',
            'profile.edit',
            'profile.update',
            'profile.password.update',
        ];

        if ($routeName && in_array($routeName, $commonAllowed, true)) {
            return $next($request);
        }

        if ($roleName === 'kk_admin') {
            $allowedRouteNames = [
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

        if ($roleName === 'konkurs_admin') {
            $allowedRouteNames = [
                'competitions.index',
                'competitions.show',
                'competitions.archive',
                'admin.dashboard',
                'admin.applications.show',
                'admin.competitions.index',
                'admin.competitions.show',
                'admin.competitions.create',
                'admin.competitions.store',
                'admin.competitions.edit',
                'admin.competitions.update',
                'admin.competitions.publish',
                'admin.competitions.destroy',
                'admin.competitions.close',
                'admin.competitions.ranking',
                'admin.competitions.select-winners',
                'admin.competitions.decision',
                'admin.commissions.index',
                'admin.commissions.create',
                'admin.commissions.store',
                'admin.commissions.show',
                'admin.commissions.edit',
                'admin.commissions.update',
                'admin.commissions.destroy',
                'admin.commissions.members.add',
                'admin.commissions.members.sign',
                'admin.commissions.members.store-declarations',
                'admin.commissions.members.update-status',
                'admin.commissions.members.delete',
            ];

            if ($routeName && in_array($routeName, $allowedRouteNames, true)) {
                return $next($request);
            }

            if ($request->is('admin/competitions*') || $request->is('admin/commissions*') || $request->is('competitions*') || $request->is('profile*')) {
                return $next($request);
            }

            return redirect()->route('admin.dashboard');
        }

        // Ostale role nemaju dodatna ograničenja ovim middleware-om.
        return $next($request);
    }
}
