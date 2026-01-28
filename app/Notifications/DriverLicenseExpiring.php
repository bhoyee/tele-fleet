<?php

namespace App\Notifications;

use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverLicenseExpiring extends Notification
{
    use Queueable;

    public function __construct(
        public Driver $driver
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiry = $this->driver->license_expiry?->format('M d, Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("Driver License Expiry Reminder - {$this->driver->full_name}")
            ->greeting('Hello,')
            ->line("The driver's license for {$this->driver->full_name} is expiring soon.")
            ->line("License Number: {$this->driver->license_number}")
            ->line("Expiry Date: {$expiry}")
            ->line('Please renew the license before the expiry date to stay compliant.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'driver_license_expiring',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'license_number' => $this->driver->license_number,
            'license_expiry' => $this->driver->license_expiry?->toDateString(),
        ];
    }
}
