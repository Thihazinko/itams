@extends('layouts.app')
@section('title', 'User Management')
@section('content')
@php
    $kpiTotal  = (int) ($kpis['total']  ?? 0);
    $kpiAdmin  = (int) ($kpis['admins'] ?? 0);
    $kpiUser   = (int) ($kpis['users']  ?? 0);
    $base = route('users.index');
    $totalKpi = !request('search') && !request('role');
    $adminKpi = request('role') === 'admin';
    $userKpi  = request('role') === 'user';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <div class="page-subtitle">Accounts, roles, and per-module access for your team.</div>
    </div>
    <a href="{{ route('users.create') }}" class="quick-action quick-action-primary">
        <i class="bi bi-plus-circle"></i> Add User
    </a>
</div>

<div class="stat-row mb-3" style="--stat-cols: 3;">
    <a class="stat-cell stat-link {{ $totalKpi ? 'is-active' : '' }}"
       href="{{ $base }}"
       style="--stat-color: #0d6efd;"
       title="Show all users">
        <span class="stat-icon"><i class="bi bi-people"></i></span>
        <div class="stat-body">
            <div class="stat-label">Total Users</div>
            <div class="stat-value">{{ number_format($kpiTotal) }}</div>
            <div class="stat-foot">All accounts</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $adminKpi ? 'is-active' : '' }}"
       href="{{ $base . '?role=admin' }}"
       style="--stat-color: #f59e0b;"
       title="Show only admins">
        <span class="stat-icon"><i class="bi bi-shield-lock"></i></span>
        <div class="stat-body">
            <div class="stat-label">Admins</div>
            <div class="stat-value">{{ number_format($kpiAdmin) }}</div>
            <div class="stat-foot">Full access</div>
        </div>
    </a>
    <a class="stat-cell stat-link {{ $userKpi ? 'is-active' : '' }}"
       href="{{ $base . '?role=user' }}"
       style="--stat-color: #10b981;"
       title="Show only standard users">
        <span class="stat-icon"><i class="bi bi-person"></i></span>
        <div class="stat-body">
            <div class="stat-label">Users</div>
            <div class="stat-value">{{ number_format($kpiUser) }}</div>
            <div class="stat-foot">Module-restricted access</div>
        </div>
    </a>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 ps-0" placeholder="Search name or email...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="user"  @selected(request('role') === 'user')>User</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['search','role']))
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Module Access</th>
                    <th>Joined</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $u)
                    @php $isMe = $u->id === auth()->id(); @endphp
                    <tr>
                        <td class="text-muted small">{{ ($users->firstItem() ?? 1) + $i }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($u->avatar)
                                    <img src="{{ asset('storage/' . $u->avatar) }}" alt="" class="user-row-avatar">
                                @else
                                    <span class="user-row-avatar gradient-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                @endif
                                <div style="min-width:0;">
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        {{ $u->name }}
                                        @if($isMe)<span class="badge bg-primary-subtle text-primary-emphasis" style="font-size:.62rem;">You</span>@endif
                                    </div>
                                    <div class="text-muted small text-truncate" style="max-width: 260px;" title="{{ $u->email }}">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($u->isAdmin())
                                <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-shield-lock"></i> Admin</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-person"></i> User</span>
                            @endif
                        </td>
                        <td>
                            @if($u->isAdmin())
                                <span class="text-muted small">All modules</span>
                            @else
                                @php $modules = [
                                    'pc_assets'            => ['PC',      'pc-display'],
                                    'subscriptions'        => ['Subs',    'calendar-event'],
                                    'licenses_contracts'   => ['L&C',     'file-earmark-text'],
                                    'devices'              => ['Devices', 'hdd-network'],
                                    'email_master'         => ['Email',   'envelope-at'],
                                    'financial_management' => ['Finance', 'cash-coin'],
                                    'gcp_costs'            => ['GCP',      'cloud'],
                                ]; @endphp
                                <div class="d-flex gap-1 flex-wrap module-access-badges">
                                    @foreach($modules as $key => [$label, $icon])
                                        @php
                                            $fullLabel = \App\Models\User::MODULES[$key] ?? $label;
                                            $canEdit = (bool) $u->{"can_edit_{$key}"};
                                            $canView = (bool) $u->{"can_view_{$key}"} || $canEdit;
                                            if ($canEdit) {
                                                $cls = 'module-badge-edit';
                                                $tag = 'V·E';
                                                $title = "{$fullLabel}: view + edit";
                                            } elseif ($canView) {
                                                $cls = 'module-badge-view';
                                                $tag = 'V';
                                                $title = "{$fullLabel}: view only";
                                            } else {
                                                $cls = 'module-badge-none';
                                                $tag = '—';
                                                $title = "{$fullLabel}: no access";
                                            }
                                        @endphp
                                        <span class="badge module-badge {{ $cls }}" title="{{ $title }}">
                                            <i class="bi bi-{{ $icon }}"></i> {{ $label }}
                                            <span class="module-badge-tag">{{ $tag }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-muted small text-nowrap" title="{{ $u->created_at?->format('Y-m-d H:i') }}">
                            {{ $u->created_at?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="text-end text-nowrap pe-3">
                            <a href="{{ route('users.edit', $u) }}" class="btn-icon-soft" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                            @if(!$isMe)
                            <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline"
                                  data-app-confirm
                                  data-confirm-title="Delete this user?"
                                  data-confirm-label="{{ $u->name }} ({{ $u->email }})"
                                  data-confirm-action="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-soft text-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                <div class="fw-semibold">No users found</div>
                                <div class="small">
                                    @if(request()->hasAny(['search','role']))
                                        Try clearing the filters or <a href="{{ route('users.index') }}">view all</a>.
                                    @else
                                        <a href="{{ route('users.create') }}">Add the first user</a> to get started.
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

<div class="mt-3">{{ $users->links() }}</div>

<style>
    .user-row-avatar {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid rgba(31, 38, 135, 0.1);
    }
    .gradient-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        font-size: .85rem;
        font-weight: 700;
        border: 0;
    }

    .module-access-badges { max-width: 22rem; }
    .module-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .28rem .5rem;
        font-size: .72rem;
        font-weight: 600;
        border-radius: .4rem;
        border: 1px solid transparent;
        line-height: 1;
    }
    .module-badge i { font-size: .82rem; }
    .module-badge-tag {
        font-size: .58rem;
        font-weight: 700;
        letter-spacing: .02em;
        padding: .08rem .28rem;
        border-radius: .3rem;
        background: rgba(0, 0, 0, 0.06);
    }
    .module-badge-edit { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .module-badge-view { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
    .module-badge-none { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; }
    .module-badge-none .module-badge-tag { background: rgba(100, 116, 139, 0.12); }

    [data-bs-theme="dark"] .module-badge-edit { background: rgba(52, 211, 153, 0.16); color: #6ee7b7; border-color: rgba(52, 211, 153, 0.3); }
    [data-bs-theme="dark"] .module-badge-view { background: rgba(147, 197, 253, 0.16); color: #93c5fd; border-color: rgba(147, 197, 253, 0.3); }
    [data-bs-theme="dark"] .module-badge-none { background: rgba(255, 255, 255, 0.04); color: #64748b; border-color: rgba(255, 255, 255, 0.1); }
    [data-bs-theme="dark"] .module-badge-tag { background: rgba(255, 255, 255, 0.08); }
</style>
@endsection
