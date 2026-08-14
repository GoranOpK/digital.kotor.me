<?php

namespace App\Console\Commands;

use App\Models\NewsletterSubscription;
use App\Services\Newsletter\NewsletterFirstIncludeDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendCulturalCalendarNewsletter extends Command
{
    public const LOCK_KEY = 'newsletter-regular-cycle';

    protected $signature = 'cultural-calendar:send-newsletter';

    protected $description = 'Šalje kanonski redovni Newsletter (first_include) pretplatnicima.';

    public function handle(NewsletterFirstIncludeDeliveryService $delivery): int
    {
        $lock = Cache::lock(self::LOCK_KEY, 1800);
        if (! $lock->get()) {
            $this->info('Preskočeno: redovni Newsletter ciklus je već u toku.');

            return self::SUCCESS;
        }

        $inspected = 0;
        $sent = 0;
        $events = 0;
        $skippedEmpty = 0;
        $skippedIneligible = 0;
        $failed = 0;

        try {
            NewsletterSubscription::query()
                ->where('status', NewsletterSubscription::STATUS_ACTIVE)
                ->orderBy('id')
                ->chunkById(50, function ($subscriptions) use (
                    $delivery,
                    &$inspected,
                    &$sent,
                    &$events,
                    &$skippedEmpty,
                    &$skippedIneligible,
                    &$failed
                ): void {
                    foreach ($subscriptions as $subscription) {
                        $inspected++;
                        $result = $delivery->deliverForSubscription($subscription);

                        if ($result->wasSent()) {
                            $sent++;
                            $events += $result->eventsDelivered;
                            continue;
                        }

                        if ($result->wasFailed()) {
                            $failed++;
                            $this->error(
                                'Neuspjeh slanja pretplate #'.$subscription->id
                                .($result->error !== null ? ': '.$result->error : '')
                            );
                            continue;
                        }

                        if ($result->wasSkippedEmpty()) {
                            $skippedEmpty++;
                            continue;
                        }

                        $skippedIneligible++;
                    }
                });
        } finally {
            optional($lock)->release();
        }

        $this->info(sprintf(
            'Newsletter ciklus: pregledano=%d poslato=%d dogadjaja=%d prazno=%d nepodobno=%d neuspjeh=%d',
            $inspected,
            $sent,
            $events,
            $skippedEmpty,
            $skippedIneligible,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
