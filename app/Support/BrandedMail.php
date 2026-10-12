<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * The one look for every email GTrack sends (welcome, forgot password, ...):
 * logo, "Hi {name},", a line or two, one button, a small footnote.
 *
 * Laravel's stock mail template adds "Hello!", "Regards" and an "All rights
 * reserved" footer, and shows the full URL again under the button. Both kinds
 * of email go through here so they can't drift apart.
 *
 * Lines may use **bold**. The views are resources/views/emails/branded*.
 */
class BrandedMail
{
    /**
     * @param  array<int, string>  $lines  Paragraphs under the greeting.
     * @param  array{label: string, url: string}|null  $footnoteLink  Appended to the footnote.
     */
    public static function message(
        string $subject,
        string $name,
        array $lines,
        string $actionText,
        string $actionUrl,
        string $footnote,
        ?array $footnoteLink = null,
    ): MailMessage {
        return (new MailMessage)
            ->subject($subject)
            ->view(
                ['emails.branded', 'emails.branded-text'],
                [
                    'name' => $name,
                    'lines' => $lines,
                    'actionText' => $actionText,
                    'actionUrl' => $actionUrl,
                    'footnote' => $footnote,
                    'footnoteLink' => $footnoteLink,
                ],
            );
    }

    /** Minutes a password link stays valid (config/auth.php). */
    public static function linkMinutes(): int
    {
        return (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
    }
}
