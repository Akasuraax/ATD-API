<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        Commands\PlanJourneyFromScheduleCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:plan-journey-from-schedule-command')->timezone('Europe/Paris')->dailyAt('16:20');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
