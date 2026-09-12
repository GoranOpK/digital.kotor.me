<?php

namespace App\Identity\Runtime;

use App\Identity\Validation\JmbIdentifierValidator;
use App\Models\User;
use App\Security\JmbEncryptedReadService;
use App\Security\JmbLookupService;

/**
 * Server-side read of a usable users.jmb_encrypted + users.jmb_lookup pair.
 * Plaintext users.jmb is never a source.
 */
final class ExistingSubjectIdentityStoredJmb
{
    public const CONFLICT_MESSAGE = 'Dopuna se ne može završiti jer identifikacioni podatak nije raspoloživ za novi identitet.';

    public function readUsable(User $user): ?string
    {
        $encrypted = $this->present($user->jmb_encrypted);
        $lookup = $this->present($user->jmb_lookup);
        if ($encrypted === null || $lookup === null) {
            return null;
        }

        $value = app(JmbEncryptedReadService::class)->readValue(
            $encrypted,
            null,
            'users',
            $user->id,
            'jmb_encrypted',
        );
        if (! is_string($value) || ! preg_match('/^[0-9]{13}$/', $value)) {
            return null;
        }

        if (! (new JmbIdentifierValidator)->isValid($value)) {
            return null;
        }

        $digest = app(JmbLookupService::class)->digest($value);
        if (! is_string($digest) || strlen($lookup) !== strlen($digest) || ! hash_equals($digest, $lookup)) {
            return null;
        }

        return $value;
    }

    private function present(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
