<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalOutcome;
use App\Models\Competition;
use App\Models\MmspDecisionDocument;
use App\Models\User;
use App\Services\Competitions\MmspDecisionDocumentStore;
use App\Services\Competitions\MmspDecisionDocumentStoreException;
use App\Services\Competitions\MmspDecisionProposalGate;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Support\Facades\DB;

/**
 * Predsjedničko zatvaranje MMSP konkursa nakon sačuvane Word odluke.
 * Ne traži sekretarijatski lanac. Ne prepisuje bodove, ishode ni iznose.
 * Postojeće zatvaranje preko CanonicalCloseService ostaje neizmijenjeno.
 */
final class MmspDecisionCloseService
{
    public const DEADLINE_MESSAGE = 'Ne možete zatvoriti konkurs dok je još otvoren za prijave. Rok za prijave mora prvo isteći.';

    public const OUTCOMES_MESSAGE = 'Zatvaranje zahtijeva konačno potvrđene ishode i iznose.';

    public const ALREADY_CLOSED_MESSAGE = 'Konkurs je već zatvoren.';

    public function __construct(
        private readonly CanonicalAdministrativeGate $gate,
        private readonly MmspDecisionProposalGate $decisionGate,
        private readonly MmspDecisionDocumentStore $documents,
    ) {}

    public function close(Competition $competition, User $actor): ProcessWriteResult
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null && $base !== CanonicalAdministrativeGate::CLOSED_MESSAGE) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->chairBlockReason($actor, $competition);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        if ($base === CanonicalAdministrativeGate::CLOSED_MESSAGE) {
            return ProcessWriteResult::failed(self::ALREADY_CLOSED_MESSAGE);
        }

        try {
            return DB::transaction(function () use ($competition, $actor) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if ($locked === null) {
                    return ProcessWriteResult::failed(CanonicalAdministrativeGate::CLOSED_MESSAGE);
                }

                if (CanonicalAdministrativeGate::isClosed($locked)) {
                    return ProcessWriteResult::failed(self::ALREADY_CLOSED_MESSAGE);
                }

                $actorReason = $this->gate->chairBlockReason($actor, $locked);
                if ($actorReason !== null) {
                    return ProcessWriteResult::failed($actorReason);
                }

                if (! $locked->deadline || ! now()->isAfter($locked->deadline)) {
                    return ProcessWriteResult::failed(self::DEADLINE_MESSAGE);
                }

                if (! $this->decisionGate->rankingIsFinal($locked) || ! $this->outcomesAreConfirmed($locked)) {
                    return ProcessWriteResult::failed(self::OUTCOMES_MESSAGE);
                }

                $document = $this->verifiedDocument($locked);
                if (! $document instanceof MmspDecisionDocument) {
                    return ProcessWriteResult::failed(MmspDecisionDocumentStore::CLOSE_BLOCKED);
                }

                $locked->forceFill([
                    'status' => 'completed',
                    'closed_at' => now(),
                ])->save();

                $this->gate->audit($locked, CanonicalAdministrativeEvent::COMPETITION_CLOSED, $actor, $locked, [
                    'status' => 'completed',
                    'mmsp_decision_document_id' => (int) $document->id,
                    'secretariat_chain_required' => false,
                ]);

                return ProcessWriteResult::ok($locked->id);
            });
        } catch (MmspDecisionDocumentStoreException) {
            return ProcessWriteResult::failed('Zatvaranje konkursa trenutno nije moguće jer sačuvana Word odluka nije mogla biti provjerena.');
        }
    }

    private function outcomesAreConfirmed(Competition $competition): bool
    {
        $outcomes = CanonicalOutcome::query()->where('competition_id', $competition->id)->get();
        if ($outcomes->isEmpty()) {
            return false;
        }

        foreach ($outcomes as $outcome) {
            if ($outcome->confirmed_at === null) {
                return false;
            }
            if ($outcome->isSupport() && ($outcome->approved_amount === null || bccomp((string) $outcome->approved_amount, '0', 2) !== 1)) {
                return false;
            }
        }

        return true;
    }

    private function verifiedDocument(Competition $competition): ?MmspDecisionDocument
    {
        $document = $this->documents->latestValidDocument($competition);
        if ($document === null) {
            return null;
        }

        $lockedDocument = MmspDecisionDocument::query()->whereKey($document->id)->lockForUpdate()->first();
        if ($lockedDocument === null || (int) $lockedDocument->competition_id !== (int) $competition->id) {
            return null;
        }

        $confirmed = $this->documents->latestValidDocument($competition);

        return $confirmed !== null && (int) $confirmed->id === (int) $lockedDocument->id
            ? $lockedDocument
            : null;
    }
}
