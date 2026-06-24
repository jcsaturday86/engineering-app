<?php

namespace App\Notifications;

use App\Models\Permit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Model $application,
        private Permit $permit,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Permit Generated - {$this->permit->permit_number}")
            ->greeting("Congratulations, {$notifiable->first_name}!")
            ->line("Your permit has been generated successfully.")
            ->line("**Permit Number:** {$this->permit->permit_number}")
            ->line("**Application:** {$this->application->application_number}")
            ->line("**Issued Date:** {$this->permit->issued_date->format('F d, Y')}")
            ->line('You may now claim your permit at the Engineering Office.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'permit_generated',
            'application_id' => $this->application->id,
            'permit_number' => $this->permit->permit_number,
            'message' => "Permit generated: {$this->permit->permit_number} for application {$this->application->application_number}",
        ];
    }
}
