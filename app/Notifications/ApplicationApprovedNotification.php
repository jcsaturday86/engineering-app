<?php

namespace App\Notifications;

use App\Models\Application;
use App\Models\Permit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Application $application,
        private Permit $permit,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Permit Generated - {$this->permit->permit_number}")
            ->greeting("Congratulations, {$notifiable->first_name}!")
            ->line("Your permit has been generated successfully.")
            ->line("**Permit Number:** {$this->permit->permit_number}")
            ->line("**Application:** {$this->application->application_number}")
            ->line("**Permit Type:** {$this->application->permitType->name}")
            ->line("**Issued Date:** {$this->permit->issued_date->format('F d, Y')}")
            ->action('View Application', url(route('applications.show', $this->application)))
            ->line('You may now claim your permit at the Engineering Office.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
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
