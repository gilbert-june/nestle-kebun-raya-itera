<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly sensor data export on the first day of each month at 2:00 AM
Schedule::command('sensors:export-monthly')
    ->monthlyOn(1, '02:00')
    ->description('Export all sensor data to Excel files monthly');
