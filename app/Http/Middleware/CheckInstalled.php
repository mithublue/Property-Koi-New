<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Check if the application is installed
     */
    public static function isInstalled(): bool
    {
        // 1. If storage/installed marker exists
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        // 2. Fallback check: if APP_INSTALLED=true in env or config
        if (env('APP_INSTALLED') === true || env('APP_INSTALLED') === 'true' || config('app.installed') === true) {
            return true;
        }

        // 3. Fallback: if database is already configured and users table has an admin
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::where('role', 'admin')->exists()) {
                // Auto-create the installed marker so we don't query DB on every request
                @file_put_contents(storage_path('installed'), json_encode([
                    'installed_at' => now()->toIso8601String(),
                    'auto_detected' => true,
                    'version' => '1.0.0',
                ], JSON_PRETTY_PRINT));
                return true;
            }
        } catch (\Throwable $e) {
            // DB not connected or table doesn't exist yet -> definitely not installed!
            return false;
        }

        return false;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = self::isInstalled();
        $isInstallRoute = $request->is('install*');

        // If not installed and not requesting an install route or asset, redirect to installer
        if (!$isInstalled) {
            if (!$isInstallRoute && !$request->is('build/*') && !$request->is('assets/*') && !$request->is('up') && !$request->is('api/*')) {
                return redirect()->route('install.index');
            }
        }

        return $next($request);
    }
}

