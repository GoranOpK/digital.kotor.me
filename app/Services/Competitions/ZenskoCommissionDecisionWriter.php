<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\Competition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Upis zaključka ženskog preduzetništva.
 * Prvo čuvanje odluke koja još nije postojala trajno je zaključava.
 * Ranije sačuvana odluka bez vremena zaključavanja ostaje izmjena, ali ne smije
 * da potroši budžet ni prioritet koji zakonit završetak još treba.
 */
final class ZenskoCommissionDecisionWriter
{
    public const DECISION_LOCKED_MESSAGE =
        'Odluka je konačna i ne može se mijenjati.';

    public function __construct(
        protected AllocationCompletionGuard $completion,
        protected ZpEqualScoreAllocationGuard $equalScores,
    ) {}

    /**
     * @param  array{
     *     commission_decision: string,
     *     commission_justification?: string|null,
     *     commission_notes?: string|null,
     *     approved_amount?: mixed
     * }  $input
     */
    public function store(Application $application, array $input): void
    {
        $application->loadMissing('competition');
        $competition = $application->competition;
        if ($competition === null || $competition->type !== 'zensko') {
            abort(403);
        }

        DB::transaction(function () use ($application, $competition, $input) {
            $lockedCompetition = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($lockedCompetition === null) {
                abort(404);
            }

            $lockedRows = Application::query()
                ->where('competition_id', $lockedCompetition->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $locked = $lockedRows->firstWhere('id', $application->id);
            if ($locked === null) {
                abort(404);
            }

            if ($locked->commission_decision_locked_at !== null) {
                abort(403, self::DECISION_LOCKED_MESSAGE);
            }

            $decision = (string) ($input['commission_decision'] ?? '');
            $justification = trim((string) ($input['commission_justification'] ?? ''));
            $notes = $input['commission_notes'] ?? null;
            $approvedAmount = $input['approved_amount'] ?? null;
            $firstDecision = ! in_array($locked->commission_decision, ['podrzava_potpuno', 'odbija'], true);
            $pool = $this->equalScores->rankingApplications($lockedCompetition);

            if (
                $justification === ''
                && $this->equalScores->requiresCommissionJustification(
                    $lockedCompetition,
                    $locked,
                    ['commission_decision' => $decision]
                )
            ) {
                throw ValidationException::withMessages([
                    'commission_justification' => ZpEqualScoreAllocationGuard::SAME_STAGE_JUSTIFICATION_MESSAGE,
                ]);
            }

            if ($decision === 'podrzava_potpuno') {
                $this->assertSupport($locked, $lockedCompetition, $pool, $approvedAmount, $justification);
                $amount = $this->money((string) $approvedAmount);
                $locked->forceFill([
                    'commission_decision' => 'podrzava_potpuno',
                    'commission_justification' => $justification !== '' ? $justification : null,
                    'commission_notes' => $notes,
                    'approved_amount' => $amount,
                    'commission_decision_date' => now(),
                    'signed_by_chairman' => true,
                    'status' => 'approved',
                    'rejection_reason' => null,
                    'commission_decision_locked_at' => $firstDecision ? now() : null,
                ])->save();

                return;
            }

            if ($decision === 'odbija') {
                if ($justification === '') {
                    throw ValidationException::withMessages([
                        'commission_justification' => 'Za zaključak Odbija obrazloženje je obavezno.',
                    ]);
                }

                if ($this->completion->startupRejectionBlocked($pool, $locked)) {
                    throw ValidationException::withMessages([
                        'commission_decision' => AllocationCompletionGuard::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE,
                    ]);
                }

                $locked->forceFill([
                    'commission_decision' => 'odbija',
                    'commission_justification' => $justification,
                    'commission_notes' => $notes,
                    'approved_amount' => null,
                    'commission_decision_date' => now(),
                    'signed_by_chairman' => true,
                    'status' => 'rejected',
                    'rejection_reason' => $justification,
                    'commission_decision_locked_at' => $firstDecision ? now() : null,
                ])->save();

                return;
            }

            throw ValidationException::withMessages([
                'commission_decision' => 'Morate odabrati zaključak komisije.',
            ]);
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Application>  $pool
     */
    private function assertSupport(
        Application $locked,
        Competition $competition,
        $pool,
        mixed $approvedAmount,
        string $justification,
    ): void {
        if ($approvedAmount === null || $approvedAmount === '' || (float) $approvedAmount <= 0) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Za zaključak Podržava predloženi iznos podrške je obavezan i mora biti veći od nule.',
            ]);
        }

        $amount = $this->money((string) $approvedAmount);

        if (
            $locked->requested_amount !== null
            && bccomp($amount, $this->money((string) $locked->requested_amount), 2) === 1
        ) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Odobreni iznos ne može biti veći od traženog iznosa.',
            ]);
        }

        $requiredFunds = $locked->displayRequiredFunds();
        if (
            $requiredFunds !== null
            && $requiredFunds > 0
            && bccomp($amount, $this->money(number_format($requiredFunds, 2, '.', '')), 2) === 1
        ) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Odobreni iznos ne može biti veći od ukupno potrebnih sredstava za realizaciju biznis plana.',
            ]);
        }

        $budget = $this->money((string) ($competition->budget ?? 0));
        if ((bool) $locked->bonus_green_innovative) {
            $twentyPercentCap = $this->money(number_format(round((float) $budget * 0.20, 2), 2, '.', ''));
            if (bccomp($amount, $twentyPercentCap, 2) === 1) {
                throw ValidationException::withMessages([
                    'approved_amount' => 'Odobreni iznos ne može biti veći od 20% ukupnog budžeta konkursa.',
                ]);
            }
        }

        if ($this->completion->developmentSupportBlocked($pool, $locked)) {
            throw ValidationException::withMessages([
                'commission_decision' => AllocationCompletionGuard::DEVELOPMENT_INSTEAD_OF_STARTUP_MESSAGE,
            ]);
        }

        $room = $this->completion->supportRoom(
            $budget,
            $pool,
            $locked,
            fn (Application $other) => $this->completion->zenskoMaxClaim($other, $budget),
        );
        if (bccomp($amount, $room, 2) === 1) {
            throw ValidationException::withMessages([
                'approved_amount' => 'Odobreni iznos ne može biti veći od preostalih sredstava konkursa.',
            ]);
        }

        unset($justification);
    }

    private function money(string $value): string
    {
        return bcadd($value, '0', 2);
    }
}
