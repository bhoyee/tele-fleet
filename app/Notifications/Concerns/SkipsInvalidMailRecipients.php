<?php

namespace App\Notifications\Concerns;

trait SkipsInvalidMailRecipients
{
    private function shouldSendMailTo(object $notifiable): bool
    {
        $email = trim((string) ($notifiable->email ?? ''));
        if ($email === '') {
            return false;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $lower = strtolower($email);

        // Avoid common local/dev placeholder domains that SMTP providers reject.
        if (str_ends_with($lower, '.test') || str_contains($lower, '@tele-fleet.test')) {
            return false;
        }

        return true;
    }
}

