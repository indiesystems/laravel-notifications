# Laravel Notifications Starter Package

Generic notification system for Laravel applications with AdminLTE UI.

## What This Package Provides

1. **NotificationController** - Full CRUD for database notifications: index (paginated), recent (JSON for bell dropdown), mark as read, mark all as read, delete
2. **Bell Dropdown** - AdminLTE navbar dropdown with unread badge, auto-refresh, AJAX mark-as-read
3. **Full Notifications Page** - Paginated table with expandable detail rows, read/unread state, inline actions
4. **Spatie Notification Log** - Audit trail + fingerprint-based duplicate prevention (spatie/laravel-notification-log is a composer dependency, it handles its own migration + config)
5. **Configurable** - Icons, colors, routes, middleware, pagination, refresh interval all via config

## Architecture

### How Notification Formatting Works

The controller formats notifications for display using a **3-layer resolution** for message, icon, and color:

1. **`$notification->data['message']`** / `data['icon']` / `data['color']` - If the notification class includes these in its `toArray()` method, the controller uses them directly. This is the **recommended approach for new notification types** - no controller or config changes needed.

2. **Config maps** (`indie-notifications.icons` and `indie-notifications.colors`) - Class basename mapped to icon/color. For messages, the controller has a switch statement.

3. **Defaults** - `fa-bell` icon, `info` color, class basename as message.

So when adding a new notification type, the **easiest path** is to include `message`, `icon`, `color` in `toArray()`. If you want centralized control, add to the config maps and the controller switch.

### Spatie Notification Log Integration

This is the **key feature** for duplicate prevention. The `spatie/laravel-notification-log` package is a composer dependency - it auto-discovers its own service provider, runs its own migration (`notification_log_items` table), and manages its own config. We do NOT duplicate any of that.

To customize Spatie's config:
```bash
php artisan vendor:publish --provider="Spatie\NotificationLog\NotificationLogServiceProvider"
```

The default prune is 30 days. The pattern for using it in notification classes:

```php
use Spatie\NotificationLog\Models\Concerns\HasHistory;

class MyNotification extends Notification implements ShouldQueue
{
    use Queueable, HasHistory;

    // Unique key for this logical notification
    public function fingerprint($notifiable): string
    {
        return "thing-{$this->model->id}-failed-" . now()->format('Y-m-d');
    }

    // Check if already sent
    public function shouldSend($notifiable): bool
    {
        return !$this->wasSentTo($notifiable, withSameFingerprint: true)
            ->inThePastMinutes(24 * 60);
    }
}
```

The `notification_log_items` table stores every sent notification with its fingerprint. `shouldSend()` queries this table to prevent duplicates.

**When to use fingerprinting:**
- Notifications triggered by cron jobs (runs daily but should only notify once)
- Notifications from events that might fire multiple times (retries, queue restarts)
- Alert-type notifications where you want a cooldown (e.g., max 1 per hour)

### User Model Requirements

The consuming app's User model needs these traits:

```php
use Illuminate\Notifications\Notifiable;
use Spatie\NotificationLog\Models\Concerns\HasNotifiableHistory;

class User extends Authenticatable
{
    use Notifiable, HasNotifiableHistory;
}
```

- `Notifiable` - Laravel's built-in trait, provides `$user->notify()`, `$user->notifications()`, `$user->unreadNotifications()`
- `HasNotifiableHistory` - Spatie's trait, enables `wasSentTo()` queries in notification classes

### Frontend Dependencies

The views assume these are loaded (standard AdminLTE stack):
- **jQuery 3.x** - AJAX calls
- **Bootstrap 4** - Dropdowns, tables, badges, collapse
- **FontAwesome 5** - Notification icons
- **toastr.js** (optional) - Toast messages on mark-all-read
- **`<meta name="csrf-token">`** - Required in `<head>` for AJAX POST requests

### How the Bell Dropdown Works

1. Page loads -> `loadNotifications()` fires AJAX GET to `/notifications/recent`
2. Controller returns JSON: `{ notifications: [...], unread_count: N }`
3. Each notification object includes `formatted_message`, `icon`, `color`, `created_at_human`
4. JS renders the dropdown list items
5. `setInterval` polls every 30 seconds (configurable)
6. Clicking a notification -> AJAX POST to mark as read -> reloads dropdown
7. "Mark all as read" -> AJAX POST -> toastr success -> reloads dropdown

All AJAX URLs are generated server-side via `route()` helper, so they work regardless of app base path or route prefix.

### Fixes Applied vs Original central-indie Code

- **markAllAsRead()** - Original returned `redirect()->back()` for all requests, but the bell dropdown calls it via AJAX and expects JSON. Fixed to check `request()->wantsJson() || request()->ajax()`.
- **Bell dropdown mark-as-read URL** - Original used hardcoded `/notifications/{id}/read` path. Fixed to use `route()` helper so it works with any route prefix or app base path.
- **Index page inline JS** - Original used `@push('scripts')` which requires `@stack('scripts')` in the layout (the original layout used `@yield('scripts')`, so this was silently broken). Fixed to use inline `<script>` inside the content section - works with any layout.
- **Notifications migration** - Removed. This is Laravel's standard `notifications` table (`php artisan notifications:table`). Not our table to own.
- **Spatie migration** - Removed. Spatie's package auto-loads its own `notification_log_items` migration. Duplicating it would cause "table already exists" errors.

