<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        ini_set('upload_max_filesize', '10M');
        ini_set('post_max_size', '12M');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL')
                . '/reset-password?token=' . $token
                . '&email=' . urlencode($user->email);
        });
    }
}
