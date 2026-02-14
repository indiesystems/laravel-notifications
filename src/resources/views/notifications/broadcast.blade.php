@extends(config('indie-notifications.layout', 'layouts.app'))

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-bullhorn mr-2"></i>{{ __('indie-notifications::messages.broadcast') }}</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default btn-sm float-right" href="{{ route('notifications.index') }}">
                    <i class="fas fa-arrow-left mr-1"></i>{{ __('indie-notifications::messages.back_to_notifications') }}
                </a>
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

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <form method="POST" action="{{ route('notifications.broadcast.send') }}">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('indie-notifications::messages.send_notification') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="message">{{ __('indie-notifications::messages.message') }}</label>
                                <textarea name="message" id="message" class="form-control" rows="3" required maxlength="500" placeholder="{{ __('indie-notifications::messages.broadcast_placeholder') }}">{{ old('message') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="target">{{ __('indie-notifications::messages.target') }}</label>
                                        <select name="target" id="target" class="form-control" required>
                                            <option value="all" {{ old('target') === 'all' ? 'selected' : '' }}>
                                                {{ __('indie-notifications::messages.all_users') }} ({{ $userCount }})
                                            </option>
                                            @if(!empty($roles))
                                                <option value="role" {{ old('target') === 'role' ? 'selected' : '' }}>
                                                    {{ __('indie-notifications::messages.specific_role') }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="role-select-wrapper" style="{{ old('target') === 'role' ? '' : 'display:none;' }}">
                                    <div class="form-group">
                                        <label for="role">{{ __('indie-notifications::messages.role') }}</label>
                                        <select name="role" id="role" class="form-control">
                                            <option value="">-- Select role --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="icon">{{ __('indie-notifications::messages.icon') }}</label>
                                        <select name="icon" id="icon" class="form-control">
                                            <option value="fa-bullhorn">Announcement (bullhorn)</option>
                                            <option value="fa-info-circle">Info (info-circle)</option>
                                            <option value="fa-exclamation-triangle">Warning (exclamation-triangle)</option>
                                            <option value="fa-check-circle">Success (check-circle)</option>
                                            <option value="fa-wrench">Maintenance (wrench)</option>
                                            <option value="fa-star">Feature (star)</option>
                                            <option value="fa-gift">Promotion (gift)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="color">{{ __('indie-notifications::messages.color') }}</label>
                                        <select name="color" id="color" class="form-control">
                                            <option value="info">Info (blue)</option>
                                            <option value="success">Success (green)</option>
                                            <option value="warning">Warning (yellow)</option>
                                            <option value="danger">Danger (red)</option>
                                            <option value="primary">Primary (blue)</option>
                                            <option value="secondary">Secondary (gray)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('{{ __('indie-notifications::messages.confirm_broadcast') }}')">
                                <i class="fas fa-paper-plane mr-1"></i>{{ __('indie-notifications::messages.send') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-eye mr-1"></i>{{ __('indie-notifications::messages.preview') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="media" id="notification-preview">
                            <i class="fas fa-bullhorn text-info mr-3 mt-1" id="preview-icon"></i>
                            <div class="media-body">
                                <p class="text-sm mb-0" id="preview-message">Your message will appear here...</p>
                                <p class="text-xs text-muted mb-0"><i class="far fa-clock"></i> just now</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
var targetSelect = document.getElementById('target');
var roleWrapper = document.getElementById('role-select-wrapper');
var messageInput = document.getElementById('message');
var iconSelect = document.getElementById('icon');
var colorSelect = document.getElementById('color');
var previewMessage = document.getElementById('preview-message');
var previewIcon = document.getElementById('preview-icon');

if (targetSelect) {
    targetSelect.addEventListener('change', function() {
        roleWrapper.style.display = this.value === 'role' ? '' : 'none';
    });
}

if (messageInput) {
    messageInput.addEventListener('input', function() {
        previewMessage.textContent = this.value || 'Your message will appear here...';
    });
}

if (iconSelect) {
    iconSelect.addEventListener('change', function() {
        previewIcon.className = 'fas ' + this.value + ' text-' + colorSelect.value + ' mr-3 mt-1';
    });
}

if (colorSelect) {
    colorSelect.addEventListener('change', function() {
        previewIcon.className = 'fas ' + iconSelect.value + ' text-' + this.value + ' mr-3 mt-1';
    });
}
</script>
@endsection
