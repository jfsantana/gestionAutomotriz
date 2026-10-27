<?php

use App\Services\MaintenanceReminderNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('maintenance:send-reminders')
    ->dailyAt('08:00')
    ->timezone(MaintenanceReminderNotificationService::TIMEZONE)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/maintenance-reminders.log'));

Schedule::command('fleet-subscriptions:process-expired')
    ->dailyAt('00:05')
    ->withoutOverlapping();
