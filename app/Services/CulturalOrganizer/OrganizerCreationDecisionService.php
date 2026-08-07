<?php

namespace App\Services\CulturalOrganizer;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Atomsko odobravanje zahtjeva za kreiranje Organizatora (PO-ORG-03).
 */
final class OrganizerCreationDecisionService
{
    public function approve(CulturalOrganizerCreationRequest $request, User $editor, ?string $decisionNote = null): CulturalOrganizer
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odobriti zahtjev.');
        }

        return DB::transaction(function () use ($request, $editor, $decisionNote) {
            /** @var CulturalOrganizerCreationRequest $locked */
            $locked = CulturalOrganizerCreationRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isSubmitted()) {
                throw new InvalidArgumentException('Zahtjev nije u statusu Podnesen.');
            }

            $moderator = User::query()->find($locked->proposed_moderator_user_id);
            if (! CulturalPortalAccess::isPlatformUserActive($moderator)) {
                throw new InvalidArgumentException('Predloženi Moderator mora biti postojeći aktivan nalog.');
            }

            $organizer = CulturalOrganizer::create([
                'naziv' => $locked->proposed_naziv,
                'opis' => $locked->proposed_opis,
                'contact_email' => $locked->proposed_contact_email,
                'contact_phone' => $locked->proposed_contact_phone,
                'website' => $locked->proposed_website,
                'status' => CulturalOrganizer::STATUS_ACTIVE,
                'approved_creation_request_id' => $locked->id,
            ]);

            CulturalModeratorAuthorization::create([
                'user_id' => $locked->proposed_moderator_user_id,
                'organizer_id' => $organizer->id,
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_INITIAL,
                'activated_at' => now(),
                'removed_at' => null,
            ]);

            $locked->update([
                'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
                'decision_user_id' => $editor->id,
                'decision_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            return $organizer->fresh(['authorizations', 'approvedCreationRequest']);
        });
    }

    public function reject(CulturalOrganizerCreationRequest $request, User $editor, ?string $decisionNote = null): CulturalOrganizerCreationRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odbiti zahtjev.');
        }

        return DB::transaction(function () use ($request, $editor, $decisionNote) {
            /** @var CulturalOrganizerCreationRequest $locked */
            $locked = CulturalOrganizerCreationRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isSubmitted()) {
                throw new InvalidArgumentException('Zahtjev nije u statusu Podnesen.');
            }

            $locked->update([
                'status' => CulturalOrganizerCreationRequest::STATUS_REJECTED,
                'decision_user_id' => $editor->id,
                'decision_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            return $locked->fresh();
        });
    }
}
