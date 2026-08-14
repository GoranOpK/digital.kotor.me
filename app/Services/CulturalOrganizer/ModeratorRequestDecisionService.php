<?php

namespace App\Services\CulturalOrganizer;

use App\Mail\CulturalModeratorAddApprovedMail;
use App\Mail\CulturalModeratorAddRejectedMail;
use App\Mail\CulturalModeratorRemoveApprovedMail;
use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
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
 * Odluke o zahtjevima za dodjelu / uklanjanje Moderatora + PO-ORG-06 Package 5 outcome emails.
 */
final class ModeratorRequestDecisionService
{
    public function __construct(
        private readonly CulturalActivityEmitter $activityEmitter,
    ) {}

    public function approve(CulturalModeratorRequest $request, User $editor, ?string $decisionNote = null): CulturalModeratorRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odobriti zahtjev.');
        }

        $approved = DB::transaction(function () use ($request, $editor, $decisionNote) {
            /** @var CulturalModeratorRequest $locked */
            $locked = CulturalModeratorRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isSubmitted()) {
                throw new InvalidArgumentException('Zahtjev nije u statusu Podnesen.');
            }

            $organizer = CulturalOrganizer::query()
                ->whereKey($locked->organizer_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->type === CulturalModeratorRequest::TYPE_ADD) {
                $this->approveAdd($locked, $organizer);
            } elseif ($locked->type === CulturalModeratorRequest::TYPE_REMOVE) {
                $this->approveRemove($locked, $organizer);
            } else {
                throw new InvalidArgumentException('Nepoznat tip zahtjeva.');
            }

            $locked->update([
                'status' => CulturalModeratorRequest::STATUS_APPROVED,
                'decision_user_id' => $editor->id,
                'decision_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            return $locked->fresh(['organizer', 'targetUser']);
        });

        $this->sendApprovalOutcome($approved);

        $catalogId = $approved->type === CulturalModeratorRequest::TYPE_ADD
            ? CulturalActivityCatalog::MOD_02
            : CulturalActivityCatalog::MOD_05;
        $decisionAt = $approved->decision_at ?? now();
        $context = ['request_id' => (int) $approved->id];
        if ($approved->type === CulturalModeratorRequest::TYPE_ADD && $approved->target_user_id !== null) {
            $context['user_id'] = (int) $approved->target_user_id;
        }

        $this->activityEmitter->emitUser(
            $catalogId,
            CulturalActivityEventId::once($catalogId, (int) $approved->id),
            $editor,
            (int) $approved->id,
            $decisionAt,
            $context,
            (int) $approved->organizer_id,
        );

        return $approved;
    }

    public function reject(CulturalModeratorRequest $request, User $editor, ?string $decisionNote = null): CulturalModeratorRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odbiti zahtjev.');
        }

        $rejected = DB::transaction(function () use ($request, $editor, $decisionNote) {
            /** @var CulturalModeratorRequest $locked */
            $locked = CulturalModeratorRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isSubmitted()) {
                throw new InvalidArgumentException('Zahtjev nije u statusu Podnesen.');
            }

            $locked->update([
                'status' => CulturalModeratorRequest::STATUS_REJECTED,
                'decision_user_id' => $editor->id,
                'decision_at' => now(),
                'decision_note' => $decisionNote,
            ]);

            return $locked->fresh(['organizer', 'targetUser']);
        });

        if ($rejected->type === CulturalModeratorRequest::TYPE_ADD) {
            $this->sendAddRejectionOutcome($rejected);
        }

        $catalogId = $rejected->type === CulturalModeratorRequest::TYPE_ADD
            ? CulturalActivityCatalog::MOD_03
            : CulturalActivityCatalog::MOD_06;
        $decisionAt = $rejected->decision_at ?? now();
        $this->activityEmitter->emitUser(
            $catalogId,
            CulturalActivityEventId::once($catalogId, (int) $rejected->id),
            $editor,
            (int) $rejected->id,
            $decisionAt,
            ['request_id' => (int) $rejected->id],
            (int) $rejected->organizer_id,
        );

        return $rejected;
    }

    private function approveAdd(CulturalModeratorRequest $request, CulturalOrganizer $organizer): void
    {
        $target = User::query()->find($request->target_user_id);
        if (! CulturalPortalAccess::isPlatformUserActive($target)) {
            throw new InvalidArgumentException('Ciljni korisnik mora biti postojeći aktivan nalog.');
        }

        $existing = CulturalModeratorAuthorization::query()
            ->where('user_id', $request->target_user_id)
            ->where('organizer_id', $organizer->id)
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->isActive()) {
            throw new InvalidArgumentException('Korisnik već ima aktivno ovlašćenje za ovog Organizatora.');
        }

        if ($existing) {
            $existing->update([
                'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
                'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
                'activated_at' => now(),
                'removed_at' => null,
            ]);

            return;
        }

        CulturalModeratorAuthorization::create([
            'user_id' => $request->target_user_id,
            'organizer_id' => $organizer->id,
            'status' => CulturalModeratorAuthorization::STATUS_ACTIVE,
            'source' => CulturalModeratorAuthorization::SOURCE_SUBSEQUENT,
            'activated_at' => now(),
            'removed_at' => null,
        ]);
    }

    private function approveRemove(CulturalModeratorRequest $request, CulturalOrganizer $organizer): void
    {
        $authorization = CulturalModeratorAuthorization::query()
            ->where('user_id', $request->target_user_id)
            ->where('organizer_id', $organizer->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first();

        if (! $authorization) {
            throw new InvalidArgumentException('Ciljni korisnik nema aktivno ovlašćenje.');
        }

        $activeCount = CulturalModeratorAuthorization::query()
            ->where('organizer_id', $organizer->id)
            ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
            ->lockForUpdate()
            ->count();

        if ($activeCount <= 1) {
            throw new InvalidArgumentException('Nije dozvoljeno ukloniti posljednjeg aktivnog Moderatora.');
        }

        $authorization->update([
            'status' => CulturalModeratorAuthorization::STATUS_REMOVED,
            'removed_at' => now(),
        ]);
    }

    private function sendApprovalOutcome(CulturalModeratorRequest $request): void
    {
        $organizer = $request->organizer ?? CulturalOrganizer::query()->find($request->organizer_id);
        $recipient = $this->boundTargetRecipient($request);

        if (! $organizer instanceof CulturalOrganizer || $recipient === null) {
            Log::error('PO-ORG-06 Moderator approval outcome mail skipped: missing organizer/recipient.', [
                'moderator_request_id' => $request->id,
            ]);

            return;
        }

        try {
            if ($request->type === CulturalModeratorRequest::TYPE_ADD) {
                Mail::to($recipient)->send(new CulturalModeratorAddApprovedMail($request, $organizer));
            } elseif ($request->type === CulturalModeratorRequest::TYPE_REMOVE) {
                Mail::to($recipient)->send(new CulturalModeratorRemoveApprovedMail($request, $organizer));
            }
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 Moderator approval outcome mail failed after successful decision.', [
                'moderator_request_id' => $request->id,
                'type' => $request->type,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function sendAddRejectionOutcome(CulturalModeratorRequest $request): void
    {
        $organizer = $request->organizer ?? CulturalOrganizer::query()->find($request->organizer_id);
        $recipient = $this->boundTargetRecipient($request);

        if (! $organizer instanceof CulturalOrganizer || $recipient === null) {
            Log::error('PO-ORG-06 ADD rejection outcome mail skipped: missing organizer/recipient.', [
                'moderator_request_id' => $request->id,
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new CulturalModeratorAddRejectedMail($request, $organizer));
        } catch (Throwable $e) {
            Log::error('PO-ORG-06 ADD rejection outcome mail failed after successful decision.', [
                'moderator_request_id' => $request->id,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function boundTargetRecipient(CulturalModeratorRequest $request): ?string
    {
        $user = User::query()->find($request->target_user_id);
        if (! $user instanceof User) {
            return null;
        }

        $email = trim((string) $user->email);

        return $email !== '' ? $email : null;
    }
}
