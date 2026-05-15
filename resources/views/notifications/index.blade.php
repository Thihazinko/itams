@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
@php
    $kpiTotal  = (int) ($kpis['total']  ?? 0);
    $kpiUnread = (int) ($kpis['unread'] ?? 0);
    $kpiRead   = (int) ($kpis['read']   ?? 0);
    $base = route('notifications.index');
    $allKpi    = !request('status');
    $unreadKpi = request('status') === 'unread';
    $readKpi   = request('status') === 'read';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <div class="page-subtitle">
            @if($kpiUnread > 0)
                <strong>{{ $kpiUnread }}</strong> unread of {{ $kpiTotal }} total
            @else
                You're all caught up — {{ $kpiTotal }} notification{{ $kpiTotal === 1 ? '' : 's' }} total
            @endif
        </div>
    </div>
    @if($kpiUnread > 0)
    <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
        @csrf
        <button class="quick-action quick-action-primary" title="Mark every unread notification as read">
            <i class="bi bi-check-all"></i> Mark all as read
        </button>
    </form>
    @endif
</div>

<div class="stat-row mb-3" style="--stat-cols: 3;">
    <a class="stat-cell stat-link {{ $allKpi ? 'is-active' : '' }}"
       href="{{ $base }}"
       style="--stat-color: #0d6efd;"
       title="Show all notifications">
        <span class="stat-icon"><i class="bi bi-collection"></i></span>
        <div class="stat-body">
            <div class="stat-label">All</div>
            <div class="stat-value">{{ number_format($kpiTotal) }}</div>
            <div class="stat-foot">Across all time</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $unreadKpi ? 'is-active' : '' }}"
       href="{{ $base . '?status=unread' }}"
       style="--stat-color: #f59e0b;"
       title="Show unread notifications only">
        <span class="stat-icon"><i class="bi bi-bell-fill"></i></span>
        <div class="stat-body">
            <div class="stat-label">Unread</div>
            <div class="stat-value">{{ number_format($kpiUnread) }}</div>
            <div class="stat-foot">{{ $kpiUnread > 0 ? 'Need your attention' : 'All caught up' }}</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $readKpi ? 'is-active' : '' }}"
       href="{{ $base . '?status=read' }}"
       style="--stat-color: #10b981;"
       title="Show read notifications only">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div class="stat-body">
            <div class="stat-label">Read</div>
            <div class="stat-value">{{ number_format($kpiRead) }}</div>
            <div class="stat-foot">Already acknowledged</div>
        </div>
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse($notifications as $n)
            @php
                $isUnread = ! $n->read_at;
                $days = $n->days_remaining;
                if ($days === null && $n->subscription) {
                    $days = (int) \Carbon\Carbon::today()->diffInDays($n->subscription->expire_date, false);
                }
                if ($days !== null) {
                    $tone = $days < 0 ? 'danger' : ($days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'info'));
                } else {
                    $tone = 'info';
                }
                if (! $isUnread) $tone = 'secondary';
            @endphp
            <div class="notification-item {{ $isUnread ? 'is-unread' : '' }}">
                <span class="notification-icon bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis">
                    <i class="bi {{ $isUnread ? 'bi-bell-fill' : 'bi-bell' }}"></i>
                </span>
                <div class="notification-body">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <strong class="notification-title">{{ $n->title }}</strong>
                        @if($isUnread)<span class="badge bg-primary-subtle text-primary-emphasis">New</span>@endif
                        @if($days !== null)
                            <span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis">
                                {{ $days < 0 ? abs($days) . 'd overdue' : $days . 'd left' }}
                            </span>
                        @endif
                    </div>
                    <div class="notification-message">{{ $n->message }}</div>
                    <div class="notification-meta">
                        <span><i class="bi bi-clock"></i> {{ $n->created_at->diffForHumans() }}</span>
                        <span class="text-muted">{{ $n->created_at->format('Y-m-d H:i') }}</span>
                        @if($n->subscription)
                            <a href="{{ route('subscriptions.edit', $n->subscription) }}" class="notification-link">
                                <i class="bi bi-link-45deg"></i> View subscription
                            </a>
                        @endif
                    </div>
                </div>
                @if($isUnread)
                <form method="POST" action="{{ route('notifications.read', $n) }}" class="notification-action">
                    @csrf
                    <button type="submit" class="btn-icon-soft" title="Mark as read" aria-label="Mark as read">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
                @endif
            </div>
        @empty
            <div class="text-center py-5">
                <div class="text-muted">
                    @if(request('status') === 'unread')
                        <i class="bi bi-check2-all fs-2 d-block mb-2 opacity-50"></i>
                        <div class="fw-semibold">No unread notifications</div>
                        <div class="small">You're all caught up. <a href="{{ route('notifications.index') }}">View all</a>.</div>
                    @elseif(request('status') === 'read')
                        <i class="bi bi-bell fs-2 d-block mb-2 opacity-50"></i>
                        <div class="fw-semibold">Nothing here yet</div>
                        <div class="small">No notifications have been marked as read. <a href="{{ route('notifications.index') }}">View all</a>.</div>
                    @else
                        <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-50"></i>
                        <div class="fw-semibold">No notifications yet</div>
                        <div class="small">Renewal reminders will appear here when subscriptions or contracts approach expiry.</div>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>

<style>
    .notification-item {
        display: flex;
        gap: .85rem;
        padding: .9rem 1.1rem;
        border-bottom: 1px solid rgba(31, 38, 135, 0.06);
        align-items: flex-start;
        position: relative;
        transition: background .15s ease;
    }
    .notification-item:last-child { border-bottom: 0; }
    .notification-item:hover { background: rgba(13, 110, 253, 0.025); }
    .notification-item.is-unread {
        background: rgba(13, 110, 253, 0.04);
    }
    .notification-item.is-unread::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: #0d6efd;
    }
    .notification-icon {
        width: 38px; height: 38px;
        border-radius: .55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .notification-body { flex-grow: 1; min-width: 0; }
    .notification-title { font-size: .92rem; }
    .notification-message { font-size: .85rem; color: #475569; margin-top: .2rem; }
    .notification-meta {
        font-size: .72rem;
        color: #6c757d;
        margin-top: .35rem;
        display: flex;
        gap: .85rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .notification-link {
        color: #0d6efd;
        text-decoration: none;
        font-weight: 500;
    }
    .notification-link:hover { text-decoration: underline; }
    .notification-action { flex-shrink: 0; align-self: center; margin: 0; }

    [data-bs-theme="dark"] .notification-item { border-bottom-color: rgba(255, 255, 255, 0.06); }
    [data-bs-theme="dark"] .notification-item:hover { background: rgba(147, 197, 253, 0.04); }
    [data-bs-theme="dark"] .notification-item.is-unread { background: rgba(147, 197, 253, 0.06); }
    [data-bs-theme="dark"] .notification-item.is-unread::before { background: #93c5fd; }
    [data-bs-theme="dark"] .notification-message { color: #cbd5e0; }
    [data-bs-theme="dark"] .notification-link { color: #93c5fd; }
</style>
@endsection
