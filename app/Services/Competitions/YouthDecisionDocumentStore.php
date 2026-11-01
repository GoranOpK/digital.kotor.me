<?php

namespace App\Services\Competitions;

use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Trajno čuva već generisani PM Word dokument.
 * Ne generiše dokument i ne mijenja zaključke Komisije.
 */
class YouthDecisionDocumentStore
{
    public const DISK_UNAVAILABLE = 'Privatno skladište PM odluke nije dostupno.';

    public const UNREADABLE = 'Ulazni Word dokument nije dostupan za čitanje.';

    public const EMPTY_DOCUMENT = 'Ulazni Word dokument nema sadržaj.';

    public const NOT_YOUTH = 'Trajno čuvanje PM Word odluke važi samo za omladinski konkurs.';

    public const NOT_CHAIRMAN = 'Samo aktivni predsjednik Komisije može trajno sačuvati PM odluku.';

    public const WRITE_FAILED = 'PM Word odluka nije upisana u privatno skladište.';

    public const NOT_OVERWRITTEN = 'Postojeći fajl PM odluke neće biti prepisan.';

    public const HASH_MISMATCH = 'Provjera integriteta sačuvane PM odluke nije uspjela.';

    public const NOT_STORED = 'PM Word odluka nije trajno sačuvana.';

    public const CLOSE_BLOCKED = 'Zatvaranje konkursa nije dozvoljeno dok Word odluka nije uspješno generisana i trajno sačuvana.';

    public function __construct(
        private readonly YouthDecisionRankingIdentity $ranking,
    ) {}

    public function store(Competition $competition, User $actor, string $sourcePath): YouthDecisionDocument
    {
        $this->assertPrivateLocalDisk();
        $bytes = $this->readSource($sourcePath);
        $contentHash = hash('sha256', $bytes);
        $relativePath = null;

        try {
            return DB::transaction(function () use ($competition, $actor, $bytes, $contentHash, &$relativePath): YouthDecisionDocument {
                $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
                if ($locked === null || ! $locked->isOmladinskoProfile()) {
                    throw new YouthDecisionDocumentStoreException(self::NOT_YOUTH);
                }

                $this->assertActiveChairman($locked, $actor);
                $rankingHash = $this->ranking->rankingSha256($locked);
                $relativePath = $this->relativePath($locked);
                $this->writeExclusively($relativePath, $bytes);
                $stored = $this->readStoredBytes($relativePath);
                if (! hash_equals($contentHash, hash('sha256', $stored))) {
                    throw new YouthDecisionDocumentStoreException(self::HASH_MISMATCH);
                }

                return $this->persist($locked, $actor, $relativePath, $contentHash, $rankingHash);
            });
        } catch (Throwable $exception) {
            $this->discardUnreferenced($relativePath);
            if ($exception instanceof YouthDecisionDocumentStoreException) {
                throw $exception;
            }

            throw new YouthDecisionDocumentStoreException(self::NOT_STORED, 0, $exception);
        }
    }

    /**
     * Vraća stvarnu putanju već sačuvanog dokumenta nakon provjere putanje i SHA-256.
     * Ne generiše dokument i ne mijenja red ni fajl.
     */
    public function verifiedDownloadPath(Competition $competition, YouthDecisionDocument $document): string
    {
        $this->assertPrivateLocalDisk();
        if ((int) $document->competition_id !== (int) $competition->id) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }

