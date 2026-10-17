<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (empty(config('app.key'))) {
            // Temporary in-memory key so the installer can boot and run before .env is populated
            config(['app.key' => 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fallback session to file if application is not yet installed
        if (!file_exists(storage_path('installed')) && env('APP_INSTALLED') !== true && env('APP_INSTALLED') !== 'true') {
            config(['session.driver' => 'file']);
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $driver = \App\Models\Setting::get('adm_mail_mailer', env('MAIL_MAILER', 'log'));
                if ($driver === 'smtp') {
                    $host = \App\Models\Setting::get('adm_mail_host', env('MAIL_HOST'));
                    $port = (int) \App\Models\Setting::get('adm_mail_port', env('MAIL_PORT', 587));
                    $username = \App\Models\Setting::get('adm_mail_username', env('MAIL_USERNAME'));
                    $password = \App\Models\Setting::get('adm_mail_password', env('MAIL_PASSWORD'));
                    $encryption = \App\Models\Setting::get('adm_mail_encryption', env('MAIL_ENCRYPTION', 'tls'));
                    $fromAddress = \App\Models\Setting::get('adm_mail_from_address', env('MAIL_FROM_ADDRESS'));
                    $fromName = \App\Models\Setting::get('adm_mail_from_name', env('MAIL_FROM_NAME', config('app.name', 'PropertyKoi')));

                    if (!empty($host)) {
                        config([
                            'mail.default' => 'smtp',
                            'mail.mailers.smtp.transport' => 'smtp',
                            'mail.mailers.smtp.host' => $host,
                            'mail.mailers.smtp.port' => $port,
                            'mail.mailers.smtp.encryption' => ($encryption === 'none' || empty($encryption)) ? null : $encryption,
                            'mail.mailers.smtp.username' => $username,
                            'mail.mailers.smtp.password' => $password,
                            'mail.from.address' => $fromAddress ?: config('mail.from.address'),
                            'mail.from.name' => $fromName ?: config('mail.from.name'),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fall back during migrations or if settings table is inaccessible
        }
    }
}
