<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('projects:purge-deleted')->daily()->withoutOverlapping();
Schedule::command('app:recover-stale-generations')->everyTwoMinutes()->withoutOverlapping();
