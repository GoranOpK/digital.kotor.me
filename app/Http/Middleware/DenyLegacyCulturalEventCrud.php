<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 6A-CLOSE-02 — Kill-switch za legacy admin CRUD (`cultural-events.*`).
 * Ne dira public legacy read niti kanonski domen.
 */
class DenyLegacyCulturalEventCrud
{
    public function handle(Request $request, Closure $next): Response
    {
        abort(403);
    }
}
