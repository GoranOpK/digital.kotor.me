<?php

namespace App\Http\Controllers;

use App\Models\CulturalEventEntry;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizerCreationRequest;
use Illuminate\View\View;

/**
 * TS-010.2 — Urednik Dashboard / Inbox (DU-01, DU-03, DU-04, DU-05).
 * Samo brojači + navigacija; bez DU-02; bez poslovnih akcija.
 */
class CulturalEditorialDashboardController extends Controller
{
    public function index(): View
    {
        $cards = [
            [
                'id' => 'DU-01',
                'title' => 'Čeka pregled',
                'description' => 'Događaji na odobrenju koji čekaju uredničku odluku.',
                'count' => CulturalEventEntry::query()
                    ->where('status', CulturalEventEntry::STATUS_PENDING_APPROVAL)
                    ->count(),
                'url' => route('cultural-event-entries.index', [
                    'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
                ]),
            ],
            [
                'id' => 'DU-03',
                'title' => 'Nacrti bez Organizatora',
                'description' => 'Nacrti koje Urednik vodi bez Organizatora.',
                'count' => CulturalEventEntry::query()
                    ->where('status', CulturalEventEntry::STATUS_DRAFT)
                    ->whereNull('organizer_id')
                    ->count(),
                'url' => route('cultural-event-entries.index', [
                    'status' => CulturalEventEntry::STATUS_DRAFT,
                    'organizer' => 'none',
                ]),
            ],
            [
                'id' => 'DU-04',
                'title' => 'Zahtjevi za Organizatora',
                'description' => 'Otvoreni zahtjevi za kreiranje Organizatora.',
                'count' => CulturalOrganizerCreationRequest::query()
                    ->where('status', CulturalOrganizerCreationRequest::STATUS_SUBMITTED)
                    ->count(),
                'url' => route('cultural-organizer-creation-requests.index', [
                    'status' => CulturalOrganizerCreationRequest::STATUS_SUBMITTED,
                ]),
            ],
            [
                'id' => 'DU-05',
                'title' => 'Zahtjevi za Moderatore',
                'description' => 'Otvoreni zahtjevi za dodjelu ili uklanjanje Moderatora.',
                'count' => CulturalModeratorRequest::query()
                    ->where('status', CulturalModeratorRequest::STATUS_SUBMITTED)
                    ->count(),
                'url' => route('cultural-moderator-requests.index', [
                    'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
                ]),
            ],
        ];

        return view('cultural-calendar.admin.editorial-dashboard.index', compact('cards'));
    }
}
