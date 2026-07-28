<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class ApplicationReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Model $application,
        private string $decision,
        private ?string $remarks = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->decision === 'approved'
            ? "Your application {$this->application->application_number} was approved by Engineering and has moved to the next stage."
            : "Your application {$this->application->application_number} was returned for revision" . ($this->remarks ? ": {$this->remarks}" : '.');

        return [
            'type' => $this->decision === 'approved' ? 'application_approved' : 'application_returned',
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'message' => $message,
        ];
    }
}
