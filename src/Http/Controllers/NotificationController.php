<?php

namespace IndieSystems\Notifications\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();

        // Filter by read/unread
        if ($request->has('unread')) {
            $query->whereNull('read_at');
        }

        // Paginate
        $notifications = $query->latest()->paginate(config('indie-notifications.per_page', 20));

        // Format notifications with human-readable messages
        $notifications->getCollection()->transform(function ($notification) {
            $notification->formatted_message = $this->getNotificationMessage($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            $notification->color = $this->getNotificationColor($notification);

            return $notification;
        });

        return view('indie-notifications::notifications.index', [
            'title' => __('indie-notifications::messages.title'),
            'notifications' => $notifications,
        ]);
    }

    public function recent()
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->limit(config('indie-notifications.recent_limit', 10))
            ->get();

        // Format notifications
        $notifications->transform(function ($notification) {
            $notification->formatted_message = $this->getNotificationMessage($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            $notification->color = $this->getNotificationColor($notification);
            $notification->created_at_human = $notification->created_at->diffForHumans();

            return $notification;
        });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        // Return JSON for AJAX requests, redirect for regular form submissions
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('indie-notifications::messages.all_marked_read'),
            ]);
        }

        return redirect()->back()->with('success', __('indie-notifications::messages.all_marked_read'));
    }

    public function destroy($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('indie-notifications::messages.deleted'));
    }

    /**
     * Get formatted message for a notification.
     *
     * Resolution order:
     * 1. $notification->data['message'] - if the notification class includes it in toArray()
     * 2. Switch statement for known notification types (add your app's types here)
     * 3. Falls back to the class basename
     */
    protected function getNotificationMessage($notification): string
    {
        $data = $notification->data;

        // 1. Check if notification data includes a pre-formatted message
        if (isset($data['message'])) {
            return $data['message'];
        }

        // 2. Format based on notification type
        $className = class_basename($notification->type);

        switch ($className) {
            //
            // Add your application's notification types here. Examples:
            //
            // case 'PaymentReceived':
            //     return __('indie-notifications::messages.payment_received', [
            //         'order_number' => $data['order_number'] ?? '?',
            //         'amount' => '$' . number_format(($data['amount'] ?? 0) / 100, 2),
            //     ]);
            //
            // case 'SubscriptionExpiring':
            //     return __('indie-notifications::messages.subscription_expiring', [
            //         'name' => $data['subscription_name'] ?? 'Subscription',
            //         'days' => $data['days_until_expiry'] ?? '?',
            //     ]);
            //

            default:
                return $className;
        }
    }

    /**
     * Get icon for a notification.
     *
     * Resolution order:
     * 1. $notification->data['icon'] - if the notification class includes it in toArray()
     * 2. Config map (indie-notifications.icons)
     * 3. Falls back to 'fa-bell'
     */
    protected function getNotificationIcon($notification): string
    {
        $data = $notification->data;

        // 1. Check notification data
        if (isset($data['icon'])) {
            return $data['icon'];
        }

        // 2. Check config map
        $className = class_basename($notification->type);
        $icons = config('indie-notifications.icons', []);

        return $icons[$className] ?? 'fa-bell';
    }

    /**
     * Get color for a notification.
     *
     * Resolution order:
     * 1. $notification->data['color'] - if the notification class includes it in toArray()
     * 2. Config map (indie-notifications.colors)
     * 3. Falls back to 'info'
     */
    protected function getNotificationColor($notification): string
    {
        $data = $notification->data;

        // 1. Check notification data
        if (isset($data['color'])) {
            return $data['color'];
        }

        // 2. Check config map
        $className = class_basename($notification->type);

        // Special handling for announcements (color based on severity)
        if ($className === 'AnnouncementNotification') {
            return match ($data['severity'] ?? 'info') {
                'critical' => 'danger',
                'warning' => 'warning',
                default => 'info',
            };
        }

        $colors = config('indie-notifications.colors', []);

        return $colors[$className] ?? 'info';
    }
}
