<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckInstalled;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallController extends Controller
{
    /**
     * Show the WordPress-like Installation Wizard
     */
    public function index(Request $request)
    {
        $isInstalled = CheckInstalled::isInstalled();
        $isReinstall = $request->boolean('reinstall') || $request->boolean('force');

        // Check Server Requirements & Permissions
        $requirements = $this->getRequirements();
        $permissions = $this->getPermissions();

        $allRequirementsPassed = !in_array(false, array_column($requirements, 'pass'), true);
        $allPermissionsPassed = !in_array(false, array_column($permissions, 'pass'), true);
        $canInstall = $allRequirementsPassed && $allPermissionsPassed;

        // Default or detected configuration
        $config = [
            'app_name' => config('app.name', 'PropertyKoi'),
            'app_url' => url('/'),
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_port' => env('DB_PORT', '3306'),
            'db_database' => env('DB_DATABASE', 'propertykoi'),
            'db_username' => env('DB_USERNAME', 'root'),
            'db_password' => env('DB_PASSWORD', ''),
        ];

        return view('install.wizard', compact(
            'isInstalled',
            'isReinstall',
            'requirements',
            'permissions',
            'allRequirementsPassed',
            'allPermissionsPassed',
            'canInstall',
            'config'
        ));
    }

    /**
     * AJAX Database Connection Test
     */
    public function testDb(Request $request): JsonResponse
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host = $request->input('db_host', '127.0.0.1');
        $port = (int) $request->input('db_port', 3306);
        $database = $request->input('db_database');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '') ?? '';

        try {
            // 1. First test raw PDO connection to MySQL server without selecting db
            $dsnWithoutDb = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsnWithoutDb, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4,
            ]);

            // 2. Check if the specified database exists
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db");
            $stmt->execute(['db' => $database]);
            $exists = (bool) $stmt->fetch();

            $created = false;
            if (!$exists) {
                try {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $created = true;
                } catch (Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "ডাটাবেজ '{$database}' পাওয়া যায়নি এবং স্বয়ংক্রিয়ভাবে তৈরি করার অনুমতি নেই। অনুগ্রহ করে cPanel MySQL Databases থেকে ডাটাবেজটি ম্যানুয়ালি তৈরি করে আবার টেস্ট করুন। ত্রুটি: " . $e->getMessage(),
                    ], 422);
                }
            }

            // 3. Connect directly into the database to verify full access
            $dsnWithDb = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new PDO($dsnWithDb, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4,
            ]);

            return response()->json([
                'success' => true,
                'message' => $created
                    ? "ডাটাবেজ সার্ভারের সাথে কানেকশন সফল! নতুন ডাটাবেজ '{$database}' স্বয়ংক্রিয়ভাবে তৈরি করা হয়েছে।"
                    : "ডাটাবেজ সার্ভার এবং '{$database}' ডাটাবেজের সাথে সফলভাবে সংযোগ স্থাপিত হয়েছে!",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ডাটাবেজ কানেকশন ব্যর্থ হয়েছে: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Run Installation (One-Click Migration, Admin Setup & Lock)
     */
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url|max:255',
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:150',
            'admin_phone' => 'required|string|max:30',
            'admin_password' => 'required|string|min:6|confirmed',
            'seed_demo' => 'nullable|boolean',
            'fresh_migration' => 'nullable|boolean',
        ]);

        @set_time_limit(300);

        try {
            $dbHost = $validated['db_host'];
            $dbPort = (int) $validated['db_port'];
            $dbName = $validated['db_database'];
            $dbUser = $validated['db_username'];
            $dbPass = $validated['db_password'] ?? '';

            // Verify DB exists or create it
            $dsnWithoutDb = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsnWithoutDb, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Ensure app key exists
            $appKey = env('APP_KEY');
            if (empty($appKey)) {
                $appKey = 'base64:' . base64_encode(random_bytes(32));
            }

            // Step 1: Update .env file
            $this->updateEnv([
                'APP_NAME' => $validated['app_name'],
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $validated['app_url'],
                'APP_KEY' => $appKey,
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbName,
                'DB_USERNAME' => $dbUser,
                'DB_PASSWORD' => $dbPass,
                'APP_INSTALLED' => 'true',
            ]);

            // Step 2: Runtime dynamic database reconfiguration
            config([
                'app.name' => $validated['app_name'],
                'app.url' => $validated['app_url'],
                'app.key' => $appKey,
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $dbHost,
                'database.connections.mysql.port' => $dbPort,
                'database.connections.mysql.database' => $dbName,
                'database.connections.mysql.username' => $dbUser,
                'database.connections.mysql.password' => $dbPass,
            ]);

            DB::purge('mysql');
            DB::reconnect('mysql');

            // Step 3: Run Database Migrations
            if (!empty($validated['fresh_migration'])) {
                Artisan::call('migrate:fresh', ['--force' => true]);
            } else {
                Artisan::call('migrate', ['--force' => true]);
            }

            // Step 4: Seed Demo Data if selected
            if (!empty($validated['seed_demo'])) {
                try {
                    Artisan::call('db:seed', ['--force' => true]);
                } catch (Throwable $e) {
                    // Continue even if demo seeder encounters an edge case
                }
            }

            // Step 5: Create or Update the Super Admin User
            $admin = User::updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'username' => Str::slug($validated['admin_name'], '_') ?: 'admin',
                    'phone' => $validated['admin_phone'],
                    'password' => Hash::make($validated['admin_password']),
                    'role' => 'admin',
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ]
            );

            // Step 6: Initialize Default Site Settings
            try {
                Setting::set('adm_site_name', $validated['app_name']);
                Setting::set('adm_site_email', $validated['admin_email']);
                if (!empty($validated['admin_phone'])) {
                    Setting::set('adm_site_phone', $validated['admin_phone']);
                    Setting::set('adm_admin_whatsapp_number', $validated['admin_phone']);
                }
            } catch (Throwable $e) {
                // Settings table might not be present if custom migration changed
            }

            // Step 7: Write Installation Lock Marker
            $lockData = [
                'installed_at' => now()->toIso8601String(),
                'app_name' => $validated['app_name'],
                'admin_email' => $validated['admin_email'],
                'app_url' => $validated['app_url'],
                'version' => '1.0.0',
            ];
            file_put_contents(storage_path('installed'), json_encode($lockData, JSON_PRETTY_PRINT));

            // Step 8: Clear caches
            @Artisan::call('config:clear');
            @Artisan::call('cache:clear');
            @Artisan::call('view:clear');

            // Save installation credentials in session for success screen display
            session([
                'installed_info' => [
                    'app_name' => $validated['app_name'],
                    'app_url' => $validated['app_url'],
                    'admin_name' => $validated['admin_name'],
                    'admin_email' => $validated['admin_email'],
                    'admin_phone' => $validated['admin_phone'],
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ইনস্টলেশন সফলভাবে সম্পন্ন হয়েছে!',
                'redirect_url' => route('install.success'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ইনস্টলেশনে ত্রুটি ঘটেছে: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show Installation Success Screen
     */
    public function success(Request $request)
    {
        $installedInfo = session('installed_info');

        if (!$installedInfo && file_exists(storage_path('installed'))) {
            $lock = json_decode(file_get_contents(storage_path('installed')), true);
            $installedInfo = [
                'app_name' => $lock['app_name'] ?? config('app.name'),
                'app_url' => $lock['app_url'] ?? url('/'),
                'admin_name' => 'Super Admin',
                'admin_email' => $lock['admin_email'] ?? 'admin@propertykoi.com',
                'admin_phone' => 'N/A',
            ];
        }

        return view('install.success', compact('installedInfo'));
    }

    /**
     * Helper: Check Server Requirements
     */
    protected function getRequirements(): array
    {
        return [
            'php' => [
                'name' => 'PHP ভার্সন (PHP Version)',
                'required' => '>= 8.2.0',
                'current' => PHP_VERSION,
                'pass' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'help' => 'cPanel-এর "Select PHP Version" থেকে PHP 8.2 বা তার উপরের ভার্সন সিলেক্ট করুন।',
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'required' => 'Enables database connectivity',
                'current' => extension_loaded('pdo') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('pdo'),
                'help' => 'php.ini ফাইলে extension=pdo সক্রিয় করুন।',
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Driver',
                'required' => 'MySQL/MariaDB connection',
                'current' => extension_loaded('pdo_mysql') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('pdo_mysql'),
                'help' => 'php.ini ফাইলে extension=pdo_mysql সক্রিয় করুন।',
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'required' => 'Multibyte Bangla string support',
                'current' => extension_loaded('mbstring') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('mbstring'),
                'help' => 'cPanel PHP Extensions থেকে mbstring সক্রিয় করুন।',
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'required' => 'Cryptographic security & hashing',
                'current' => extension_loaded('openssl') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('openssl'),
                'help' => 'php.ini ফাইলে extension=openssl সক্রিয় করুন।',
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'required' => 'External APIs (Cloudinary, WP Import)',
                'current' => extension_loaded('curl') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('curl'),
                'help' => 'php.ini ফাইলে extension=curl সক্রিয় করুন।',
            ],
            'fileinfo' => [
                'name' => 'Fileinfo Extension',
                'required' => 'File & media MIME detection',
                'current' => extension_loaded('fileinfo') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('fileinfo'),
                'help' => 'cPanel PHP Extensions থেকে fileinfo সক্রিয় করুন।',
            ],
            'gd_imagick' => [
                'name' => 'GD বা ImageMagick Extension',
                'required' => 'Watermark & WebP image processing',
                'current' => (extension_loaded('gd') || extension_loaded('imagick')) ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('gd') || extension_loaded('imagick'),
                'help' => 'cPanel PHP Extensions থেকে gd অথবা imagick সক্রিয় করুন।',
            ],
            'tokenizer' => [
                'name' => 'Tokenizer Extension',
                'required' => 'Blade template compilation',
                'current' => extension_loaded('tokenizer') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('tokenizer'),
                'help' => 'php.ini ফাইলে extension=tokenizer সক্রিয় করুন।',
            ],
            'xml' => [
                'name' => 'XML Extension',
                'required' => 'XML parsing & Sitemap generator',
                'current' => extension_loaded('xml') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('xml'),
                'help' => 'cPanel PHP Extensions থেকে xml সক্রিয় করুন।',
            ],
            'bcmath' => [
                'name' => 'BCMath Extension',
                'required' => 'High precision calculation',
                'current' => extension_loaded('bcmath') ? 'সক্রিয় (Active)' : 'নিষ্ক্রিয় (Disabled)',
                'pass' => extension_loaded('bcmath'),
                'help' => 'cPanel PHP Extensions থেকে bcmath সক্রিয় করুন।',
            ],
        ];
    }

    /**
     * Helper: Check Directory Permissions
     */
    protected function getPermissions(): array
    {
        $items = [
            'storage' => [
                'name' => 'storage/',
                'path' => storage_path(),
                'pass' => is_writable(storage_path()),
            ],
            'storage_framework' => [
                'name' => 'storage/framework/',
                'path' => storage_path('framework'),
                'pass' => is_writable(storage_path('framework')),
            ],
            'storage_logs' => [
                'name' => 'storage/logs/',
                'path' => storage_path('logs'),
                'pass' => is_writable(storage_path('logs')),
            ],
            'bootstrap_cache' => [
                'name' => 'bootstrap/cache/',
                'path' => base_path('bootstrap/cache'),
                'pass' => is_writable(base_path('bootstrap/cache')),
            ],
            'env_file' => [
                'name' => '.env ফাইল / রুট ডিরেক্টরি',
                'path' => base_path('.env'),
                'pass' => file_exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
            ],
        ];

        return array_map(function ($item) {
            $item['help'] = 'cPanel File Manager থেকে এই ফোল্ডার বা ফাইলের পারমিশন 775 অথবা 755 (ফাইল হলে 644/664) করে দিন।';
            return $item;
        }, $items);
    }

    /**
     * Helper: Safely update .env file
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $value = (string) $value;
            // Quote value if contains spaces, hashes or special characters
            if (preg_match('/\s|#/', $value) || str_contains($value, '"')) {
                $formatted = '"' . addcslashes($value, '"\\') . '"';
            } else {
                $formatted = $value;
            }

            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formatted}", $content);
            } else {
                $content .= "\n{$key}={$formatted}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
