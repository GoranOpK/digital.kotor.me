<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\MmspDecisionDocument;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Generiše MMSP Word odluku i trajno je čuva.
 * Ne upisuje datume, iznose ni zaključke i ne zatvara konkurs.
 */
class MmspDecisionGenerator
{
    public const GENERATED_MESSAGE = 'Odluka je uspješno generisana i sačuvana.';

    public function __construct(
        private readonly MmspDecisionProposalGate $gate,
        private readonly MmspDecisionProposalBuilder $builder,
        private readonly MmspDecisionDocxWriter $writer,
        private readonly MmspDecisionDocumentStore $store,
        private readonly MmspDecisionRankingIdentity $ranking,
    ) {}

    /**
     * @param  array{first_session_date: string, second_session_date: string, third_session_date: string}  $dates
     */
    public function generate(Competition $competition, User $actor, array $dates, Carbon $generatedAt): MmspDecisionDocument
    {
        $temporaryPath = null;
        $storedPath = null;

        try {
            $document = DB::transaction(function () use ($competition, $actor, $dates, $generatedAt, &$temporaryPath, &$storedPath) {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if ($locked === null) {
                    abort(404);
                }

                $reason = $this->gate->denyReason($locked, $actor);
                if ($reason !== null) {
                    abort(403, $reason);
                }

                $before = $this->ranking->rankingSha256($locked);
                $proposal = $this->builder->build($locked, $dates, $generatedAt);
                $temporaryPath = $this->writer->write($proposal);
                $after = $this->ranking->rankingSha256($locked);
                if (! hash_equals($before, $after)) {
                    throw ValidationException::withMessages([
                        'decision' => 'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
                    ]);
                }

                $document = $this->store->store($locked, $actor, $temporaryPath);
                $storedPath = $document->storage_path;
                if (! hash_equals($before, $document->ranking_sha256)) {
                    throw ValidationException::withMessages([
                        'decision' => 'Rang-lista je izmijenjena tokom generisanja. Odluka nije sačuvana.',
                    ]);
                }

                return $document;
            });
        } catch (\Throwable $exception) {
            $this->removeTemporary($temporaryPath);
            $this->removeUnreferenced($storedPath);
            throw $exception;
        }

        $this->removeTemporary($temporaryPath);

        return $document;
    }

    private function removeTemporary(?string $temporaryPath): void
    {
        if (! is_string($temporaryPath) || $temporaryPath === '' || is_link($temporaryPath) || ! is_file($temporaryPath)) {
            return;
        }

        @unlink($temporaryPath);
    }

    private function removeUnreferenced(?string $storedPath): void
    {
        if (! is_string($storedPath) || preg_match(
            '#^'.MmspDecisionDocumentStore::DIRECTORY.'/([0-9]+)/([0-9A-HJKMNP-TV-Z]{26})\.docx$#',
            $storedPath
        ) !== 1) {
            return;
        }

        try {
            if (MmspDecisionDocument::query()->where('storage_path', $storedPath)->exists()) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $absolute = Storage::disk('local')->path($storedPath);
        if (! is_file($absolute) || is_link($absolute)) {
            return;
        }

        $real = realpath($absolute);
        $root = realpath(storage_path('app/private').DIRECTORY_SEPARATOR.MmspDecisionDocumentStore::DIRECTORY);
        if ($real === false || $root === false) {
            return;
        }

        $realPath = strtolower(str_replace('\\', '/', $real));
        $rootPath = rtrim(strtolower(str_replace('\\', '/', $root)), '/');
        if ($realPath === $rootPath || ! str_starts_with($realPath, $rootPath.'/')) {
            return;
        }

        @unlink($real);
    }
}
