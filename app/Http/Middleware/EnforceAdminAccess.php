<?php

namespace App\Http\Middleware;

use App\Services\AccessControlService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminAccess
{
    /**
     * Handle an incoming request to /admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Must be authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'অ্যাডমিন ড্যাশবোর্ডে প্রবেশ করতে অনুগ্রহ করে লগইন করুন।');
        }

        $user = Auth::user();

        // 2. Administrators always have access
        if ($user->isAdmin() || $user->role === 'administrator') {
            return $next($request);
        }

        // 3. Check role-wise permissions configured in Settings Tab 5
        if (AccessControlService::canAccessAdminPanel($user)) {
            return $next($request);
        }

        // 4. Access blocked - calculate target destination
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized admin panel access.'], 403);
        }

        $targetUrl = AccessControlService::getRedirectForBlockedAdmin($user);

        return redirect($targetUrl)->with('error', 'আপনার অ্যাকাউন্টের রোলের জন্য অ্যাডমিন ড্যাশবোর্ড এক্সেস সংরক্ষিত নয়।');
    }
}
