@php
    // Tab navigation shared by the Device Master pages: the device list and the
    // Repair Logs module. The active tab is derived from the current route.
    $deviceCount = \App\Models\Device::count();
    $repairCount = \App\Models\DeviceRepairLog::count();
    $activeTab   = request()->routeIs('device-repair-logs.*') ? 'repair' : 'devices';
@endphp
<ul class="nav nav-pills gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $activeTab === 'devices' ? 'active' : '' }}" href="{{ route('devices.index') }}">
            <i class="bi bi-hdd-network"></i> Devices
            <span class="badge rounded-pill {{ $activeTab === 'devices' ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($deviceCount) }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $activeTab === 'repair' ? 'active' : '' }}" href="{{ route('device-repair-logs.index') }}">
            <i class="bi bi-tools"></i> Repair Logs
            <span class="badge rounded-pill {{ $activeTab === 'repair' ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($repairCount) }}</span>
        </a>
    </li>
</ul>
