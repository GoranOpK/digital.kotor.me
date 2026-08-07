<?php

namespace App\Http\Middleware;

use App\Support\CulturalPortalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uski gate za KK urednički portal (PO-ORG-04). Ne mijenja RoleMiddleware.
 */
class EnsureCulturalEditorialPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! CulturalPortalAccess::allows($request->user())) {
            abort(403);
        }

        return $next($request);
    }
}
