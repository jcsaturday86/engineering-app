<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification
{
    public function __construct(private Model $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $permitCode = method_exists($this->application, 'getPermitTypeCode')
            ? $this->application->getPermitTypeCode()
            : 'BP';

        return (new MailMessage)
            ->subject("Application Submitted - {$this->application->application_number}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("A new {$permitCode} application has been submitted and requires your attention.")
            ->line("**Applicant:** {$this->application->applicant_first_name} {$this->application->applicant_last_name}")
            ->line("**Application No:** {$this->application->application_number}")
            ->line('Please review this application at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        $permitCode = method_exists($this->application, 'getPermitTypeCode')
            ? $this->application->getPermitTypeCode()
            : 'BP';

        return [
            'type' => 'application_submitted',
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'message' => "{$permitCode} application {$this->application->application_number} is awaiting your review.",
            'url' => route('application-review.index'),
        ];
    }
}
