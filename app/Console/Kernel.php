<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecutar el comando 'relaciones:generar' los días 8 y 23 de cada mes a las 3 AM
        $schedule->command('relaciones:generar')->cron('0 3 8,23 * *');
        //$schedule->command('relaciones:generar')->cron('20 12 8,21 * *');
        // $schedule->command('relaciones:generar')->cron('24 12 8,21 * *');
        //Ejecuta el comando 'command:test-cronjob' cada minuto
        $schedule->command('command:test-cronjob')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
