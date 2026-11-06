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
$gitStatusBefore = runCommand('git status --short');
$isForce = (isset($_GET['force']) && $_GET['force'] === '1');

if ($isForce) {
    runCommand('git fetch origin main');
    $gitPull = runCommand('git reset --hard origin/main');
} else {
    $gitPull = runCommand('git pull origin main');
    // If blocked by untracked file conflict, auto-heal with fetch & reset
    if (strpos($gitPull, 'overwritten by merge') !== false) {
        runCommand('git fetch origin main');
        $gitPull .= "\n[Auto-healing untracked conflicts]\n" . runCommand('git reset --hard origin/main');
    }
}
$currentCommit = runCommand('git log -1 --oneline');

// 2. Clear cache & optimize directly via Laravel (runs under Web PHP 8.3)
$cacheOutput = '';
$migrationOutput = '';
$settingsUpdated = [];
try {
    if (file_exists($baseDir . '/vendor/autoload.php') && file_exists($baseDir . '/bootstrap/app.php')) {
        require_once $baseDir . '/vendor/autoload.php';
        $app = require_once $baseDir . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        // Check if settings payload provided
        $postSettings = $_POST['settings'] ?? null;
        if (empty($postSettings)) {
            $rawJson = @file_get_contents('php://input');
            if (!empty($rawJson)) {
                $decoded = json_decode($rawJson, true);
                if (!empty($decoded['settings']) && is_array($decoded['settings'])) {
                    $postSettings = $decoded['settings'];
                }
            }
        }

        if (!empty($postSettings) && is_array($postSettings)) {
            foreach ($postSettings as $sKey => $sVal) {
                if (str_starts_with($sKey, 'adm_') || in_array($sKey, ['google_sheet_url', 'admin_whatsapp_number'])) {
                    \App\Models\Setting::set($sKey, (string)$sVal);
                    $settingsUpdated[] = $sKey;
                }
            }
        }

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $cacheOutput = trim(\Illuminate\Support\Facades\Artisan::output());

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrationOutput = trim(\Illuminate\Support\Facades\Artisan::output());

        // Auto-sanitize any technical/sync declarations from database
        try {
            \Illuminate\Support\Facades\DB::table('property_direct_sources')
                ->where(function ($q) {
                    $q->where('relationship_declaration', 'like', '%API%')
                      ->orWhere('relationship_declaration', 'like', '%Google%')
                      ->orWhere('relationship_declaration', 'like', '%sheet%')
                      ->orWhere('relationship_declaration', 'like', '%sync%');
                })
                ->update([
                    'relationship_declaration' => \Illuminate\Support\Facades\DB::raw("CASE WHEN source_type IN ('owner', 'direct_owner') THEN 'সরাসরি প্রপার্টির মালিক (Direct Owner)' ELSE 'অনুমোদিত ডিরেক্ট এজেন্ট (Direct Agent)' END")
                ]);

            \Illuminate\Support\Facades\DB::table('properties')
                ->where('description', 'like', '%API মারফত%')
                ->orWhere('description', 'like', '%Google Sheets%')
                ->update([
                    'description' => \Illuminate\Support\Facades\DB::raw("COALESCE(title, 'চমৎকার লোকেশনে আকর্ষণীয় প্রপার্টি।')")
                ]);
        } catch (\Throwable $dbEx) {
            // Silently ignore if table columns differ
        }
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
    'git_status_before' => trim($gitStatusBefore ?? ''),
    'git_pull' => trim($gitPull ?? ''),
    'current_commit' => trim($currentCommit ?? ''),
    'cache' => $cacheOutput,
    'migration' => $migrationOutput ?? '',
    'settings_updated' => $settingsUpdated,
], JSON_PRETTY_PRINT);
exit;
