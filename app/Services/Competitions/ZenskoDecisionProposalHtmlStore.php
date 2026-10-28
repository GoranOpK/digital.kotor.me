<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Priprema i čuva HTML Predlog odluke za žensko preduzetništvo.
 * Ne zatvara konkurs i ne upisuje red snimka.
 */
class ZenskoDecisionProposalHtmlStore
{
    public function __construct(
        private readonly CompetitionDecisionDocumentBuilder $documents,
    ) {}

    public function store(Competition $competition): ZenskoDecisionProposalStoredFile
    {
        if ($competition->type !== 'zensko') {
            throw new ZenskoDecisionProposalStoreException(
                'Snimak Predloga odluke može da se sačuva samo za konkurs ženskog preduzetništva.'
            );
        }

        $html = $this->embedLocalMark(
            view('competitions.partials.decision-document', $this->documents->build($competition))->render()
        );
        $this->assertDocumentIsSelfContained($html);

        $hash = hash('sha256', $html);
        $documentId = $this->newDocumentId();
        $directory = 'zensko-decision-proposals/'.((int) $competition->id);
        $finalPath = $directory.'/'.$documentId.'.html';
        $tempPath = $finalPath.'.partial';
        $disk = $this->disk();
        $tempWritten = false;

        try {
            $written = $disk->put($tempPath, $html);
            if ($written !== true) {
                throw new ZenskoDecisionProposalStoreException(
                    'Privremeni fajl Predloga odluke nije upisan.'
                );
            }
            $tempWritten = true;

            if ($disk->exists($finalPath)) {
                throw new ZenskoDecisionProposalStoreException(
                    'Konačni fajl Predloga odluke već postoji i neće biti prepisan.'
                );
            }

            $this->publishExclusively($disk, $tempPath, $finalPath, $hash);

            $stored = $this->readFinalBytes($disk, $finalPath);
            if (! hash_equals($hash, hash('sha256', $stored))) {
                $disk->delete($finalPath);
                throw new ZenskoDecisionProposalStoreException(
                    'Provjera integriteta sačuvanog Predloga odluke nije uspjela.'
                );
            }
        } catch (ZenskoDecisionProposalStoreException $exception) {
            if ($tempWritten) {
                $disk->delete($tempPath);
            }

            throw $exception;
        }

        $disk->delete($tempPath);

        return new ZenskoDecisionProposalStoredFile(
            (int) $competition->id,
            $finalPath,
            $hash,
        );
    }

    protected function newDocumentId(): string
    {
        $documentId = (string) Str::ulid();
        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $documentId)) {
            throw new ZenskoDecisionProposalStoreException(
                'Privremeni fajl Predloga odluke nije upisan.'
            );
        }

        return $documentId;
    }

    protected function readFinalBytes(FilesystemAdapter $disk, string $path): string
    {
        $bytes = $disk->get($path);
        if (! is_string($bytes)) {
            throw new ZenskoDecisionProposalStoreException(
                'Provjera integriteta sačuvanog Predloga odluke nije uspjela.'
            );
        }

        return $bytes;
    }

    protected function disk(): FilesystemAdapter
    {
        $disk = Storage::disk('local');
        if (! $disk instanceof FilesystemAdapter) {
            throw new ZenskoDecisionProposalStoreException(
                'Privremeni fajl Predloga odluke nije upisan.'
            );
        }

        return $disk;
    }

    /**
     * Flysystem local move() calls PHP rename(), which replaces an existing
     * destination on POSIX. Exclusive create fails instead of replacing it.
     */
    private function publishExclusively(
        FilesystemAdapter $disk,
        string $tempPath,
        string $finalPath,
        string $expectedHash,
    ): void {
        $tempBytes = $disk->get($tempPath);
        if (! is_string($tempBytes) || ! hash_equals($expectedHash, hash('sha256', $tempBytes))) {
            throw new ZenskoDecisionProposalStoreException(
                'Privremeni fajl Predloga odluke nije upisan.'
            );
        }

        $absolute = $disk->path($finalPath);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new ZenskoDecisionProposalStoreException(
                'Konačni fajl Predloga odluke nije mogao da se premjesti bez prepisivanja.'
            );
        }

        $handle = @fopen($absolute, 'xb');
        if ($handle === false) {
            throw new ZenskoDecisionProposalStoreException(
                'Konačni fajl Predloga odluke nije mogao da se premjesti bez prepisivanja.'
            );
        }

        try {
            $written = fwrite($handle, $tempBytes);
            if ($written !== strlen($tempBytes)) {
                throw new ZenskoDecisionProposalStoreException(
                    'Konačni fajl Predloga odluke nije mogao da se premjesti bez prepisivanja.'
                );
            }
        } catch (Throwable $exception) {
            fclose($handle);
            if (is_file($absolute)) {
                @unlink($absolute);
            }

            throw $exception;
        }

        fclose($handle);
    }

    private function embedLocalMark(string $html): string
    {
        foreach ([
            'img/grb-kotor.png' => public_path('img/grb-kotor.png'),
            'img/logo.png' => public_path('img/logo.png'),
        ] as $relative => $absolute) {
            $url = asset($relative);
            if (! str_contains($html, $url)) {
                continue;
            }

            if (! is_file($absolute)) {
                throw new ZenskoDecisionProposalStoreException(
                    'Predlog odluke ne može da se sačuva jer nedostaje lokalni grb ili logotip.'
                );
            }

            $binary = file_get_contents($absolute);
            if (! is_string($binary) || $binary === '') {
                throw new ZenskoDecisionProposalStoreException(
                    'Predlog odluke ne može da se sačuva jer nedostaje lokalni grb ili logotip.'
                );
            }

            $dataUri = 'data:'.$this->mimeFromImageBytes($binary).';base64,'.base64_encode($binary);
            $embedded = str_replace($url, $dataUri, $html);
            if (! str_contains($embedded, $dataUri) || str_contains($embedded, $url)) {
                throw new ZenskoDecisionProposalStoreException(
                    'Predlog odluke ne može da se sačuva jer nedostaje lokalni grb ili logotip.'
                );
            }

            return $embedded;
        }

        throw new ZenskoDecisionProposalStoreException(
            'Predlog odluke ne može da se sačuva jer nedostaje lokalni grb ili logotip.'
        );
    }

    private function mimeFromImageBytes(string $binary): string
    {
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'image/gif';
        }

        throw new ZenskoDecisionProposalStoreException(
            'Predlog odluke ne može da se sačuva jer lokalni grb ili logotip nije podržana slika.'
        );
    }

    private function assertDocumentIsSelfContained(string $html): void
    {
        if (preg_match('/<img\b[^>]*\bsrc\s*=\s*["\'](?!data:)/i', $html)) {
            throw new ZenskoDecisionProposalStoreException(
                'Predlog odluke ne može da se sačuva jer sadrži udaljeni resurs.'
            );
        }

        if (preg_match('/(?:src|href)\s*=\s*["\']\s*(?:https?:)?\/\//i', $html)) {
            throw new ZenskoDecisionProposalStoreException(
                'Predlog odluke ne može da se sačuva jer sadrži udaljeni resurs.'
            );
        }

        if (preg_match('/url\(\s*["\']?(?!data:)/i', $html)) {
            throw new ZenskoDecisionProposalStoreException(
                'Predlog odluke ne može da se sačuva jer sadrži udaljeni resurs.'
            );
        }
    }
}
