<?php

namespace App\Services\Competitions\Lifecycle;

use App\Models\Commission;
use App\Models\Competition;
use App\Services\Competitions\Profile\CompetitionProfileResolver;

/**
 * Fail-closed isolation of the legacy Women/Youth commission flows (Obrazac 3, legacy Prigovor,
 * admin-entered member declarations) from profiles that run their own governed process
 * (CompetitionProfilePolicy::allowsLegacyCommissionFlows). Must run before any write.
 */
final class LegacyCommissionFlowGuard
{
    public const BLOCKED_MESSAGE = 'Ženski/omladinski tok Komisije (Obrazac 3, Prigovor) nije dostupan za kanonski MMSP Poziv. Koristi se kanonska provjera potpunosti i kanonski prigovor.';

    public const DECLARATIONS_BLOCKED_MESSAGE = 'Izjave člana kanonske MMSP Komisije ne evidentiraju se kroz ženski/omladinski administratorski obrazac.';

    public static function allows(?Competition $competition): bool
    {
        return $competition === null || $competition->profile()->allowsLegacyCommissionFlows();
    }

    public static function abortUnlessAllowed(?Competition $competition): void
    {
        if (! self::allows($competition)) {
            abort(403, self::BLOCKED_MESSAGE);
        }
    }

    public static function abortUnlessCommissionAllowed(?Commission $commission): void
    {
        if ($commission !== null
            && ! CompetitionProfileResolver::resolve($commission->profile_type)->allowsLegacyCommissionFlows()) {
            abort(403, self::DECLARATIONS_BLOCKED_MESSAGE);
        }
    }
}
