<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->applyDbMailSettings();
    }

    private function applyDbMailSettings(): void
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }

            $settings = MailSetting::query()->first();
            if (! $settings || ! $settings->enabled) {
                return;
            }

            config([
                'mail.default' => $settings->mailer ?: 'smtp',
                'mail.mailers.smtp.host' => $settings->host,
                'mail.mailers.smtp.port' => $settings->port,
                'mail.mailers.smtp.encryption' => $settings->encryption,
                'mail.mailers.smtp.auth_mode' => $settings->auth_mode,
                'mail.mailers.smtp.username' => $settings->username,
                'mail.mailers.smtp.password' => $settings->password,
                'mail.from.address' => $settings->from_address ?: config('mail.from.address'),
                'mail.from.name' => $settings->from_name ?: config('mail.from.name'),
            ]);
        } catch (\Throwable $e) {
            // Don't break boot if DB is unavailable (e.g. during migrate).
        }
    }
}
