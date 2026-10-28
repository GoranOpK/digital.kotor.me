<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\ZenskoDecisionProposalSnapshot;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Čita trajno sačuvan HTML Predlog odluke i provjerava da pripada konkursu.
 * Ne generiše dokument i ne upisuje snimak.
 */
class ZenskoDecisionProposalHtmlReader
{
    public const LEGACY_NOTICE = 'Ovo nije trajno sačuvan Predlog odluke. Prikaz je izrađen iz trenutnih podataka.';

    public function read(Competition $competition): string
    {
        $snapshotId = $competition->zensko_decision_proposal_snapshot_id;
        if ($snapshotId === null) {
            throw new ZenskoDecisionProposalReadException();
        }

        $snapshot = ZenskoDecisionProposalSnapshot::query()->find($snapshotId);
        if (! $snapshot instanceof ZenskoDecisionProposalSnapshot) {
            throw new ZenskoDecisionProposalReadException();
        }

        if ((int) $snapshot->competition_id !== (int) $competition->id) {
            throw new ZenskoDecisionProposalReadException();
        }

        $path = $snapshot->storage_path;
        if (! is_string($path) || $path === '') {
            throw new ZenskoDecisionProposalReadException();
        }

        try {
            $bytes = Storage::disk('local')->get($path);
        } catch (Throwable) {
            throw new ZenskoDecisionProposalReadException();
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new ZenskoDecisionProposalReadException();
        }

        $expected = strtolower(trim((string) $snapshot->content_sha256));
        $actual = hash('sha256', $bytes);
        if (! hash_equals($expected, $actual)) {
            throw new ZenskoDecisionProposalReadException();
        }

        return $bytes;
    }
}
