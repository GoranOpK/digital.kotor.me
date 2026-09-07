<?php

namespace App\Identity\Runtime;

use App\Identity\CanonicalIdentityWriteException;
use App\Identity\CanonicalIdentityWriter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ExistingSubjectIdentityCompletionService
{
    public function __construct(
        private readonly IdentityMutationGuard $guard = new IdentityMutationGuard,
        private readonly ExistingSubjectIdentityEligibility $eligibility = new ExistingSubjectIdentityEligibility,
        private readonly ExistingSubjectIdentitySnapshotMapper $mapper = new ExistingSubjectIdentitySnapshotMapper,
        private readonly CanonicalIdentityWriter $writer = new CanonicalIdentityWriter,
        private readonly DerivedUserTypeMirror $mirror = new DerivedUserTypeMirror,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function complete(User $user, array $validated): string
    {
        $this->guard->assertCanonicalHttpWriteAllowed();

        $before = $this->eligibility->inspect($user);
        if ($before->isCurrent()) {
            $this->log($user, $before->branch, 'duplicate_prevented', 'already_current');

            return 'duplicate';
        }

        if ($before->isMalformed()) {
            $this->log($user, null, 'ineligible', ExistingSubjectIdentityEligibilityResult::DENY_MALFORMED);
            throw new IdentityUseGateException(
                IdentityAccess::INVALID,
                'Identitet nije validan i mora se ispraviti (D14).'
            );
        }

        if (! $before->eligible || $before->branch === null) {
            $this->log($user, $before->branch, 'ineligible', $before->denyReason ?? 'ineligible');
            throw new IdentityMutationDeniedException('Dopuna identiteta subjekta nije dozvoljena.');
        }

        $branch = $before->branch;

        try {
            $outcome = DB::transaction(function () use ($user, $validated, $branch): string {
                User::query()->whereKey($user->id)->lockForUpdate()->first();
                $lockedUser = $user->fresh();
                if ($lockedUser === null) {
                    throw new RuntimeException('User missing during identity completion.');
                }

                $state = $this->eligibility->inspect($lockedUser);
                if ($state->isCurrent()) {
                    return 'duplicate';
                }
                if ($state->isMalformed()) {
                    throw new IdentityUseGateException(
                        IdentityAccess::INVALID,
                        'Identitet nije validan i mora se ispraviti (D14).'
                    );
                }
                if (! $state->eligible || $state->branch !== $branch) {
                    throw new IdentityMutationDeniedException('Dopuna identiteta subjekta nije dozvoljena.');
                }

                $snapshot = $this->mapper->fromValidated($lockedUser, $branch, $validated);
                $this->writer->createForUser($lockedUser, $snapshot);
                $this->mirror->sync($lockedUser, $snapshot);

                return 'created';
            });
        } catch (CanonicalIdentityWriteException $e) {
            $after = $this->eligibility->inspect($user->fresh());
            if ($after->isCurrent()) {
                $this->log($user, $branch, 'duplicate_prevented', 'writer_race');

                return 'duplicate';
            }

            throw $e;
        } catch (Throwable $e) {
            if ($e instanceof IdentityUseGateException || $e instanceof IdentityMutationDeniedException) {
                throw $e;
            }

            throw new CanonicalIdentityWriteException('Canonical identity completion failed.', 0, $e);
        }

        $this->log($user, $branch, $outcome === 'duplicate' ? 'duplicate_prevented' : 'success', $outcome);

        return $outcome;
    }

    private function log(User $user, ?string $branch, string $outcome, ?string $reasonCode): void
    {
        Log::info('identity.completion', [
            'user_id' => $user->id,
            'branch' => $branch,
            'outcome' => $outcome,
            'reason_code' => $reasonCode,
        ]);
    }
}
