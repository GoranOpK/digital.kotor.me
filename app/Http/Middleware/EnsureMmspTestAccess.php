<?php

namespace App\Http\Middleware;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Competition;
use App\Models\Contract;
use App\Models\Report;
use App\Services\MmspTestAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks canonical MMSP test-mode resources unless the account is allowed.
 * Runs after module_access_restrict so existing role redirects stay in place.
 */
class EnsureMmspTestAccess
{
    public function __construct(
        private readonly MmspTestAccessService $access,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $competition = $this->competitionFromRequest($request);
        if ($competition === null || ! $competition->isCanonicalTestMode()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user === null) {
            abort(404);
        }

        $user->loadMissing('role');
        $role = $user->role?->name;

        if (in_array($role, ['admin', 'superadmin', 'konkurs_admin', 'komisija'], true)) {
            return $next($request);
        }

        if ($role === 'korisnik' && $this->access->allows($user)) {
            return $next($request);
        }

        abort(404);
    }

    private function competitionFromRequest(Request $request): ?Competition
    {
        $competition = $request->route('competition');
        if ($competition instanceof Competition) {
            return $competition;
        }

        $application = $this->applicationFromRequest($request);
        if ($application === null) {
            return null;
        }

        $application->loadMissing('competition');

        return $application->competition;
    }

    private function applicationFromRequest(Request $request): ?Application
    {
        $application = $request->route('application');
        if ($application instanceof Application) {
            return $application;
        }

        $document = $request->route('document');
        if ($document instanceof ApplicationDocument) {
            $document->loadMissing('application');

            return $document->application;
        }

        $contract = $request->route('contract');
        if ($contract instanceof Contract) {
            $contract->loadMissing('application');

            return $contract->application;
        }

        $report = $request->route('report');
        if ($report instanceof Report) {
            $report->loadMissing('application');

            return $report->application;
        }

        return null;
    }
}
