<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\IdentitySnapshot;
use App\Models\ForeignBranchIdentity;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Application-level identifier uniqueness against canonical tables,
 * plus leftover users.* identity columns that were never dual-written
 * off after cutover. Does not introduce a new uniqueness business rule:
 * it retargets the existing HTTP uniqueness checks to current authority.
 *
 * DB unique indexes are not added: DK-TS-002 D1/D8/D9 do not adopt
 * identifier uniqueness as schema, identifiers are nullable, and
 * passport uniqueness is not specified as composite-with-country.
 */
final class CanonicalIdentifierUniqueness
{
    public function jmbTaken(?string $jmb, ?int $exceptUserId = null): bool
    {
        if (! is_string($jmb) || $jmb === '') {
            return false;
        }

        $users = User::query()->where('jmb', $jmb);
        if ($exceptUserId !== null) {
            $users->where('id', '!=', $exceptUserId);
        }

        return PhysicalPersonIdentity::query()->where('jmb', $jmb)->exists()
            || $users->exists();
    }

    public function pibTaken(?string $pib, ?int $exceptUserId = null): bool
    {
        if (! is_string($pib) || $pib === '') {
            return false;
        }

        $users = User::query()->where('pib', $pib);
        $physical = PhysicalPersonIdentity::query()->where('pib', $pib);
        $legal = LegalEntityIdentity::query()->where('pib', $pib);
        $foreign = ForeignBranchIdentity::query()->where('pib', $pib);

        if ($exceptUserId !== null) {
            $users->where('id', '!=', $exceptUserId);
            $this->excludeOnlyCurrentUserCanonicalOwner($physical, $exceptUserId);
            $this->excludeOnlyCurrentUserCanonicalOwner($legal, $exceptUserId);
            $this->excludeOnlyCurrentUserCanonicalOwner($foreign, $exceptUserId);
        }

        return $physical->exists()
            || $legal->exists()
            || $foreign->exists()
            || $users->exists();
    }

    public function physicalPassportTaken(?string $passport, ?int $exceptUserId = null): bool
    {
        if (! is_string($passport) || $passport === '') {
            return false;
        }

        $value = strtoupper($passport);
        $users = User::query()->where('passport_number', $value);
        if ($exceptUserId !== null) {
            $users->where('id', '!=', $exceptUserId);
        }

        return PhysicalPersonIdentity::query()->where('passport_number', $value)->exists()
            || $users->exists();
    }

    public function assertAvailableForSnapshot(IdentitySnapshot $snapshot): void
    {
        $exceptUserId = $snapshot->userId;
        $jmb = $snapshot->physicalPerson?->jmb;
        $pib = $snapshot->physicalPerson?->pib
            ?? $snapshot->legalEntity?->pib
            ?? $snapshot->foreignBranch?->pib;
        $passport = $snapshot->physicalPerson?->passportNumber;

        if ($this->jmbTaken($jmb, $exceptUserId)) {
            throw new CanonicalIdentityWriteException('JMB je već registrovan.');
        }

        if ($this->pibTaken($pib, $exceptUserId)) {
            throw new CanonicalIdentityWriteException('PIB je već registrovan.');
        }

        if ($this->physicalPassportTaken($passport, $exceptUserId)) {
            throw new CanonicalIdentityWriteException('Broj pasoša je već registrovan.');
        }
    }

    /**
     * Update-path self-exclusion: ignore only a canonical PIB owned by
     * $exceptUserId. Orphan rows (no platform identity) and other users
     * remain taken.
     */
    private function excludeOnlyCurrentUserCanonicalOwner(Builder $query, int $exceptUserId): void
    {
        $query->where(function ($inner) use ($exceptUserId): void {
            $inner->whereDoesntHave('platformIdentity')
                ->orWhereHas('platformIdentity', function ($owner) use ($exceptUserId): void {
                    $owner->where('user_id', '!=', $exceptUserId);
                });
        });
    }
}
