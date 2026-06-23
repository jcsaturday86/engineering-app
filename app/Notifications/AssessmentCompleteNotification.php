<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentCompleteNotification extends Notification
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assessment_complete',
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'message' => "Assessment completed for {$this->application->application_number}",
        ];
    }
}
