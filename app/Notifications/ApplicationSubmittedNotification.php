<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Application $application)
    {
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
            ->subject("Application Submitted - {$this->application->application_number}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("A new application has been submitted and requires your attention.")
            ->line("**Applicant:** {$this->application->applicant_first_name} {$this->application->applicant_last_name}")
            ->line("**Permit Type:** {$this->application->permitType->name}")
            ->line("**Project Title:** {$this->application->project_title}")
            ->action('View Application', url(route('applications.show', $this->application)))
            ->line('Please review this application at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'application_submitted',
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'message' => "New application submitted: {$this->application->application_number}",
        ];
    }
}
