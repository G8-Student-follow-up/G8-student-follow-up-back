<?php

namespace App\Providers;

use App\Models\Card;
use App\Models\Comment;
use App\Models\Attachment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use App\Observers\CommentObserver;

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
        Comment::observe(CommentObserver::class);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('FRONTEND_URL')
                . '/reset-password?token=' . $token
                . '&email=' . urlencode($user->email);
        });

        Route::bind('card', fn($value) => Card::with('board.workspace')->findOrFail($value));
        Route::bind('comment', fn($value) => Comment::with('card.board')->findOrFail($value));
        Route::bind('attachment', fn($value) => Attachment::with('card.board')->findOrFail($value));
    }
}