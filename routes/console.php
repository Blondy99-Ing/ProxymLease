<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\Penalites\ApplicationPenaliteController;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');







// Cron job pour appliquer les pénalités
Schedule::call(function () {
    app(ApplicationPenaliteController::class)->appliquerPourAujourdhui(request());
})
->everyMinute(); // ⏱ tu peux ajuster: everyMinute(), hourly(), dailyAt('14:05') etc.
