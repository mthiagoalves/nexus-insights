<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * You can register commands here or they will be discovered automatically via PSR-4.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        // Se você registrar comandos manualmente coloque a classe aqui, ex:
        // \App\Console\Commands\SyncAllWorkspacesMetaInsights::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // exemplo: roda o comando que criamos a cada 30 minutos
        $schedule->command('meta:sync-all-insights')->everyThirtyMinutes();

        // exemplo alternativo: roda diariamente às 01:30
        // $schedule->command('meta:sync-all-insights')->dailyAt('01:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
