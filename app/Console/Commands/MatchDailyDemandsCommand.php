<?php

namespace App\Console\Commands;

use App\Services\BuyerDemandMatchService;
use Illuminate\Console\Command;

class MatchDailyDemandsCommand extends Command
{
    protected $signature = 'demand:match {--force : Force execution ignoring 20h throttle}';
    protected $description = 'Match active buyer demands against live properties and send notifications';

    public function handle(): int
    {
        $this->info('Starting buyer demand matching process...');
        $force = (bool) $this->option('force');

        $result = BuyerDemandMatchService::runDailyDemandMatching($force);

        $this->info("Completed. Checked demands: {$result['total_requirements']}");
        $this->info("Matched demands: {$result['matched_demands']}");
        $this->info("Push sent: {$result['push_sent']}, Email sent: {$result['email_sent']}, Telegram sent: {$result['telegram_sent']}");

        return Command::SUCCESS;
    }
}
