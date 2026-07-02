@php
    // $active: 'pos' | 'receipts'. Counts ($poCount, $receiptCount) are optional —
    // a badge shows only when its count is provided.
    $active = $active ?? 'pos';
    $periodQs = $periodQs ?? [];
    $pill = fn ($on) => $on ? 'bg-light text-dark' : 'bg-secondary-subtle text-secondary-emphasis';
@endphp
<ul class="nav nav-pills gap-2 mb-3">
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $active === 'pos' ? 'active' : '' }}" href="{{ route('financial-pos.index', $periodQs) }}">
            <i class="bi bi-card-list"></i> Purchase Orders
            @isset($poCount)<span class="badge rounded-pill {{ $pill($active === 'pos') }}">{{ number_format($poCount) }}</span>@endisset
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 {{ $active === 'receipts' ? 'active' : '' }}" href="{{ route('financial-pos.index', array_merge(['tab' => 'receipts'], $periodQs)) }}">
            <i class="bi bi-receipt"></i> Receipts
            @isset($receiptCount)<span class="badge rounded-pill {{ $pill($active === 'receipts') }}">{{ number_format($receiptCount) }}</span>@endisset
        </a>
    </li>
</ul>
