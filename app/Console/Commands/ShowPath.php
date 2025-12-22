<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowPath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'path:show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prikazuje putanju do projekta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Putanja do projekta:');
        $this->line(base_path());
        
        return 0;
    }
}

