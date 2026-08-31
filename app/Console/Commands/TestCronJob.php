<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;

class TestCronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:test-cronjob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para verficiar el funcionamiento de un cron job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Cron job is working!');
        return 0;    
    }
}
