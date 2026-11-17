<?php

namespace App\Console\Commands;

use App\Services\Notices\StandalonePdfNoticePublisher;
use Illuminate\Console\Command;

class PublishStandalonePdfNoticeCommand extends Command
{
    protected $signature = 'notices:publish-pdf
                            {path : Putanja do PDF fajla}
                            {--title= : Naslov u rubrici Obavještenja}
                            {--date= : Datum objave (Y-m-d)}';

    protected $description = 'Objavi samostalni PDF u javnu rubriku Obavještenja';

    public function handle(StandalonePdfNoticePublisher $publisher): int
    {
        $path = (string) $this->argument('path');
        $title = trim((string) $this->option('title'));
        $date = trim((string) $this->option('date'));

        if ($title === '') {
            $title = pathinfo($path, PATHINFO_FILENAME);
        }

        if ($date === '') {
            $date = now()->toDateString();
        }

        $notice = $publisher->publish($title, $date, $path);

        $this->info('Obavještenje #'.$notice->id.' je objavljeno: '.$notice->title);

        return self::SUCCESS;
    }
}
