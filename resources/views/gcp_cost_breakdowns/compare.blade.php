@extends('layouts.app')

@section('title', 'GCP Cost Comparison · ' . $currency)

@section('content')
<style>
    /* Comparison grid — theme-aware header/footer bands and a sticky first column
       that stays readable in both light and dark mode. */
    .gcp-compare-table thead th,
    .gcp-compare-table tfoot td {
        background: var(--bs-tertiary-bg);
        border-bottom-color: var(--bs-border-color);
    }
    .gcp-compare-table td.sticky-col,
    .gcp-compare-table th.sticky-col {
        position: sticky;
        left: 0;
        z-index: 2;
        background: var(--bs-body-bg);
        box-shadow: 1px 0 0 var(--bs-border-color);
    }
    .gcp-compare-table thead th.sticky-col,
    .gcp-compare-table tfoot td.sticky-col {
        background: var(--bs-tertiary-bg);
        z-index: 3;
    }
    /* Keep the sticky project name aligned with its row highlight on hover. */
    .gcp-compare-table tbody tr:hover td { background: var(--bs-secondary-bg); }
    .gcp-compare-table tbody tr:hover td.sticky-col { background: var(--bs-secondary-bg); }

    /* Month-over-month change: cost up = red, down = green, brightened for dark. */
    .gcp-compare-table .chg { font-weight: 600; line-height: 1.2; }
    .gcp-compare-table .chg-up   { color: #d6336c; }
    .gcp-compare-table .chg-down { color: #2b8a3e; }
    [data-bs-theme="dark"] .gcp-compare-table .chg-up   { color: #ff8a95; }
    [data-bs-theme="dark"] .gcp-compare-table .chg-down { color: #5edc86; }
</style>
@php
    $sym = $isJpy ? '¥' : '$';
    // Amount with up to 6 decimals, trailing zeros trimmed (matches the sheet).
    $fmt = function ($v) {
        $s = number_format((float) $v, 6, '.', ',');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };

    // Month-over-month change badge vs the previous column. Cost up = red, down =
    // green (finance convention). Returns '' when there's no comparable baseline.
    $change = function ($curr, $prev) use ($fmt, $sym) {
        if ($curr === null || $prev === null) {
            return '';
        }
        $delta = (float) $curr - (float) $prev;
        if (abs($delta) < 0.0000005) {
            return '<div class="small text-muted"><i class="bi bi-dash"></i> 0%</div>';
        }
        $up   = $delta > 0;
        $cls  = $up ? 'chg chg-up' : 'chg chg-down';
        $icon = $up ? 'bi-caret-up-fill' : 'bi-caret-down-fill';
        $label = ((float) $prev != 0.0)
            ? number_format(abs($delta / (float) $prev * 100), 1) . '%'
            : $sym . ' ' . $fmt(abs($delta));
        return '<div class="small ' . $cls . '"><i class="bi ' . $icon . '"></i> ' . $label . '</div>';
    };
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">GCP Cost Comparison</h1>
        <div class="page-subtitle">{{ $currency }} monthly usage cost by project · {{ $year }}</div>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Year">
                @foreach($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
        </form>
        <a href="{{ route('gcp-costs.index', ['tab' => $tab, 'year' => $year]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-line text-primary"></i><strong>Monthly cost by project</strong>
        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ count($matrix) }} project(s)</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ count($months) }} month(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 gcp-compare-table">
            <thead>
                <tr>
                    <th style="min-width:220px;" class="sticky-col">Project</th>
                    @foreach($months as $m)
                    <th class="text-end text-nowrap">{{ $m['label'] }}</th>
                    @endforeach
                    <th class="text-end text-nowrap">Total ({{ $sym }})</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matrix as $project => $cells)
                @php $prev = null; @endphp
                <tr>
                    <td class="fw-semibold sticky-col">{{ $project }}</td>
                    @foreach($months as $m)
                        @php $curr = $cells[$m['id']] ?? null; @endphp
                        <td class="text-end text-nowrap">
                            {{ $curr !== null ? $sym . ' ' . $fmt($curr) : '—' }}
                            {!! $change($curr, $prev) !!}
                        </td>
                        @php $prev = $curr; @endphp
                    @endforeach
                    <td class="text-end text-nowrap fw-bold">{{ $sym }} {{ $fmt($rowTotals[$project]) }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ count($months) + 2 }}" class="text-center text-muted py-5">
                    <i class="bi bi-bar-chart-line d-block mb-2" style="font-size:1.8rem;"></i>
                    No {{ $currency }} cost data for {{ $year }}.
                </td></tr>
                @endforelse
            </tbody>
            @if(count($matrix))
            <tfoot>
                <tr class="fw-bold">
                    <td class="sticky-col">Monthly Total</td>
                    @php $prev = null; @endphp
                    @foreach($months as $m)
                        @php $curr = $colTotals[$m['id']] ?? 0; @endphp
                        <td class="text-end text-nowrap">
                            {{ $sym }} {{ $fmt($curr) }}
                            {!! $change($curr, $prev) !!}
                        </td>
                        @php $prev = $curr; @endphp
                    @endforeach
                    <td class="text-end text-nowrap">{{ $sym }} {{ $fmt($grand) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
