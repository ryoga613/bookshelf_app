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
        // 日付が変わったら、期日を過ぎた進行中の計画を期限切れにする
        $schedule->command('app:expire-reading-plans')
            ->dailyAt('00:00')
            ->withoutOverlapping();

        $schedule->command('app:send-goal-reminder')
            ->dailyAt('20:00')
            ->withoutOverlapping();
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
