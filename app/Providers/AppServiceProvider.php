<?php

namespace App\Providers;

use App\Models\LtUser;
use App\Support\Auth\Guards;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MariaDB/older-MySQL index-length safety for utf8mb4 (CLAUDE.md rule).
        Schema::defaultStringLength(191);

        // Reset-link URL must carry the guard (two split-table brokers), so the
        // reset page knows which broker to reset against. Guard is inferred from
        // the notifiable's model class.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $guard = $notifiable instanceof LtUser ? Guards::LT : Guards::TEAM;

            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
                'guard' => $guard,
            ], absolute: false));
        });
    }
}
