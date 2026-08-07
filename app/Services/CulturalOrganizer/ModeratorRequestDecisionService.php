<?php

namespace App\Services\CulturalOrganizer;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Models\User;
use App\Support\CulturalPortalAccess;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Odluke o zahtjevima za dodjelu / uklanjanje Moderatora + invariant ≥1.
 */
final class ModeratorRequestDecisionService
{
    public function approve(CulturalModeratorRequest $request, User $editor, ?string $decisionNote = null): CulturalModeratorRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odobriti zahtjev.');
        }

        return DB::transaction(function () use ($request, $editor, $decisionNote) {
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

            return $locked->fresh();
        });
    }

    public function reject(CulturalModeratorRequest $request, User $editor, ?string $decisionNote = null): CulturalModeratorRequest
    {
        if (! CulturalPortalAccess::isKkEditor($editor)) {
            throw new RuntimeException('Samo Urednik može odbiti zahtjev.');
        }

        return DB::transaction(function () use ($request, $editor, $decisionNote) {
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

            return $locked->fresh();
        });
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
}
