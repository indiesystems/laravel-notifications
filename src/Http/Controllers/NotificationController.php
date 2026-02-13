<?php

namespace IndieSystems\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use IndieSystems\Notifications\Notifications\BroadcastNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();

        $filter = $request->get('filter', 'all');
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(config('indie-notifications.per_page', 20));

        // Preserve filter in pagination links
        $notifications->appends($request->only('filter'));

        // Format notifications
        $notifications->getCollection()->transform(function ($notification) {
            $notification->formatted_message = $this->getNotificationMessage($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            $notification->color = $this->getNotificationColor($notification);
            $notification->url = $notification->data['url'] ?? null;
            return $notification;
        });

        $unreadCount = Auth::user()->unreadNotifications()->count();
        $readCount = Auth::user()->readNotifications()->count();
        $totalCount = Auth::user()->notifications()->count();

        return view('indie-notifications::notifications.index', [
            'title' => __('indie-notifications::messages.title'),
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'totalCount' => $totalCount,
        ]);
    }

    public function recent()
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->limit(config('indie-notifications.recent_limit', 10))
            ->get();

        $notifications->transform(function ($notification) {
            $notification->formatted_message = $this->getNotificationMessage($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            $notification->color = $this->getNotificationColor($notification);
            $notification->created_at_human = $notification->created_at->diffForHumans();
            $notification->url = $notification->data['url'] ?? null;
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

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        // Redirect to notification URL if it has one
        $url = $notification->data['url'] ?? null;
        if ($url) {
            return redirect($url);
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

    public function destroyRead()
    {
        $count = Auth::user()->readNotifications()->count();
        Auth::user()->readNotifications()->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->back()->with('success', __('indie-notifications::messages.read_deleted', ['count' => $count]));
    }

    public function destroyAll()
    {
        $count = Auth::user()->notifications()->count();
        Auth::user()->notifications()->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->back()->with('success', __('indie-notifications::messages.all_deleted', ['count' => $count]));
    }

    /**
     * Show broadcast form (admin).
     */
    public function broadcastForm()
    {
        $roles = [];
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name')->toArray();
        }

        return view('indie-notifications::notifications.broadcast', [
            'roles' => $roles,
            'userCount' => User::count(),
        ]);
    }

    /**
     * Send broadcast notification (admin).
     */
    public function broadcastSend(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|in:primary,secondary,success,danger,warning,info',
            'target' => 'required|in:all,role',
            'role' => 'required_if:target,role|nullable|string',
        ]);

        $query = User::query();

        if ($request->get('target') === 'role' && $request->get('role')) {
            $query->role($request->get('role'));
        }

        $users = $query->get();
        $count = $users->count();

        Notification::send($users, new BroadcastNotification(
            message: $request->get('message'),
            icon: $request->get('icon', 'fa-bullhorn'),
            color: $request->get('color', 'info'),
            sentBy: Auth::user()->name,
        ));

        return redirect()->route('notifications.broadcast')
            ->with('success', __('indie-notifications::messages.broadcast_sent', ['count' => $count]));
    }

    /**
     * Get formatted message for a notification.
     */
    protected function getNotificationMessage($notification): string
    {
        $data = $notification->data;

        if (isset($data['message'])) {
            return $data['message'];
        }

        $className = class_basename($notification->type);

        switch ($className) {
            default:
                return $className;
        }
    }

    /**
     * Get icon for a notification.
     */
    protected function getNotificationIcon($notification): string
    {
        $data = $notification->data;

        if (isset($data['icon'])) {
            return $data['icon'];
        }

        $className = class_basename($notification->type);
        $icons = config('indie-notifications.icons', []);

        return $icons[$className] ?? 'fa-bell';
    }

    /**
     * Get color for a notification.
     */
    protected function getNotificationColor($notification): string
    {
        $data = $notification->data;

        if (isset($data['color'])) {
            return $data['color'];
        }

        $className = class_basename($notification->type);
        $colors = config('indie-notifications.colors', []);

        return $colors[$className] ?? 'info';
    }
}
