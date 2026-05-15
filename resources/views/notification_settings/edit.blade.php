@extends('layouts.app')
@section('title', 'Notification Settings')
@section('content')
@php
    $modules = \App\Models\NotificationSetting::MODULES;
    $moduleIcons = [
        'pc_assets'          => 'bi-pc-display',
        'devices'            => 'bi-hdd-network',
        'subscriptions'      => 'bi-calendar-event',
        'licenses_contracts' => 'bi-file-earmark-text',
    ];
    $moduleHints = [
        'pc_assets'          => 'Warranty expiry reminders for PC assets.',
        'devices'            => 'Warranty expiry reminders for devices.',
        'subscriptions'      => 'Renewal reminders based on subscription expire date.',
        'licenses_contracts' => 'Renewal reminders based on license / contract expire date.',
    ];
    $supported = ['subscriptions', 'licenses_contracts'];
    $activeTab = session('active_tab', 'subscriptions');
    if (! array_key_exists($activeTab, $modules)) $activeTab = 'subscriptions';
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Notification Settings</h1>
        <div class="page-subtitle">Configure per-module reminder emails &mdash; window, recipients, and on/off.</div>
    </div>
    <a href="{{ route('mail-settings.edit') }}" class="quick-action">
        <i class="bi bi-envelope-gear"></i> Mail Settings
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <ul class="nav nav-tabs notification-tabs" role="tablist">
            @foreach($modules as $key => $label)
                @php
                    $setting = $settings[$key] ?? null;
                    $isActive = $key === $activeTab;
                    $isSupported = in_array($key, $supported);
                @endphp
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $isActive ? 'active' : '' }}" id="tab-{{ $key }}"
                            data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}" type="button"
                            role="tab" aria-controls="pane-{{ $key }}" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                        <i class="bi {{ $moduleIcons[$key] }}"></i>
                        <span class="ms-1">{{ $label }}</span>
                        @if($isSupported && $setting && $setting->enabled)
                            <span class="badge bg-success-subtle text-success-emphasis ms-1" title="Notifications enabled">on</span>
                        @elseif(! $isSupported)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" title="Not yet supported">soon</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content p-4">
            @foreach($modules as $key => $label)
                @php
                    $setting = $settings[$key] ?? null;
                    $isActive = $key === $activeTab;
                    $isSupported = in_array($key, $supported);
                    $errorKey = "recipients_{$key}";
                @endphp
                <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}" id="pane-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}">

                    @if(! $isSupported)
                        {{-- Coming-soon placeholder for PC and Device until they have a real warranty-end date column --}}
                        <div class="text-center py-5">
                            <div class="coming-soon-icon mx-auto mb-3"><i class="bi {{ $moduleIcons[$key] }}"></i></div>
                            <h5 class="mb-2">{{ $label }} reminders &mdash; coming soon</h5>
                            <p class="text-muted mb-0">
                                {{ $label }} currently uses a free-text <code>warranty</code> field, which the reminder engine can't parse for a specific expiry date.<br>
                                Once a <code>warranty_end_date</code> column is added to the schema, this tab will let you configure reminders just like Subscriptions.
                            </p>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="module-tab-icon"><i class="bi {{ $moduleIcons[$key] }}"></i></span>
                            <div>
                                <div class="fw-semibold">{{ $label }}</div>
                                <div class="text-muted small">{{ $moduleHints[$key] }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('notification-settings.update', $key) }}">
                            @csrf @method('PUT')

                            {{-- Enable / source toggle (reuses mail-enable-card pattern) --}}
                            <div class="mail-enable-card card mb-3 {{ $setting->enabled ? 'is-on' : '' }}">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="mail-enable-icon">
                                        <i class="bi bi-bell"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Send reminders for {{ $label }}</div>
                                        <div class="text-muted small">When off, no notifications or emails are generated for this module.</div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input type="hidden" name="enabled" value="0">
                                        <input type="checkbox" name="enabled" value="1" id="enabled_{{ $key }}" class="form-check-input notification-enable-toggle" role="switch" @checked($setting->enabled) style="width: 3rem; height: 1.6rem;">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Reminder days before expiry <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="days_before" min="1" max="365" value="{{ old('days_before', $setting->days_before ?? 30) }}" class="form-control @error('days_before') is-invalid @enderror" required>
                                        <span class="input-group-text">days</span>
                                    </div>
                                    <small class="text-muted">Records within this window trigger reminders.</small>
                                    @error('days_before')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Recipients</label>
                                    <textarea name="recipients" rows="3" class="form-control @error($errorKey) is-invalid @enderror" placeholder="One or more emails — separate with comma, semicolon, or newline.&#10;e.g. ops@company.com, admin@company.com">{{ old('recipients', $setting->recipients) }}</textarea>
                                    <small class="text-muted">Leave empty to fall back to all admin users' emails.</small>
                                    @error($errorKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary"><i class="bi bi-check2"></i> Save {{ $label }} settings</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="text-muted small mt-3">
    <i class="bi bi-info-circle"></i>
    Notifications are sent by the daily <code>app:check-expirations</code> command at 09:00. Email transport is configured in
    <a href="{{ route('mail-settings.edit') }}">Mail Settings</a>.
</div>

<style>
    .notification-tabs {
        border-bottom: 1px solid rgba(31, 38, 135, 0.08);
        padding: .5rem .5rem 0;
        margin-bottom: 0;
        gap: .25rem;
    }
    .notification-tabs .nav-link {
        border: 1px solid transparent;
        border-radius: .55rem .55rem 0 0;
        color: #475569;
        font-size: .88rem;
        font-weight: 500;
        padding: .55rem .85rem;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .notification-tabs .nav-link:hover {
        color: #0d6efd;
        background: rgba(13, 110, 253, 0.04);
        border-color: transparent;
    }
    .notification-tabs .nav-link.active {
        color: #0d6efd;
        background: #fff;
        border-color: rgba(31, 38, 135, 0.08) rgba(31, 38, 135, 0.08) #fff;
        font-weight: 600;
    }

    .module-tab-icon {
        width: 40px; height: 40px;
        border-radius: .55rem;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .coming-soon-icon {
        width: 72px; height: 72px;
        border-radius: .85rem;
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    [data-bs-theme="dark"] .notification-tabs { border-bottom-color: rgba(255, 255, 255, 0.06); }
    [data-bs-theme="dark"] .notification-tabs .nav-link { color: #cfd8dc; }
    [data-bs-theme="dark"] .notification-tabs .nav-link:hover { background: rgba(147, 197, 253, 0.06); color: #93c5fd; }
    [data-bs-theme="dark"] .notification-tabs .nav-link.active {
        background: #1a1f29;
        color: #93c5fd;
        border-color: rgba(255, 255, 255, 0.06) rgba(255, 255, 255, 0.06) #1a1f29;
    }
    [data-bs-theme="dark"] .module-tab-icon { background: rgba(147, 197, 253, 0.15); color: #93c5fd; }
    [data-bs-theme="dark"] .coming-soon-icon { background: rgba(255, 255, 255, 0.05); color: #cfd8dc; }
</style>

<script>
    (function () {
        // Wire each enable-toggle to its own card visual sync
        document.querySelectorAll('.notification-enable-toggle').forEach(input => {
            const card = input.closest('.mail-enable-card');
            input.addEventListener('change', () => {
                card?.classList.toggle('is-on', input.checked);
            });
        });
    })();
</script>
@endsection
