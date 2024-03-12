<?php

namespace App\Notifications;

use NotificationChannels\Webhook\{WebhookChannel, WebhookMessage};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Bus\Queueable;

class DisbursementNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Transaction $transaction){}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [ WebhookChannel::class ];
    }

    public function toWebhook($notifiable): WebhookMessage
    {
        $application = config('app.name');

        return WebhookMessage::create()
            ->data([
                'payload' => [
                    'webhook' => $this->transaction->meta
                ]
            ])
            ->userAgent("Custom-User-Agent")
            ->header('X-Custom', 'Custom-Header');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
