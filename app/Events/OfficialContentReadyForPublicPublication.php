<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfficialContentReadyForPublicPublication
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $title,
        public readonly ?string $short_description,
        public readonly string $source_type,
        public readonly int $source_id,
        public readonly string $content_delivery,
        public readonly ?int $supersedes_notice_id = null,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     short_description: string|null,
     *     source_type: string,
     *     source_id: int,
     *     content_delivery: string,
     *     supersedes_notice_id: int|null
     * }
     */
    public function toPublicationPayload(): array
    {
        return [
            'title' => $this->title,
            'short_description' => $this->short_description,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'content_delivery' => $this->content_delivery,
            'supersedes_notice_id' => $this->supersedes_notice_id,
        ];
    }
}
