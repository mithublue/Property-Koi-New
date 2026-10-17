<?php

use App\Models\Setting;
use App\Services\BuyerDemandMatchService;
use App\Services\GoogleSheetSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demand:match {--force}', function () {
    $force = (bool) $this->option('force');
    $res = BuyerDemandMatchService::runDailyDemandMatching($force);
    $this->info('Demand matching completed: ' . json_encode($res));
})->purpose('Run daily buyer demand matching and send alerts');

// PropertyKoi background schedulers
Schedule::command('demand:match')->dailyAt('09:00');
Schedule::command('sheets:sync')
    ->hourly()
    ->when(fn () => in_array(Setting::get('adm_sheet_auto_sync', '1'), ['1', 'yes', 'true'], true));
