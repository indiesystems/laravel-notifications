{{-- Sidebar navigation link with unread badge. Include in your AdminLTE sidebar nav: --}}
{{-- @include('indie-notifications::partials.sidebar-link') --}}

@auth
@php
    $unread = auth()->user()->unreadNotifications()->count();
@endphp
<li class="nav-item">
    <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-bell"></i>
        <p>
            {{ __('indie-notifications::messages.notifications') }}
            @if($unread > 0)
                <span class="badge badge-warning right">{{ $unread > 99 ? '99+' : $unread }}</span>
            @endif
        </p>
    </a>
</li>
@endauth
