<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetSyncService;
use Illuminate\Console\Command;

class SyncGoogleSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sheets:sync {--url= : Optional Google Sheet CSV/Edit URL override}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize property listings from configured Google Sheet CSV';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Sheets property sync...');

        $url = $this->option('url');
        $result = GoogleSheetSyncService::syncNow($url ?: null);

        if (!empty($result['success'])) {
            $this->info($result['message'] ?? 'Google Sheets sync completed successfully.');
            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'Google Sheets sync failed.');
        return self::FAILURE;
    }
}
