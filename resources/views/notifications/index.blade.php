@extends(config('indie-notifications.layout', 'layouts.app'))

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-bell mr-2"></i>{{ $title }}</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <a href="{{ route('notifications.broadcast') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-bullhorn mr-1"></i>{{ __('indie-notifications::messages.broadcast') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        {{-- Filter tabs --}}
                        <ul class="nav nav-pills nav-sm">
                            <li class="nav-item">
                                <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                                    All <span class="badge badge-light ml-1">{{ $totalCount }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $filter === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}">
                                    Unread <span class="badge badge-warning ml-1">{{ $unreadCount }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $filter === 'read' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'read']) }}">
                                    Read <span class="badge badge-light ml-1">{{ $readCount }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-right">
                        @if($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check-double mr-1"></i>{{ __('indie-notifications::messages.mark_all_read') }}
                                </button>
                            </form>
                        @endif
                        @if($readCount > 0)
                            <form method="POST" action="{{ route('notifications.destroy-read') }}" class="d-inline" onsubmit="return confirm('{{ __('indie-notifications::messages.confirm_delete_read') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-broom mr-1"></i>{{ __('indie-notifications::messages.delete_read') }}
                                </button>
                            </form>
                        @endif
                        @if($totalCount > 0)
                            <form method="POST" action="{{ route('notifications.destroy-all') }}" class="d-inline" onsubmit="return confirm('{{ __('indie-notifications::messages.confirm_delete_all') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash mr-1"></i>{{ __('indie-notifications::messages.delete_all') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
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
                                    <tr class="{{ $notification->read_at ? '' : 'table-active' }} notification-row"
                                        style="cursor: pointer;"
                                        data-toggle="collapse"
                                        data-target="#notification-details-{{ $notification->id }}">
                                        <td width="50" class="text-center">
                                            <i class="fas {{ $notification->icon }} text-{{ $notification->color }}"></i>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    @if($notification->url)
                                                        <a href="{{ route('notifications.read', $notification->id) }}" class="text-dark" onclick="event.stopPropagation();">
                                                            <strong>{{ $notification->formatted_message }}</strong>
                                                        </a>
                                                    @else
                                                        <strong>{{ $notification->formatted_message }}</strong>
                                                    @endif
                                                    @if(!$notification->read_at)
                                                        <span class="badge badge-primary ml-2">{{ __('indie-notifications::messages.new') }}</span>
                                                    @endif
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </small>
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

                                                    @php
                                                        $data = $notification->data;
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
                                                                @elseif(is_string($value) && filter_var($value, FILTER_VALIDATE_URL))
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
                @endif
            </div>

            @if($notifications->hasPages())
                <div class="card-footer clearfix">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
