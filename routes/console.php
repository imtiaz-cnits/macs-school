<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:resolve-missing-out-punches')->dailyAt('23:59')->timezone('Asia/Dhaka');
Schedule::command('fees:auto-generate')->dailyAt('00:05')->timezone('Asia/Dhaka');
Schedule::command('sms:dispatch-scheduled')->everyMinute()->withoutOverlapping();
