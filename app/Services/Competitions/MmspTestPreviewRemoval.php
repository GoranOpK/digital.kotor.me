<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Briše samo MMSP nacrt sa uključenim testnim pregledom i podatke koji pripadaju tom nacrtu.
 * Fizički fajlovi se uklanjaju tek nakon potvrđene transakcije baze.
 * Ne briše komisiju, korisnike, zvanične konkurse ni podatke drugih modula.
 */
class MmspTestPreviewRemoval
{
    public const CONFIRMATION_PREFIX = 'UKLONI TEST ';

    /**
     * @return array{removed: list<string>, kept: list<string>, failed: list<string>, audit_warning: ?string}
     */
    public function execute(User $actor, Competition $competition, string $confirmation): array
    {
        $this->assertAdministrator($actor);

        try {
            $this->assertConfirmation($competition, $confirmation);
            $planned = DB::transaction(function () use ($actor, $competition): array {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if ($locked === null) {
                    throw new MmspTestPreviewRemovalException('Testni konkurs nije pronađen.', 404);
                }

                $reason = $this->ineligibilityReason($locked);
                if ($reason !== null) {
                    throw new MmspTestPreviewRemovalException($reason);
                }

                $applicationIds = $this->applicationIds((int) $locked->id);
                $dependency = $this->externalDependencyReason((int) $locked->id, $applicationIds);
                if ($dependency !== null) {
                    throw new MmspTestPreviewRemovalException($dependency);
                }

                $files = $this->planFiles((int) $locked->id, $applicationIds);
                $this->deleteDatabaseRows((int) $locked->id, $applicationIds);

                $commissionId = $locked->commission_id;
                $locked->delete();

                if ($commissionId !== null && ! DB::table('commissions')->where('id', $commissionId)->exists()) {
                    throw new MmspTestPreviewRemovalException('Uklanjanje je zaustavljeno jer bi obrisalo komisiju.');
                }

                $this->beforeCommit();
                $this->record($actor, [
                    'competition_id' => (int) $competition->id,
                    'applications' => count($applicationIds),
                    'commission_id' => $commissionId,
                    'result' => 'database_removed',
                    'planned_files' => array_column($files, 'path'),
                ]);

                return $files;
            });
        } catch (MmspTestPreviewRemovalException $exception) {
            $this->recordSafely($actor, [
                'competition_id' => (int) $competition->id,
                'result' => 'refused',
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (Throwable $exception) {
            $this->recordSafely($actor, [
                'competition_id' => (int) $competition->id,
                'result' => 'aborted',
                'message' => 'Uklanjanje je prekinuto prije potvrde transakcije. Baza i dokumenti nijesu izmijenjeni.',
            ]);
            throw $exception;
        }

        $fileResult = $this->deleteFiles($planned);
        $fileResult['audit_warning'] = null;
        try {
            $this->record($actor, [
                'competition_id' => (int) $competition->id,
                'result' => $fileResult['failed'] === [] ? 'files_removed' : 'files_partial',
                'removed' => $fileResult['removed'],
                'kept' => $fileResult['kept'],
                'failed' => $fileResult['failed'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $fileResult['audit_warning'] = 'Baza je uklonjena. Evidencija brisanja fajlova nije upisana.';
        }

        return $fileResult;
    }

    public function ineligibilityReason(Competition $competition): ?string
    {
        if (! $competition->isCanonicalTestPreview()) {
            return 'Ovim postupkom može da se ukloni samo MMSP nacrt sa uključenim testnim pregledom.';
        }

        if (CompetitionPublicationClaim::query()->where('competition_id', $competition->id)->exists()) {
            return 'Testni nacrt koji ima evidenciju zvanične objave ne može da se ukloni ovim postupkom.';
        }

        foreach ([
            'youth_decision_documents',
            'zensko_decision_proposal_snapshots',
            'youth_equal_score_groups',
        ] as $table) {
            if ($this->competitionHasRows($table, (int) $competition->id)) {
                return 'Uklanjanje je zaustavljeno jer zapis drugog modula zavisi od ovog konkursa.';
            }
        }

        return null;
    }

    protected function beforeCommit(): void
    {
    }

    protected function deleteStoredFile(string $path): void
    {
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function assertAdministrator(User $actor): void
    {
        $actor->loadMissing('role');
        $role = $actor->role?->name;
        if (! in_array($role, ['admin', 'konkurs_admin', 'superadmin'], true)) {
            abort(403, 'Samo administrator može ukloniti MMSP testni konkurs.');
        }
    }

    private function assertConfirmation(Competition $competition, string $confirmation): void
    {
        $expected = self::CONFIRMATION_PREFIX.$competition->id;
        if (trim($confirmation) !== $expected) {
            throw new MmspTestPreviewRemovalException('Potvrda mora tačno glasiti: '.$expected.'.');
        }
    }

    /**
     * @return list<int>
     */
    private function applicationIds(int $competitionId): array
    {
        if (! Schema::hasTable('applications')) {
            return [];
        }

        return DB::table('applications')
            ->where('competition_id', $competitionId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $applicationIds
     */
    private function externalDependencyReason(int $competitionId, array $applicationIds): ?string
    {
        $message = 'Uklanjanje je zaustavljeno jer zapis drugog konkursa zavisi od podataka ovog testnog nacrta.';

        $noticeIds = $this->ownNoticeIds($competitionId);
        if ($noticeIds !== [] && $this->foreignNoticeReferences('superseded_notice_id', $noticeIds, $competitionId)) {
            return $message;
        }

        $copyIds = $this->idsForCompetition('competition_official_decision_copies', $competitionId);
        if ($copyIds !== [] && (
            $this->foreignNoticeReferences('source_object_id', $copyIds, $competitionId)
            || $this->otherCompetitionReferences('competition_official_decision_lifecycle_events', 'competition_official_decision_copy_id', $copyIds, $competitionId)
            || $this->otherCompetitionReferences('canonical_formal_decisions', 'official_decision_copy_id', $copyIds, $competitionId)
        )) {
            return $message;
        }

        if ($noticeIds !== [] && $this->otherCompetitionReferences('canonical_formal_decisions', 'published_notice_id', $noticeIds, $competitionId)) {
            return $message;
        }

        $predlogIds = $this->idsForCompetition('canonical_predlogs', $competitionId);
        if ($predlogIds !== [] && (
            $this->otherCompetitionReferences('canonical_formal_decisions', 'predlog_id', $predlogIds, $competitionId)
            || $this->otherCompetitionReferences('canonical_secretariat_handoffs', 'predlog_id', $predlogIds, $competitionId)
        )) {
            return $message;
        }

        $handoffIds = $this->idsForCompetition('canonical_secretariat_handoffs', $competitionId);
        if ($handoffIds !== [] && $this->otherCompetitionReferences('canonical_formal_decisions', 'handoff_id', $handoffIds, $competitionId)) {
            return $message;
        }

        $rankingIds = $this->idsForCompetition('canonical_final_rankings', $competitionId);
        if ($rankingIds !== [] && (
            $this->otherCompetitionReferences('canonical_outcomes', 'final_ranking_id', $rankingIds, $competitionId)
            || $this->otherCompetitionReferences('canonical_predlogs', 'final_ranking_id', $rankingIds, $competitionId)
        )) {
            return $message;
        }

        $assessmentIds = $this->idsForCompetition('application_completeness_assessments', $competitionId);
        if ($assessmentIds !== [] && $this->otherCompetitionReferences(
            'application_complaints',
            'completeness_assessment_id',
            $assessmentIds,
            $competitionId
        )) {
            return $message;
        }

        return $this->foreignApplicationOwnerReason($competitionId, $applicationIds);
    }

    /**
     * @param  list<int>  $applicationIds
     * @return list<array{kind: string, path: string, action: string}>
     */
    private function planFiles(int $competitionId, array $applicationIds): array
    {
        $files = [];

        foreach ([
            'mmsp_decision_documents' => 'storage_path',
            'competition_official_decision_copies' => 'storage_path',
        ] as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $rows = DB::table($table)->where('competition_id', $competitionId)->get(['id', $column]);
            foreach ($rows as $row) {
                $path = (string) $row->{$column};
                $owned = $this->decisionPathOwnedBy($path, $competitionId)
                    && ! $this->referencedByAnotherRecord($path, $competitionId, $applicationIds);
                $files[] = [
                    'kind' => $table,
                    'path' => $path,
                    'action' => $owned ? 'ukloniti' : 'zadržati',
                ];
            }
        }

        if ($applicationIds !== [] && Schema::hasTable('application_documents')) {
            $rows = DB::table('application_documents')->whereIn('application_id', $applicationIds)->get();
            foreach ($rows as $row) {
                $path = (string) ($row->file_path ?? '');
                $library = Schema::hasColumn('application_documents', 'user_document_id')
                    && $row->user_document_id !== null;
                $shared = $library
                    || ! $this->isRelativeFilePath($path)
                    || $this->referencedByAnotherRecord($path, $competitionId, $applicationIds);
                $files[] = [
                    'kind' => 'application_documents',
                    'path' => $path,
                    'action' => $shared ? 'zadržati' : 'ukloniti',
                ];
            }
        }

        return $files;
    }

    /**
     * @param  list<int>  $applicationIds
     */
    private function deleteDatabaseRows(int $competitionId, array $applicationIds): void
    {
        $this->deleteChildIds('canonical_tie_votes', 'tie_resolution_id', 'canonical_tie_resolutions', $competitionId);
        $this->deleteChildIds('canonical_final_ranking_signatures', 'final_ranking_id', 'canonical_final_rankings', $competitionId);

        foreach ([
            'canonical_formal_decisions',
            'canonical_administrative_events',
            'canonical_secretariat_handoffs',
            'canonical_predlogs',
            'canonical_outcomes',
            'canonical_tie_resolutions',
            'canonical_final_rankings',
        ] as $table) {
            $this->deleteWhereCompetition($table, $competitionId);
        }

        foreach ([
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
        ] as $table) {
            $this->deleteWhereCompetition($table, $competitionId);
        }

        $this->deleteOwnNotices($competitionId);
        $this->deleteWhereCompetition('competition_official_decision_lifecycle_events', $competitionId);
        $this->deleteWhereCompetition('competition_official_decision_copies', $competitionId);
        $this->deleteWhereCompetition('mmsp_decision_documents', $competitionId);
        $this->deleteWhereCompetition('competition_publication_claims', $competitionId);
        $this->deleteTestGovernanceConflicts($competitionId);
        $this->deleteWhereIn('applications', 'id', $applicationIds);
    }

    /**
     * Briše samo sukobe čiji competition_id jeste ovaj testni konkurs.
     * application_id nije ključ vlasništva: zapis drugog konkursa može pokazivati
     * na prijavu koja se uklanja, a baza tu vezu ne zabranjuje.
     */
    private function deleteTestGovernanceConflicts(int $competitionId): void
    {
        $this->deleteWhereCompetition('commission_governance_conflicts', $competitionId);
    }

    /**
     * @param  list<int>  $applicationIds
     */
    private function foreignApplicationOwnerReason(int $competitionId, array $applicationIds): ?string
    {
        if ($applicationIds === []) {
            return null;
        }

        $message = 'Uklanjanje je zaustavljeno jer zapis drugog konkursa referencira prijavu ovog testnog nacrta.';

        foreach ([
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
            'canonical_outcomes',
        ] as $table) {
            if ($this->otherCompetitionReferences($table, 'application_id', $applicationIds, $competitionId)) {
                return $message;
            }
        }

        if (! Schema::hasTable('commission_governance_conflicts') || ! Schema::hasColumn('commission_governance_conflicts', 'application_id')) {
            return null;
        }

        $foreignConflict = DB::table('commission_governance_conflicts')
            ->whereIn('application_id', $applicationIds)
            ->where(function ($query) use ($competitionId): void {
                $query->whereNull('competition_id')
                    ->orWhere('competition_id', '!=', $competitionId);
            })
            ->exists();

        return $foreignConflict ? $message : null;
    }

    /**
     * @param  list<array{kind: string, path: string, action: string}>  $planned
     * @return array{removed: list<string>, kept: list<string>, failed: list<string>}
     */
    private function deleteFiles(array $planned): array
    {
        $removed = [];
        $kept = [];
        $failed = [];

        foreach ($planned as $file) {
            $path = $file['path'];
            if ($file['action'] !== 'ukloniti' || $this->stillReferenced($path) || ! $this->isRelativeFilePath($path)) {
                $kept[] = $path;

                continue;
            }

            try {
                $this->deleteStoredFile($path);
                if (Storage::disk('local')->exists($path)) {
                    $failed[] = $path;
                } else {
                    $removed[] = $path;
                }
            } catch (Throwable $exception) {
                report($exception);
                $failed[] = $path;
            }
        }

        return [
            'removed' => $removed,
            'kept' => $kept,
            'failed' => $failed,
        ];
    }

    private function stillReferenced(string $path): bool
    {
        foreach ([
            'application_documents' => 'file_path',
            'user_documents' => 'file_path',
            'mmsp_decision_documents' => 'storage_path',
            'competition_official_decision_copies' => 'storage_path',
            'youth_decision_documents' => 'storage_path',
            'zensko_decision_proposal_snapshots' => 'storage_path',
        ] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $path)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<int>  $applicationIds
     */
    private function referencedByAnotherRecord(string $path, int $competitionId, array $applicationIds): bool
    {
        if ($path === '') {
            return true;
        }

        if (Schema::hasTable('user_documents') && Schema::hasColumn('user_documents', 'file_path')) {
            if (DB::table('user_documents')->where('file_path', $path)->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('application_documents') && Schema::hasColumn('application_documents', 'file_path')) {
            $query = DB::table('application_documents')->where('file_path', $path);
            if ($applicationIds !== []) {
                $query->whereNotIn('application_id', $applicationIds);
            }
            if ($query->exists()) {
                return true;
            }
        }

        foreach ([
            'mmsp_decision_documents',
            'competition_official_decision_copies',
            'youth_decision_documents',
            'zensko_decision_proposal_snapshots',
        ] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'storage_path') || ! Schema::hasColumn($table, 'competition_id')) {
                continue;
            }
            if (DB::table($table)->where('storage_path', $path)->where('competition_id', '!=', $competitionId)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function decisionPathOwnedBy(string $path, int $competitionId): bool
    {
        if (! $this->isRelativeFilePath($path)) {
            return false;
        }

        $prefixes = [
            'mmsp-decision-documents/'.$competitionId.'/',
            'competitions/'.$competitionId.'/official-decisions/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with(str_replace('\\', '/', $path), $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isRelativeFilePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '..')) {
            return false;
        }

        $normalized = str_replace('\\', '/', $path);

        return ! str_starts_with($normalized, '/')
            && ! preg_match('/^[A-Za-z]:/', $normalized);
    }

    /**
     * @param  list<int>  $ids
     */
    private function foreignNoticeReferences(string $column, array $ids, int $competitionId): bool
    {
        if ($ids === [] || ! Schema::hasTable('notices') || ! Schema::hasColumn('notices', $column)) {
            return false;
        }

        return DB::table('notices')
            ->whereIn($column, $ids)
            ->where(function ($query) use ($competitionId): void {
                $query->where('source_type', '!=', 'competition_decision')
                    ->orWhere('source_id', '!=', $competitionId);
            })
            ->exists();
    }

    /**
     * @param  list<int>  $ids
     */
    private function otherCompetitionReferences(string $table, string $column, array $ids, int $competitionId): bool
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasColumn($table, 'competition_id')) {
            return false;
        }

        return DB::table($table)
            ->where('competition_id', '!=', $competitionId)
            ->whereIn($column, $ids)
            ->exists();
    }

    /**
     * @return list<int>
     */
    private function ownNoticeIds(int $competitionId): array
    {
        if (! Schema::hasTable('notices')) {
            return [];
        }

        return DB::table('notices')
            ->where('source_type', 'competition_decision')
            ->where('source_id', $competitionId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function idsForCompetition(string $table, int $competitionId): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'competition_id')) {
            return [];
        }

        return DB::table($table)
            ->where('competition_id', $competitionId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function deleteOwnNotices(int $competitionId): void
    {
        if (! Schema::hasTable('notices')) {
            return;
        }

        $remaining = $this->ownNoticeIds($competitionId);
        $guard = 0;
        while ($remaining !== []) {
            $guard++;
            if ($guard > 1000) {
                throw new MmspTestPreviewRemovalException('Obavještenja ovog testnog nacrta ne mogu da se uklone zbog međusobne veze.');
            }

            $blocked = [];
            if (Schema::hasColumn('notices', 'superseded_notice_id')) {
                $blocked = DB::table('notices')
                    ->whereIn('id', $remaining)
                    ->whereIn('superseded_notice_id', $remaining)
                    ->pluck('superseded_notice_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
            }

            $ready = array_values(array_diff($remaining, $blocked));
            if ($ready === []) {
                throw new MmspTestPreviewRemovalException('Obavještenja ovog testnog nacrta ne mogu da se uklone zbog međusobne veze.');
            }

            DB::table('notices')->whereIn('id', $ready)->delete();
            $remaining = array_values(array_diff($remaining, $ready));
        }
    }

    private function deleteChildIds(string $childTable, string $childColumn, string $parentTable, int $competitionId): void
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasTable($parentTable) || ! Schema::hasColumn($parentTable, 'competition_id')) {
            return;
        }

        $parentIds = DB::table($parentTable)->where('competition_id', $competitionId)->pluck('id');
        if ($parentIds->isEmpty()) {
            return;
        }

        DB::table($childTable)->whereIn($childColumn, $parentIds->all())->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    private function deleteWhereIn(string $table, string $column, array $ids): void
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $ids)->delete();
    }

    private function deleteWhereCompetition(string $table, int $competitionId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'competition_id')) {
            return;
        }

        DB::table($table)->where('competition_id', $competitionId)->delete();
    }

    private function competitionHasRows(string $table, int $competitionId): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'competition_id')
            && DB::table($table)->where('competition_id', $competitionId)->exists();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function record(User $actor, array $details): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        DB::table('logs')->insert([
            'user_id' => $actor->id,
            'action' => 'mmsp_test_preview_removal',
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordSafely(User $actor, array $details): void
    {
        try {
            $this->record($actor, $details);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
