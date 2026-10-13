<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\Competitions\Decision\CanonicalResultPublicationService;
use Illuminate\View\View;

/**
 * KN-SYNC-009 — public canonical result: competition, Odluka number/date, publication date and
 * the signed formal Odluka copy. Nothing internal is exposed.
 */
class CanonicalPublicResultController extends Controller
{
    public function show(Competition $competition, CanonicalResultPublicationService $publication): View
    {
        $result = $publication->publicResult($competition);
        if ($result === null) {
            abort(404);
        }

        return view('competitions.canonical-result', ['result' => $result]);
    }
}
