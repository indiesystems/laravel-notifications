<?php

/**
 * EXAMPLE: Notification with fingerprinting and duplicate prevention.
 *
 * This shows the deduplication pattern using Spatie's notification log:
 * - fingerprint() generates a unique key per logical notification
 * - shouldSend() checks if the same fingerprint was already sent recently
 * - wasSentTo() queries the notification_log_items table
 *
 * Use this pattern for notifications triggered by recurring jobs (cron)
 * or events that might fire multiple times.
 *
 * Real examples from central-indie:
 * - SubscriptionExpiring: fingerprint includes subscription ID + expiry date + warning days
 *   so 7-day and 3-day warnings are different fingerprints, but the same
 *   7-day warning won't be sent twice
 * - OrderFailed: fingerprint includes order ID + date, preventing duplicate
 *   failure notifications on the same day
 * - InstanceFailed: fingerprint includes instance ID + hour, preventing
 *   spam during prolonged failures (max 1 per hour)
 *
 * Copy this to your app's App\Notifications\ directory and customize.
 */

namespace IndieSystems\Notifications\Notifications\Examples;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\NotificationLog\Models\Concerns\HasHistory;

class SampleNotificationWithDedup extends Notification implements ShouldQueue
{
    use Queueable, HasHistory;

    public function __construct(
        public $model,
        public int $daysUntilExpiration = 7
    ) {}

    /**
     * Generate unique fingerprint for this notification.
     *
     * The fingerprint determines what counts as a "duplicate".
     * Include all dimensions that make a notification logically unique.
     *
     * Examples:
     * - Per day:    "order-{id}-failed-" . now()->format('Y-m-d')
     * - Per hour:   "instance-{id}-failed-" . now()->format('Y-m-d-H')
     * - Per period:  "sub-{id}-expires-{date}-warning-{days}d"
     */
    public function fingerprint($notifiable): string
    {
        return "model-{$this->model->id}-warning-{$this->daysUntilExpiration}d";
    }

    /**
     * Determine if notification should be sent (prevent duplicates).
     *
     * wasSentTo() queries the notification_log_items table.
     * inThePastMinutes() checks the time window.
     *
     * Common time windows:
     * - 24 * 60 (24 hours) - for daily job-triggered notifications
     * - 60 (1 hour) - for frequently-triggered alerts
     * - 7 * 24 * 60 (1 week) - for weekly digests
     */
    public function shouldSend($notifiable): bool
    {
        return ! $this->wasSentTo($notifiable, withSameFingerprint: true)->inThePastMinutes(24 * 60);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Expiring in ' . $this->daysUntilExpiration . ' days')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your resource expires in ' . $this->daysUntilExpiration . ' days.')
            ->action('Renew Now', url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'model_id' => $this->model->id,
            'days_until_expiration' => $this->daysUntilExpiration,
            // Self-describing notification:
            // 'message' => 'Your resource expires in ' . $this->daysUntilExpiration . ' days',
            // 'icon' => 'fa-clock',
            // 'color' => 'warning',
        ];
    }
}
