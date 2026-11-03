<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Jednokratno uklanjanje tačno jednog zatvorenog PM ili ŽP konkursa.
 * Ne prepoznaje testne konkurse i ne dira redovno brisanje ni MMSP pravila.
 * Eloquent zabrane na zapisima odluke ostaju za sve ostale putanje;
 * ovaj postupak briše samo redove izabranog konkursa preko query buildera.
 */
class ArchivedTestCompetitionRemoval
{
    public const CONFIRMATION_PREFIX = 'UKLONI ';

    /**
     * @return array{
     *     competition: Competition,
     *     eligible: bool,
     *     reason: ?string,
     *     counts: array<string, int>,
     *     files: list<array{kind: string, path: string, action: string}>
     * }
     */
    public function preview(User $actor, int $competitionId): array
    {
        $this->assertSuperadmin($actor);
        $competition = $this->find($competitionId);
        $applicationIds = $this->applicationIds($competitionId);
        $files = $this->describeFiles($competitionId, $applicationIds);
        $reason = $this->ineligibilityReason($competition);

        return [
            'competition' => $competition,
            'eligible' => $reason === null,
            'reason' => $reason,
            'counts' => $this->counts($competitionId, $applicationIds),
            'files' => $files,
        ];
    }

    /**
     * @return array{removed: list<string>, kept: list<string>, failed: list<string>, audit_warning: ?string}
     */
    public function execute(User $actor, int $competitionId, string $confirmation): array
    {
        $this->assertSuperadmin($actor);

        try {
            $this->assertConfirmation($competitionId, $confirmation);
            $planned = DB::transaction(function () use ($actor, $competitionId): array {
                $competition = $this->findForUpdate($competitionId);
                $reason = $this->ineligibilityReason($competition);
                if ($reason !== null) {
                    throw new ArchivedTestCompetitionRemovalException($reason, 422);
                }

                $applicationIds = $this->applicationIds($competitionId);
                $files = $this->describeFiles($competitionId, $applicationIds);
                $counts = $this->counts($competitionId, $applicationIds);
                $this->deleteDependents($competitionId, $applicationIds);
                $competition->delete();
                $this->beforeCommit();
                $this->record($actor, [
                    'competition_id' => $competitionId,
                    'title' => (string) $competition->title,
                    'type' => (string) $competition->type,
                    'status' => (string) $competition->status,
                    'result' => 'database_removed',
                    'counts' => $counts,
                    'planned_files' => array_column($files, 'path'),
                ]);

                return $files;
            });
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            $this->recordSafely($actor, [
                'competition_id' => $competitionId,
                'result' => 'refused',
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (Throwable $exception) {
            $this->recordSafely($actor, [
                'competition_id' => $competitionId,
                'result' => 'aborted',
                'message' => 'Uklanjanje je prekinuto prije potvrde transakcije. Baza nije izmijenjena.',
            ]);
            throw $exception;
        }

        $fileResult = $this->deleteFiles($planned);
        $fileResult['audit_warning'] = null;
        try {
            $this->record($actor, [
                'competition_id' => $competitionId,
                'result' => $fileResult['failed'] === [] ? 'files_removed' : 'files_partial',
                'removed' => $fileResult['removed'],
                'kept' => $fileResult['kept'],
                'failed' => $fileResult['failed'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $fileResult['audit_warning'] = 'Baza je uklonjena i prva evidencija postoji. Evidencija fajlova nije upisana.';
        }

        return $fileResult;
    }

    protected function beforeCommit(): void
    {
    }

    private function assertSuperadmin(User $actor): void
    {
        $actor->loadMissing('role');
        if ($actor->role === null || $actor->role->name !== 'superadmin') {
            abort(403, 'Samo super administrator može ukloniti odobreni testni konkurs iz arhive.');
        }
    }

    private function assertConfirmation(int $competitionId, string $confirmation): void
    {
        $expected = self::CONFIRMATION_PREFIX.$competitionId;
        if (trim($confirmation) !== $expected) {
            throw new ArchivedTestCompetitionRemovalException(
                'Potvrda mora tačno glasiti: '.$expected.'.',
                422
            );
        }
    }

    private function find(int $competitionId): Competition
    {
        $competition = Competition::query()->find($competitionId);
        if ($competition === null) {
            throw new ArchivedTestCompetitionRemovalException('Konkurs sa tim ID nije pronađen.', 404);
        }

        return $competition;
    }

    private function findForUpdate(int $competitionId): Competition
    {
        $competition = Competition::query()->whereKey($competitionId)->lockForUpdate()->first();
        if ($competition === null) {
            throw new ArchivedTestCompetitionRemovalException('Konkurs sa tim ID nije pronađen.', 404);
        }

        return $competition;
    }

    private function ineligibilityReason(Competition $competition): ?string
    {
        if (! in_array($competition->type, ['omladinsko', 'zensko'], true)) {
            return 'Ovaj postupak važi samo za konkurs podrške preduzetništvu mladih ili ženskom preduzetništvu.';
        }

        if (! in_array($competition->status, ['closed', 'completed'], true)) {
            return 'Ukloniti se može samo konkurs koji je zatvoren ili završen.';
        }

        if (! $competition->is_test) {
            return 'Trajno uklanjanje je dozvoljeno samo za konkurs koji je izričito označen kao testni.';
        }

        return null;
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
     * @return array<string, int>
     */
    private function counts(int $competitionId, array $applicationIds): array
    {
        $counts = [
            'applications' => count($applicationIds),
        ];

        foreach ([
            'evaluation_scores' => 'application_id',
            'application_documents' => 'application_id',
            'business_plans' => 'application_id',
            'contracts' => 'application_id',
            'reports' => 'application_id',
            'application_scores' => 'application_id',
            'youth_equal_score_round_applications' => 'application_id',
        ] as $table => $column) {
            $counts[$table] = $this->countIn($table, $column, $applicationIds);
        }

        foreach ([
            'youth_decision_documents',
            'zensko_decision_proposal_snapshots',
            'competition_official_decision_copies',
            'competition_official_decision_lifecycle_events',
            'youth_equal_score_groups',
            'commission_sessions',
            'evaluation_criteria',
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
            'competition_publication_claims',
            'canonical_formal_decisions',
            'canonical_administrative_events',
            'canonical_secretariat_handoffs',
            'canonical_predlogs',
            'canonical_outcomes',
            'canonical_tie_resolutions',
            'canonical_final_rankings',
        ] as $table) {
            $counts[$table] = $this->countCompetition($table, $competitionId);
        }

        $counts['notices'] = 0;
        if (Schema::hasTable('notices')) {
            $counts['notices'] = DB::table('notices')
                ->where('source_type', 'competition_decision')
                ->where('source_id', $competitionId)
                ->count();
        }

        return $counts;
    }

    /**
     * @param  list<int>  $ids
     */
    private function countIn(string $table, string $column, array $ids): int
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->whereIn($column, $ids)->count();
    }

    private function countCompetition(string $table, int $competitionId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'competition_id')) {
            return 0;
        }

        return DB::table($table)->where('competition_id', $competitionId)->count();
    }

    /**
     * @param  list<int>  $applicationIds
     * @return list<array{kind: string, path: string, action: string}>
     */
    private function describeFiles(int $competitionId, array $applicationIds): array
    {
        $files = [];

        foreach ([
            'youth_decision_documents' => 'storage_path',
            'zensko_decision_proposal_snapshots' => 'storage_path',
            'competition_official_decision_copies' => 'storage_path',
        ] as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)->where('competition_id', $competitionId)->get(['id', $column]);
            foreach ($rows as $row) {
                $path = (string) $row->{$column};
                $files[] = [
                    'kind' => $table,
                    'path' => $path,
                    'action' => $this->decisionPathOwnedBy($path, $competitionId) && ! $this->referencedByAnotherCompetition($path, $competitionId, $applicationIds)
                        ? 'ukloniti'
                        : 'zadržati',
                ];
            }
        }

        if ($applicationIds !== [] && Schema::hasTable('application_documents')) {
            $rows = DB::table('application_documents')->whereIn('application_id', $applicationIds)->get();
            foreach ($rows as $row) {
                $path = (string) $row->file_path;
                $library = Schema::hasColumn('application_documents', 'user_document_id')
                    && $row->user_document_id !== null;
                $shared = $library || $this->referencedByAnotherCompetition($path, $competitionId, $applicationIds) || ! $this->isRelativeFilePath($path);
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
    private function deleteDependents(int $competitionId, array $applicationIds): void
    {
        $this->deleteWhereIn('youth_equal_score_round_applications', 'application_id', $applicationIds);

        $this->deleteChildIds(
            'canonical_tie_votes',
            'tie_resolution_id',
            'canonical_tie_resolutions',
            $competitionId
        );
        $this->deleteWhereCompetition('canonical_formal_decisions', $competitionId);
        $this->deleteWhereCompetition('canonical_administrative_events', $competitionId);
        $this->deleteWhereCompetition('canonical_secretariat_handoffs', $competitionId);
        $this->deleteWhereCompetition('canonical_predlogs', $competitionId);
        $this->deleteChildIds(
            'canonical_final_ranking_signatures',
            'final_ranking_id',
            'canonical_final_rankings',
            $competitionId
        );
        $this->deleteWhereCompetition('canonical_outcomes', $competitionId);
        $this->deleteWhereCompetition('canonical_tie_resolutions', $competitionId);
        $this->deleteWhereCompetition('canonical_final_rankings', $competitionId);

        foreach ([
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
            'competition_publication_claims',
            'competition_official_decision_lifecycle_events',
        ] as $table) {
            $this->deleteWhereCompetition($table, $competitionId);
        }

        if (Schema::hasTable('notices')) {
            DB::table('notices')
                ->where('source_type', 'competition_decision')
                ->where('source_id', $competitionId)
                ->delete();
        }

        $this->deleteWhereCompetition('competition_official_decision_copies', $competitionId);

        if (Schema::hasColumn('competitions', 'zensko_decision_proposal_snapshot_id')) {
            DB::table('competitions')
                ->where('id', $competitionId)
                ->update(['zensko_decision_proposal_snapshot_id' => null]);
        }

        $this->deleteWhereCompetition('zensko_decision_proposal_snapshots', $competitionId);
        $this->deleteWhereCompetition('youth_decision_documents', $competitionId);
    }

    /**
     * @param  list<int>  $ids
     */
    private function deleteWhereIn(string $table, string $column, array $ids): void
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table($table)->whereIn($column, $chunk)->delete();
        }
    }

    private function deleteWhereCompetition(string $table, int $competitionId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'competition_id')) {
            return;
        }

        DB::table($table)->where('competition_id', $competitionId)->delete();
    }

    private function deleteChildIds(string $childTable, string $childColumn, string $parentTable, int $competitionId): void
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasTable($parentTable)) {
            return;
        }

        $parentIds = DB::table($parentTable)->where('competition_id', $competitionId)->pluck('id');
        if ($parentIds->isEmpty()) {
            return;
        }

        DB::table($childTable)->whereIn($childColumn, $parentIds->all())->delete();
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
                $disk = Storage::disk('local');
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
                if ($disk->exists($path)) {
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
            'youth_decision_documents' => 'storage_path',
            'zensko_decision_proposal_snapshots' => 'storage_path',
            'competition_official_decision_copies' => 'storage_path',
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
    private function referencedByAnotherCompetition(string $path, int $competitionId, array $applicationIds): bool
    {
        if (Schema::hasTable('user_documents') && Schema::hasColumn('user_documents', 'file_path')) {
            if (DB::table('user_documents')->where('file_path', $path)->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('application_documents')) {
            $query = DB::table('application_documents')->where('file_path', $path);
            if ($applicationIds !== []) {
                $query->whereNotIn('application_id', $applicationIds);
            }
            if ($query->exists()) {
                return true;
            }
        }

        foreach ([
            'youth_decision_documents',
            'zensko_decision_proposal_snapshots',
            'competition_official_decision_copies',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $other = DB::table($table)
                ->where('storage_path', $path)
                ->where('competition_id', '!=', $competitionId)
                ->exists();
            if ($other) {
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
            'youth-decision-documents/'.$competitionId.'/',
            'zensko-decision-proposals/'.$competitionId.'/',
            'competitions/'.$competitionId.'/official-decisions/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
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
     * @param  array<string, mixed>  $details
     */
    private function record(User $actor, array $details): void
    {
        DB::table('logs')->insert([
            'user_id' => $actor->id,
            'action' => 'archived_test_competition_removal',
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
