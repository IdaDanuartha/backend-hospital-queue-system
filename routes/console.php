<?php

use App\Jobs\CleanupOldQueues;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of old queue data daily at midnight
Schedule::job(new CleanupOldQueues)->dailyAt('00:00');
