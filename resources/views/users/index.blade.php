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
                                    'pc-display'        => ['PC',      'pc_assets'],
                                    'hdd-network'       => ['Devices', 'devices'],
                                    'calendar-event'    => ['Subs',    'subscriptions'],
                                    'file-earmark-text' => ['L&C',     'licenses_contracts'],
                                ]; @endphp
                                <div class="d-flex gap-1 flex-wrap">
                                    @foreach($modules as $icon => [$label, $key])
                                        @php
                                            $canEdit = (bool) $u->{"can_edit_{$key}"};
                                            $canView = (bool) $u->{"can_view_{$key}"} || $canEdit;
                                            if ($canEdit) {
                                                $cls = 'bg-success-subtle text-success-emphasis';
                                                $tag = 'V·E';
                                                $title = "{$label}: view + edit";
                                            } elseif ($canView) {
                                                $cls = 'bg-primary-subtle text-primary-emphasis';
                                                $tag = 'V';
                                                $title = "{$label}: view only";
                                            } else {
                                                $cls = 'bg-light text-muted border';
                                                $tag = '—';
                                                $title = "{$label}: no access";
                                            }
                                        @endphp
                                        <span class="badge {{ $cls }}" title="{{ $title }}">
                                            <i class="bi bi-{{ $icon }}"></i> {{ $label }}
                                            <span class="ms-1 opacity-75" style="font-size:.6rem;">{{ $tag }}</span>
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
                            <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete user \'{{ addslashes($u->name) }}\'?');">
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
</style>
@endsection
