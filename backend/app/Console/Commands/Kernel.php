<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\PostToHashnode::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Schedule tasks here if needed
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
