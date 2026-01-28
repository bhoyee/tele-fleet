<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleMaintenanceMileageAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Vehicle $vehicle,
        public readonly string $state,
        public readonly int $threshold,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->state === 'overdue' ? 'Overdue' : 'Due';

        return (new MailMessage())
            ->subject("Vehicle Maintenance {$statusLabel} - {$this->vehicle->registration_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Maintenance is {$statusLabel} for vehicle {$this->vehicle->registration_number} ({$this->vehicle->make} {$this->vehicle->model}).")
            ->line("Current mileage: {$this->vehicle->current_mileage} km")
            ->line("Target mileage: {$this->threshold} km")
            ->line('Please schedule or complete maintenance as soon as possible.')
            ->salutation('Tele-Fleet Operations');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'registration_number' => $this->vehicle->registration_number,
            'state' => $this->state,
            'current_mileage' => $this->vehicle->current_mileage,
            'target_mileage' => $this->threshold,
        ];
    }
}
