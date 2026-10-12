<?php

namespace App\Notifications;

use App\Support\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to someone the owner just made an account for.
 *
 * It never contains a password. It carries a one-time "set your password"
 * link (the same token and page as Forgot password), so the person chooses
 * their own and nothing secret travels through an inbox.
 *
 * Deliberately not queued: no queue worker runs on the shop PC, so a queued
 * email would just sit there.
 */
class AccountCreated extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly ?string $createdBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $email = $notifiable->getEmailForPasswordReset();

        $setPasswordUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ]);

        return BrandedMail::message(
            subject: "Your {$appName} account",
            name: $notifiable->name,
            lines: [
                $this->createdBy
                    ? "{$this->createdBy} made you an account on {$appName}."
                    : "An account was made for you on {$appName}.",
                "Your login is **{$email}**.",
            ],
            actionText: 'Set your password',
            actionUrl: $setPasswordUrl,
            footnote: 'This link works for '.BrandedMail::linkMinutes().' minutes. Already have a password from the owner?',
            footnoteLink: ['label' => 'Sign in', 'url' => route('login')],
        );
    }
}
