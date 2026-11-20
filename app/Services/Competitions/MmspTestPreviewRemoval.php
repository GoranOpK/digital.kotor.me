<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\CompetitionPublicationClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Briše samo MMSP nacrt sa uključenim testnim pregledom i podatke koji pripadaju tom nacrtu.
 * Ne briše komisiju, korisnike, zvanične konkurse ni podatke drugih modula.
 */
class MmspTestPreviewRemoval
{
    public const CONFIRMATION_PREFIX = 'UKLONI TEST ';

    public function execute(User $actor, Competition $competition, string $confirmation): void
    {
        $this->assertAdministrator($actor);
        $this->assertConfirmation($competition, $confirmation);

        DB::transaction(function () use ($actor, $competition): void {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($locked === null) {
                throw new MmspTestPreviewRemovalException('Testni konkurs nije pronađen.', 404);
            }

            $reason = $this->ineligibilityReason($locked);
            if ($reason !== null) {
                throw new MmspTestPreviewRemovalException($reason);
            }

            $applicationIds = DB::table('applications')
                ->where('competition_id', $locked->id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $this->deleteExclusiveDocumentFiles($applicationIds);
            $this->deleteRestrictingApplicationRows($applicationIds);
            $this->deleteWhereIn('applications', 'id', $applicationIds);
            $this->deleteCompetitionRows($locked->id);

            $commissionId = $locked->commission_id;
            $locked->delete();

            if ($commissionId !== null && ! DB::table('commissions')->where('id', $commissionId)->exists()) {
                throw new MmspTestPreviewRemovalException('Uklanjanje je zaustavljeno jer bi obrisalo komisiju.');
            }

            if (Schema::hasTable('logs')) {
                DB::table('logs')->insert([
                    'user_id' => $actor->id,
                    'action' => 'mmsp_test_preview_removal',
                    'details' => json_encode([
                        'competition_id' => $competition->id,
                        'applications' => count($applicationIds),
                        'commission_id' => $commissionId,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function ineligibilityReason(Competition $competition): ?string
    {
        if (! $competition->isCanonicalTestPreview()) {
            return 'Ovim postupkom može da se ukloni samo MMSP nacrt sa uključenim testnim pregledom.';
        }

        if (CompetitionPublicationClaim::query()->where('competition_id', $competition->id)->exists()) {
            return 'Testni nacrt koji ima evidenciju zvanične objave ne može da se ukloni ovim postupkom.';
        }

        return null;
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
     * @param  list<int>  $applicationIds
     */
    private function deleteExclusiveDocumentFiles(array $applicationIds): void
    {
        if ($applicationIds === [] || ! Schema::hasTable('application_documents')) {
            return;
        }

        $rows = DB::table('application_documents')->whereIn('application_id', $applicationIds)->get();
        foreach ($rows as $row) {
            $path = (string) ($row->file_path ?? '');
            if ($path === '' || str_contains($path, '..') || ($row->user_document_id ?? null) !== null) {
                continue;
            }

            $shared = DB::table('application_documents')
                ->where('file_path', $path)
                ->whereNotIn('application_id', $applicationIds)
                ->exists();
            if ($shared || (Schema::hasTable('user_documents') && DB::table('user_documents')->where('file_path', $path)->exists())) {
                continue;
            }

            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @param  list<int>  $applicationIds
     */
    private function deleteRestrictingApplicationRows(array $applicationIds): void
    {
        if ($applicationIds === []) {
            return;
        }

        foreach ([
            'application_complaints',
            'application_completeness_assessments',
            'final_application_claims',
            'canonical_outcomes',
        ] as $table) {
            $this->deleteWhereIn($table, 'application_id', $applicationIds);
        }
    }

    private function deleteCompetitionRows(int $competitionId): void
    {
        foreach ([
            'canonical_tie_votes' => 'tie_resolution_id',
        ] as $child => $column) {
            if (! Schema::hasTable($child) || ! Schema::hasTable('canonical_tie_resolutions')) {
                continue;
            }
            $parentIds = DB::table('canonical_tie_resolutions')->where('competition_id', $competitionId)->pluck('id');
            if ($parentIds->isNotEmpty()) {
                DB::table($child)->whereIn($column, $parentIds->all())->delete();
            }
        }

        if (Schema::hasTable('canonical_final_ranking_signatures') && Schema::hasTable('canonical_final_rankings')) {
            $rankingIds = DB::table('canonical_final_rankings')->where('competition_id', $competitionId)->pluck('id');
            if ($rankingIds->isNotEmpty()) {
                DB::table('canonical_final_ranking_signatures')->whereIn('final_ranking_id', $rankingIds->all())->delete();
            }
        }

        foreach ([
            'canonical_formal_decisions',
            'canonical_administrative_events',
            'canonical_secretariat_handoffs',
            'canonical_predlogs',
            'canonical_outcomes',
            'canonical_tie_resolutions',
            'canonical_final_rankings',
            'mmsp_decision_documents',
            'competition_publication_claims',
            'up_number',
        ] as $table) {
            $this->deleteWhereCompetition($table, $competitionId);
        }
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
}
