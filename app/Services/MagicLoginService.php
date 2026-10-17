<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class MagicLoginService
{
    /**
     * Get or create a 32-char magic token for a user.
     * Never returns token for admin.
     */
    public static function getOrCreateToken(int|User $user): string
    {
        $u = is_numeric($user) ? User::find($user) : $user;
        if (!$u || $u->isAdmin()) {
            return '';
        }

        if (empty($u->magic_token) || strlen($u->magic_token) < 24) {
            $u->magic_token = Str::random(32);
            $u->saveQuietly();
        }

        return $u->magic_token;
    }

    /**
     * Regenerate token
     */
    public static function regenerateToken(int|User $user): string
    {
        $u = is_numeric($user) ? User::find($user) : $user;
        if (!$u || $u->isAdmin()) {
            return '';
        }

        $u->magic_token = Str::random(32);
        $u->saveQuietly();

        return $u->magic_token;
    }

    /**
     * Build magic auto-login URL
     */
    public static function getMagicLoginUrl(int|User $user, string $redirect = ''): string
    {
        $token = self::getOrCreateToken($user);
        if (empty($token)) {
            return '';
        }

        $u = is_numeric($user) ? User::find($user) : $user;
        if (empty($redirect)) {
            $redirect = '/buyer-dashboard';
        }

        return url('/auth/magic/' . $token . '?redirect=' . urlencode($redirect));
    }
}
