<?php

namespace App\Console\Commands;

use App\Mail\SpisakKandidataMail;
use App\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCandidatesListEmails extends Command
{
    protected $signature = 'competitions:send-candidates-list
                            {--dry-run : Prikaži za koje konkurse bi se poslao mail, bez slanja}';

    protected $description = 'Šalje e-mail sa spiskom kandidata (Obrazac 1a/1b) na rada.radenovic@kotor.me kada je istekao rok za prijave. Pokrenuti npr. jednom dnevno (cron).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $competitions = Competition::with(['upNumber', 'applications' => fn ($q) => $q->whereNotNull('redni_broj')->orderBy('redni_broj')])
            ->whereNull('candidates_list_email_sent_at')
            ->where('status', 'published')
            ->get()
            ->filter(fn (Competition $c) => $c->isApplicationDeadlinePassed());

        if ($competitions->isEmpty()) {
            $this->info('Nema konkursa za koje treba poslati spisak kandidata.');
            return Command::SUCCESS;
        }

        foreach ($competitions as $competition) {
            $upBroj = $competition->upNumber?->number ?? '—';
            $count = $competition->applications->count();

            if ($dryRun) {
                $this->line("  [dry-run] Konkurs ID {$competition->id}, UP broj: {$upBroj}, prijava: {$count}");
                continue;
            }

            if ($count === 0) {
                $competition->update(['candidates_list_email_sent_at' => now()]);
                $this->line("Konkurs ID {$competition->id} (UP: {$upBroj}): nema prijava, označeno kao poslato.");
                continue;
            }

            try {
                Mail::to('rada.radenovic@kotor.me')->send(new SpisakKandidataMail($competition));
                $competition->update(['candidates_list_email_sent_at' => now()]);
                $this->info("Poslato za konkurs \"{$competition->title}\" (UP: {$upBroj}), {$count} prijava.");
            } catch (\Throwable $e) {
                $this->error("Greška pri slanju za konkurs ID {$competition->id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
