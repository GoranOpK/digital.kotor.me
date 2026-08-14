<?php

namespace App\Services\CulturalOrganizer;

use App\Mail\CulturalOrganizerModeratorInvitationMail;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use App\Services\CulturalActivity\CulturalActivityCatalog;
use App\Services\CulturalActivity\CulturalActivityEmitter;
use App\Services\CulturalActivity\CulturalActivityEventId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * PO-ORG-06 Package 2 — privacy-safe submit za Zahtjev za kreiranje Organizatora.
 *
 * Eligibility se rješava samo pri submit-u. Automatski awaiting→submitted je PACKAGE 3.
 */
final class OrganizerCreationRequestSubmissionService
{
    public function __construct(
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public const NEUTRAL_SUBMIT_STATUS_MESSAGE = 'Zahtjev je uspješno podnesen. Predloženi Moderator mora imati aktivan i verifikovan korisnički nalog na platformi Digital Kotor prije nego što zahtjev može biti dostavljen Uredniku na odlučivanje.';

    /**
     * @param  array{
     *     naziv: string,
     *     opis?: string|null,
     *     contact_email?: string|null,
     *     contact_phone?: string|null,
     *     website?: string|null,
     *     proposed_moderator_name: string,
     *     proposed_moderator_email: string
     * }  $data
     */
    public function submit(User $submitter, array $data): CulturalOrganizerCreationRequest
    {
        $name = trim((string) $data['proposed_moderator_name']);
        $email = self::normalizeEmail((string) $data['proposed_moderator_email']);
        $eligibleModerator = $this->findEligibleModeratorByEmail($email);

        $request = CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $submitter->id,
            'proposed_moderator_name' => $name,
            'proposed_moderator_email' => $email,
            'proposed_moderator_user_id' => $eligibleModerator?->id,
            'proposed_moderator_is_submitter' => $eligibleModerator !== null
                && (int) $eligibleModerator->id === (int) $submitter->id,
            'proposed_naziv' => $data['naziv'],
            'proposed_opis' => $data['opis'] ?? null,
            'proposed_contact_email' => $data['contact_email'] ?? null,
            'proposed_contact_phone' => $data['contact_phone'] ?? null,
            'proposed_website' => $data['website'] ?? null,
            'status' => $eligibleModerator !== null
                ? CulturalOrganizerCreationRequest::STATUS_SUBMITTED
                : CulturalOrganizerCreationRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
        ]);

        if ($eligibleModerator === null) {
            $this->sendInvitation($request);
        }

        $this->activityEmitter->emitUser(
            CulturalActivityCatalog::ORG_01,
            CulturalActivityEventId::once(CulturalActivityCatalog::ORG_01, (int) $request->id),
            $submitter,
            (int) $request->id,
            $request->created_at ?? now(),
            ['request_id' => (int) $request->id],
        );

        return $request;
    }

    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function findEligibleModeratorByEmail(string $normalizedEmail): ?User
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (! CulturalPortalAccess::isPlatformUserActive($user)) {
            return null;
        }

        return $user;
    }

    private function sendInvitation(CulturalOrganizerCreationRequest $request): void
    {
        try {
            Mail::to($request->proposed_moderator_email)
                ->send(new CulturalOrganizerModeratorInvitationMail($request));
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 invitation mail failed after Organizer creation request persist.', [
                'creation_request_id' => $request->id,
                'proposed_moderator_email' => $request->proposed_moderator_email,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
