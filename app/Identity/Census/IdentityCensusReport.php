<?php

namespace App\Identity\Census;

/**
 * Aggregate + row diagnostic census result. Report-only.
 */
final class IdentityCensusReport
{
    /**
     * @param  list<IdentityCensusRow>  $rows
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $aggregates
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $metadata,
        public readonly array $aggregates,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
            'aggregates' => $this->aggregates,
            'rows' => array_map(
                static fn (IdentityCensusRow $row): array => $row->toArray(),
                $this->rows
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function aggregateDocument(): array
    {
        return [
            'metadata' => $this->metadata,
            'aggregates' => $this->aggregates,
        ];
    }
}
