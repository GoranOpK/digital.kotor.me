<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\CanonicalIdentityWriteException;
use App\Models\PlatformIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Shared account-name policy. Never creates a subject or repairs a broken graph. */
final class UserNameService
{
    public function isAccountOnly(User $user): bool
    {
        if (! $user->isStaffAccount() || $user->user_type !== null
            || PlatformIdentity::query()->where('user_id', $user->id)->exists()) {
            return false;
        }

        foreach (['residential_status', 'company_name', 'jmb', 'jmb_encrypted', 'jmb_lookup', 'pib', 'passport_number'] as $field) {
            if (filled($user->getRawOriginal($field))) {
                return false;
            }
        }

        return true;
    }

    public function update(User $user, string $firstName, string $lastName, bool $updatePhone = false, ?string $phone = null): User
    {
        return DB::transaction(function () use ($user, $firstName, $lastName, $updatePhone, $phone) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $guard = app(IdentityMutationGuard::class);
            $hasGraph = PlatformIdentity::query()->where('user_id', $locked->id)->exists();

            if (! $this->isAccountOnly($locked)) {
                // A stored graph remains authoritative even when a reader switch is OFF.
                if ($hasGraph || config('identity.canonical_read') || config('identity.canonical_write')) {
                    if (! $guard->canonicalHttpWriteAllowed()) {
                        throw ValidationException::withMessages(['first_name' => 'Izmjena imena trenutno nije dozvoljena u ovom režimu identiteta.']);
                    }
                    $current = app(CanonicalIdentityReader::class)->forUser($locked);
                    $snapshot = app(ProfileIdentityMapper::class)->snapshotForAdminContact(
                        $locked, $firstName, $lastName, $updatePhone ? $phone : $current->mobilePhone
                    );
                    app(CanonicalIdentityWriter::class)->updateLiveGraph($locked, $snapshot);
                    $saved = app(CanonicalIdentityReader::class)->forUser($locked);
                    $savedFirst = $saved->physicalPerson?->firstName ?? $saved->legalEntity?->authorizedPerson?->firstName;
                    $savedLast = $saved->physicalPerson?->lastName ?? $saved->legalEntity?->authorizedPerson?->lastName;
                    if ($savedFirst !== $firstName || $savedLast !== $lastName) {
                        throw new CanonicalIdentityWriteException('Canonical name was not saved.');
                    }
                } else {
                    $guard->assertLegacyIdentityMutationAllowed();
                }
            }

            $locked->first_name = $firstName;
            $locked->last_name = $lastName;
            $locked->name = trim($firstName.' '.$lastName);
            if ($updatePhone && (! $hasGraph || ! $guard->canonicalHttpWriteAllowed())) {
                $locked->phone = $phone;
            }
            if (! $locked->save()) {
                throw new CanonicalIdentityWriteException('Account name was not saved.');
            }

            return $locked;
        });
    }
}
