<?php

namespace PHPinnacle\Casus\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use PHPinnacle\Casus\Models\Exception;

class ExceptionReported extends Notification
{
    public function __construct(
        public Exception $exception,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('phpinnacle-casus::resources.exception.notifications.reported.subject'))
            ->line(__('phpinnacle-casus::resources.exception.notifications.reported.summary', [
                'type' => $this->exception->type,
                'message' => $this->exception->message,
            ]))
            ->line(__('phpinnacle-casus::resources.exception.notifications.reported.occurrences', [
                'count' => $this->exception->occurrences,
            ]));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
