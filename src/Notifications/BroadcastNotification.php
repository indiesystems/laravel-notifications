<?php

namespace IndieSystems\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public string $icon = 'fa-bullhorn',
        public string $color = 'info',
        public ?string $sentBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'icon' => $this->icon,
            'color' => $this->color,
            'sent_by' => $this->sentBy,
        ];
    }
}
