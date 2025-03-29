<?php

namespace App\Console;

use App\Console\Commands\UpdateAccessTokenZalo;
use App\Console\Commands\DeletePendingRegistrations;
use App\Console\Commands\NotiReturnCarLate;

use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateAccessTokenZalo::class,
        DeletePendingRegistrations::class,
        NotiReturnCarLate::class,
    ];

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): DateTimeZone|string|null
    {
        // Get the timezone from the configuration
        return config('app.cron_timezone');

    }

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('update-access-token-zalo')->dailyAt('21:00');
        $schedule->command('users:delete-pending')->everyMinute();
        $schedule->command('booking:check-late-returns')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

}
