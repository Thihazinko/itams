@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Activity Log</h1>
        <div class="page-subtitle">
            Every change, login, and import &middot; {{ $logs->total() }} event{{ $logs->total() === 1 ? '' : 's' }} recorded
            @if($logs->total() > 0)
                &middot; showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }}
            @endif
        </div>
    </div>
    @if(request()->hasAny(['search','category','action','user_id','from','to']))
        <a href="{{ route('activity-logs.index') }}" class="quick-action"><i class="bi bi-x-lg"></i> Clear filters</a>
    @endif
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="form-label small mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" value="{{ request('search') }}" placeholder="Description, user...">
                </div>
            </div>
            <div class="col-xl-2 col-md-6">
                <label class="form-label small mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $key => $cfg)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $cfg['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-4">
                <label class="form-label small mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-4">
                <label class="form-label small mb-1">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-1 col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-xl-1 col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-xl-1 col-md-12 d-flex gap-1">
                <button class="btn btn-sm btn-primary w-100" title="Apply filters"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 175px;">When</th>
                    <th style="width: 220px;">User</th>
                    <th style="width: 140px;">Action</th>
                    <th>Description</th>
                    <th style="width: 150px;">Category</th>
                    <th style="width: 120px;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $actionMap = [
                            'login'        => ['bi-box-arrow-in-right',  'success'],
                            'logout'       => ['bi-box-arrow-right',     'secondary'],
                            'login_failed' => ['bi-shield-exclamation',  'danger'],
                            'created'      => ['bi-plus-circle',         'primary'],
                            'updated'      => ['bi-pencil-square',       'info'],
                            'deleted'      => ['bi-trash',               'danger'],
                            'imported'     => ['bi-upload',              'warning'],
                            'renewed'      => ['bi-arrow-repeat',        'success'],
                            'mail_test'    => ['bi-send',                'secondary'],
                        ];
                        [$icon, $tone] = $actionMap[$log->action] ?? ['bi-circle-fill', 'secondary'];
                        $initial = strtoupper(substr($log->user_name ?: ($log->user_email ?: '?'), 0, 1));
                    @endphp
                    <tr>
                        <td>
                            <div class="small fw-semibold text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                            <div class="text-muted" style="font-size: .72rem;">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($log->user_name)
                                    <span class="log-avatar">{{ $initial }}</span>
                                    <div style="min-width:0;">
                                        <div class="fw-semibold small text-truncate" style="max-width: 160px;">{{ $log->user_name }}</div>
                                        <div class="text-muted text-truncate" style="font-size: .7rem; max-width: 160px;" title="{{ $log->user_email }}">{{ $log->user_email }}</div>
                                    </div>
                                @else
                                    <span class="log-avatar log-avatar-anon">?</span>
                                    <div class="text-muted small">{{ $log->user_email ?: 'system' }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-inline-flex align-items-center gap-1">
                                <i class="bi {{ $icon }}"></i> {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <div>{{ $log->description }}</div>
                            @if(!empty($log->properties['changed_fields']))
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-pencil-square"></i>
                                    Changed: <code class="small">{{ implode(', ', $log->properties['changed_fields']) }}</code>
                                </div>
                            @endif
                            @if(!empty($log->properties['password_changed']))
                                <div class="text-muted small"><i class="bi bi-key"></i> Password changed</div>
                            @endif
                            @if($log->subject_type)
                                <div class="text-muted small">
                                    <i class="bi bi-link-45deg"></i> {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @php $cat = $log->categoryLabel(); @endphp
                            @if($cat)
                                <span class="badge rounded-pill bg-light text-dark border d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-tag"></i> {{ $cat }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">
                            @if($log->ip_address)
                                <code class="small">{{ $log->ip_address }}</code>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-50"></i>
                                <div class="fw-semibold">No activity matches your filters</div>
                                <div class="small">
                                    @if(request()->hasAny(['search','category','action','user_id','from','to']))
                                        Try adjusting or <a href="{{ route('activity-logs.index') }}">clear the filters</a>.
                                    @else
                                        No activity has been recorded yet.
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $logs->links() }}</div>

<style>
    .log-avatar {
        width: 28px;
        height: 28px;
        border-radius: .4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .log-avatar-anon {
        background: rgba(108, 117, 125, 0.15);
        color: #6c757d;
    }
</style>
@endsection
