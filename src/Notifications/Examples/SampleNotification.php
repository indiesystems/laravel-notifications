<?php

/**
 * EXAMPLE: Basic notification with HasHistory trait for Spatie notification log.
 *
 * This shows the standard pattern used across all our apps:
 * - Implements ShouldQueue (async via queue)
 * - Uses HasHistory trait for audit trail
 * - Uses SettingsService to check if notifications are enabled
 * - Sends via both 'mail' and 'database' channels
 *
 * Copy this to your app's App\Notifications\ directory and customize.
 */

namespace IndieSystems\Notifications\Notifications\Examples;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\NotificationLog\Models\Concerns\HasHistory;

class SampleNotification extends Notification implements ShouldQueue
{
    use Queueable, HasHistory;

    public function __construct(
        public $model // Replace with your actual model type
    ) {}

    public function via(object $notifiable): array
    {
        // Optional: check settings to conditionally disable
        // $settings = app(\App\Services\SettingsService::class);
        // $enabled = $settings->get('alert_something_enabled', $this->model->plan, true);
        // if (!$enabled) {
        //     return [];
        // }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Something happened')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Something happened with your resource.')
            ->action('View Details', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Data stored in the notifications table.
     *
     * TIP: Include 'message', 'icon', and 'color' keys here and the
     * NotificationController will use them automatically without needing
     * to update the controller's switch statement or config maps.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'model_id' => $this->model->id,
            // Optional: self-describing notification (no controller changes needed)
            // 'message' => 'Something happened with ' . $this->model->name,
            // 'icon' => 'fa-info-circle',
            // 'color' => 'info',
        ];
    }
}