        $relative = (string) $document->storage_path;
        if (preg_match(
            '#^youth-decision-documents/([0-9]+)/([0-9A-HJKMNP-TV-Z]{26})\.docx$#',
            $relative,
            $matches
        ) !== 1 || (int) $matches[1] !== (int) $competition->id) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }

        $absolute = $this->privateDisk()->path($relative);
        if (is_link($absolute) || ! is_file($absolute) || ! is_readable($absolute)) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }

        $real = realpath($absolute);
        $documentsRoot = realpath($this->privateRoot().DIRECTORY_SEPARATOR.'youth-decision-documents');
        $expected = $this->normalizePath($this->privateRoot().'/'.$relative);
        if (
            $real === false
            || $documentsRoot === false
            || $this->samePath($real, $documentsRoot)
            || ! $this->isInside($real, $documentsRoot)
            || $this->normalizePath($real) !== $expected
        ) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }

        $storedHash = strtolower((string) $document->content_sha256);
        $actualHash = hash_file('sha256', $real);
        if (! is_string($actualHash) || ! preg_match('/^[0-9a-f]{64}$/', $storedHash)) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }
        if (! hash_equals($storedHash, $actualHash)) {
            throw new YouthDecisionDocumentStoreException(self::HASH_MISMATCH);
        }

        return $real;
    }

    /**
     * Posljednji ispravan dokument: generated_at opadajuće, zatim id opadajuće.
     * Neispravan noviji zapis se preskače. Neispravni redovi i fajlovi ostaju netaknuti.
     * Ne generiše dokument.
     */
    public function latestValidDocument(Competition $competition): ?YouthDecisionDocument
    {
        $this->assertPrivateLocalDisk();
        if (! $competition->isOmladinskoProfile()) {
            return null;
        }

        $expectedRanking = strtolower($this->ranking->rankingSha256($competition));
        if (! preg_match('/^[0-9a-f]{64}$/', $expectedRanking)) {
            throw new YouthDecisionDocumentStoreException(self::NOT_STORED);
        }

        $documents = YouthDecisionDocument::query()
            ->where('competition_id', $competition->id)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();

        $integrityFailed = false;
        foreach ($documents as $document) {
            if ((int) $document->competition_id !== (int) $competition->id) {
                continue;
            }

            $storedRanking = strtolower((string) $document->ranking_sha256);
            if (! preg_match('/^[0-9a-f]{64}$/', $storedRanking) || ! hash_equals($expectedRanking, $storedRanking)) {
                continue;
            }

            try {
                $this->verifiedDownloadPath($competition, $document);
            } catch (YouthDecisionDocumentStoreException $exception) {
                if ($exception->getMessage() === self::HASH_MISMATCH) {
                    $integrityFailed = true;

                    continue;
                }
                if ($exception->getMessage() === self::NOT_STORED) {
                    continue;
                }

                throw $exception;
            }

            return $document;
        }

        if ($integrityFailed) {
            throw new YouthDecisionDocumentStoreException(self::HASH_MISMATCH);
        }

        return null;
    }

    protected function relativePath(Competition $competition): string
    {
        return 'youth-decision-documents/'.((int) $competition->id).'/'.$this->nextDocumentId().'.docx';
    }

    protected function nextDocumentId(): string
    {
        $documentId = (string) Str::ulid();
        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $documentId)) {
            throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
        }

        return $documentId;
    }

    protected function writeExclusively(string $relativePath, string $bytes): void
    {
        $this->assertOwnedPath($relativePath);
        $disk = $this->privateDisk();
        $absolute = $disk->path($relativePath);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
        }

        $realDirectory = realpath($directory);
        if ($realDirectory === false || ! $this->isInside($realDirectory, $this->privateRoot().DIRECTORY_SEPARATOR.'youth-decision-documents')) {
            throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
        }

        $handle = @fopen($absolute, 'xb');
        if ($handle === false) {
            throw new YouthDecisionDocumentStoreException(
                is_file($absolute) ? self::NOT_OVERWRITTEN : self::WRITE_FAILED
            );
        }

        try {
            $written = fwrite($handle, $bytes);
            if ($written !== strlen($bytes)) {
                throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
            }
        } catch (Throwable $exception) {
            fclose($handle);
            $this->unlinkOwnedFile($absolute, $relativePath);
            throw $exception;
        }

        fclose($handle);
    }

    protected function readStoredBytes(string $relativePath): string
    {
        $bytes = $this->privateDisk()->get($relativePath);
        if (! is_string($bytes)) {
            throw new YouthDecisionDocumentStoreException(self::HASH_MISMATCH);
        }

        return $bytes;
    }

    protected function persist(
        Competition $competition,
        User $actor,
        string $relativePath,
        string $contentHash,
        string $rankingHash,
    ): YouthDecisionDocument {
        return YouthDecisionDocument::query()->create([
            'competition_id' => $competition->id,
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'storage_path' => $relativePath,
            'content_sha256' => $contentHash,
            'ranking_sha256' => $rankingHash,
        ]);
    }

    private function readSource(string $sourcePath): string
    {
        if ($sourcePath === '' || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new YouthDecisionDocumentStoreException(self::UNREADABLE);
        }

        $bytes = file_get_contents($sourcePath);
        if (! is_string($bytes)) {
            throw new YouthDecisionDocumentStoreException(self::UNREADABLE);
        }
        if ($bytes === '') {
            throw new YouthDecisionDocumentStoreException(self::EMPTY_DOCUMENT);
        }

        return $bytes;
    }

    private function assertActiveChairman(Competition $competition, User $actor): void
    {
        if (! $competition->commission_id) {
            throw new YouthDecisionDocumentStoreException(self::NOT_CHAIRMAN);
        }

        $member = CommissionMember::activeForCommission((int) $actor->id, (int) $competition->commission_id);
        if ($member === null || $member->position !== 'predsjednik' || $member->status !== 'active') {
            throw new YouthDecisionDocumentStoreException(self::NOT_CHAIRMAN);
        }
    }

    private function assertPrivateLocalDisk(): void
    {
        $this->privateRoot();
    }

    private function privateDisk(): FilesystemAdapter
    {
        $disk = Storage::disk('local');
        if (! $disk instanceof FilesystemAdapter) {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }

        return $disk;
    }

    private function privateRoot(): string
    {
        $configured = config('filesystems.disks.local');
        if (! is_array($configured) || ($configured['driver'] ?? null) !== 'local') {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }
        if (is_string($configured['url'] ?? null) && $configured['url'] !== '') {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }
        if (($configured['visibility'] ?? null) === 'public') {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }

        $root = realpath($this->privateDisk()->path(''));
        $private = realpath(storage_path('app/private'));
        if ($root === false || $private === false || ! $this->samePath($root, $private)) {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }

        $public = realpath(storage_path('app/public'));
        if ($public !== false && ($this->samePath($root, $public) || $this->isInside($root, $public))) {
            throw new YouthDecisionDocumentStoreException(self::DISK_UNAVAILABLE);
        }

        return $root;
    }

    private function discardUnreferenced(?string $relativePath): void
    {
        if ($relativePath === null || ! $this->isOwnedDocumentPath($relativePath)) {
            return;
        }

        try {
            if (YouthDecisionDocument::query()->where('storage_path', $relativePath)->exists()) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $absolute = $this->privateDisk()->path($relativePath);
        $this->unlinkOwnedFile($absolute, $relativePath);
    }

    private function unlinkOwnedFile(string $absolute, string $relativePath): void
    {
        if (! $this->isOwnedDocumentPath($relativePath) || ! is_file($absolute)) {
            return;
        }

        try {
            if (YouthDecisionDocument::query()->where('storage_path', $relativePath)->exists()) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $real = realpath($absolute);
        if ($real === false) {
            return;
        }

        try {
            $documentsRoot = $this->privateRoot().DIRECTORY_SEPARATOR.'youth-decision-documents';
        } catch (Throwable) {
            return;
        }

        if (! $this->isInside($real, $documentsRoot)) {
            return;
        }

        @unlink($real);
    }

    private function assertOwnedPath(string $relativePath): void
    {
        if (! $this->isOwnedDocumentPath($relativePath)) {
            throw new YouthDecisionDocumentStoreException(self::WRITE_FAILED);
        }
    }

    private function isOwnedDocumentPath(string $relativePath): bool
    {
        return preg_match(
            '#^youth-decision-documents/[0-9]+/[0-9A-HJKMNP-TV-Z]{26}\.docx$#',
            $relativePath
        ) === 1;
    }

    private function samePath(string $left, string $right): bool
    {
        return $this->normalizePath($left) === $this->normalizePath($right);
    }

    private function isInside(string $path, string $root): bool
    {
        $path = $this->normalizePath($path);
        $root = rtrim($this->normalizePath($root), '/');

        return $path === $root || str_starts_with($path, $root.'/');
    }

    private function normalizePath(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }
}
