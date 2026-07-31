<?php

namespace App\Listeners;

use App\Events\OfficialContentReadyForPublicPublication;
use App\Services\Notices\NoticePublicationService;

class PublishOfficialContentNotice
{
    public function __construct(
        private readonly NoticePublicationService $noticePublicationService,
    ) {}

    public function handle(OfficialContentReadyForPublicPublication $event): void
    {
        $this->noticePublicationService->publish($event->toPublicationPayload());
    }
}
