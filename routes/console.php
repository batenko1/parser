<?php

use App\Models\ScheduledArticleUpdate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:parser-command')->hourly();

Schedule::command('articles:process-scheduled --batch=1000')->everyMinute();


Schedule::call(function () {
    ScheduledArticleUpdate::query()
        ->where('processed', true)
        ->where('updated_at', '<', now()->subDays(7))
        ->delete();
})->dailyAt('03:15');
