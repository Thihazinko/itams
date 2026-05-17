@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
@php
    $total      = (int) ($summary['total']     ?? 0);
    $overdue    = (int) ($summary['overdue']   ?? 0);
    $dueSoon    = (int) ($summary['due_soon']  ?? 0);
    $upcoming   = (int) ($summary['upcoming']  ?? 0);
    $byModule   = $summary['by_module'] ?? [];
    $subInfo    = $byModule['subscriptions']      ?? ['enabled' => false, 'total' => 0, 'overdue' => 0, 'due_soon' => 0];
    $lcInfo     = $byModule['licenses_contracts'] ?? ['enabled' => false, 'total' => 0, 'overdue' => 0, 'due_soon' => 0];

    $base = route('notifications.index');
    $allKpi = ! $module;
    $subKpi = $module === 'subscriptions';
    $lcKpi  = $module === 'licenses_contracts';

    $headerTone = $overdue > 0 ? 'danger' : ($dueSoon > 0 ? 'warning' : ($total > 0 ? 'info' : 'success'));
    $settingsUrl = route('notification-settings.edit');

    // Build a foot line like "3 overdue · 5 this week" for the All / per-module cells.
    $footFor = function (array $info) {
        if (! $info['enabled']) return 'Notifications off';
        if (($info['total'] ?? 0) === 0) return 'Nothing due';
        $bits = [];
        if (($info['overdue']  ?? 0) > 0) $bits[] = $info['overdue']  . ' overdue';
        if (($info['due_soon'] ?? 0) > 0) $bits[] = $info['due_soon'] . ' this week';
        if (empty($bits)) $bits[] = 'All upcoming';
        return implode(' · ', $bits);
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title d-flex align-items-center gap-2 flex-wrap">
            Notifications
            <span class="live-pill" title="Counts reflect the current expire status in real time">
                <span class="live-dot"></span> Live
            </span>
        </h1>
        <div class="page-subtitle">
            @if($total === 0)
                You're all caught up &mdash; nothing is within the reminder window
            @elseif($overdue > 0)
                <strong class="text-danger-emphasis">{{ $overdue }}</strong> overdue
                @if($dueSoon > 0) &middot; <strong>{{ $dueSoon }}</strong> due this week @endif
                @if($upcoming > 0) &middot; {{ $upcoming }} upcoming @endif
            @elseif($dueSoon > 0)
                <strong class="text-warning-emphasis">{{ $dueSoon }}</strong> due this week
                @if($upcoming > 0) &middot; {{ $upcoming }} upcoming @endif
            @else
                {{ $upcoming }} upcoming reminder{{ $upcoming === 1 ? '' : 's' }} &mdash; nothing overdue
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if($unreadAll > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                @csrf
                <button class="quick-action quick-action-primary" title="Mark every unread notification as read for you">
                    <i class="bi bi-check2-all"></i> Mark all as read
                </button>
            </form>
        @endif
        <a href="{{ $settingsUrl }}" class="quick-action">
            <i class="bi bi-bell-fill"></i> Notification Settings
        </a>
    </div>
</div>

@php
    $statusBase = request()->except(['status', 'page']);
    $statusBaseQuery = http_build_query($statusBase);
    $statusLink = function (?string $s) use ($statusBaseQuery) {
        $q = $statusBaseQuery;
        if ($s) $q = ($q ? $q . '&' : '') . 'status=' . $s;
        return route('notifications.index') . ($q ? '?' . $q : '');
    };
@endphp

<div class="status-chip-row mb-3">
    <a class="status-chip {{ $status === null ? 'is-active' : '' }}" href="{{ $statusLink(null) }}">
        All <span class="status-chip-count">{{ number_format($unreadAll + $readAll) }}</span>
    </a>
    <a class="status-chip status-chip--unread {{ $status === 'unread' ? 'is-active' : '' }}" href="{{ $statusLink('unread') }}">
        <i class="bi bi-bell-fill"></i> Unread <span class="status-chip-count">{{ number_format($unreadAll) }}</span>
    </a>
    <a class="status-chip status-chip--read {{ $status === 'read' ? 'is-active' : '' }}" href="{{ $statusLink('read') }}">
        <i class="bi bi-check2-circle"></i> Read <span class="status-chip-count">{{ number_format($readAll) }}</span>
    </a>
</div>

<div class="stat-row mb-3" style="--stat-cols: 3;">
    {{-- All --}}
    <a class="stat-cell stat-link {{ $allKpi ? 'is-active' : '' }}"
       href="{{ $base }}"
       style="--stat-color: {{ $headerTone === 'danger' ? '#ef4444' : ($headerTone === 'warning' ? '#f59e0b' : '#0d6efd') }};"
       title="Show notifications from all modules">
        <span class="stat-icon"><i class="bi bi-collection"></i></span>
        <div class="stat-body">
            <div class="stat-label">All</div>
            <div class="stat-value">{{ number_format($total) }}</div>
            <div class="stat-foot">
                @if($total === 0)
                    Nothing due
                @else
                    @if($overdue > 0)<span class="text-danger-emphasis fw-semibold">{{ $overdue }} overdue</span>@endif
                    @if($overdue > 0 && $dueSoon > 0) &middot; @endif
                    @if($dueSoon > 0)<span class="text-warning-emphasis fw-semibold">{{ $dueSoon }} this week</span>@endif
                    @if($overdue === 0 && $dueSoon === 0) All upcoming @endif
                @endif
            </div>
        </div>
    </a>

    {{-- Subscriptions --}}
    @php $subStatTone = $subInfo['enabled'] ? ($subInfo['overdue'] > 0 ? '#ef4444' : '#f59e0b') : '#94a3b8'; @endphp
    <a class="stat-cell stat-link {{ $subKpi ? 'is-active' : '' }} {{ ! $subInfo['enabled'] ? 'is-disabled' : '' }}"
       href="{{ $subInfo['enabled'] ? $base . '?module=subscriptions' : $settingsUrl }}"
       style="--stat-color: {{ $subStatTone }};"
       title="{{ $subInfo['enabled'] ? 'Show subscription reminders only' : 'Subscription notifications are off — click to configure' }}">
        <span class="stat-icon"><i class="bi bi-calendar-event"></i></span>
        <div class="stat-body">
            <div class="stat-label">
                Subscriptions
                @if(! $subInfo['enabled'])
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" style="font-size:.6rem;">off</span>
                @endif
            </div>
            <div class="stat-value">{{ number_format($subInfo['total']) }}</div>
            <div class="stat-foot">
                @if(! $subInfo['enabled'])
                    Enable in <span class="text-decoration-underline">Notification Settings</span>
                @elseif($subInfo['total'] === 0)
                    Nothing due
                @else
                    @if($subInfo['overdue'] > 0)<span class="text-danger-emphasis fw-semibold">{{ $subInfo['overdue'] }} overdue</span>@endif
                    @if($subInfo['overdue'] > 0 && $subInfo['due_soon'] > 0) &middot; @endif
                    @if($subInfo['due_soon'] > 0)<span class="text-warning-emphasis fw-semibold">{{ $subInfo['due_soon'] }} this week</span>@endif
                    @if($subInfo['overdue'] === 0 && $subInfo['due_soon'] === 0) All upcoming @endif
                @endif
            </div>
        </div>
    </a>

    {{-- Licenses & Contracts --}}
    @php $lcStatTone = $lcInfo['enabled'] ? ($lcInfo['overdue'] > 0 ? '#ef4444' : '#10b981') : '#94a3b8'; @endphp
    <a class="stat-cell stat-link {{ $lcKpi ? 'is-active' : '' }} {{ ! $lcInfo['enabled'] ? 'is-disabled' : '' }}"
       href="{{ $lcInfo['enabled'] ? $base . '?module=licenses_contracts' : $settingsUrl }}"
       style="--stat-color: {{ $lcStatTone }};"
       title="{{ $lcInfo['enabled'] ? 'Show license & contract reminders only' : 'License & contract notifications are off — click to configure' }}">
        <span class="stat-icon"><i class="bi bi-file-earmark-text"></i></span>
        <div class="stat-body">
            <div class="stat-label">
                Licenses &amp; Contracts
                @if(! $lcInfo['enabled'])
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" style="font-size:.6rem;">off</span>
                @endif
            </div>
            <div class="stat-value">{{ number_format($lcInfo['total']) }}</div>
            <div class="stat-foot">
                @if(! $lcInfo['enabled'])
                    Enable in <span class="text-decoration-underline">Notification Settings</span>
                @elseif($lcInfo['total'] === 0)
                    Nothing due
                @else
                    @if($lcInfo['overdue'] > 0)<span class="text-danger-emphasis fw-semibold">{{ $lcInfo['overdue'] }} overdue</span>@endif
                    @if($lcInfo['overdue'] > 0 && $lcInfo['due_soon'] > 0) &middot; @endif
                    @if($lcInfo['due_soon'] > 0)<span class="text-warning-emphasis fw-semibold">{{ $lcInfo['due_soon'] }} this week</span>@endif
                    @if($lcInfo['overdue'] === 0 && $lcInfo['due_soon'] === 0) All upcoming @endif
                @endif
            </div>
        </div>
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse($notifications as $n)
            @php
                $days = (int) $n->days_remaining;
                $isOverdue = $days < 0;
                $isDueSoon = $days >= 0 && $days <= 7;
                if ($isOverdue) { $tone = 'danger'; $statusText = abs($days) . 'd overdue'; }
                elseif ($days === 0) { $tone = 'danger'; $statusText = 'Due today'; }
                elseif ($isDueSoon) { $tone = 'warning'; $statusText = $days . 'd left'; }
                elseif ($days <= 14) { $tone = 'warning'; $statusText = $days . 'd left'; }
                else { $tone = 'info'; $statusText = $days . 'd left'; }
                $relative = $isOverdue
                    ? \Carbon\Carbon::today()->diffForHumans($n->expire_date, ['parts' => 1])
                    : 'in ' . $n->expire_date->diffForHumans(\Carbon\Carbon::today(), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
            @endphp
            @php
                $statusBadgeClass = ($isOverdue || $days === 0)
                    ? 'status-badge status-badge--urgent'
                    : ($isDueSoon ? 'status-badge status-badge--soon' : 'status-badge status-badge--upcoming');
                $statusIcon = $isOverdue
                    ? 'bi-exclamation-triangle-fill'
                    : ($days === 0 ? 'bi-lightning-fill' : 'bi-clock');
            @endphp
            <div class="notification-item {{ $isOverdue ? 'is-overdue' : ($isDueSoon ? 'is-due-soon' : '') }} {{ $n->is_read ? 'is-read' : '' }}">
                <span class="notification-icon notification-icon--{{ $n->module }}">
                    <i class="bi {{ $n->module_icon }}"></i>
                </span>
                <div class="notification-body">
                    <div class="notification-title-block">
                        <strong class="notification-title">{{ $n->title }}</strong>
                        <span class="badge module-badge module-badge--{{ $n->module }}">{{ $n->module_label }}</span>
                        <span class="badge {{ $statusBadgeClass }}">
                            <i class="bi {{ $statusIcon }}"></i>
                            {{ $statusText }}
                        </span>
                        @if($n->is_read)
                            <span class="badge read-badge" title="You marked this as read"><i class="bi bi-check2"></i> Read</span>
                        @endif
                    </div>
                    <div class="notification-message" title="{{ $n->message }}">{{ $n->message }}</div>
                    <div class="notification-meta">
                        <span title="{{ $n->expire_date->format('l, F j, Y') }}">
                            <i class="bi bi-calendar"></i>
                            {{ $n->expire_date->format('Y-m-d') }}
                            <span class="notification-meta-relative">&middot; {{ $relative }}</span>
                        </span>
                        <a href="{{ route($n->link_route, $n->link_param) }}" class="notification-link">
                            Open <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
                @if(! $n->is_read)
                    <form method="POST" action="{{ route('notifications.read', ['module' => $n->module, 'id' => $n->record_id]) }}" class="notification-action m-0">
                        @csrf
                        <button type="submit" class="notification-read-btn" title="Mark as read" aria-label="Mark as read">
                            <i class="bi bi-check2"></i>
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-5">
                <div class="text-muted">
                    @if($module === 'subscriptions')
                        @if(! $subInfo['enabled'])
                            <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-50"></i>
                            <div class="fw-semibold">Subscription notifications are off</div>
                            <div class="small">
                                <a href="{{ $settingsUrl }}">Enable them in Notification Settings</a>
                                or <a href="{{ $base }}">view all</a>.
                            </div>
                        @else
                            <i class="bi bi-calendar-check fs-2 d-block mb-2 opacity-50"></i>
                            <div class="fw-semibold">No subscription reminders right now</div>
                            <div class="small">Nothing is within the reminder window. <a href="{{ $base }}">View all</a>.</div>
                        @endif
                    @elseif($module === 'licenses_contracts')
                        @if(! $lcInfo['enabled'])
                            <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-50"></i>
                            <div class="fw-semibold">License &amp; contract notifications are off</div>
                            <div class="small">
                                <a href="{{ $settingsUrl }}">Enable them in Notification Settings</a>
                                or <a href="{{ $base }}">view all</a>.
                            </div>
                        @else
                            <i class="bi bi-file-earmark-check fs-2 d-block mb-2 opacity-50"></i>
                            <div class="fw-semibold">No license &amp; contract reminders right now</div>
                            <div class="small">Nothing is within the reminder window. <a href="{{ $base }}">View all</a>.</div>
                        @endif
                    @else
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 opacity-50 text-success"></i>
                        <div class="fw-semibold">You're all caught up</div>
                        <div class="small">No records are within their reminder window. Adjust windows in <a href="{{ $settingsUrl }}">Notification Settings</a>.</div>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>

<style>
    /* Live indicator next to the page title */
    .live-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .68rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #16a34a;
        background: rgba(22, 163, 74, 0.1);
        border: 1px solid rgba(22, 163, 74, 0.25);
        border-radius: 999px;
        padding: .15rem .55rem;
        line-height: 1;
        vertical-align: middle;
    }
    .live-pill .live-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #16a34a;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.6);
        animation: live-pulse 2.2s ease-out infinite;
    }
    @keyframes live-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.5); }
        70%  { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
        100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }
    @media (prefers-reduced-motion: reduce) { .live-pill .live-dot { animation: none; } }

    /* Disabled stat-cell shows as muted, still clickable to take user to settings */
    .stat-cell.is-disabled .stat-value { color: #94a3b8 !important; }
    .stat-cell.is-disabled .stat-icon { opacity: .55; }

    /* Status filter chips (All / Unread / Read) */
    .status-chip-row { display: flex; gap: .5rem; flex-wrap: wrap; }
    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        color: #475569;
        background: #fff;
        border: 1px solid rgba(31, 38, 135, 0.12);
        text-decoration: none;
        transition: background .12s ease, border-color .12s ease, color .12s ease;
    }
    .status-chip:hover { color: #0d6efd; border-color: rgba(13, 110, 253, 0.3); background: rgba(13, 110, 253, 0.04); }
    .status-chip.is-active {
        color: #0d6efd;
        background: rgba(13, 110, 253, 0.1);
        border-color: rgba(13, 110, 253, 0.4);
    }
    .status-chip--unread.is-active { color: #b45309; background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.4); }
    .status-chip--read.is-active { color: #047857; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.4); }
    .status-chip-count {
        font-size: .7rem;
        font-weight: 700;
        background: rgba(31, 38, 135, 0.08);
        color: inherit;
        padding: .05rem .4rem;
        border-radius: 999px;
        min-width: 1.5rem;
        text-align: center;
        line-height: 1.2;
    }

    .notification-item {
        display: flex;
        gap: .65rem;
        padding: .5rem .85rem;
        border-bottom: 1px solid rgba(31, 38, 135, 0.06);
        align-items: flex-start;
        position: relative;
        transition: background .15s ease;
    }
    .notification-item:last-child { border-bottom: 0; }
    .notification-item:hover { background: rgba(13, 110, 253, 0.025); }

    /* Reserve the colored left bar + tinted bg for the genuinely urgent rows */
    .notification-item.is-overdue { background: rgba(239, 68, 68, 0.045); }
    .notification-item.is-overdue:hover { background: rgba(239, 68, 68, 0.08); }
    .notification-item.is-overdue::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
        background: #ef4444;
    }
    .notification-item.is-due-soon::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
        background: #f59e0b;
    }

    .notification-icon {
        width: 28px; height: 28px;
        border-radius: .4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        flex-shrink: 0;
        margin-top: .1rem;
    }
    /* Per-module icon hue — so the row identifies its module at a glance */
    .notification-icon--subscriptions      { background: rgba(245, 158, 11, 0.14); color: #b45309; }
    .notification-icon--licenses_contracts { background: rgba(16, 185, 129, 0.14); color: #047857; }
    .notification-body {
        flex-grow: 1;
        min-width: 0;
        line-height: 1.35;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .notification-title-block {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        flex: 0 1 auto;
        min-width: 0;
        flex-wrap: wrap;
    }
    .notification-title {
        font-size: .8rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 26ch;
    }
    .notification-message {
        font-size: .72rem;
        color: #64748b;
        margin: 0;
        line-height: 1.35;
        flex: 1 1 200px;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notification-meta {
        font-size: .68rem;
        color: #6c757d;
        margin: 0;
        display: inline-flex;
        gap: .7rem;
        align-items: center;
        flex: 0 0 auto;
        white-space: nowrap;
        margin-left: auto;
    }
    .notification-item .badge {
        font-size: .62rem;
        padding: .2em .5em;
        font-weight: 600;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .notification-item .badge i { font-size: .65rem; }

    /* Module chip — colored by module so subs vs L&C are distinguishable in a list of red rows */
    .module-badge--subscriptions      { background: rgba(245, 158, 11, 0.12); color: #92400e; }
    .module-badge--licenses_contracts { background: rgba(16, 185, 129, 0.12); color: #065f46; }

    /* Status chip — solid red for urgent so it reads as the single dominant signal */
    .status-badge--urgent {
        background: #dc2626;
        color: #fff;
        box-shadow: 0 1px 2px rgba(220, 38, 38, 0.25);
    }
    .status-badge--soon {
        background: rgba(245, 158, 11, 0.18);
        color: #92400e;
    }
    .status-badge--upcoming {
        background: rgba(14, 165, 233, 0.14);
        color: #075985;
    }

    .notification-link {
        color: #0d6efd;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .15rem;
        padding: .1rem .35rem;
        border-radius: .3rem;
        transition: background .12s ease, color .12s ease;
    }
    .notification-link:hover {
        text-decoration: none;
        background: rgba(13, 110, 253, 0.1);
        color: #0a58ca;
    }
    .notification-meta-relative { color: #94a3b8; }

    /* Lift contrast of secondary text on red-tinted rows so meta and message stay readable */
    .notification-item.is-overdue .notification-message { color: #475569; }
    .notification-item.is-overdue .notification-meta { color: #475569; }
    .notification-item.is-overdue .notification-meta-relative { color: #94a3b8; }
    .notification-item.is-overdue .notification-link { color: #0a58ca; }

    /* Per-item "mark as read" round button */
    .notification-action { flex-shrink: 0; align-self: center; }
    .notification-read-btn {
        width: 28px; height: 28px;
        border-radius: 50%;
        border: 1px solid rgba(31, 38, 135, 0.15);
        background: #fff;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        cursor: pointer;
        transition: background .12s ease, color .12s ease, border-color .12s ease, transform .12s ease;
    }
    .notification-read-btn:hover {
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
        border-color: rgba(16, 185, 129, 0.4);
        transform: scale(1.05);
    }

    /* Read-state visual: row fades, icon dims, badges desaturate */
    .notification-item.is-read { opacity: .58; }
    .notification-item.is-read.is-overdue { background: transparent; }
    .notification-item.is-read.is-overdue::before,
    .notification-item.is-read.is-due-soon::before { background: #cbd5e1; }
    .notification-item.is-read .status-badge--urgent { background: #94a3b8; box-shadow: none; }
    .notification-item.is-read:hover { opacity: .85; }

    .read-badge {
        background: rgba(16, 185, 129, 0.14);
        color: #047857;
    }

    [data-bs-theme="dark"] .live-pill { color: #6ee7b7; background: rgba(110, 231, 183, 0.08); border-color: rgba(110, 231, 183, 0.25); }
    [data-bs-theme="dark"] .live-pill .live-dot { background: #6ee7b7; }
    [data-bs-theme="dark"] .notification-item { border-bottom-color: rgba(255, 255, 255, 0.06); }
    [data-bs-theme="dark"] .notification-item:hover { background: rgba(147, 197, 253, 0.04); }
    [data-bs-theme="dark"] .notification-item.is-overdue { background: rgba(239, 68, 68, 0.08); }
    [data-bs-theme="dark"] .notification-item.is-overdue:hover { background: rgba(239, 68, 68, 0.12); }
    [data-bs-theme="dark"] .notification-message { color: #cbd5e0; }
    [data-bs-theme="dark"] .notification-link { color: #93c5fd; }
    [data-bs-theme="dark"] .notification-link:hover { background: rgba(147, 197, 253, 0.12); color: #bfdbfe; }
    [data-bs-theme="dark"] .stat-cell.is-disabled .stat-value { color: #64748b !important; }

    [data-bs-theme="dark"] .notification-icon--subscriptions      { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }
    [data-bs-theme="dark"] .notification-icon--licenses_contracts { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
    [data-bs-theme="dark"] .module-badge--subscriptions      { background: rgba(245, 158, 11, 0.18); color: #fbbf24; }
    [data-bs-theme="dark"] .module-badge--licenses_contracts { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
    [data-bs-theme="dark"] .status-badge--urgent   { background: #ef4444; color: #fff; box-shadow: 0 1px 2px rgba(239, 68, 68, 0.4); }
    [data-bs-theme="dark"] .status-badge--soon     { background: rgba(245, 158, 11, 0.22); color: #fbbf24; }
    [data-bs-theme="dark"] .status-badge--upcoming { background: rgba(56, 189, 248, 0.18); color: #7dd3fc; }
    [data-bs-theme="dark"] .notification-meta-relative { color: #64748b; }
    [data-bs-theme="dark"] .notification-item.is-overdue .notification-message,
    [data-bs-theme="dark"] .notification-item.is-overdue .notification-meta { color: #e2e8f0; }

    [data-bs-theme="dark"] .status-chip { background: rgba(255, 255, 255, 0.04); color: #cbd5e0; border-color: rgba(255, 255, 255, 0.08); }
    [data-bs-theme="dark"] .status-chip:hover { color: #93c5fd; border-color: rgba(147, 197, 253, 0.3); background: rgba(147, 197, 253, 0.06); }
    [data-bs-theme="dark"] .status-chip.is-active { color: #93c5fd; background: rgba(147, 197, 253, 0.12); border-color: rgba(147, 197, 253, 0.4); }
    [data-bs-theme="dark"] .status-chip--unread.is-active { color: #fbbf24; background: rgba(245, 158, 11, 0.18); border-color: rgba(245, 158, 11, 0.4); }
    [data-bs-theme="dark"] .status-chip--read.is-active { color: #6ee7b7; background: rgba(16, 185, 129, 0.18); border-color: rgba(16, 185, 129, 0.4); }
    [data-bs-theme="dark"] .status-chip-count { background: rgba(255, 255, 255, 0.08); }
    [data-bs-theme="dark"] .notification-read-btn { background: rgba(255, 255, 255, 0.04); color: #cbd5e0; border-color: rgba(255, 255, 255, 0.08); }
    [data-bs-theme="dark"] .notification-read-btn:hover { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.4); }
    [data-bs-theme="dark"] .read-badge { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
    [data-bs-theme="dark"] .notification-item.is-read.is-overdue::before,
    [data-bs-theme="dark"] .notification-item.is-read.is-due-soon::before { background: #475569; }
</style>
@endsection
