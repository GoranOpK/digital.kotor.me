<?php

namespace App\Services\CulturalOrganizer;

use App\Mail\CulturalOrganizerCreationApprovedMail;
use App\Mail\CulturalOrganizerCreationRejectedMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Atomsko odobravanje / odbijanje zahtjeva za kreiranje Organizatora
 * (PO-ORG-03 / PO-ORG-05 / PO-ORG-06 Package 4 outcome emails).
 */
final class OrganizerCreationDecisionService
{
    public function approve(CulturalOrganizerCreationRequest $request, User $editor, ?string $decisionNote = null): CulturalOrganizer
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odobriti zahtjev.');
        }

        $organizer = DB::transaction(function () use ($request, $editor, $decisionNote) {
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

        $this->sendApprovalOutcome($request->fresh() ?? $request, $organizer);

        return $organizer;
    }

    public function reject(CulturalOrganizerCreationRequest $request, User $editor, ?string $decisionNote = null): CulturalOrganizerCreationRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odbiti zahtjev.');
        }

        $rejected = DB::transaction(function () use ($request, $editor, $decisionNote) {
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

        $this->sendRejectionOutcome($rejected);

        return $rejected;
    }

    private function sendApprovalOutcome(
        CulturalOrganizerCreationRequest $request,
        CulturalOrganizer $organizer
    ): void {
        $recipient = $this->boundModeratorRecipient($request);
        if ($recipient === null) {
            Log::error('PO-ORG-06 approval outcome mail skipped: bound Moderator recipient missing.', [
                'creation_request_id' => $request->id,
                'organizer_id' => $organizer->id,
            ]);

            return;
        }

        try {
            Mail::to($recipient)
                ->send(new CulturalOrganizerCreationApprovedMail($request, $organizer));
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 approval outcome mail failed after successful Organizer creation decision.', [
                'creation_request_id' => $request->id,
                'organizer_id' => $organizer->id,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function sendRejectionOutcome(CulturalOrganizerCreationRequest $request): void
    {
        $recipient = $this->boundModeratorRecipient($request);
        if ($recipient === null) {
            Log::error('PO-ORG-06 rejection outcome mail skipped: bound Moderator recipient missing.', [
                'creation_request_id' => $request->id,
            ]);

            return;
        }

        try {
            Mail::to($recipient)
                ->send(new CulturalOrganizerCreationRejectedMail($request));
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 rejection outcome mail failed after successful Organizer creation rejection.', [
                'creation_request_id' => $request->id,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Outcome recipient is the bound User account email (canonical after eligibility bind).
     */
    private function boundModeratorRecipient(CulturalOrganizerCreationRequest $request): ?string
    {
        $moderator = User::query()->find($request->proposed_moderator_user_id);
        if (! $moderator instanceof User) {
            return null;
        }

        $email = trim((string) $moderator->email);

        return $email !== '' ? $email : null;
    }
}
