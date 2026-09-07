<?php

namespace App\Listeners;

use App\Events\OfficialContentPublicAvailabilityRevoked;
use App\Models\Notice;
use App\Services\Notices\NoticePublicationService;

class RevokeOfficialContentPublicAvailability
{
    public function __construct(
        private readonly NoticePublicationService $noticePublicationService,
    ) {}

    public function handle(OfficialContentPublicAvailabilityRevoked $event): void
    {
        $notice = Notice::query()->findOrFail($event->notice_id);

        $this->noticePublicationService->revokePublicAvailability($notice);
    }
}
