@php
    // Tab navigation shared by the PC Master pages: the PC list and the
    // Repair Logs module. The active tab is derived from the current route.
    $pcCount       = \App\Models\PcAsset::count();
    $disposedCount = \App\Models\PcAsset::onlyTrashed()->count();
    $repairCount   = \App\Models\RepairLog::count();
    $isDisposed  = request()->routeIs('pc-assets.*') && request('view') === 'disposed';
    $activeTab   = request()->routeIs('repair-logs.*') ? 'repair' : ($isDisposed ? 'disposed' : 'pc');
@endphp
<ul class="nav nav-pills gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $activeTab === 'pc' ? 'active' : '' }}" href="{{ route('pc-assets.index') }}">
            <i class="bi bi-pc-display"></i> PC Assets
            <span class="badge rounded-pill {{ $activeTab === 'pc' ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($pcCount) }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $activeTab === 'repair' ? 'active' : '' }}" href="{{ route('repair-logs.index') }}">
            <i class="bi bi-tools"></i> Repair Logs
            <span class="badge rounded-pill {{ $activeTab === 'repair' ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($repairCount) }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $activeTab === 'disposed' ? 'active' : '' }}" href="{{ route('pc-assets.index', ['view' => 'disposed']) }}">
            <i class="bi bi-archive"></i> Disposed
            <span class="badge rounded-pill {{ $activeTab === 'disposed' ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">{{ number_format($disposedCount) }}</span>
        </a>
    </li>
</ul>
