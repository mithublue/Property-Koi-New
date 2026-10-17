<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class AccessControlService
{
    const OPTION_ADMIN_BAR   = 'adm_role_admin_bar';
    const OPTION_ADMIN_PANEL = 'adm_role_admin_panel';
    const OPTION_REDIRECTS   = 'adm_role_admin_redirects';
    const OPTION_RULES       = 'adm_role_redirect_rules';

    public static function getAvailableRoles(): array
    {
        return [
            'administrator' => '👑 Administrator (অ্যাডমিনিস্ট্রেটর)',
            'adm_agent'     => '💼 Direct Agent (ভেরিফাইড এজেন্ট)',
            'adm_owner'     => '🏠 Direct Owner (প্রপার্টি মালিক)',
            'adm_buyer'     => '🛒 Buyer / Customer (ক্রেতা / কাস্টমার)',
            'subscriber'    => '👤 Subscriber (সাবস্ক্রাইবার)',
            'editor'        => '✏️ Editor (এডিটর)',
            'author'        => '📝 Author (অথর)',
            'contributor'   => '📄 Contributor (কন্ট্রিবিউটর)',
            'default'       => '🌐 Default Fallback (অন্যান্য সকল রোল / ফলব্যাক)',
        ];
    }

    public static function getRolesSummary(): array
    {
        $available = self::getAvailableRoles();
        $summary = [];

        foreach ($available as $rKey => $rName) {
            $count = 0;
            if ($rKey !== 'default') {
                try {
                    // Match either strict role key or normalized roles
                    $count = User::where(function ($q) use ($rKey) {
                        $q->where('role', $rKey);
                        if ($rKey === 'adm_agent') {
                            $q->orWhere('role', 'agent');
                        } elseif ($rKey === 'adm_owner') {
                            $q->orWhere('role', 'owner');
                        } elseif ($rKey === 'adm_buyer') {
                            $q->orWhere('role', 'buyer');
                        }
                    })->count();
                } catch (\Throwable $e) {
                    $count = 0;
                }
            }

            $summary[] = [
                'key'   => $rKey,
                'name'  => $rName,
                'count' => $count,
            ];
        }

        return $summary;
    }

    public static function getAdminBarSettings(): array
    {
        $defaults = [
            'administrator' => 1,
            'editor'        => 1,
            'adm_agent'     => 0,
            'adm_owner'     => 0,
            'adm_buyer'     => 0,
            'subscriber'    => 0,
            'author'        => 0,
            'contributor'   => 0,
            'default'       => 0,
        ];

        $saved = Setting::get(self::OPTION_ADMIN_BAR, $defaults);
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }

        return array_merge($defaults, (array) $saved);
    }

    public static function getAdminPanelSettings(): array
    {
        $defaults = [
            'administrator' => 1,
            'editor'        => 1,
            'adm_agent'     => 0,
            'adm_owner'     => 0,
            'adm_buyer'     => 0,
            'subscriber'    => 0,
            'author'        => 0,
            'contributor'   => 0,
            'default'       => 0,
        ];

        $saved = Setting::get(self::OPTION_ADMIN_PANEL, $defaults);
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }

        $merged = array_merge($defaults, (array) $saved);
        $merged['administrator'] = 1; // Locked for administrator
        return $merged;
    }

    public static function getAdminRedirects(): array
    {
        $defaults = [
            'administrator' => 'auto',
            'editor'        => 'auto',
            'adm_agent'     => 'auto',
            'adm_owner'     => 'auto',
            'adm_buyer'     => 'auto',
            'subscriber'    => 'auto',
            'author'        => 'auto',
            'contributor'   => 'auto',
            'default'       => 'auto',
        ];

        $saved = Setting::get(self::OPTION_REDIRECTS, $defaults);
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }

        return array_merge($defaults, (array) $saved);
    }

    public static function getRoleRedirectRules(): array
    {
        $saved = Setting::get(self::OPTION_RULES, []);
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }

        return is_array($saved) ? $saved : [];
    }

    /**
     * Determine if top admin bar should be shown on the frontend for this user
     */
    public static function shouldShowAdminBar(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->role === 'administrator') {
            return true;
        }

        $settings = self::getAdminBarSettings();
        $userRole = self::normalizeRoleKey($user->role);

        return !empty($settings[$userRole]);
    }

    /**
     * Determine if user has permission to enter /admin panel
     */
    public static function canAccessAdminPanel(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->role === 'administrator') {
            return true;
        }

        $settings = self::getAdminPanelSettings();
        $userRole = self::normalizeRoleKey($user->role);

        return !empty($settings[$userRole]);
    }

    /**
     * Calculate destination when user is blocked from /admin
     */
    public static function getRedirectForBlockedAdmin(?User $user): string
    {
        if (!$user) {
            return route('login');
        }

        $redirects = self::getAdminRedirects();
        $userRole = self::normalizeRoleKey($user->role);
        $setting = $redirects[$userRole] ?? 'auto';

        if ($setting === 'home') {
            return url('/');
        }

        if ($setting === 'login') {
            return route('login');
        }

        return route('buyer.dashboard');
    }

    /**
     * Calculate destination for login/registration redirection repeater
     */
    public static function getLoginRedirectUrl(?User $user): string
    {
        if (!$user) {
            return route('login');
        }

        $rules = self::getRoleRedirectRules();
        $userRole = self::normalizeRoleKey($user->role);

        // Find match in saved rules
        foreach ($rules as $rule) {
            $ruleRole = self::normalizeRoleKey($rule['role'] ?? '');
            if ($ruleRole === $userRole || $ruleRole === 'default') {
                $type = $rule['type'] ?? 'custom_url';
                if ($type === 'custom_url' && !empty($rule['custom_url'])) {
                    return url($rule['custom_url']);
                }
                if ($type === 'page' && !empty($rule['page_id'])) {
                    $slug = self::resolvePageSlugById((int) $rule['page_id']);
                    if ($slug) {
                        return url('/' . ltrim($slug, '/'));
                    }
                }
            }
        }

        // Fallbacks if no rule matches
        if ($user->isAdmin() || $userRole === 'administrator') {
            return route('admin.dashboard');
        }

        // Default: directly go to buyer dashboard
        return route('buyer.dashboard');
    }

    protected static function normalizeRoleKey(?string $role): string
    {
        $role = (string) $role;
        return match ($role) {
            'agent'  => 'adm_agent',
            'owner'  => 'adm_owner',
            'buyer'  => 'adm_buyer',
            default  => $role,
        };
    }

    protected static function resolvePageSlugById(int $id): string
    {
        return match ($id) {
            1 => 'chahida',
            2 => 'agent-portal',
            3 => 'buyer-dashboard',
            4 => 'sell-property',
            5 => 'properties',
            default => '',
        };
    }
}
