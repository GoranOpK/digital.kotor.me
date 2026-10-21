<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\CanonicalFormalDecision;
use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\ProcessWriteResult;
use App\Services\Notices\NoticePublicationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * KN-SYNC-009 — one-time publication of the signed formal Odluka copy through the existing
 * Notice channel. The public authoritative artifact is the signed copy only; the preliminary
 * ranking, internal outcome table, Predlog and Commission documents are never published.
 * No correction, replacement, unpublish or republication for canonical Pozivi.
 */
final class CanonicalResultPublicationService
{
    public const NOTICE_SOURCE_TYPE = 'competition_decision';

    public const NOTICE_DELIVERY = 'competition_decision_signed_copy';

    public const SIGNED_COPY_REQUIRED_MESSAGE = 'Objava zahtijeva priložen potpisani primjerak formalne Odluke.';

    public const SIGNED_COPY_INVALID_MESSAGE = 'Potpisani primjerak ne pripada ovom Pozivu ili nije dostupan. Objava je blokirana.';

    public const PREDLOG_MISMATCH_MESSAGE = 'Formalna Odluka ne odgovara finalizovanom Predlogu i predaji Sekretarijatu. Objava je blokirana.';

    public const ALREADY_PUBLISHED_MESSAGE = 'Rezultat je već objavljen. Ponovna objava, zamjena ili korekcija nijesu dozvoljene.';

    public const TEST_ALREADY_PUBLISHED_MESSAGE = 'TESTNA objava je već evidentirana. Nije kreiran javni rezultat niti javno Obavještenje.';

    public const TEST_PUBLICATION_REQUIRED_MESSAGE = 'Zatvaranje TEST poziva zahtijeva evidentiranu testnu objavu potpisanog primjerka formalne Odluke.';

    public function __construct(
        private readonly CanonicalAdministrativeGate $gate,
        private readonly CanonicalPredlogService $predlogs,
        private readonly CanonicalSecretariatHandoffService $handoffs,
        private readonly CanonicalFormalDecisionService $decisions,
        private readonly CanonicalCommissionGovernance $governance,
        private readonly NoticePublicationService $notices,
    ) {
    }

