<?php

namespace App\Http\Controllers;

use App\Models\CulturalEventEntry;
use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * TS-010.6 — Moderator Dashboard / Radna tabla (DM-01–DM-03).
 * Samo brojači + navigacija; bez poslovnih akcija.
 * Opseg: isključivo aktivni Organizator kontekst.
 */
class CulturalModeratorDashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();
        CulturalModeratorEventAccess::assertActiveModerator($user);

        $available = CulturalOrganizerContext::availableOrganizers($user);
        $active = CulturalOrganizerContext::get($user);

        if ($active === null) {
            return view('cultural-calendar.moderator-events.select-context', [
                'organizers' => $available,
            ]);
        }

        $organizerId = (int) $active->id;

        $cards = [
            [
                'id' => 'DM-01',
                'title' => 'Nacrti',
                'description' => 'Događaji u statusu Nacrt za aktivnog Organizatora (uključujući vraćene na doradu).',
                'count' => CulturalEventEntry::query()
                    ->where('organizer_id', $organizerId)
                    ->where('status', CulturalEventEntry::STATUS_DRAFT)
                    ->count(),
                'url' => route('cultural-moderator-events.index', [
                    'status' => CulturalEventEntry::STATUS_DRAFT,
                ]),
            ],
            [
                'id' => 'DM-02',
                'title' => 'Na odobrenju',
                'description' => 'Događaji poslati na odobrenje koji čekaju uredničku odluku.',
                'count' => CulturalEventEntry::query()
                    ->where('organizer_id', $organizerId)
                    ->where('status', CulturalEventEntry::STATUS_PENDING_APPROVAL)
                    ->count(),
                'url' => route('cultural-moderator-events.index', [
                    'status' => CulturalEventEntry::STATUS_PENDING_APPROVAL,
                ]),
            ],
            [
                'id' => 'DM-03',
                'title' => 'Aktivni prijedlozi izmjena',
                'description' => 'Objavljeni događaji sa operativnim aktivnim prijedlogom izmjene.',
                'count' => CulturalEventEntry::query()
                    ->where('organizer_id', $organizerId)
                    ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
                    ->whereHas('changeProposals', function ($q) {
                        $q->active()->whereNotNull('active_for_event_id');
                    })
                    ->count(),
                'url' => route('cultural-moderator-events.index', [
                    'has_active_proposal' => '1',
                ]),
            ],
        ];

        return view('cultural-calendar.moderator-dashboard.index', [
            'cards' => $cards,
            'activeOrganizer' => $active,
            'availableOrganizers' => $available,
        ]);
    }
}
