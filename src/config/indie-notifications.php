<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and middleware for notification routes.
    | Routes registered: index, recent (JSON), markAsRead, markAllAsRead, destroy
    |
    */

    'route_prefix' => 'notifications',

    'route_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The layout that the notifications index page extends.
    | Change this to match your application's layout.
    |
    */

    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Number of notifications per page on the full notifications index page.
    |
    */

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Recent Notifications Limit
    |--------------------------------------------------------------------------
    |
    | Number of notifications shown in the bell dropdown.
    |
    */

    'recent_limit' => 10,

    /*
    |--------------------------------------------------------------------------
    | Auto-Refresh Interval (milliseconds)
    |--------------------------------------------------------------------------
    |
    | How often the bell dropdown polls for new notifications.
    | Set to 0 to disable auto-refresh.
    |
    */

    'refresh_interval' => 30000,

    /*
    |--------------------------------------------------------------------------
    | Notification Icons
    |--------------------------------------------------------------------------
    |
    | Map notification class basenames to FontAwesome icon classes.
    | The controller checks $notification->data['icon'] first, then falls
    | back to this map, then defaults to 'fa-bell'.
    |
    | Add your application's notification types here:
    |
    */

    'icons' => [
        // Add your notification class basenames => FontAwesome icons here.
        // Falls back to 'fa-bell' for unmapped types.
        //
        // Examples:
        // 'UserRegistered'       => 'fa-user-plus',
        // 'PaymentReceived'      => 'fa-credit-card',
        // 'PaymentFailed'        => 'fa-exclamation-triangle',
        // 'SubscriptionCreated'  => 'fa-plus-circle',
        // 'SubscriptionExpiring' => 'fa-clock',
        // 'TicketReply'          => 'fa-reply',
        // 'SystemAlert'          => 'fa-exclamation-triangle',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Colors
    |--------------------------------------------------------------------------
    |
    | Map notification class basenames to Bootstrap color classes.
    | The controller checks $notification->data['color'] first, then falls
    | back to this map, then defaults to 'info'.
    |
    | Values: primary, secondary, success, danger, warning, info, light, dark
    |
    */

    'colors' => [
        // Add your notification class basenames => Bootstrap colors here.
        // Falls back to 'info' for unmapped types.
        // Values: primary, secondary, success, danger, warning, info, light, dark
        //
        // Examples:
        // 'UserRegistered'       => 'success',
        // 'PaymentReceived'      => 'success',
        // 'PaymentFailed'        => 'danger',
        // 'SubscriptionCreated'  => 'success',
        // 'SubscriptionExpiring' => 'warning',
        // 'TicketReply'          => 'info',
        // 'SystemAlert'          => 'warning',
    ],

];
