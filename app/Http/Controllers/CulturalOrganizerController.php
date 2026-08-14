<?php

namespace App\Http\Controllers;

use App\Http\Requests\CulturalOrganizerUpdateRequest;
use App\Models\CulturalOrganizer;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use App\Support\CulturalPortalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CulturalOrganizerController extends Controller
{
    public function __construct(
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function index(): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        $organizers = CulturalOrganizer::query()
            ->withCount([
                'authorizations as active_moderators_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->orderedByName()
            ->paginate(20);

        return view('cultural-calendar.admin.organizers.index', compact('organizers'));
    }

    public function edit(CulturalOrganizer $organizatori): View
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        return view('cultural-calendar.admin.organizers.edit', [
            'organizer' => $organizatori->load(['activeAuthorizations.user', 'approvedCreationRequest']),
        ]);
    }

    public function update(CulturalOrganizerUpdateRequest $request, CulturalOrganizer $organizatori): RedirectResponse
    {
        $before = [
            'naziv' => $organizatori->naziv,
            'opis' => $organizatori->opis,
            'contact_email' => $organizatori->contact_email,
            'contact_phone' => $organizatori->contact_phone,
            'website' => $organizatori->website,
        ];

        $organizatori->update($request->validated());
        $fresh = $organizatori->fresh() ?? $organizatori;
        $after = [
            'naziv' => $fresh->naziv,
            'opis' => $fresh->opis,
            'contact_email' => $fresh->contact_email,
            'contact_phone' => $fresh->contact_phone,
            'website' => $fresh->website,
        ];

        if ($before !== $after) {
            $actor = $request->user();
            if ($actor !== null) {
                $this->activityEmitter->emitUser(
                    CulturalActivityCatalog::ORG_06,
                    CulturalActivityEventId::repeatable(
                        CulturalActivityCatalog::ORG_06,
                        (int) $fresh->id,
                        ['from' => $before, 'to' => $after],
                        $organizatori->updated_at ?? $fresh->updated_at ?? now()
                    ),
                    $actor,
                    (int) $fresh->id,
                    $fresh->updated_at ?? now(),
                    ['organizer_id' => (int) $fresh->id],
                    (int) $fresh->id,
                );
            }
        }

        return redirect()
            ->route('cultural-organizers.index')
            ->with('status', 'Organizator je ažuriran.');
    }

    public function deactivate(CulturalOrganizer $organizatori): RedirectResponse
    {
        abort_unless(CulturalPortalAccess::isKkEditor(auth()->user()), 403);

        if ($organizatori->isDeactivated()) {
            return redirect()
                ->route('cultural-organizers.index')
                ->with('status', 'Organizator je već deaktiviran.');
        }

        $organizatori->update([
            'status' => CulturalOrganizer::STATUS_DEACTIVATED,
        ]);

        $fresh = $organizatori->fresh() ?? $organizatori;
        $actor = auth()->user();
        if ($actor !== null) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::ORG_04,
                CulturalActivityEventId::once(CulturalActivityCatalog::ORG_04, (int) $fresh->id),
                $actor,
                (int) $fresh->id,
                $fresh->updated_at ?? now(),
                ['organizer_id' => (int) $fresh->id],
                (int) $fresh->id,
            );
        }

        return redirect()
            ->route('cultural-organizers.index')
            ->with('status', 'Organizator je deaktiviran.');
    }
}
