<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGuideApiKey
{
    /**
     * Default fallback API key if not yet configured in settings or env.
     */
    public const DEFAULT_KEY = 'propertykoi_guides_sec_2026';

    /**
     * Handle an incoming request for Guide API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If user is already authenticated as an Admin via session or Sanctum, allow
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->isAdmin() || $user->role === 'admin' || $user->role === 'administrator') {
                return $next($request);
            }
        }

        // 2. Extract token from Headers, Bearer, or Query
        $providedKey = $request->header('X-API-KEY')
            ?: ($request->header('X-Guide-Api-Key')
            ?: ($request->bearerToken()
            ?: $request->query('api_key')));

        if (empty($providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key is required. Please provide it via X-API-KEY header or Authorization Bearer token.',
            ], 401);
        }

        // 3. Expected Key from Setting, env, or default
        $configuredKey = trim((string) Setting::get('adm_api_secret_key', env('PROPERTYKOI_API_KEY', self::DEFAULT_KEY)));
        if (empty($configuredKey)) {
            $configuredKey = self::DEFAULT_KEY;
        }

        // 4. Secure constant-time string comparison
        if (!hash_equals($configuredKey, trim((string) $providedKey))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid API Key provided.',
            ], 401);
        }

        return $next($request);
    }
}
