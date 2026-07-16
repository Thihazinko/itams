@extends('layouts.app')

@section('title', 'Task Management — Summary')

@section('content')
@php
    $periodLabel = $months[$month] . ' ' . $year;
    // Hours may be fractional after edited time spans — trim trailing zeros.
    $fmt = function ($v) {
        $s = number_format((float) $v, 2, '.', '');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };

    // Prev / next month for the period stepper.
    $cur   = \Carbon\Carbon::createFromDate($year, $month, 1);
    $prevM = $cur->copy()->subMonthNoOverflow();
    $nextM = $cur->copy()->addMonthNoOverflow();

    // A stable colour per member for avatars / bars.
    $palette = ['#0d6efd', '#10b981', '#f59e0b', '#8b5cf6', '#14b8a6', '#f43f5e', '#6366f1', '#06b6d4'];
    $colorFor = function ($id) use ($palette) {
        return $palette[$id % count($palette)];
    };
    $initialsFor = function ($name) {
        $parts = preg_split('/\s+/', trim($name));
        $a = mb_substr($parts[0] ?? '', 0, 1);
        $b = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($a . $b);
    };

    // Members ranked by hours for the leaderboard cards.
    $ranked = $members
        ->map(fn ($m) => ['m' => $m, 'h' => $memberTotal[$m->id] ?? 0])
        ->sortByDesc('h')->values();
    $maxMember = (float) ($ranked->max('h') ?: 1);
@endphp

