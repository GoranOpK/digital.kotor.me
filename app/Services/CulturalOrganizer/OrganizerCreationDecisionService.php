<?php

namespace App\Services\CulturalOrganizer;

use App\Mail\CulturalOrganizerCreationApprovedMail;
use App\Mail\CulturalOrganizerCreationRejectedMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
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
    public function __construct(
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

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

        $approvedRequest = $request->fresh() ?? $request;
        $occurredAt = $approvedRequest->decision_at ?? now();
        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::ORG_02,
            CulturalActivityEventId::of(
                CulturalActivityCatalog::ORG_02,
                (int) $approvedRequest->id,
                (int) $organizer->id
            ),
            $editor,
            (int) $organizer->id,
            $occurredAt,
            [
                'request_id' => (int) $approvedRequest->id,
                'organizer_id' => (int) $organizer->id,
            ],
            (int) $organizer->id,
        );

        $grant = CulturalModeratorAuthorization::query()
            ->where('organizer_id', $organizer->id)
            ->where('source', CulturalModeratorAuthorization::SOURCE_INITIAL)
            ->orderByDesc('id')
            ->first();
        if ($grant !== null) {
            $this->activityEmitter->emitUser(
                CulturalActivityCatalog::ORG_07,
                CulturalActivityEventId::of(
                    CulturalActivityCatalog::ORG_07,
                    (int) $organizer->id,
                    (int) $grant->id
                ),
                $editor,
                (int) $grant->id,
                $grant->activated_at ?? $occurredAt,
                [
                    'organizer_id' => (int) $organizer->id,
                    'user_id' => (int) $grant->user_id,
                ],
                (int) $organizer->id,
            );
        }

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

        $decisionAt = $rejected->decision_at ?? now();
        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::ORG_03,
            CulturalActivityEventId::once(CulturalActivityCatalog::ORG_03, (int) $rejected->id),
            $editor,
            (int) $rejected->id,
            $decisionAt,
            ['request_id' => (int) $rejected->id],
        );

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
