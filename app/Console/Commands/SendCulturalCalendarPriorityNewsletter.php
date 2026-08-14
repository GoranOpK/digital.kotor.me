<?php

namespace App\Console\Commands;

use App\Services\Newsletter\NewsletterPriorityDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendCulturalCalendarPriorityNewsletter extends Command
{
    public const LOCK_KEY = 'newsletter-priority-cycle';

    protected $signature = 'cultural-calendar:send-newsletter-priority';

    protected $description = 'Šalje kanonski prioritetni Newsletter (priority_change) pretplatnicima.';

    public function handle(NewsletterPriorityDeliveryService $delivery): int
    {
        $lock = Cache::lock(self::LOCK_KEY, 1800);
        if (! $lock->get()) {
            $this->info('Preskočeno: prioritetni Newsletter ciklus je već u toku.');

            return self::SUCCESS;
        }

        try {
            $stats = $delivery->flushDueChanges();
        } finally {
            optional($lock)->release();
        }

        $this->info(sprintf(
            'Prioritetni Newsletter ciklus: pregledano=%d poslato=%d izmjena=%d prazno=%d nepodobno=%d neuspjeh=%d obradjeno=%d',
            $stats['inspected'],
            $stats['sent'],
            $stats['changes'],
            $stats['skipped_empty'],
            $stats['skipped_ineligible'],
            $stats['failed'],
            $stats['processed']
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
