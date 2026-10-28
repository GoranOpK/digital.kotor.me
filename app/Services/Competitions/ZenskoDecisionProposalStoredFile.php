<?php

namespace App\Services\Competitions;

final class ZenskoDecisionProposalStoredFile
{
    public function __construct(
        public readonly int $competitionId,
        public readonly string $storagePath,
        public readonly string $contentSha256,
    ) {}

    /**
     * @return array{competition_id: int, storage_path: string, content_sha256: string}
     */
    public function toArray(): array
    {
        return [
            'competition_id' => $this->competitionId,
            'storage_path' => $this->storagePath,
            'content_sha256' => $this->contentSha256,
        ];
    }
}
