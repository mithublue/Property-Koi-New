<?php
/**
 * Auto-Deployment Webhook Script for PropertyKoi
 * Triggers on GitHub Push Event
 */

// সিক্রেট কি (GitHub Webhook-এ যা দিয়েছিলেন)
$secret = 'MyPropertyKoiSecret123';

// টোকেন যাচাই (Query parameter অথবা Header থেকে)
$requestToken = $_GET['token'] ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

if (empty($requestToken) || !hash_equals($secret, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid Token']);
    exit;
}

// কমান্ড রান করার ফাংশন
function runCommand($cmd) {
    return shell_exec($cmd . ' 2>&1');
}

$baseDir = dirname(__DIR__);
if (!file_exists($baseDir . '/artisan')) {
    $baseDir = __DIR__;
}

chdir($baseDir);

// Find available PHP 8.3 / PHP 8 binaries (supporting CloudLinux alt-php and cPanel ea-php)
$findPhp = runCommand('ls /opt/alt/php83/usr/bin/php 2>/dev/null || ls /opt/alt/php8*/usr/bin/php 2>/dev/null || which ea-php83 2>/dev/null || which php83 2>/dev/null || which php8.3 2>/dev/null || ls /usr/local/bin/ea-php* 2>/dev/null || ls /opt/cpanel/ea-php*/root/usr/bin/php 2>/dev/null || which php');
$phpCandidates = array_filter(array_map('trim', explode("\n", (string)$findPhp)));
$phpBin = 'php';
foreach ($phpCandidates as $candidate) {
    if (strpos($candidate, '83') !== false || strpos($candidate, '8.3') !== false) {
        $phpBin = $candidate;
        break;
    }
}
if ($phpBin === 'php' && !empty($phpCandidates[0])) {
    foreach ($phpCandidates as $candidate) {
        if (strpos($candidate, '8') !== false) {
            $phpBin = $candidate;
            break;
        }
    }
}

// 1. Git pull
$gitPull = runCommand('git pull origin main');

// 2. Clear cache & optimize directly via Laravel (runs under Web PHP 8.3)
$cacheOutput = '';
$migrationOutput = '';
try {
    if (file_exists($baseDir . '/vendor/autoload.php') && file_exists($baseDir . '/bootstrap/app.php')) {
        require_once $baseDir . '/vendor/autoload.php';
        $app = require_once $baseDir . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $cacheOutput = trim(\Illuminate\Support\Facades\Artisan::output());

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $latestDemand = \App\Models\BuyerDemand::where('requirement_code', 'REQ-RXHXQP')->first();
        $demandNotifs = $latestDemand ? \App\Models\DemandNotification::where('buyer_demand_id', $latestDemand->id)->get() : [];
        $fcmSettings = \App\Models\Setting::where('key', 'LIKE', '%fcm%')->pluck('value', 'key')->toArray();
        $tokens = \App\Models\FcmDeviceToken::all()->map(fn($t) => ['id' => $t->id, 'user_id' => $t->user_id, 'device' => $t->device_info, 'updated_at' => (string)$t->updated_at])->toArray();
    } else {
        $cacheOutput = 'Laravel bootstrap not found';
    }
} catch (\Throwable $e) {
    $cacheOutput = 'Artisan error: ' . $e->getMessage();
}

// রেসপন্স প্রদান
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'git_pull' => trim($gitPull ?? ''),
    'cache' => $cacheOutput,
    'latest_demand' => $latestDemand,
    'demand_notifs' => $demandNotifs,
    'fcm_settings' => $fcmSettings ?? [],
    'fcm_tokens' => $tokens ?? [],
], JSON_PRETTY_PRINT);
exit;
