<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ... existing code ...

        // Example: Automated recovery for failed forms
        // Uncomment and adjust as needed for your environment

        // $schedule->command('forms:requeue --force --older-than=2 --limit=50')
        //          ->hourly()
        //          ->withoutOverlapping()
        //          ->runInBackground()
        //          ->appendOutputTo(storage_path('logs/requeue.log'));

        // $schedule->command('forms:requeue --verification-only --force --older-than=6')
        //          ->dailyAt('02:00')
        //          ->withoutOverlapping()
        //          ->emailOutputOnFailure('admin@example.com');
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
