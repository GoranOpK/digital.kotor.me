<?php

namespace App\Http\Middleware;

use App\Identity\Runtime\IdentityMutationDeniedException;
use App\Identity\Runtime\IdentityMutationGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class IdentityWriteFreezeMiddleware
{
    public function __construct(
        private readonly IdentityMutationGuard $guard,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($request->isMethod('post') && $request->is('register')) {
                $this->guard->assertSubjectCreationAllowed();
            }

            if ($request->routeIs('profile.update')) {
                if ($this->guard->canonicalHttpWriteAllowed()) {
                    $this->guard->assertCanonicalHttpWriteAllowed();
                } else {
                    $this->guard->assertLegacyIdentityMutationAllowed();
                }
            }

            if ($request->routeIs('payments.declaration.store')) {
                $this->guard->assertLegacyIdentityMutationAllowed();
            }
        } catch (IdentityMutationDeniedException $e) {
            abort(403, $e->getMessage());
        }

        return $next($request);
    }
}
