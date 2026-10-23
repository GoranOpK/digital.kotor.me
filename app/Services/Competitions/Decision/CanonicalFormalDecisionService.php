<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalFormalDecision;
use App\Models\CanonicalPredlog;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\CompetitionOfficialDecisionCopyService;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\ProcessWriteResult;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * KN-SYNC-009 — records the formal Odluka adopted by the competent authority outside the platform.
 * The platform never decides, numbers or generates the Odluka; it stores the supplied number/date
 * and the signed copy through the existing official decision copy infrastructure.
 */
final class CanonicalFormalDecisionService
{
    public const HANDOFF_REQUIRED_MESSAGE = 'Formalna Odluka se evidentira tek nakon predaje finalizovanog Predloga Sekretarijatu.';

    public const HANDOFF_MISMATCH_MESSAGE = 'Predaja Sekretarijatu ne odgovara finalizovanom Predlogu.';

    public const NUMBER_REQUIRED_MESSAGE = 'Broj formalne Odluke je obavezan.';

    public const NUMBER_TOO_LONG_MESSAGE = 'Broj formalne Odluke ne može biti duži od 128 karaktera.';

    public const DATE_REQUIRED_MESSAGE = 'Datum formalne Odluke je obavezan (format GGGG-MM-DD).';

    public const DATE_FUTURE_MESSAGE = 'Datum formalne Odluke ne može biti u budućnosti.';

    public const DATE_BEFORE_HANDOFF_MESSAGE = 'Datum formalne Odluke ne može biti prije predaje Predloga Sekretarijatu.';

    public const DUPLICATE_MESSAGE = 'Formalna Odluka je već evidentirana za ovaj Poziv. Zamjena nije dozvoljena.';

    public const DECISION_REQUIRED_MESSAGE = 'Formalna Odluka nije evidentirana.';

    public const COPY_ALREADY_ATTACHED_MESSAGE = 'Potpisani primjerak Odluke je već priložen. Zamjena nije dozvoljena.';

    public const PDF_REQUIRED_MESSAGE = 'Potpisani primjerak Odluke mora biti PDF fajl do 2 MB.';

    public const MAX_PDF_KB = 2048;

    public function __construct(
        private readonly CanonicalAdministrativeGate $gate,
        private readonly CanonicalPredlogService $predlogs,
        private readonly CanonicalSecretariatHandoffService $handoffs,
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CompetitionOfficialDecisionCopyService $copies,
    ) {
    }

