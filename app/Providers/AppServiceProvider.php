<?php

namespace App\Providers;

use App\Support\BrandedMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
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
        // One pagination bar for every list: "Showing x–y of z" and page
        // numbers at phone width (see resources/views/pagination/gtrack).
        Paginator::defaultView('pagination.gtrack');

        // Forgot-password email in the same look as every other GTrack email.
        // Laravel's ResetPassword notification stays (tests and the framework
        // rely on it); only the message it builds is replaced.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $appName = config('app.name');
            $email = $notifiable->getEmailForPasswordReset();

            return BrandedMail::message(
                subject: "Reset your {$appName} password",
                name: $notifiable->name,
                lines: [
                    "We got a request to reset the password for **{$email}**.",
                ],
                actionText: 'Reset password',
                actionUrl: route('password.reset', ['token' => $token, 'email' => $email]),
                footnote: 'This link works for '.BrandedMail::linkMinutes()
                    .' minutes. If you didn\'t ask for this, ignore this email — your password won\'t change.',
            );
        });
    }
}
