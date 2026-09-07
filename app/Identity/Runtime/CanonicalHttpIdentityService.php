<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\IdentityRollout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CanonicalHttpIdentityService
{
    public function __construct(
        private readonly IdentityRollout $rollout = new IdentityRollout,
        private readonly IdentityMutationGuard $guard = new IdentityMutationGuard,
        private readonly CanonicalIdentityWriter $writer = new CanonicalIdentityWriter,
        private readonly RegistrationIdentityMapper $registrationMapper = new RegistrationIdentityMapper,
        private readonly ProfileIdentityMapper $profileMapper = new ProfileIdentityMapper,
        private readonly DerivedUserTypeMirror $mirror = new DerivedUserTypeMirror,
    ) {
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $validated
     */
    public function registerFromValidated(
        array $userData,
        array $validated,
        ?string $jmb,
        string $storedUserType,
    ): User {
        $this->guard->assertSubjectCreationAllowed();

        if (! $this->guard->canonicalHttpWriteAllowed()) {
            return User::create($userData);
        }

        $account = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'role_id' => $userData['role_id'],
            'activation_status' => $userData['activation_status'],
        ];

        try {
            return DB::transaction(function () use ($account, $userData, $validated, $jmb, $storedUserType) {
                $user = User::create($account);
                $snapshot = $this->registrationMapper->snapshot($user, $userData, $validated, $jmb, $storedUserType);
                $this->writer->createForUser($user, $snapshot);
                $this->mirror->sync($user, $snapshot);

                return $user->refresh();
            });
        } catch (CanonicalIdentityWriteException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CanonicalIdentityWriteException('Canonical registration failed.', 0, $e);
        }
    }

    public function updateProfileIdentity(User $user, Request $request): void
    {
        if (! $this->guard->canonicalHttpWriteAllowed()) {
            $this->guard->assertLegacyIdentityMutationAllowed();

            return;
        }

        $this->guard->assertCanonicalHttpWriteAllowed();

        $snapshot = $this->profileMapper->snapshotForUpdate($user, $request);
        $this->writer->updateLiveGraph($user, $snapshot);
        $this->mirror->sync($user, $snapshot);
    }

    public function updateAdminIdentityContact(User $user, string $firstName, string $lastName, ?string $phone): void
    {
        if (! $this->guard->canonicalHttpWriteAllowed()) {
            $this->guard->assertLegacyIdentityMutationAllowed();

            return;
        }

        $this->guard->assertCanonicalHttpWriteAllowed();

        $snapshot = $this->profileMapper->snapshotForAdminContact($user, $firstName, $lastName, $phone);
        $this->writer->updateLiveGraph($user, $snapshot);
        $this->mirror->sync($user, $snapshot);
    }

    public function usesCanonicalWrites(): bool
    {
        return $this->guard->canonicalHttpWriteAllowed();
    }

    public function usesCanonicalReads(): bool
    {
        return $this->rollout->readsCanonical();
    }
}
