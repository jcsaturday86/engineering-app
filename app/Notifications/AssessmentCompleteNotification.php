<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class AssessmentCompleteNotification extends Notification
{
    use Queueable;

    public function __construct(private Model $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

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
