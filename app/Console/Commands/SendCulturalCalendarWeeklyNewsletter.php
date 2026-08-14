<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Legacy weekly Newsletter. Files KEEP; runtime DISABLED after canonical cutover (TS-011 §26 / PRAVILO 5.3.4).
 * Canonical regular command: cultural-calendar:send-newsletter.
 */
class SendCulturalCalendarWeeklyNewsletter extends Command
{
    protected $signature = 'cultural-calendar:send-weekly-newsletter {--dry-run : Prikazuje primaoce bez slanja mejla}';

    protected $description = 'Legacy sedmični Newsletter — ISKLJUČEN. Koristite cultural-calendar:send-newsletter.';

    public function handle(): int
    {
        $this->warn(
            'Legacy weekly Newsletter je isključen. Kanonska komanda je cultural-calendar:send-newsletter.'
        );

        return self::SUCCESS;
    }
}