    public function recordBlockReason(Competition $competition): ?string
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return $base;
        }

        $predlogReason = $this->predlogs->finalizedPredlogBlockReason($competition);
        if ($predlogReason !== null) {
            return $predlogReason;
        }

        $handoff = $this->handoffs->handoffFor($competition);
        if ($handoff === null) {
            return self::HANDOFF_REQUIRED_MESSAGE;
        }

        if ((int) $handoff->predlog_id !== (int) $this->predlogs->predlogFor($competition)?->id) {
            return self::HANDOFF_MISMATCH_MESSAGE;
        }

        if ($this->decisionFor($competition) !== null) {
            return self::DUPLICATE_MESSAGE;
        }

        return null;
    }

    public function record(
        Competition $competition,
        User $actor,
        ?string $decisionNumber,
        ?string $decisionDate,
        ?string $issuer = null,
        ?string $notes = null,
    ): ProcessWriteResult {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->secretariatBlockReason($actor);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        $decisionNumber = trim((string) $decisionNumber);
        if ($decisionNumber === '') {
            return ProcessWriteResult::failed(self::NUMBER_REQUIRED_MESSAGE);
        }
        if (mb_strlen($decisionNumber) > 128) {
            return ProcessWriteResult::failed(self::NUMBER_TOO_LONG_MESSAGE);
        }

        $date = $this->parseDate($decisionDate);
        if ($date === null) {
            return ProcessWriteResult::failed(self::DATE_REQUIRED_MESSAGE);
        }

        $timezone = $this->governance->policy()->publicationTimezone();
        if ($date->greaterThan(CarbonImmutable::now($timezone)->startOfDay())) {
            return ProcessWriteResult::failed(self::DATE_FUTURE_MESSAGE);
        }

        try {
            return DB::transaction(function () use ($competition, $actor, $decisionNumber, $date, $issuer, $notes, $timezone) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();

                $reason = $this->recordBlockReason($locked);
                if ($reason !== null) {
                    return ProcessWriteResult::failed($reason);
                }

                $handoff = $this->handoffs->handoffFor($locked);
                $handedOffOn = CarbonImmutable::parse($handoff->handed_off_at)->setTimezone($timezone)->startOfDay();
                if ($date->lessThan($handedOffOn)) {
                    return ProcessWriteResult::failed(self::DATE_BEFORE_HANDOFF_MESSAGE);
                }

                $predlog = CanonicalPredlog::query()->where('competition_id', $locked->id)->firstOrFail();

                $decision = CanonicalFormalDecision::query()->create([
                    'competition_id' => $locked->id,
                    'publication_slot' => CanonicalFormalDecision::SLOT,
                    'predlog_id' => $predlog->id,
                    'handoff_id' => $handoff->id,
                    'decision_number' => $decisionNumber,
                    'decision_date' => $date->toDateString(),
                    'issuer' => self::nullableTrim($issuer),
                    'notes' => self::nullableTrim($notes),
                    'recorded_by_user_id' => $actor->id,
                    'recorded_at' => now(),
                ]);

                $this->gate->audit($locked, CanonicalAdministrativeEvent::FORMAL_DECISION_RECORDED, $actor, $decision, [
                    'decision_number' => $decisionNumber,
                    'decision_date' => $date->toDateString(),
                    'predlog_id' => (int) $predlog->id,
                    'handoff_id' => (int) $handoff->id,
                ]);

                return ProcessWriteResult::ok($decision->id);
            });
        } catch (UniqueConstraintViolationException) {
            return ProcessWriteResult::failed(self::DUPLICATE_MESSAGE);
        }
    }

    public function attachSignedCopy(Competition $competition, User $actor, UploadedFile $file): ProcessWriteResult
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->secretariatBlockReason($actor);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        if (! self::isAcceptablePdf($file)) {
            return ProcessWriteResult::failed(self::PDF_REQUIRED_MESSAGE);
        }

        $storedPath = null;

        try {
            return DB::transaction(function () use ($competition, $actor, $file, &$storedPath) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
                $decision = CanonicalFormalDecision::query()
                    ->where('competition_id', $locked->id)
                    ->where('publication_slot', CanonicalFormalDecision::SLOT)
                    ->lockForUpdate()
                    ->first();

                if ($decision === null) {
                    return ProcessWriteResult::failed(self::DECISION_REQUIRED_MESSAGE);
                }

                if ($decision->official_decision_copy_id !== null || $decision->isPublished()) {
                    return ProcessWriteResult::failed(self::COPY_ALREADY_ATTACHED_MESSAGE);
                }

                $copy = $this->copies->store($locked, $file, $actor, self::businessTitle($decision));
                $storedPath = $copy->storage_path;

                $decision->forceFill([
                    'official_decision_copy_id' => $copy->id,
                    'attached_by_user_id' => $actor->id,
                    'attached_at' => now(),
                ])->save();

                $this->gate->audit($locked, CanonicalAdministrativeEvent::SIGNED_COPY_ATTACHED, $actor, $decision, [
                    'official_decision_copy_id' => (int) $copy->id,
                ]);

                return ProcessWriteResult::ok($copy->id);
            });
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function decisionFor(Competition $competition): ?CanonicalFormalDecision
    {
        return CanonicalFormalDecision::query()
            ->where('competition_id', $competition->id)
            ->where('publication_slot', CanonicalFormalDecision::SLOT)
            ->first();
    }

    public static function businessTitle(CanonicalFormalDecision $decision): string
    {
        return 'Odluka br. '.$decision->decision_number.' od '.$decision->decision_date->format('d.m.Y');
    }

    private static function isAcceptablePdf(UploadedFile $file): bool
    {
        return $file->isValid()
            && strtolower((string) $file->getClientOriginalExtension()) === 'pdf'
            && in_array($file->getMimeType(), ['application/pdf', 'application/x-pdf'], true)
            && ($file->getSize() ?? PHP_INT_MAX) <= self::MAX_PDF_KB * 1024;
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $timezone = $this->governance->policy()->publicationTimezone();
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    private static function nullableTrim(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
