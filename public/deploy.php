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

// Detect PHP 8.3 CLI binary on cPanel / HostTier
$phpBin = 'php';
foreach ([
    '/usr/local/bin/ea-php83',
    '/opt/cpanel/ea-php83/root/usr/bin/php',
    '/usr/bin/ea-php83',
    '/usr/local/bin/php83',
    '/usr/bin/php83',
    'php83',
    'php8.3',
] as $candidate) {
    if (@file_exists($candidate) && @is_executable($candidate)) {
        $phpBin = $candidate;
        break;
    }
}

// 1. Git pull
$gitPull = runCommand('git pull origin main');

// 2. Clear cache
$cacheClear = runCommand($phpBin . ' artisan optimize:clear');

// 3. Migrate if needed
$migration = runCommand($phpBin . ' artisan migrate --force');

// রেসপন্স প্রদান
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'git_pull' => trim($gitPull ?? ''),
    'cache' => trim($cacheClear ?? ''),
    'migration' => trim($migration ?? ''),
], JSON_PRETTY_PRINT);
