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

// Find available PHP 8.3 / PHP 8 binaries
$findPhp = runCommand('which ea-php83 2>/dev/null || which php83 2>/dev/null || which php8.3 2>/dev/null || ls /usr/local/bin/ea-php* 2>/dev/null || ls /opt/cpanel/ea-php*/root/usr/bin/php 2>/dev/null || which php');
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

// 2. Clear cache
$cacheClear = runCommand($phpBin . ' artisan optimize:clear');

// 3. Migrate if needed
$migration = runCommand($phpBin . ' artisan migrate --force');

// রেসপন্স প্রদান
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_used' => $phpBin,
    'php_detected' => trim((string)$findPhp),
    'git_pull' => trim($gitPull ?? ''),
    'cache' => trim($cacheClear ?? ''),
    'migration' => trim($migration ?? ''),
], JSON_PRETTY_PRINT);