## Installation in a New App

### 1. Add to composer.json

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/indiesystems/laravel-notifications"
        }
    ],
    "require": {
        "indiesystems/laravel-notifications": "*"
    }
}
```

Then `composer update`.

Or for a VCS-based install:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/indiesystems/laravel-notifications.git"
        }
    ]
}
```

### 2. Publish config (optional - auto-discovered)

```bash
php artisan vendor:publish --tag=indie-notifications-config
```

### 3. Run migrations

If your app doesn't already have the standard Laravel notifications table:
```bash
php artisan notifications:table
```

Then run migrations (this also creates Spatie's `notification_log_items` table):
```bash
php artisan migrate
```

### 4. Add traits to User model

```php
use Illuminate\Notifications\Notifiable;
use Spatie\NotificationLog\Models\Concerns\HasNotifiableHistory;

class User extends Authenticatable
{
    use Notifiable, HasNotifiableHistory;
}
```

### 5. Add bell dropdown to your layout

In your AdminLTE layout's navbar (`<ul class="navbar-nav ml-auto">`):

```blade
@include('indie-notifications::partials.bell-dropdown')
```

Before `</body>`, after jQuery is loaded:

```blade
@include('indie-notifications::partials.bell-scripts')
```

### 6. Publish views for customization (optional)

```bash
php artisan vendor:publish --tag=indie-notifications-views
```

Views will be in `resources/views/vendor/indie-notifications/`.

## Config Reference

```php
// config/indie-notifications.php

'route_prefix'     => 'notifications',    // URL prefix
'route_middleware'  => ['web', 'auth'],    // Middleware stack
'layout'           => 'layouts.app',      // Layout for index page
'per_page'         => 20,                 // Pagination on index
'recent_limit'     => 10,                 // Bell dropdown count
'refresh_interval' => 30000,              // Auto-refresh ms (0 = off)
'icons'            => [...],              // ClassName => fa-icon
'colors'           => [...],              // ClassName => bootstrap-color
```

## Routes Provided

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | /notifications | notifications.index | Full page with pagination |
| GET | /notifications/recent | notifications.recent | JSON API for bell dropdown |
| POST | /notifications/{id}/read | notifications.read | Mark one as read |
| POST | /notifications/read-all | notifications.read-all | Mark all as read |
| DELETE | /notifications/{id} | notifications.destroy | Delete notification |

## Adding New Notification Types

### Option A: Self-describing (recommended for new types)

Include `message`, `icon`, `color` in `toArray()`:

```php
public function toArray($notifiable): array
{
    return [
        'model_id' => $this->model->id,
        'message' => 'Your thing is ready: ' . $this->model->name,
        'icon' => 'fa-check-circle',
        'color' => 'success',
    ];
}
```

No controller or config changes needed. It just works.

### Option B: Config + Controller (centralized control)

1. Add icon to `config/indie-notifications.php` `icons` array
2. Add color to `config/indie-notifications.php` `colors` array
3. Add case to `NotificationController::getNotificationMessage()` switch
4. Add translation key to `lang/en/messages.php`

### Example notification classes

See `src/Notifications/Examples/` for:
- `SampleNotification.php` - Basic pattern with HasHistory
- `SampleNotificationWithDedup.php` - Fingerprinting + duplicate prevention

## File Structure

```
├── composer.json
├── config/
│   ├── indie-notifications.php      # Main config (icons, colors, routes, etc.)
│   └── notification-log.php         # Reference copy of Spatie's config (not auto-published)
├── resources/
│   ├── lang/en/messages.php         # All UI + notification message translations
│   └── views/
│       ├── notifications/
│       │   └── index.blade.php      # Full notifications page
│       └── partials/
│           ├── bell-dropdown.blade.php  # Navbar dropdown HTML
│           └── bell-scripts.blade.php   # Bell widget JavaScript
├── routes/
│   └── web.php                      # 5 notification routes
└── src/
    ├── IndieNotificationsServiceProvider.php
    ├── Http/Controllers/
    │   └── NotificationController.php
    └── Notifications/Examples/
        ├── SampleNotification.php
        └── SampleNotificationWithDedup.php
```

Note: No migrations are shipped. The `notifications` table is Laravel's standard (`php artisan notifications:table`). The `notification_log_items` table is provided by `spatie/laravel-notification-log` which auto-discovers its own service provider + migration.

## What's NOT in This Package (add per app)

- **Actual notification classes** - These are app-specific. Use the examples as templates.
- **Event listeners/subscribers** - Wire up your app's events to dispatch notifications.
- **Announcements system** - central-indie has a full announcements system with targeting, scheduling, etc. This package only covers the core notifications infrastructure.
- **Broadcasting/WebSocket** - Infrastructure exists in central-indie (Echo channel 'alerts') but is disabled. Can be added later.
- **Per-user notification preferences** - Settings are system/plan-level via SettingsService. User-level prefs can be added.
- **SMS/Slack/Push channels** - Only mail + database channels. Add more as needed.

## Origin

Extracted from `central-indie` (Laravel 10 SaaS). The original code uses:
- `spatie/laravel-notification-log ^1.3` for audit trail + dedup
- AdminLTE 3 + Bootstrap 4 for UI
- jQuery for AJAX notification management
- FontAwesome 5 for icons
- toastr.js for toast messages
