<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Kontrolisano označavanje jednog već objavljenog PM, ŽP ili MMSP konkursa kao testnog.
 * Ne zaključuje oznaku iz naziva, datuma ili ID-a, ne briše konkurs i ne dira is_test_preview.
 */
class ArchivedTestCompetitionMarking
{
    public const CONFIRMATION_PREFIX = 'OZNACI ';

    /**
     * @return array{competition: Competition, eligible: bool, reason: ?string}
     */
    public function preview(User $actor, int $competitionId): array
    {
        $this->assertSuperadmin($actor);
        $competition = $this->find($competitionId);

        return [
            'competition' => $competition,
            'eligible' => $this->ineligibilityReason($competition) === null,
            'reason' => $this->ineligibilityReason($competition),
        ];
    }

    public function execute(User $actor, int $competitionId, string $confirmation): void
    {
        $this->assertSuperadmin($actor);

        try {
            $this->assertConfirmation($competitionId, $confirmation);
            DB::transaction(function () use ($actor, $competitionId): void {
                $competition = Competition::query()->whereKey($competitionId)->lockForUpdate()->first();
                if ($competition === null) {
                    throw new ArchivedTestCompetitionRemovalException('Konkurs sa tim ID nije pronađen.', 404);
                }

                $reason = $this->ineligibilityReason($competition);
                if ($reason !== null) {
                    throw new ArchivedTestCompetitionRemovalException($reason, 422);
                }

                $updated = DB::table('competitions')
                    ->where('id', $competitionId)
                    ->where('is_test', false)
                    ->update([
                        'is_test' => true,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new ArchivedTestCompetitionRemovalException(
                        'Oznaka testnog konkursa nije upisana.',
                        422
                    );
                }

                DB::table('logs')->insert([
                    'user_id' => $actor->id,
                    'action' => 'archived_test_competition_marked',
                    'details' => json_encode([
                        'competition_id' => $competitionId,
                        'title' => (string) $competition->title,
                        'type' => (string) $competition->type,
                        'status' => (string) $competition->status,
                        'result' => 'marked',
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            $this->recordSafely($actor, [
                'competition_id' => $competitionId,
                'result' => 'refused',
                'message' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    private function assertSuperadmin(User $actor): void
    {
        $actor->loadMissing('role');
        if ($actor->role === null || $actor->role->name !== 'superadmin') {
            abort(403, 'Samo super administrator može označiti raniji konkurs kao testni.');
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

    private function ineligibilityReason(Competition $competition): ?string
    {
        if (! in_array($competition->type, self::supportedTypes(), true)) {
            return 'Oznaka se ovim postupkom može dodati samo konkursu podrške preduzetništvu mladih, ženskom preduzetništvu ili mikro, malim i srednjim preduzećima.';
        }

        if ($competition->status === 'draft') {
            return 'Nacrt označava super administrator na obrascu konkursa. Ovaj postupak je za konkurs koji je već objavljen.';
        }

        if (! in_array($competition->status, ['published', 'closed', 'completed'], true)) {
            return 'Ovim postupkom može se označiti samo objavljen, zatvoren ili završen konkurs.';
        }

        if ($competition->is_test) {
            return 'Konkurs je već označen kao testni.';
        }

        if (! app(ApprovedHistoricalTestCompetitionIds::class)->allows((int) $competition->id)) {
            return 'Ovaj konkurs nije na spisku odobrenom za naknadno označavanje.';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function supportedTypes(): array
    {
        return [
            CompetitionProfileResolver::TYPE_YOUTH,
            CompetitionProfileResolver::TYPE_WOMEN,
            CompetitionProfileResolver::TYPE_CANONICAL,
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordSafely(User $actor, array $details): void
    {
        try {
            DB::table('logs')->insert([
                'user_id' => $actor->id,
                'action' => 'archived_test_competition_marked',
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