<style>
    /* ---- Monthly Summary ---- */
    .tm-toolbar {
        display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        padding: .6rem .85rem;
    }
    .tm-step {
        width: 34px; height: 34px; border-radius: .55rem;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid rgba(31, 38, 135, .12);
        background: rgba(255, 255, 255, .7); color: #475569; text-decoration: none;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
        flex-shrink: 0;
    }
    .tm-step:hover { background: #fff; color: #0d6efd; border-color: rgba(13,110,253,.3); }
    .tm-period { font-weight: 700; font-size: 1.02rem; min-width: 9.5rem; text-align: center; }
    [data-bs-theme="dark"] .tm-step { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #cfd8dc; }
    [data-bs-theme="dark"] .tm-step:hover { background: rgba(255,255,255,.1); color: #93c5fd; border-color: rgba(147,197,253,.35); }

    /* Member leaderboard cards */
    .tm-member {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem .95rem; height: 100%;
    }
    .tm-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .82rem; font-weight: 700; color: #fff; flex-shrink: 0;
        background: var(--tm-c, #0d6efd);
        box-shadow: 0 2px 6px color-mix(in srgb, var(--tm-c, #0d6efd) 45%, transparent);
    }
    @supports not (background: color-mix(in srgb, red, blue)) {
        .tm-avatar { box-shadow: 0 2px 6px rgba(13,110,253,.35); }
    }
    .tm-member-body { flex-grow: 1; min-width: 0; }
    .tm-member-top { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
    .tm-member-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tm-member-hrs { font-weight: 700; white-space: nowrap; }
    .tm-member-hrs small { font-weight: 400; color: #94a3b8; }
    .tm-bar { height: 5px; border-radius: 999px; background: rgba(31,38,135,.08); overflow: hidden; margin-top: .5rem; }
    .tm-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--tm-c, #0d6efd); transition: width .4s ease; }
    .tm-share { font-size: .7rem; color: #94a3b8; margin-top: .3rem; }
    [data-bs-theme="dark"] .tm-bar { background: rgba(255,255,255,.09); }
    [data-bs-theme="dark"] .tm-member-hrs small, [data-bs-theme="dark"] .tm-share { color: #64748b; }

    /* Breakdown table */
    .tm-table { margin-bottom: 0; }
    .tm-table thead th {
        background: rgba(31,38,135,.03);
        font-size: .7rem; text-transform: uppercase; letter-spacing: .04em;
        color: #64748b; font-weight: 700; vertical-align: middle;
        border-bottom: 2px solid rgba(31,38,135,.1);
    }
    .tm-table tbody td { vertical-align: middle; }
    .tm-table .col-total { background: rgba(13,110,253,.045); font-weight: 700; }
    .tm-table thead th.col-total { background: rgba(13,110,253,.09); color: #0d6efd; }
    .tm-cat-name { font-weight: 600; }
    .tm-group-alt > td { background: rgba(31,38,135,.018); }
    .tm-num { font-variant-numeric: tabular-nums; }
    .tm-zero { color: #cbd5e1; }
    .tm-table tfoot td { border-top: 2px solid rgba(31,38,135,.12); }
    [data-bs-theme="dark"] .tm-table thead th { background: rgba(255,255,255,.03); color: #94a3b8; border-bottom-color: rgba(255,255,255,.1); }
    [data-bs-theme="dark"] .tm-table .col-total { background: rgba(147,197,253,.08); }
    [data-bs-theme="dark"] .tm-table thead th.col-total { background: rgba(147,197,253,.14); color: #93c5fd; }
    [data-bs-theme="dark"] .tm-group-alt > td { background: rgba(255,255,255,.02); }
    [data-bs-theme="dark"] .tm-zero { color: #475569; }
    [data-bs-theme="dark"] .tm-table tfoot td { border-top-color: rgba(255,255,255,.12); }

    /* Leaderboard polish — hover lift + rank medals */
    .tm-member-card { transition: transform .15s ease, box-shadow .15s ease; }
    .tm-member-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(31,38,135,.10); }
    .tm-rank {
        position: absolute; top: -9px; left: -9px; min-width: 22px; height: 22px; padding: 0 5px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .68rem; font-weight: 700; color: #fff; background: #94a3b8;
        border-radius: 999px; box-shadow: 0 2px 6px rgba(31,38,135,.25); z-index: 3;
        border: 2px solid #fff;
    }
    [data-bs-theme="dark"] .tm-rank { border-color: #1e2430; }
    .tm-rank-1 { background: linear-gradient(135deg, #fcd34d, #f59e0b); color: #452c00; }
    .tm-rank-2 { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #1f2937; }
    .tm-rank-3 { background: linear-gradient(135deg, #f0b27a, #b45309); }

    /* Plan vs achieved — difference pills + progress bar */
    .tm-diff { display: inline-block; font-weight: 700; font-size: .78rem; padding: .08rem .5rem; border-radius: 999px; }
    .tm-diff-up { color: #059669; background: rgba(16,185,129,.12); }
    .tm-diff-down { color: #e11d48; background: rgba(244,63,94,.12); }
    [data-bs-theme="dark"] .tm-diff-up { color: #34d399; background: rgba(16,185,129,.16); }
    [data-bs-theme="dark"] .tm-diff-down { color: #fb7185; background: rgba(244,63,94,.16); }
    .tm-prog { display: flex; align-items: center; gap: .6rem; }
    .tm-prog-bar { flex-grow: 1; height: 8px; border-radius: 999px; background: rgba(31,38,135,.08); overflow: hidden; min-width: 80px; }
    .tm-prog-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--pc, #0d6efd); transition: width .5s ease; }
    .tm-prog-pct { min-width: 42px; text-align: right; font-size: .78rem; font-weight: 600; color: #64748b; }
    [data-bs-theme="dark"] .tm-prog-bar { background: rgba(255,255,255,.09); }
    [data-bs-theme="dark"] .tm-prog-pct { color: #94a3b8; }

    /* Breakdown table — row hover */
    .tm-table tbody tr:hover > td { background: rgba(13,110,253,.035); }
    .tm-table tbody tr:hover > td.col-total { background: rgba(13,110,253,.09); }
    [data-bs-theme="dark"] .tm-table tbody tr:hover > td { background: rgba(147,197,253,.06); }
    [data-bs-theme="dark"] .tm-table tbody tr:hover > td.col-total { background: rgba(147,197,253,.16); }

    .tm-hint { font-weight: 400; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Monthly Summary</h1>
        <div class="page-subtitle">Man-hours rolled up from daily task sheets — each logged hour-slot counts as one man-hour.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('task-management.summary.export', ['year' => $year, 'month' => $month]) }}" class="quick-action"><i class="bi bi-download"></i> Export {{ $months[$month] }} {{ $year }}</a>
        <a href="{{ route('task-management.index', ['date' => sprintf('%04d-%02d-01', $year, $month)]) }}" class="quick-action"><i class="bi bi-calendar-check"></i> Daily Task</a>
        <a href="{{ route('task-management.monthly', ['year' => $year, 'month' => $month]) }}" class="quick-action"><i class="bi bi-list-check"></i> Monthly List</a>
    </div>
</div>

{{-- Period picker: stepper + explicit month / year selectors --}}
<div class="card mb-4">
    <form method="GET" action="{{ route('task-management.summary') }}" class="tm-toolbar">
        <a href="{{ route('task-management.summary', ['year' => $prevM->year, 'month' => $prevM->month]) }}" class="tm-step" title="Previous month" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
        <div class="tm-period">
            <i class="bi bi-calendar3 text-primary me-1"></i>{{ $periodLabel }}
        </div>
        <a href="{{ route('task-management.summary', ['year' => $nextM->year, 'month' => $nextM->month]) }}" class="tm-step" title="Next month" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>

        <div class="vr d-none d-sm-block mx-1"></div>

        <div class="d-flex align-items-center gap-2 ms-sm-auto flex-wrap">
            <select name="month" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()" aria-label="Month">
                @foreach($months as $num => $label)
                    <option value="{{ $num }}" @selected($num === $month)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()" aria-label="Year">
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

@if($members->isEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle"></i>
        <div>No members yet. Grant Task Management access to users in User Management first.</div>
    </div>
@elseif(round($grand, 2) <= 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-2"><i class="bi bi-calendar-x text-secondary" style="font-size: 2.25rem; opacity: .5;"></i></div>
            <h5 class="mb-1">Nothing logged for {{ $periodLabel }}</h5>
            <p class="text-muted mb-3">No daily task hours have been recorded for this period yet.</p>
            <a href="{{ route('task-management.index', ['date' => sprintf('%04d-%02d-01', $year, $month)]) }}" class="quick-action quick-action-primary">
                <i class="bi bi-pencil-square"></i> Open Daily Task
            </a>
        </div>
    </div>
@else

{{-- Per-member leaderboard --}}
<h6 class="section-title mb-2"><i class="bi bi-bar-chart-line me-1"></i> Man-hours by member <span class="text-muted tm-hint small">· ranked</span></h6>
<div class="row g-3 mb-4">
    @foreach($ranked as $entry)
        @php
            $member = $entry['m'];
            $hrs    = $entry['h'];
            $share  = $grand > 0 ? $hrs / $grand * 100 : 0;
            $barPct = $maxMember > 0 ? $hrs / $maxMember * 100 : 0;
            $c      = $colorFor($member->id);
            $rk     = $loop->iteration;
        @endphp
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 tm-member-card position-relative">
                <span class="tm-rank {{ $hrs > 0 && $rk <= 3 ? 'tm-rank-' . $rk : '' }}">{{ $rk }}</span>
                <div class="tm-member" style="--tm-c: {{ $c }};">
                    <span class="tm-avatar">{{ $initialsFor($member->name) }}</span>
                    <div class="tm-member-body">
                        <div class="tm-member-top">
                            <span class="tm-member-name" title="{{ $member->name }}">{{ $member->name }}</span>
                            <span class="tm-member-hrs tm-num">{{ $fmt($hrs) }}<small> h</small></span>
                        </div>
                        <div class="tm-bar"><span style="width: {{ $barPct }}%;"></span></div>
                        <div class="tm-share">{{ round($share) }}% of total</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Plan vs achieved by category — planned target (from Manage Tasks) against logged hours --}}
@php
    $planRows = [];
    $totPlan  = 0.0;
    $totAch   = 0.0;
    foreach ($categories as $category) {
        $plan = (float) $category->plan_hours;
        $ach  = (float) ($catTotal[$category->id] ?? 0);
        if ($plan <= 0 && $ach <= 0) {
            continue;
        }
        $totPlan += $plan;
        $totAch  += $ach;
        $planRows[] = ['name' => $category->name, 'plan' => $plan, 'ach' => $ach];
    }
@endphp
@if(! empty($planRows))
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2 fw-semibold">
        <i class="bi bi-clipboard-check text-primary"></i> Plan vs achieved by category
        <span class="text-muted tm-hint small d-none d-md-inline ms-auto">Planned hours (Manage Tasks) vs logged hours</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered tm-table align-middle mb-0">
            <thead class="text-center">
                <tr>
                    <th class="text-start" style="min-width: 170px;">Category</th>
                    <th style="min-width: 90px;">Plan</th>
                    <th class="col-total" style="min-width: 90px;">Achieved</th>
                    <th style="min-width: 110px;">Difference</th>
                    <th style="min-width: 200px;">Progress</th>
                </tr>
            </thead>
            <tbody>
                @foreach($planRows as $pr)
                    @php
                        $plan = $pr['plan'];
                        $ach  = $pr['ach'];
                        $diff = $ach - $plan;
                        $rawPct = $plan > 0 ? $ach / $plan * 100 : 0;
                        $pct  = $plan > 0 ? min(100, $rawPct) : 0;
                        $pc   = $rawPct >= 100 ? '#10b981' : ($rawPct >= 60 ? '#0d6efd' : ($rawPct >= 30 ? '#f59e0b' : '#f43f5e'));
                    @endphp
                    <tr class="text-center">
                        <td class="text-start tm-cat-name">{{ $pr['name'] }}</td>
                        <td class="tm-num">{{ $plan > 0 ? $fmt($plan) : '—' }}</td>
                        <td class="col-total tm-num">{{ $fmt($ach) }}</td>
                        <td class="tm-num">
                            @if($plan <= 0)
                                <span class="tm-zero">—</span>
                            @elseif($diff >= 0)
                                <span class="tm-diff tm-diff-up">+{{ $fmt($diff) }}</span>
                            @else
                                <span class="tm-diff tm-diff-down">{{ $fmt($diff) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($plan <= 0)
                                <span class="text-muted small">No plan set</span>
                            @else
                                <div class="tm-prog" role="progressbar" aria-valuenow="{{ round($rawPct) }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="tm-prog-bar"><span style="width: {{ $pct }}%; --pc: {{ $pc }};"></span></div>
                                    <span class="tm-prog-pct tm-num">{{ round($rawPct) }}%</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-semibold table-light text-center">
                    <td class="text-start"><i class="bi bi-sigma me-1"></i>Total</td>
                    <td class="tm-num">{{ $fmt($totPlan) }}</td>
                    <td class="col-total tm-num">{{ $fmt($totAch) }}</td>
                    <td class="tm-num">
                        @php $td = $totAch - $totPlan; @endphp
                        @if($totPlan <= 0)
                            <span class="tm-zero">—</span>
                        @elseif($td >= 0)
                            <span class="tm-diff tm-diff-up">+{{ $fmt($td) }}</span>
                        @else
                            <span class="tm-diff tm-diff-down">{{ $fmt($td) }}</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $totPlan > 0 ? round($totAch / $totPlan * 100) . '% of plan' : '—' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Category → task breakdown, member columns (man-hours) --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2 fw-semibold">
        <i class="bi bi-table text-primary"></i> Category &amp; task breakdown
        <span class="text-muted tm-hint small d-none d-md-inline ms-auto">Man-hours per member</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered tm-table">
            <thead class="text-center">
                <tr>
                    <th class="text-start" style="min-width: 170px;">Category</th>
                    <th class="text-start" style="min-width: 200px;">Task</th>
                    @foreach($members as $member)
                        <th style="min-width: 90px;">{{ $member->name }}</th>
                    @endforeach
                    <th class="col-total" style="min-width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $catIndex = 0; @endphp
                @foreach($categories as $category)
                    @php $cTotal = $catTotal[$category->id] ?? 0; @endphp
                    @if($cTotal > 0)
                        @php
                            // Task rows for this category that have hours.
                            $taskRows = [];
                            foreach ($category->items as $task) {
                                $tRow = $taskMember[$task->id] ?? [];
                                if (array_sum($tRow) > 0) {
                                    $taskRows[] = [
                                        'name'    => $task->name,
                                        'per'     => $tRow,
                                        'total'   => array_sum($tRow),
                                    ];
                                }
                            }
                            // Category hours not tied to any specific task (category chosen, no task).
                            $remPer = [];
                            foreach ($members as $m) {
                                $catH  = $catMember[$category->id][$m->id] ?? 0;
                                $taskH = 0;
                                foreach ($taskRows as $tr) { $taskH += $tr['per'][$m->id] ?? 0; }
                                $rem = $catH - $taskH;
                                if ($rem > 0.001) { $remPer[$m->id] = $rem; }
                            }
                            if (! empty($remPer)) {
                                $taskRows[] = [
                                    'name'  => '(No specific task)',
                                    'per'   => $remPer,
                                    'total' => array_sum($remPer),
                                    'muted' => true,
                                ];
                            }
                            $rowspan   = count($taskRows);
                            $groupAlt  = $catIndex % 2 === 1;
                            $catIndex++;
                        @endphp
                        @foreach($taskRows as $i => $tr)
                            <tr class="text-end {{ $groupAlt ? 'tm-group-alt' : '' }}">
                                @if($i === 0)
                                    <td class="text-start align-middle" rowspan="{{ $rowspan }}">
                                        <div class="tm-cat-name">{{ $category->name }}</div>
                                    </td>
                                @endif
                                <td class="text-start {{ ($tr['muted'] ?? false) ? 'text-muted fst-italic' : '' }}">
                                    {{ $tr['name'] }}
                                </td>
                                @foreach($members as $member)
                                    <td class="tm-num">
                                        @if(isset($tr['per'][$member->id]))
                                            {{ $fmt($tr['per'][$member->id]) }}
                                        @else
                                            <span class="tm-zero">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="col-total tm-num">{{ $fmt($tr['total']) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-semibold table-light text-end">
                    <td class="text-start" colspan="2"><i class="bi bi-sigma me-1"></i>Total man-hours</td>
                    @foreach($members as $member)
                        <td class="tm-num">{{ $fmt($memberTotal[$member->id] ?? 0) }}</td>
                    @endforeach
                    <td class="col-total tm-num">{{ $fmt($grand) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif
@endsection

