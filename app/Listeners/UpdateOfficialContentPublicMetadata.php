<?php

namespace App\Listeners;

use App\Events\OfficialContentPublicMetadataUpdated;
use App\Models\Notice;
use App\Services\Notices\NoticePublicationService;

class UpdateOfficialContentPublicMetadata
{
    public function __construct(
        private readonly NoticePublicationService $noticePublicationService,
    ) {}

    public function handle(OfficialContentPublicMetadataUpdated $event): void
    {
        $notice = Notice::query()->findOrFail($event->notice_id);

        $this->noticePublicationService->updatePublicMetadata($notice, [
            'title' => $event->title,
            'public_display_date' => $event->public_display_date,
        ]);
    }
}
