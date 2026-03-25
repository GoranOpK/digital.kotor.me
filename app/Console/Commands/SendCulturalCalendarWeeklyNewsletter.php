<?php

namespace App\Console\Commands;

use App\Mail\CulturalCalendarNewsletterWeeklyMail;
use App\Models\NewsletterSubscriber;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendCulturalCalendarWeeklyNewsletter extends Command
{
    protected $signature = 'cultural-calendar:send-weekly-newsletter {--dry-run : Prikazuje primaoce bez slanja mejla}';

    protected $description = 'Šalje sedmični newsletter pretplatnicima Kalendara kulture.';

    public function handle(): int
    {
        Carbon::setLocale('sr');

        $dryRun = (bool) $this->option('dry-run');
        $weekStart = Carbon::now()->startOfWeek()->addWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $weekEventsLink = URL::route('cultural-calendar.events', [
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
        ]);

        $subscribers = NewsletterSubscriber::query()
            ->where('is_subscribed', true)
            ->orderBy('email')
            ->get();

        if ($subscribers->isEmpty()) {
            $this->info('Nema aktivnih newsletter pretplatnika.');
            return Command::SUCCESS;
        }

        foreach ($subscribers as $subscriber) {
            if ($dryRun) {
                $this->line('[dry-run] ' . $subscriber->email);
                continue;
            }

            try {
                Mail::to($subscriber->email)->send(
                    new CulturalCalendarNewsletterWeeklyMail(
                        subscriber: $subscriber,
                        weekStart: $weekStart->copy(),
                        weekEnd: $weekEnd->copy(),
                        weekEventsLink: $weekEventsLink
                    )
                );
            } catch (\Throwable $e) {
                $this->error('Greška pri slanju za ' . $subscriber->email . ': ' . $e->getMessage());
            }
        }

        $this->info('Sedmični newsletter je obrađen za ' . $subscribers->count() . ' primaoca.');

        return Command::SUCCESS;
    }
}

