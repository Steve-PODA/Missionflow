<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('personnel:check-leaves')->dailyAt('00:05');
Schedule::command('missions:remind')->dailyAt('07:00');
Schedule::command('missions:day-alerts')->dailyAt('06:00');
