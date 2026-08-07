<?php

namespace App\Http\Middleware;

use App\Support\CulturalModeratorEventAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TS-010.1 — samo aktivni Moderator (ovlašćenje), ne čisti kk_admin bez Mod grant-a.
 */
class EnsureActiveCulturalModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! CulturalModeratorEventAccess::isActiveModerator($user)) {
            abort(403);
        }

        return $next($request);
    }
}
