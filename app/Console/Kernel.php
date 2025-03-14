<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    final function schedule(Schedule $schedule): void
    {
        $schedule->command('auth:prune-sessions')->daily();
        $schedule->command('auth:unlock-accounts')->everyFiveMinutes();
    }

    final function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