    public function blockReason(Competition $competition): ?string
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return $base;
        }

        $decision = $this->decisions->decisionFor($competition);
        if ($decision === null) {
            return CanonicalFormalDecisionService::DECISION_REQUIRED_MESSAGE;
        }

        if ($competition->isCanonicalTestMode() && $this->testPublicationEvent($competition) !== null) {
            return self::TEST_ALREADY_PUBLISHED_MESSAGE;
        }

        if ($decision->isPublished() || CompetitionOfficialDecisionCopy::competitionHasPublishedSignedCopy($competition->id)) {
            return self::ALREADY_PUBLISHED_MESSAGE;
        }

        $chainReason = $this->chainBlockReason($competition, $decision);
        if ($chainReason !== null) {
            return $chainReason;
        }

        return $this->signedCopyBlockReason($competition, $decision);
    }

    public function publish(Competition $competition, User $actor): ProcessWriteResult
    {
        $base = $this->gate->baseBlockReason($competition);
        if ($base !== null) {
            return ProcessWriteResult::failed($base);
        }

        $actorReason = $this->gate->secretariatBlockReason($actor);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        return DB::transaction(function () use ($competition, $actor) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();
            CanonicalFormalDecision::query()->where('competition_id', $locked->id)->lockForUpdate()->get();

            $reason = $this->blockReason($locked);
            if ($reason !== null) {
                return ProcessWriteResult::failed($reason);
            }

            $decision = $this->decisions->decisionFor($locked);
            $copy = CompetitionOfficialDecisionCopy::query()->findOrFail($decision->official_decision_copy_id);
            $publishedOn = CarbonImmutable::now($this->governance->policy()->publicationTimezone())->toDateString();

            // Kontrolisani MMSP TEST završava isti interni workflow, ali nikada ne kreira
            // javno Obavještenje, ne označava potpisani PDF kao javno objavljen i ne puni
            // published_* polja formalne Odluke. Jedini trajni zapis je append-only audit.
            if ($locked->isCanonicalTestMode()) {
                $event = $this->gate->audit($locked, CanonicalAdministrativeEvent::RESULT_TEST_PUBLISHED, $actor, $decision, [
                    'test_preview' => true,
                    'official_decision_copy_id' => (int) $copy->id,
                    'tested_on' => $publishedOn,
                ]);

                return ProcessWriteResult::ok($event->id);
            }

            $title = CanonicalFormalDecisionService::businessTitle($decision);

            $copy->business_published_on = $publishedOn;
            $copy->save();

            CompetitionOfficialDecisionCopy::revokeLeftoverDecisionHtmlPublications($locked->id);

            $notice = $this->notices->publish([
                'title' => $title,
                'short_description' => $locked->title,
                'source_type' => self::NOTICE_SOURCE_TYPE,
                'source_id' => $locked->id,
                'content_delivery' => self::NOTICE_DELIVERY,
                'source_object_id' => $copy->id,
                'public_display_date' => $publishedOn,
            ]);

            $decision->forceFill([
                'published_notice_id' => $notice->id,
                'published_by_user_id' => $actor->id,
                'published_at' => now(),
            ])->save();

            $this->gate->audit($locked, CanonicalAdministrativeEvent::RESULT_PUBLISHED, $actor, $decision, [
                'notice_id' => (int) $notice->id,
                'official_decision_copy_id' => (int) $copy->id,
                'published_on' => $publishedOn,
            ]);

            return ProcessWriteResult::ok($notice->id);
        });
    }

    /**
     * Published and still publicly available — used by the close gate.
     */
    public function publishedBlockReason(Competition $competition): ?string
    {
        $decision = $this->decisions->decisionFor($competition);
        if ($decision === null) {
            return CanonicalFormalDecisionService::DECISION_REQUIRED_MESSAGE;
        }

        if ($competition->isCanonicalTestMode()) {
            if ($this->testPublicationEvent($competition) === null) {
                return self::TEST_PUBLICATION_REQUIRED_MESSAGE;
            }

            $chainReason = $this->chainBlockReason($competition, $decision);
            if ($chainReason !== null) {
                return $chainReason;
            }

            return $this->signedCopyBlockReason($competition, $decision);
        }

        if (! $decision->isPublished()) {
            return 'Zatvaranje zahtijeva objavljen potpisani primjerak formalne Odluke.';
        }

        $chainReason = $this->chainBlockReason($competition, $decision);
        if ($chainReason !== null) {
            return $chainReason;
        }

        $notice = Notice::query()->find($decision->published_notice_id);
        if ($notice === null
            || ! $notice->publicly_available
            || $notice->content_delivery !== self::NOTICE_DELIVERY
            || (int) $notice->source_id !== (int) $competition->id
            || (int) $notice->source_object_id !== (int) $decision->official_decision_copy_id) {
            return 'Objava potpisanog primjerka formalne Odluke nije javno dostupna.';
        }

        return $this->signedCopyBlockReason($competition, $decision);
    }

    /**
     * Public facts only: never scores, votes, ties, complaints, evidence, PIB, remarks or the Predlog.
     *
     * @return array{competition_title: string, competition_year: mixed, decision_number: string, decision_date: string, published_at: string, signed_copy_url: string}|null
     */
    public function publicResult(Competition $competition): ?array
    {
        if (! $this->gate->isCanonical($competition) || $competition->isCanonicalTestMode()) {
            return null;
        }

        $decision = $this->decisions->decisionFor($competition);
        if ($decision === null || ! $decision->isPublished()) {
            return null;
        }

        $notice = Notice::query()->find($decision->published_notice_id);
        if ($notice === null || ! $notice->publicly_available) {
            return null;
        }

        return [
            'competition_title' => (string) $competition->title,
            'competition_year' => $competition->year,
            'decision_number' => (string) $decision->decision_number,
            'decision_date' => $decision->decision_date->toDateString(),
            'published_at' => $decision->published_at->toIso8601String(),
            'signed_copy_url' => route('notices.public-content', $notice),
        ];
    }

    public function testPublicationEvent(Competition $competition): ?CanonicalAdministrativeEvent
    {
        if (! $competition->isCanonicalTestMode()) {
            return null;
        }

        return CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->where('action', CanonicalAdministrativeEvent::RESULT_TEST_PUBLISHED)
            ->orderByDesc('id')
            ->first();
    }

    private function chainBlockReason(Competition $competition, CanonicalFormalDecision $decision): ?string
    {
        $predlogReason = $this->predlogs->finalizedPredlogBlockReason($competition);
        if ($predlogReason !== null) {
            return $predlogReason;
        }

        $predlog = $this->predlogs->predlogFor($competition);
        $handoff = $this->handoffs->handoffFor($competition);

        if ($handoff === null
            || (int) $decision->competition_id !== (int) $competition->id
            || (int) $decision->predlog_id !== (int) $predlog->id
            || (int) $decision->handoff_id !== (int) $handoff->id
            || (int) $handoff->predlog_id !== (int) $predlog->id) {
            return self::PREDLOG_MISMATCH_MESSAGE;
        }

        return null;
    }

    private function signedCopyBlockReason(Competition $competition, CanonicalFormalDecision $decision): ?string
    {
        if ($decision->official_decision_copy_id === null) {
            return self::SIGNED_COPY_REQUIRED_MESSAGE;
        }

        $copy = CompetitionOfficialDecisionCopy::query()->find($decision->official_decision_copy_id);
        if ($copy === null
            || (int) $copy->competition_id !== (int) $competition->id
            || $copy->permanently_deleted_at !== null
            || $copy->permanent_delete_pending_at !== null
            || ! is_string($copy->storage_path)
            || $copy->storage_path === ''
            || ! Storage::disk('local')->exists($copy->storage_path)) {
            return self::SIGNED_COPY_INVALID_MESSAGE;
        }

        return null;
    }
}
