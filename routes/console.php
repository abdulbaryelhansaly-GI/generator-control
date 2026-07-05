<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('generators:check-alerts')->everyMinute();
Schedule::exec('"C:\\Program Files\\R\\R-4.x.x\\bin\\Rscript.exe" "C:\\Users\\DANGER\\Desktop\\generator\\reliability_analysis.R"')
         ->everyFiveMinutes();