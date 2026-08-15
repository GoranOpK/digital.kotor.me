<?php

namespace App\Services\CulturalOrganizer;

use App\Mail\CulturalModeratorAddInvitationMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

/**
 * PO-ORG-06 Package 5 — subsequent Moderator ADD/REMOVE submit.
 */
final class ModeratorRequestSubmissionService
{
    public function __construct(
        private readonly \App\Services\CulturalActivity\CulturalActivityEmitter $activityEmitter,
    ) {}

    /**
     * @param  array{
     *     type: string,
     *     proposed_moderator_name?: string,
     *     proposed_moderator_email?: string,
     *     target_user_id?: int|null
     * }  $data
     */
    public function submit(User $submitter, CulturalOrganizer $organizer, array $data): CulturalModeratorRequest
    {
        if ($data['type'] === CulturalModeratorRequest::TYPE_ADD) {
            return $this->submitAdd($submitter, $organizer, $data);
        }

        if ($data['type'] === CulturalModeratorRequest::TYPE_REMOVE) {
            return $this->submitRemove($submitter, $organizer, $data);
        }

        throw new InvalidArgumentException('Nepoznat tip zahtjeva.');
    }

    /**
     * @param  array{proposed_moderator_name: string, proposed_moderator_email: string}  $data
     */
    private function submitAdd(User $submitter, CulturalOrganizer $organizer, array $data): CulturalModeratorRequest
    {
        $name = trim((string) $data['proposed_moderator_name']);
        $email = OrganizerCreationRequestSubmissionService::normalizeEmail((string) $data['proposed_moderator_email']);
        $eligible = $this->findEligibleUserByEmail($email);

        $request = DB::transaction(function () use ($submitter, $organizer, $name, $email, $eligible) {
            $this->assertNoUnfinishedAddDuplicate($organizer->id, $email);

            if ($eligible !== null) {
                $alreadyActive = CulturalModeratorAuthorization::query()
                    ->where('organizer_id', $organizer->id)
                    ->where('user_id', $eligible->id)
                    ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                    ->exists();

                if ($alreadyActive) {
                    throw new InvalidArgumentException('Korisnik već ima aktivno ovlašćenje za ovog Organizatora.');
                }
            }

            return CulturalModeratorRequest::create([
                'organizer_id' => $organizer->id,
                'submitter_user_id' => $submitter->id,
                'target_user_id' => $eligible?->id,
                'proposed_moderator_name' => $name,
                'proposed_moderator_email' => $email,
                'type' => CulturalModeratorRequest::TYPE_ADD,
                'status' => $eligible !== null
                    ? CulturalModeratorRequest::STATUS_SUBMITTED
                    : CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
            ]);
        });

        if ($eligible === null) {
            $this->sendInvitation($request, $organizer);
        }

        $this->activityEmitter->emitUser(
            \App\Services\CulturalActivity\CulturalActivityCatalog::MOD_01,
            \App\Services\CulturalActivity\CulturalActivityEventId::once(
                \App\Services\CulturalActivity\CulturalActivityCatalog::MOD_01,
                (int) $request->id
            ),
            $submitter,
            (int) $request->id,
            $request->created_at ?? now(),
            [
                'request_id' => (int) $request->id,
                'organizer_id' => (int) $organizer->id,
            ],
            (int) $organizer->id,
        );

        return $request;
    }

    /**
     * @param  array{target_user_id: int}  $data
     */
    private function submitRemove(User $submitter, CulturalOrganizer $organizer, array $data): CulturalModeratorRequest
    {
        $targetId = (int) $data['target_user_id'];

        $created = DB::transaction(function () use ($submitter, $organizer, $targetId) {
            $isTargetActive = CulturalModeratorAuthorization::query()
                ->where('organizer_id', $organizer->id)
                ->where('user_id', $targetId)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->lockForUpdate()
                ->exists();

            if (! $isTargetActive) {
                throw new InvalidArgumentException('Ciljni korisnik nije aktivan Moderator ovog Organizatora.');
            }

            $activeCount = CulturalPortalAccess::activeModeratorCount($organizer);
            if ($activeCount <= 1) {
                throw new InvalidArgumentException('Nije dozvoljeno podnijeti uklanjanje posljednjeg aktivnog Moderatora.');
            }

            return CulturalModeratorRequest::create([
                'organizer_id' => $organizer->id,
                'submitter_user_id' => $submitter->id,
                'target_user_id' => $targetId,
                'proposed_moderator_name' => null,
                'proposed_moderator_email' => null,
                'type' => CulturalModeratorRequest::TYPE_REMOVE,
                'status' => CulturalModeratorRequest::STATUS_SUBMITTED,
            ]);
        });

        $this->activityEmitter->emitUser(
            \App\Services\CulturalActivity\CulturalActivityCatalog::MOD_04,
            \App\Services\CulturalActivity\CulturalActivityEventId::once(
                \App\Services\CulturalActivity\CulturalActivityCatalog::MOD_04,
                (int) $created->id
            ),
            $submitter,
            (int) $created->id,
            $created->created_at ?? now(),
            [
                'request_id' => (int) $created->id,
                'organizer_id' => (int) $organizer->id,
            ],
            (int) $organizer->id,
        );

        return $created;
    }

    private function assertNoUnfinishedAddDuplicate(int $organizerId, string $normalizedEmail): void
    {
        $exists = CulturalModeratorRequest::query()
            ->where('organizer_id', $organizerId)
            ->where('type', CulturalModeratorRequest::TYPE_ADD)
            ->where('proposed_moderator_email', $normalizedEmail)
            ->whereIn('status', [
                CulturalModeratorRequest::STATUS_AWAITING_MODERATOR_ELIGIBILITY,
                CulturalModeratorRequest::STATUS_SUBMITTED,
            ])
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException(
                'Već postoji nezavršen zahtjev za dodjelu Moderatora sa istim e-mailom za ovog Organizatora.'
            );
        }
    }

    private function findEligibleUserByEmail(string $normalizedEmail): ?User
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (! CulturalPortalAccess::isPlatformUserActive($user)) {
            return null;
        }

        return $user;
    }

    private function sendInvitation(CulturalModeratorRequest $request, CulturalOrganizer $organizer): void
    {
        try {
            Mail::to($request->proposed_moderator_email)
                ->send(new CulturalModeratorAddInvitationMail($request, $organizer->naziv));
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 ADD invitation mail failed after Moderator request persist.', [
                'moderator_request_id' => $request->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
