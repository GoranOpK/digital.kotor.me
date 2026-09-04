<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfficialContentPublicMetadataUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $notice_id,
        public readonly string $title,
        public readonly string $public_display_date,
    ) {}
}
