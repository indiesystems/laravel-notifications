{{--
    Notification Bell Widget JavaScript.

    Include this before </body> in your layout:
        @include('indie-notifications::partials.bell-scripts')

    This powers the bell dropdown: AJAX loading, mark-as-read, auto-refresh, toast alerts.
    Handles deferred jQuery loading (Vite, module scripts, etc.)
--}}

<script>
// Notification Bell Widget — waits for jQuery if loaded via deferred/module script
(function initBellWidget() {
    if (typeof $ === 'undefined' || typeof $.fn === 'undefined') {
        setTimeout(initBellWidget, 50);
        return;
    }

    $(document).ready(function() {
        var notificationReadUrl = '{{ route("notifications.read", ["id" => "__ID__"]) }}';
        var lastUnreadCount = -1;

        function loadNotifications() {
            $.ajax({
                url: '{{ route("notifications.recent") }}',
                method: 'GET',
                success: function(response) {
                    // Toast on new notifications (skip initial load)
                    if (lastUnreadCount >= 0 && response.unread_count > lastUnreadCount) {
                        var diff = response.unread_count - lastUnreadCount;
                        showNotificationToast(diff, response.notifications);
                    }
                    lastUnreadCount = response.unread_count;
                    updateNotificationBell(response);
                },
                error: function(xhr) {
                    console.error('Failed to load notifications', xhr);
                }
            });
        }

        function showNotificationToast(count, notifications) {
            // Find the newest unread notification for the toast message
            var newest = null;
            for (var i = 0; i < notifications.length; i++) {
                if (!notifications[i].read_at) {
                    newest = notifications[i];
                    break;
                }
            }
            var msg = newest ? newest.formatted_message : (count + ' new notification' + (count > 1 ? 's' : ''));

            if (typeof toastr !== 'undefined') {
                toastr.info(msg, '{{ __("indie-notifications::messages.new_notification") }}');
            }
        }

        function updateNotificationBell(data) {
            var unreadCount = data.unread_count;
            var $unreadBadge = $('#unread-count');
            var $notificationsList = $('#notifications-list');
            var $countText = $('#notification-count-text');

            if (unreadCount > 0) {
                $unreadBadge.text(unreadCount > 99 ? '99+' : unreadCount).show();
            } else {
                $unreadBadge.hide();
            }

            $countText.text(unreadCount + ' ' + (unreadCount === 1 ? '{{ __("indie-notifications::messages.notification") }}' : '{{ __("indie-notifications::messages.notifications") }}'));

            if (data.notifications.length === 0) {
                $notificationsList.html(
                    '<div class="dropdown-item-text text-center text-muted">' +
                    '<i class="far fa-bell-slash"></i> {{ __("indie-notifications::messages.no_new_notifications") }}' +
                    '</div>'
                );
            } else {
                var html = '';
                data.notifications.forEach(function(notification) {
                    var message = notification.formatted_message || 'New notification';
                    var timeAgo = notification.created_at_human || notification.created_at;
                    var url = notification.url;
                    var unreadDot = notification.read_at ? '' : '<span class="badge badge-warning" style="width:8px;height:8px;padding:0;border-radius:50;position:absolute;top:8px;right:8px;"></span>';

                    html += '<a href="#" class="dropdown-item notification-item" data-id="' + notification.id + '"' +
                        (url ? ' data-url="' + url + '"' : '') +
                        ' style="position:relative;white-space:normal;">' + unreadDot +
                        '<div class="media">' +
                        '<i class="fas ' + notification.icon + ' text-' + notification.color + ' mr-3 mt-1"></i>' +
                        '<div class="media-body">' +
                        '<p class="text-sm mb-0">' + message + '</p>' +
                        '<p class="text-xs text-muted mb-0">' +
                        '<i class="far fa-clock"></i> ' + timeAgo +
                        '</p></div></div></a>' +
                        '<div class="dropdown-divider"></div>';
                });
                $notificationsList.html(html);
            }
        }

        // Mark notification as read when clicked — navigate to URL if available
        $(document).on('click', '.notification-item', function(e) {
            e.preventDefault();
            var $item = $(this);
            var notificationId = $item.data('id');
            var url = $item.data('url');

            $.ajax({
                url: notificationReadUrl.replace('__ID__', notificationId),
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    if (url) {
                        window.location.href = url;
                    } else {
                        loadNotifications();
                    }
                }
            });
        });

        // Mark all as read
        $('#mark-all-read').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            $.ajax({
                url: '{{ route("notifications.read-all") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || '{{ __("indie-notifications::messages.all_marked_read") }}');
                    }
                    loadNotifications();
                }
            });
        });

        // Load notifications when dropdown is opened
        $('#notifications-bell').on('click', function() {
            loadNotifications();
        });

        // Initial load
        loadNotifications();

        // Auto-refresh
        var refreshInterval = {{ config('indie-notifications.refresh_interval', 30000) }};
        if (refreshInterval > 0) {
            setInterval(loadNotifications, refreshInterval);
        }
    });
})();
</script>
