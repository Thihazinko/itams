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
    <div class="card-header bg-transparent d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-bar-chart-line text-primary"></i><strong>Monthly cost by project</strong>
        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ count($matrix) }} project(s)</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ count($months) }} month(s)</span>
        @if(count($matrix))
        <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="View mode">
            <button type="button" class="btn btn-primary" id="gcpTableBtn" aria-pressed="true">
                <i class="bi bi-table"></i> Table
            </button>
            <button type="button" class="btn btn-outline-primary" id="gcpChartBtn" aria-pressed="false">
                <i class="bi bi-bar-chart"></i> Chart
            </button>
        </div>
        @endif
    </div>
    <div id="gcpTableView" class="table-responsive">
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
    @if(count($matrix))
    <div id="gcpChartView" class="p-3" style="display:none;">
        <div id="gcpChartLegend" class="d-flex flex-wrap gap-2 mb-3 justify-content-center"></div>
        <div style="position:relative; height:460px;">
            <canvas id="gcpCostChart"></canvas>
        </div>
        <div class="text-center small text-muted mt-2">
            Monthly {{ $currency }} cost by project · {{ $year }} — click a project to show / hide it
        </div>
    </div>
    @endif
</div>

@if(count($matrix))
@php
    // Chart series: one dataset per project (biggest-spender order preserved from
    // the matrix), each carrying its monthly cost across the year's columns.
    $chartLabels = $months->map(fn ($m) => $m['label'])->values();
    $chartSeries = [];
    foreach ($matrix as $project => $cells) {
        $data = [];
        foreach ($months as $m) {
            $data[] = round((float) ($cells[$m['id']] ?? 0), 6);
        }
        $chartSeries[] = ['label' => (string) $project, 'data' => $data];
    }
@endphp
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const tableBtn  = document.getElementById('gcpTableBtn');
    const chartBtn  = document.getElementById('gcpChartBtn');
    const tableView = document.getElementById('gcpTableView');
    const chartView = document.getElementById('gcpChartView');
    const canvas    = document.getElementById('gcpCostChart');
    if (!tableBtn || !chartBtn || !canvas) return;

    const legendBox = document.getElementById('gcpChartLegend');
    const labels = @json($chartLabels);
    const series = @json($chartSeries);
    const symbol = @json($sym);

    // Distinct-ish palette; cycles for projects beyond its length.
    const PALETTE = [
        '#0d6efd', '#10b981', '#f59e0b', '#8b5cf6', '#14b8a6', '#f43f5e',
        '#6366f1', '#06b6d4', '#e879f9', '#84cc16', '#fb923c', '#ec4899',
        '#22c55e', '#eab308', '#3b82f6', '#a855f7', '#ef4444', '#0ea5e9',
    ];
    const colorFor = (i) => PALETTE[i % PALETTE.length];

    // Amount with up to 6 decimals + thousands separators (matches the table).
    const fmt = (v) => Number(v).toLocaleString('en-US', { maximumFractionDigits: 6 });
    const esc = window.appHtmlEscape || ((s) => String(s == null ? '' : s));

    const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

    let chart = null;

    function buildChart() {
        if (chart) return;
        const tick = isDark() ? '#cbd5e1' : '#475569';
        const grid = isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';

        // Line-chart design mirroring Financial Management → Budget Usage:
        // smooth (tension 0.3), area fill at ~20% alpha, colored per series.
        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: series.map((s, i) => {
                    const color = colorFor(i);
                    return {
                        label: s.label,
                        data: s.data,
                        borderColor: color,
                        backgroundColor: color + '33',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { ticks: { color: tick }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { color: tick, callback: (v) => symbol + ' ' + fmt(v) },
                        grid: { color: grid },
                    },
                },
                plugins: {
                    legend: { display: false }, // replaced by the clickable color legend below
                    tooltip: {
                        callbacks: {
                            label: (c) => `${c.dataset.label}: ${symbol} ${fmt(c.parsed.y)}`,
                        },
                    },
                },
            },
        });
        buildLegend();
    }

    // Clickable color legend — one chip per project; click toggles its line on the
    // chart so users can isolate/compare individual projects.
    function buildLegend() {
        if (!chart || !legendBox) return;
        legendBox.innerHTML = '';
        chart.data.datasets.forEach((ds, i) => {
            const visible = chart.isDatasetVisible(i);
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-sm btn-light border d-inline-flex align-items-center gap-1' + (visible ? '' : ' opacity-50');
            chip.style.textDecoration = visible ? 'none' : 'line-through';
            chip.setAttribute('aria-pressed', String(visible));
            chip.innerHTML =
                '<span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:' +
                colorFor(i) + ';"></span>' + esc(ds.label);
            chip.addEventListener('click', () => {
                chart.setDatasetVisibility(i, !chart.isDatasetVisible(i));
                chart.update();
                buildLegend();
            });
            legendBox.appendChild(chip);
        });
    }

    function show(mode) {
        const wantChart = mode === 'chart';
        tableView.style.display = wantChart ? 'none' : '';
        chartView.style.display = wantChart ? '' : 'none';
        tableBtn.classList.toggle('btn-primary', !wantChart);
        tableBtn.classList.toggle('btn-outline-primary', wantChart);
        chartBtn.classList.toggle('btn-primary', wantChart);
        chartBtn.classList.toggle('btn-outline-primary', !wantChart);
        tableBtn.setAttribute('aria-pressed', String(!wantChart));
        chartBtn.setAttribute('aria-pressed', String(wantChart));
        if (wantChart) {
            buildChart();           // lazy: canvas must be visible to size correctly
            chart && chart.resize();
        }
        try { localStorage.setItem('gcp.compare.view', mode); } catch (e) {}
    }

    tableBtn.addEventListener('click', () => show('table'));
    chartBtn.addEventListener('click', () => show('chart'));

    // Re-theme the chart when the app's light/dark toggle flips, preserving which
    // projects the user has hidden.
    new MutationObserver(() => {
        if (!chart) return;
        const vis = chart.data.datasets.map((_, i) => chart.isDatasetVisible(i));
        chart.destroy();
        chart = null;
        if (chartView.style.display !== 'none') {
            buildChart();
            vis.forEach((v, i) => chart.setDatasetVisibility(i, v));
            chart.update();
            buildLegend();
        }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

    let saved = 'table';
    try { saved = localStorage.getItem('gcp.compare.view') || 'table'; } catch (e) {}
    show(saved);
})();
</script>
@endpush
@endif
@endsection
