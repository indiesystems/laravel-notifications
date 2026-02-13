{{--
    Notification Bell Dropdown for AdminLTE navbar.

    Include this in your layout's navbar <ul class="navbar-nav ml-auto">:
        @include('indie-notifications::partials.bell-dropdown')

    Then include the scripts partial before </body>:
        @include('indie-notifications::partials.bell-scripts')

    Requirements: jQuery, FontAwesome, Bootstrap 4 (AdminLTE), toastr.js (optional, for toast messages)
--}}

<!-- Notifications Dropdown Menu -->
<li class="nav-item dropdown" id="notifications-dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" id="notifications-bell">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge" id="unread-count" style="display:none;">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notifications-menu">
        <span class="dropdown-header" id="notifications-header">
            <span id="notification-count-text">0 {{ __('indie-notifications::messages.notifications') }}</span>
        </span>
        <div class="dropdown-divider"></div>
        <div id="notifications-list">
            <div class="dropdown-item-text text-center text-muted">
                <i class="fas fa-spinner fa-spin"></i> {{ __('indie-notifications::messages.loading') }}
            </div>
        </div>
        <div class="dropdown-divider"></div>
        <div class="px-3 py-2">
            <button class="btn btn-sm btn-block btn-outline-secondary" id="mark-all-read">
                <i class="fas fa-check-double"></i> {{ __('indie-notifications::messages.mark_all_read') }}
            </button>
        </div>
        <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">
            {{ __('indie-notifications::messages.see_all') }}
        </a>
    </div>
</li>
