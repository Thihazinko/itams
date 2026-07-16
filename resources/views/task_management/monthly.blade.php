@extends('layouts.app')

@section('title', 'Task Management — Monthly List')

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

    // Preserve which member's list we're on across the period / member links.
    $memberParam = $target->id !== auth()->id() ? $target->id : null;
    $stepParams  = fn ($y, $m) => array_filter(['member' => $memberParam, 'year' => $y, 'month' => $m]);
@endphp

<style>
    /* ---- Monthly List (reuses Summary toolbar look) ---- */
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

    .tm-list { margin-bottom: 0; }
    .tm-list thead th {
        background: rgba(31,38,135,.03); position: sticky; top: 0; z-index: 1;
        font-size: .7rem; text-transform: uppercase; letter-spacing: .04em;
        color: #64748b; font-weight: 700; vertical-align: middle;
        border-bottom: 2px solid rgba(31,38,135,.1); white-space: nowrap;
    }
    .tm-list tbody td { vertical-align: middle; }
    .tm-list .tm-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .tm-cat-head td {
        background: rgba(13,110,253,.06); border-top: 2px solid rgba(31,38,135,.12);
        font-weight: 700; padding-top: .5rem; padding-bottom: .5rem;
    }
    .tm-cat-name { font-size: .92rem; color: #0d6efd; }
    .tm-cat-meta { font-weight: 500; font-size: .72rem; color: #64748b; }
    .tm-date { font-weight: 600; white-space: nowrap; }
    .tm-date small { font-weight: 400; font-size: .68rem; color: #94a3b8; margin-left: .3rem; }
    .tm-badge {
        display: inline-block; font-size: .72rem; font-weight: 600; color: #475569;
        background: rgba(31,38,135,.06); border-radius: .35rem; padding: .05rem .4rem;
    }
    .tm-detail-text { white-space: pre-line; word-break: break-word; max-width: 320px; }
    .tm-muted { color: #cbd5e1; }
    .tm-list tfoot td { border-top: 2px solid rgba(31,38,135,.12); background: rgba(13,110,253,.045); }
    [data-bs-theme="dark"] .tm-list thead th { background: rgba(255,255,255,.03); color: #94a3b8; border-bottom-color: rgba(255,255,255,.1); }
    [data-bs-theme="dark"] .tm-cat-head td { background: rgba(147,197,253,.1); border-top-color: rgba(255,255,255,.12); }
    [data-bs-theme="dark"] .tm-cat-name { color: #93c5fd; }
    [data-bs-theme="dark"] .tm-cat-meta { color: #94a3b8; }
    [data-bs-theme="dark"] .tm-badge { color: #cbd5e1; background: rgba(255,255,255,.07); }
    [data-bs-theme="dark"] .tm-date small { color: #64748b; }
    [data-bs-theme="dark"] .tm-muted { color: #475569; }
    [data-bs-theme="dark"] .tm-list tfoot td { border-top-color: rgba(255,255,255,.12); background: rgba(147,197,253,.08); }
    .tm-noresult td { color: #94a3b8; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Monthly Task List</h1>
        <div class="page-subtitle">
            Every task logged by <strong>{{ $target->name }}</strong> in {{ $periodLabel }}, grouped by category — {{ $entryCount }} {{ \Illuminate\Support\Str::plural('entry', $entryCount) }} across {{ $groups->count() }} {{ \Illuminate\Support\Str::plural('category', $groups->count()) }} over {{ $daysLogged }} {{ \Illuminate\Support\Str::plural('day', $daysLogged) }}.
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export {{ $months[$month] }} {{ $year }}
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('task-management.export', array_filter(['member' => $memberParam, 'year' => $year, 'month' => $month])) }}">
                        <i class="bi bi-person"></i> {{ $target->name }}{{ $target->id === auth()->id() ? ' (me)' : '' }}
                    </a>
                </li>
                @if($isAdmin)
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('task-management.export', ['scope' => 'all', 'year' => $year, 'month' => $month]) }}">
                            <i class="bi bi-people"></i> All members
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <a href="{{ route('task-management.index', array_filter(['member' => $memberParam, 'date' => sprintf('%04d-%02d-01', $year, $month)])) }}" class="quick-action"><i class="bi bi-calendar-check"></i> Daily Task</a>
        <a href="{{ route('task-management.summary', ['year' => $year, 'month' => $month]) }}" class="quick-action"><i class="bi bi-bar-chart-line"></i> Monthly Summary</a>
    </div>
</div>

{{-- Period picker: stepper + explicit month / year selectors + member (admins) --}}
<div class="card mb-3">
    <form method="GET" action="{{ route('task-management.monthly') }}" class="tm-toolbar">
        @if($memberParam)<input type="hidden" name="member" value="{{ $memberParam }}">@endif
        <a href="{{ route('task-management.monthly', $stepParams($prevM->year, $prevM->month)) }}" class="tm-step" title="Previous month" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
        <div class="tm-period">
            <i class="bi bi-calendar3 text-primary me-1"></i>{{ $periodLabel }}
        </div>
        <a href="{{ route('task-management.monthly', $stepParams($nextM->year, $nextM->month)) }}" class="tm-step" title="Next month" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>

        <div class="vr d-none d-sm-block mx-1"></div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
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

        @if($isAdmin && $members->isNotEmpty())
            <div class="d-flex align-items-center gap-2 ms-sm-auto">
                <label class="form-label small text-muted mb-0">Member</label>
                <select name="member" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()" aria-label="Member">
                    <option value="">{{ auth()->user()->name }} (me)</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected($member->id === $target->id && $member->id !== auth()->id())>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </form>
</div>

@if($groups->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-2"><i class="bi bi-calendar-x text-secondary" style="font-size: 2.25rem; opacity: .5;"></i></div>
            <h5 class="mb-1">Nothing logged for {{ $periodLabel }}</h5>
            <p class="text-muted mb-3">No daily task entries have been recorded for this period yet.</p>
            <a href="{{ route('task-management.index', array_filter(['member' => $memberParam, 'date' => sprintf('%04d-%02d-01', $year, $month)])) }}" class="quick-action quick-action-primary">
                <i class="bi bi-pencil-square"></i> Open Daily Task
            </a>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2 flex-wrap">
            <i class="bi bi-list-check text-primary"></i>
            <span class="fw-semibold">Entries by category</span>
            <div class="ms-auto" style="max-width: 260px; width: 100%;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="tmFilter" class="form-control" placeholder="Filter this month…" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover tm-list align-middle">
                <thead class="text-nowrap">
                    <tr>
                        <th style="width: 44px;">No</th>
                        <th style="min-width: 110px;">Date</th>
                        <th style="min-width: 130px;">Hours</th>
                        <th style="min-width: 150px;">Task</th>
                        <th style="min-width: 120px;">Project</th>
                        <th style="min-width: 120px;">Expense</th>
                        <th style="min-width: 110px;">Reg. / Temp.</th>
                        <th style="min-width: 100px;">Work / Study</th>
                        <th style="min-width: 200px;">Task Detail</th>
                    </tr>
                </thead>
                <tbody id="tmRows">
                    @foreach($groups as $g)
                        <tr class="tm-cat-head" data-cat-head>
                            <td colspan="9">
                                <i class="bi bi-folder2-open text-primary me-1"></i>
                                <span class="tm-cat-name">{{ $g['name'] }}</span>
                                <span class="tm-cat-meta ms-2">{{ count($g['rows']) }} {{ \Illuminate\Support\Str::plural('entry', count($g['rows'])) }} · {{ $fmt($g['total']) }}h</span>
                            </td>
                        </tr>
                        @foreach($g['rows'] as $i => $row)
                            <tr data-cat-row>
                                <td class="text-muted tm-num">{{ $i + 1 }}</td>
                                <td>
                                    @if($row['date'])
                                        <span class="tm-date">{{ $row['date']->format('j M') }}<small>{{ $row['date']->format('D') }}</small></span>
                                    @else
                                        <span class="tm-muted">—</span>
                                    @endif
                                </td>
                                <td class="tm-num">
                                    @if($row['start_time'] || $row['end_time'])
                                        {{ $row['start_time'] ?: '—' }} <span class="text-muted">to</span> {{ $row['end_time'] ?: '—' }}
                                    @else
                                        <span class="tm-muted">—</span>
                                    @endif
                                    <span class="tm-badge ms-1">{{ $fmt($row['hours']) }}h</span>
                                </td>
                                <td>{{ $row['task'] ?: '' }}</td>
                                <td>{{ $row['project'] ?: '' }}</td>
                                <td>{{ $row['expense'] ?: '' }}</td>
                                <td>{{ $row['work_type'] ?: '' }}</td>
                                <td>{{ $row['study_type'] ?: '' }}</td>
                                <td class="tm-detail-text">{{ $row['detail'] ?: '' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="tm-noresult d-none">
                        <td colspan="9" class="text-center py-3">No entries match your filter.</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="2" class="text-end"><i class="bi bi-sigma me-1"></i>Total man-hours</td>
                        <td class="tm-num">{{ $fmt($total) }}h</td>
                        <td colspan="6" class="text-muted small">Counted from each row's time span.</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('tmFilter');
        var body  = document.getElementById('tmRows');
        if (!input || !body) return;

        var noResult = body.querySelector('.tm-noresult');

        // Each category header owns the entry rows that follow it up to the next
        // header — filter the entries, then hide any header left with no matches.
        var groups = [];
        var current = null;
        Array.prototype.slice.call(body.querySelectorAll('tr')).forEach(function (r) {
            if (r.classList.contains('tm-noresult')) return;
            if (r.hasAttribute('data-cat-head')) {
                current = { head: r, rows: [] };
                groups.push(current);
            } else if (r.hasAttribute('data-cat-row') && current) {
                current.rows.push(r);
            }
        });

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var shown = 0;
            groups.forEach(function (g) {
                var visible = 0;
                g.rows.forEach(function (r) {
                    var match = !q || r.textContent.toLowerCase().indexOf(q) !== -1;
                    r.classList.toggle('d-none', !match);
                    if (match) { visible++; shown++; }
                });
                g.head.classList.toggle('d-none', visible === 0);
            });
            if (noResult) noResult.classList.toggle('d-none', shown !== 0);
        });
    })();
</script>
@endpush
