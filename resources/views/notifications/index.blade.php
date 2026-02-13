@extends(config('indie-notifications.layout', 'layouts.app'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">{{ $title }}</h3>
                    @if(auth()->user()->unreadNotifications()->count() > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-check-double"></i> {{ __('indie-notifications::messages.mark_all_read') }}
                            </button>
                        </form>
                    @endif
                </div>

                <div class="card-body p-0">
                    @if($notifications->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <p>{{ __('indie-notifications::messages.no_notifications') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    @foreach($notifications as $notification)
                                        <tr class="{{ $notification->read_at ? 'table-light' : 'table-active' }} notification-row"
                                            style="cursor: pointer;"
                                            data-toggle="collapse"
                                            data-target="#notification-details-{{ $notification->id }}">
                                            <td width="50" class="text-center">
                                                <i class="fas {{ $notification->icon }} text-{{ $notification->color }}"></i>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <strong>{{ $notification->formatted_message }}</strong>
                                                        @if(!$notification->read_at)
                                                            <span class="badge badge-primary ml-2">{{ __('indie-notifications::messages.new') }}</span>
                                                        @endif
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $notification->created_at->diffForHumans() }}
                                                        </small>
                                                        <small class="float-right text-muted">{{ __('indie-notifications::messages.click_to_expand') }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td width="150" class="text-right">
                                                @if(!$notification->read_at)
                                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); return confirm('Are you sure?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="notification-details-{{ $notification->id }}">
                                            <td colspan="3" class="bg-light">
                                                <div class="p-3">
                                                    <h6>{{ __('indie-notifications::messages.notification_details') }}</h6>
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-3">{{ __('indie-notifications::messages.type') }}</dt>
                                                        <dd class="col-sm-9">
                                                            <span class="badge badge-{{ $notification->color }}">
                                                                {{ class_basename($notification->type) }}
                                                            </span>
                                                        </dd>

                                                        <dt class="col-sm-3">{{ __('indie-notifications::messages.created') }}</dt>
                                                        <dd class="col-sm-9">
                                                            {{ $notification->created_at->format('Y-m-d H:i:s') }}
                                                            <small class="text-muted">({{ $notification->created_at->diffForHumans() }})</small>
                                                        </dd>

                                                        @if($notification->read_at)
                                                            <dt class="col-sm-3">{{ __('indie-notifications::messages.read_at') }}</dt>
                                                            <dd class="col-sm-9">
                                                                {{ $notification->read_at->format('Y-m-d H:i:s') }}
                                                                <small class="text-muted">({{ $notification->read_at->diffForHumans() }})</small>
                                                            </dd>
                                                        @endif

                                                        {{-- Generic data field renderer --}}
                                                        {{-- Loops over all notification data keys and displays them --}}
                                                        @php
                                                            $data = $notification->data;
                                                            // Keys already shown above or used internally
                                                            $excludeKeys = ['message', 'icon', 'color'];
                                                        @endphp

                                                        @foreach($data as $key => $value)
                                                            @if(!in_array($key, $excludeKeys) && !is_null($value))
                                                                <dt class="col-sm-3">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                                                <dd class="col-sm-9">
                                                                    @if(is_bool($value))
                                                                        <span class="badge badge-{{ $value ? 'success' : 'secondary' }}">{{ $value ? 'Yes' : 'No' }}</span>
                                                                    @elseif(is_array($value))
                                                                        <code>{{ json_encode($value) }}</code>
                                                                    @elseif(filter_var($value, FILTER_VALIDATE_URL))
                                                                        <a href="{{ $value }}" target="_blank">{{ $value }}</a>
                                                                    @else
                                                                        {{ $value }}
                                                                    @endif
                                                                </dd>
                                                            @endif
                                                        @endforeach
                                                    </dl>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inline script - no @push/@stack dependency, works with any layout --}}
<script>
$(document).ready(function() {
    // Handle mark as read button with AJAX
    $(document).on('submit', 'form[action*="/notifications/"][action*="/read"]', function(e) {
        e.preventDefault();

        var $form = $(this);
        var url = $form.attr('action');
        var $button = $form.find('button');
        var $row = $form.closest('tr');

        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Remove the "New" badge
                $row.find('.badge-primary').remove();

                // Change row background to light (read state)
                $row.removeClass('table-active').addClass('table-light');

                // Remove the mark as read button
                $button.closest('form').fadeOut(200, function() {
                    $(this).remove();
                });
            },
            error: function(xhr) {
                console.error('Failed to mark notification as read', xhr);
            }
        });
    });

    // Auto-mark as read when expanding details
    $('.notification-row').on('click', function() {
        var $row = $(this);
        var target = $row.attr('data-target');
        var $target = $(target);

        if (!$target.hasClass('show')) {
            // Find mark as read form and submit it via AJAX
            var $markReadForm = $row.find('form[action*="read"]');
            if ($markReadForm.length) {
                setTimeout(function() {
                    $markReadForm.submit();
                }, 300);
            }
        }
    });
});
</script>
@endsection
