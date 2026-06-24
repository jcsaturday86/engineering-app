<?php

namespace App\Notifications;

use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Model $application,
        private Collection $collection,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Received - OR {$this->collection->or_number}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("Your payment has been received and recorded.")
            ->line("**Official Receipt:** {$this->collection->or_number}")
            ->line("**Application:** {$this->application->application_number}")
            ->line("**Amount Paid:** PHP " . number_format($this->collection->amount_received, 2))
            ->line("**Payment Mode:** " . ucfirst($this->collection->payment_mode))
            ->line("**Date:** {$this->collection->or_date->format('F d, Y')}")
            ->line('Thank you for your payment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_posted',
            'application_id' => $this->application->id,
            'or_number' => $this->collection->or_number,
            'amount' => $this->collection->amount_received,
            'message' => "Payment received - OR {$this->collection->or_number} for application {$this->application->application_number}",
        ];
    }
}
