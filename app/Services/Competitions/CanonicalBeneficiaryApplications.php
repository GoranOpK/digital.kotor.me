<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\CanonicalInvestmentPlan;
use App\Models\Competition;
use App\Models\FinalApplicationClaim;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;
use App\Support\Pib;

/**
 * KN-SYNC-008A — čl.19 submission integrity for the canonical annual call:
 * one application = one subsidy type; a beneficiary (normalized PIB) may hold at most
 * two FINAL claims, each for a different type. One-support enforcement is KN-SYNC-008.
 *
 * Women/Youth keep one application per user per competition and are never counted.
 */
final class CanonicalBeneficiaryApplications
{
    public const PIB_REQUIRED_MESSAGE = 'Za konačnu predaju na kanonski konkurs potreban je važeći PIB podnosioca (8 cifara) u Obrascu P1a/P1b.';

    public const DUPLICATE_TYPE_MESSAGE = 'Za isti konkurs već postoji konačno podnesena prijava ovog podnosioca za istu vrstu subvencije.';

    public const MAX_TWO_MESSAGE = 'Isti podnosilac (PIB) može na jedan kanonski konkurs konačno podnijeti najviše dvije prijave, za različite vrste subvencija.';

    public const LEGACY_CLAIM_MESSAGE = 'Postoji ranija konačna prijava bez evidentirane vrste subvencije; nova konačna predaja nije moguća.';

    /**
     * Application a user continues for a competition; null means a new draft may be started.
     * Canonical: open draft first; a new draft is allowed while the user holds fewer than two claims.
     */
    public function userApplicationForCompetition(Competition $competition, int $userId): ?Application
    {
        $query = Application::query()
            ->where('competition_id', $competition->id)
            ->where('user_id', $userId);

        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return $query->first();
        }

        $draft = (clone $query)->where('status', 'draft')->orderBy('id')->first();
        if ($draft) {
            return $draft;
        }

        $claims = FinalApplicationClaim::query()
            ->where('competition_id', $competition->id)
            ->where('user_id', $userId)
            ->get(['subsidy_type']);

        $canStartAnother = $claims->count() < CanonicalSubsidyType::MAX_APPLICATIONS_PER_BENEFICIARY
            && $claims->every(fn (FinalApplicationClaim $claim) => $claim->subsidy_type !== null);

        return $canStartAnother ? null : $query->orderBy('id')->first();
    }

    /**
     * Early draft feedback: the type is already claimed at FINAL by this user/PIB, or already
     * chosen on another of the user's applications in the same call. FINAL remains authoritative.
     */
    public function subsidyTypeAlreadyTaken(Application $application, string $subsidyType): bool
    {
        $pib = Pib::normalize($application->pib);

        $claimed = FinalApplicationClaim::query()
            ->where('competition_id', $application->competition_id)
            ->where('subsidy_type', $subsidyType)
            ->where('application_id', '!=', $application->id)
            ->where(function ($query) use ($application, $pib) {
                $query->where('user_id', $application->user_id);
                if ($pib !== null) {
                    $query->orWhere('beneficiary_pib', $pib);
                }
            })
            ->exists();

        return $claimed || CanonicalInvestmentPlan::query()
            ->where('subsidy_type', $subsidyType)
            ->whereHas('application', fn ($query) => $query
                ->where('competition_id', $application->competition_id)
                ->where('user_id', $application->user_id)
                ->whereKeyNot($application->id))
            ->exists();
    }

    /**
     * Must run inside the FINAL transaction after the competition row is locked.
     *
     * @return array{error: ?string, slot: ?int}
     */
    public function assessFinalClaim(Competition $competition, int $userId, string $pib, string $subsidyType): array
    {
        $claims = FinalApplicationClaim::query()
            ->where('competition_id', $competition->id)
            ->where(function ($query) use ($userId, $pib) {
                $query->where('user_id', $userId)->orWhere('beneficiary_pib', $pib);
            })
            ->lockForUpdate()
            ->get(['id', 'user_id', 'subsidy_type', 'beneficiary_pib', 'beneficiary_slot']);

        if ($claims->contains(fn (FinalApplicationClaim $claim) => $claim->subsidy_type === null)) {
            return ['error' => self::LEGACY_CLAIM_MESSAGE, 'slot' => null];
        }

        if ($claims->contains(fn (FinalApplicationClaim $claim) => $claim->subsidy_type === $subsidyType)) {
            return ['error' => self::DUPLICATE_TYPE_MESSAGE, 'slot' => null];
        }

        $usedSlots = $claims
            ->filter(fn (FinalApplicationClaim $claim) => $claim->beneficiary_pib === $pib)
            ->pluck('beneficiary_slot')
            ->map(fn ($slot) => (int) $slot)
            ->all();

        foreach (range(1, CanonicalSubsidyType::MAX_APPLICATIONS_PER_BENEFICIARY) as $slot) {
            if (! in_array($slot, $usedSlots, true)) {
                return ['error' => null, 'slot' => $slot];
            }
        }

        return ['error' => self::MAX_TWO_MESSAGE, 'slot' => null];
    }
}
