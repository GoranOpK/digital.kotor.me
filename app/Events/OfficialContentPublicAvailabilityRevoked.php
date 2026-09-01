<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfficialContentPublicAvailabilityRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $notice_id,
    ) {}
}
