@extends('layouts.app')

@section('title', 'Daily Task')

@section('content')
@php
    $dateStr = $date->toDateString();
    $colspanNote = $canEdit ? 7 : 6; // columns after the "Total" cell

    // Preserve which member's sheet we're on across calendar links.
    $memberParam = $target->id !== auth()->id() ? $target->id : null;
    $link = fn (array $params = []) => route('task-management.index', array_merge(array_filter(['member' => $memberParam]), $params));

    $calKey  = $calMonth->format('Y-m');
    $prevCal = $calMonth->copy()->subMonthNoOverflow()->format('Y-m');
    $nextCal = $calMonth->copy()->addMonthNoOverflow()->format('Y-m');
    $lead    = (int) $calMonth->copy()->startOfMonth()->dayOfWeek; // 0=Sun … 6=Sat
    $logged  = array_flip($loggedDates);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Daily Task</h1>
        <div class="page-subtitle">
            Hourly working log for <strong>{{ $target->name }}</strong> — {{ $date->format('l, j M Y') }}.
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="dropdown">
            <button type="button" class="quick-action dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Export {{ $date->format('M Y') }}
                <i class="bi bi-chevron-down ms-1 small opacity-75"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('task-management.export', array_filter(['member' => $memberParam, 'year' => $date->year, 'month' => $date->month])) }}">
                        <i class="bi bi-person"></i> {{ $target->name }}{{ $target->id === auth()->id() ? ' (me)' : '' }}
                    </a>
                </li>
                @if($isAdmin)
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('task-management.export', ['scope' => 'all', 'year' => $date->year, 'month' => $date->month]) }}">
                            <i class="bi bi-people"></i> All members
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>

{{-- Calendar picker for choosing the working day --}}
<div class="d-flex flex-wrap gap-3 mb-3 align-items-start">
    <div class="card cal-card">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <a href="{{ $link(['date' => $dateStr, 'cal' => $prevCal]) }}" class="btn btn-sm cal-nav" title="Previous month"><i class="bi bi-chevron-left"></i></a>
                <span class="fw-semibold small">{{ $calMonth->format('F Y') }}</span>
                <a href="{{ $link(['date' => $dateStr, 'cal' => $nextCal]) }}" class="btn btn-sm cal-nav" title="Next month"><i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="cal-grid mb-1">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                    <div class="dow">{{ $dow[0] }}</div>
                @endforeach
            </div>
            <div class="cal-grid">
                @for($i = 0; $i < $lead; $i++)
                    <span class="cal-empty"></span>
                @endfor
                @for($d = 1; $d <= $calMonth->daysInMonth; $d++)
                    @php
                        $cur    = $calMonth->copy()->day($d);
                        $curStr = $cur->toDateString();
                        $classes = 'cal-day';
                        if ($curStr === $dateStr) $classes .= ' is-selected';
                        if ($cur->isToday()) $classes .= ' is-today';
                        if (isset($logged[$curStr])) $classes .= ' has-log';
                    @endphp
                    <a href="{{ $link(['date' => $curStr, 'cal' => $calKey]) }}" class="{{ $classes }}"
                       title="{{ $cur->format('l, j M Y') }}{{ isset($logged[$curStr]) ? ' — has entries' : '' }}">{{ $d }}</a>
                @endfor
            </div>
            <div class="d-flex align-items-center gap-3 mt-2 px-1 text-muted" style="font-size:.68rem;">
                <span class="d-inline-flex align-items-center gap-1"><span class="cal-legend-dot"></span> Has entries</span>
                <span class="d-inline-flex align-items-center gap-1"><span class="cal-legend-today"></span> Today</span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-2">
        <div>
            <div class="text-muted small">Selected day</div>
            <div class="fs-5 fw-semibold">{{ $date->format('l, j M Y') }}</div>
        </div>
        <form method="GET" action="{{ route('task-management.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            @if($memberParam)<input type="hidden" name="member" value="{{ $memberParam }}">@endif
            <div>
                <label class="form-label small text-muted mb-1">Jump to date</label>
                <input type="date" name="date" value="{{ $dateStr }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            <a href="{{ $link(['date' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Today</a>
        </form>
        @if($isAdmin && $members->isNotEmpty())
            <form method="GET" action="{{ route('task-management.index') }}" class="d-flex gap-2 align-items-end">
                <input type="hidden" name="date" value="{{ $dateStr }}">
                <div>
                    <label class="form-label small text-muted mb-1">Member</label>
                    <select name="member" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ auth()->user()->name }} (me)</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected($member->id === $target->id && $member->id !== auth()->id())>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif
    </div>
</div>

@unless($canEdit)
    <div class="alert alert-info d-flex align-items-center gap-2 py-2">
        <i class="bi bi-eye"></i><div>You are viewing this sheet in read-only mode.</div>
    </div>
@endunless

<form method="POST" action="{{ route('task-management.save', array_filter(['member' => $target->id !== auth()->id() ? $target->id : null])) }}" id="dailyForm">
    @csrf
    <input type="hidden" name="date" value="{{ $dateStr }}">

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0 daily-sheet">
                <thead class="text-center small">
                    <tr>
                        <th style="width: 38px;">No</th>
                        <th style="min-width: 200px;">Hours</th>
                        <th style="min-width: 150px;">Category</th>
                        <th style="min-width: 170px;">Task List</th>
                        <th style="min-width: 130px;">Project Name</th>
                        <th style="min-width: 130px;">Expense Name</th>
                        <th style="min-width: 120px;">Regular / Temp.</th>
                        <th style="min-width: 110px;">Work / Study</th>
                        <th style="min-width: 200px;">Task Detail</th>
                        @if($canEdit)<th style="width: 44px;"></th>@endif
                    </tr>
                </thead>
                <tbody id="rowsBody"><!-- rows rendered by JS --></tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="2" class="text-end fw-semibold">Total man-hours</td>
                        <td class="fw-semibold" id="dayTotal">0</td>
                        <td colspan="{{ $colspanNote }}" class="text-muted small">Man-hours are counted from each row's time span.</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        @if($canEdit)
            <button type="button" class="btn btn-outline-secondary" id="addRowBtn"><i class="bi bi-plus-lg"></i> Add Row</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save {{ $date->format('j M Y') }}</button>
        @endif
    </div>
</form>

<style>
    .daily-sheet td, .daily-sheet th { vertical-align: middle; }

    .cal-card { width: 300px; }
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    .cal-grid .dow { text-align: center; font-size: .68rem; color: #94a3b8; padding: 2px 0; font-weight: 600; }
    .cal-day {
        text-align: center; padding: .35rem 0; border-radius: .4rem; position: relative;
        text-decoration: none; color: inherit; font-size: .85rem; display: block;
    }
    .cal-day:hover { background: rgba(13, 110, 253, .10); }
    .cal-day.is-today { outline: 1px solid #0d6efd; outline-offset: -1px; }
    .cal-day.is-selected { background: #0d6efd; color: #fff; font-weight: 600; }
    .cal-day.has-log::after {
        content: ''; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%);
        width: 5px; height: 5px; border-radius: 50%; background: #198754;
    }
    .cal-day.is-selected.has-log::after { background: #fff; }
    .cal-legend-dot { width: 6px; height: 6px; border-radius: 50%; background: #198754; display: inline-block; }
    .cal-legend-today { width: 10px; height: 10px; border-radius: 3px; outline: 1px solid #0d6efd; display: inline-block; }

    /* Month prev/next arrows — theme-aware (btn-light stays white in dark mode). */
    .cal-nav {
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid rgba(31, 38, 135, .12);
        background: rgba(255, 255, 255, .7); color: #475569;
    }
    .cal-nav:hover { background: #fff; color: #0d6efd; border-color: rgba(13, 110, 253, .3); }

    [data-bs-theme="dark"] .cal-day:hover { background: rgba(147, 197, 253, .16); }
    [data-bs-theme="dark"] .cal-grid .dow { color: #64748b; }
    [data-bs-theme="dark"] .cal-nav { background: rgba(255, 255, 255, .05); border-color: rgba(255, 255, 255, .1); color: #cfd8dc; }
    [data-bs-theme="dark"] .cal-nav:hover { background: rgba(255, 255, 255, .1); color: #93c5fd; border-color: rgba(147, 197, 253, .35); }
</style>

<script>
    (function () {
        const initialRows     = @json($rows);
        const categoryList    = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values());
        const tasksByCategory = @json($tasksByCategory);
        const workTypes       = @json($workTypes);
        const studyTypes      = @json($studyTypes);
        const canEdit         = @json($canEdit);

        const form = document.getElementById('dailyForm');
        const body = document.getElementById('rowsBody');
        if (!form || !body) return;

        let idx = 0;
        const dis = canEdit ? '' : 'disabled';

        function addOptions(select, items) {
            items.forEach(it => {
                const o = document.createElement('option');
                o.value = it.value;
                o.textContent = it.label;
                select.appendChild(o);
            });
        }

        function fillTasks(catSelect, want) {
            const taskSelect = catSelect.closest('tr').querySelector('.task-select');
            taskSelect.innerHTML = '';
            addOptions(taskSelect, [{ value: '', label: '—' }]);
            (tasksByCategory[catSelect.value] || []).forEach(t => {
                const o = document.createElement('option');
                o.value = t.id;
                o.textContent = t.name;
                if (String(t.id) === String(want ?? '')) o.selected = true;
                taskSelect.appendChild(o);
            });
        }

        function buildRow(data) {
            data = data || {};
            const i = idx++;
            const tr = document.createElement('tr');
            // Editable: two time pickers. Read-only: clean 24h text + hours badge —
            // avoids disabled pickers rendering locale AM/PM that overflows the box.
            const timeInput = field =>
                `<input type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" ` +
                `pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" name="slots[${i}][${field}]" ` +
                `class="form-control form-control-sm slot-time text-center" style="width:70px">`;
            const hoursCell = canEdit
                ? '<td><div class="d-flex align-items-center gap-1">' +
                    timeInput('start_time') +
                    '<span class="text-muted small">to</span>' +
                    timeInput('end_time') +
                  '</div></td>'
                : '<td class="slot-readonly text-nowrap"></td>';
            tr.innerHTML =
                '<td class="text-center text-muted row-no"></td>' +
                hoursCell +
                `<td><select name="slots[${i}][task_category_id]" class="form-select form-select-sm cat-select" ${dis}></select></td>` +
                `<td><select name="slots[${i}][task_item_id]" class="form-select form-select-sm task-select" ${dis}></select></td>` +
                `<td><input type="text" name="slots[${i}][project_name]" class="form-control form-control-sm" maxlength="255" ${dis}></td>` +
                `<td><input type="text" name="slots[${i}][expense_name]" class="form-control form-control-sm" maxlength="255" ${dis}></td>` +
                `<td><select name="slots[${i}][work_type]" class="form-select form-select-sm" ${dis}></select></td>` +
                `<td><select name="slots[${i}][study_type]" class="form-select form-select-sm" ${dis}></select></td>` +
                `<td><input type="text" name="slots[${i}][task_detail]" class="form-control form-control-sm" maxlength="1000" ${dis}></td>` +
                (canEdit ? '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger row-remove" title="Remove row"><i class="bi bi-x-lg"></i></button></td>' : '');

            const q = s => tr.querySelector(s);
            // Category options
            const cat = q('.cat-select');
            addOptions(cat, [{ value: '', label: '—' }].concat(categoryList.map(c => ({ value: c.id, label: c.name }))));
            cat.value = data.task_category_id || '';
            fillTasks(cat, data.task_item_id);
            // Work / Study options
            addOptions(q('[name$="[work_type]"]'), [{ value: '', label: '—' }].concat(workTypes.map(v => ({ value: v, label: v }))));
            addOptions(q('[name$="[study_type]"]'), [{ value: '', label: '—' }].concat(studyTypes.map(v => ({ value: v, label: v }))));
            // Values
            const start = data.start_time || '', end = data.end_time || '';
            if (canEdit) {
                q('[name$="[start_time]"]').value = start;
                q('[name$="[end_time]"]').value   = end;
            } else {
                const hrs = spanHours(start, end);
                tr.dataset.hours = hrs;
                const span = (start || end)
                    ? `${start || '—'} <span class="text-muted">to</span> ${end || '—'}`
                    : '<span class="text-muted">—</span>';
                q('.slot-readonly').innerHTML =
                    `${span} <span class="badge rounded-pill text-bg-light border ms-1">${fmtHM(hrs)}</span>`;
            }
            q('[name$="[project_name]"]').value = data.project_name || '';
            q('[name$="[expense_name]"]').value = data.expense_name || '';
            q('[name$="[work_type]"]').value  = data.work_type || '';
            q('[name$="[study_type]"]').value = data.study_type || '';
            q('[name$="[task_detail]"]').value = data.task_detail || '';

            body.appendChild(tr);
            return tr;
        }

        function toMin(v) {
            const m = /^(\d{1,2}):(\d{2})/.exec(v || '');
            return m ? (+m[1]) * 60 + (+m[2]) : null;
        }
        function spanHours(start, end) {
            const s = toMin(start), e = toMin(end);
            if (s === null || e === null) return 1;
            const d = (e - s) / 60;
            return d > 0 ? d : 1;
        }
        // Decimal hours -> "Xh Ym" (e.g. 8.25 -> "8h 15m") so 15/30-min spans read true.
        function fmtHM(hours) {
            const totalMin = Math.round(hours * 60);
            if (totalMin === 0) return '0h';
            const h = Math.floor(totalMin / 60), m = totalMin % 60;
            if (h && m) return `${h}h ${m}m`;
            return h ? `${h}h` : `${m}m`;
        }
        // Keep the text field a clean 24-hour HH:MM as the user types.
        function maskTime(el) {
            let d = el.value.replace(/\D/g, '').slice(0, 4);
            let hh = d.slice(0, 2), mm = d.slice(2, 4);
            if (hh.length === 2 && +hh > 23) hh = '23';
            if (mm.length === 2 && +mm > 59) mm = '59';
            el.value = (mm.length || d.length > 2) ? hh + ':' + mm : hh;
        }
        function durationFor(tr) {
            const t = tr.querySelectorAll('.slot-time');
            if (t.length >= 2) return spanHours(t[0].value, t[1].value);
            // Read-only rows have no inputs; use the span computed at build time.
            return parseFloat(tr.dataset.hours) || 1;
        }
        function renumber() {
            body.querySelectorAll('tr').forEach((tr, n) => { tr.querySelector('.row-no').textContent = n + 1; });
        }
        function recalcTotal() {
            let total = 0;
            body.querySelectorAll('tr').forEach(tr => {
                const cat = tr.querySelector('.cat-select');
                if (cat && cat.value) total += durationFor(tr);
            });
            document.getElementById('dayTotal').textContent = fmtHM(total);
        }

        // Delegated events
        body.addEventListener('change', e => {
            if (e.target.matches('.cat-select')) { fillTasks(e.target); recalcTotal(); }
        });
        form.addEventListener('input', e => {
            if (e.target.matches('.slot-time')) { maskTime(e.target); recalcTotal(); }
        });
        body.addEventListener('click', e => {
            const btn = e.target.closest('.row-remove');
            if (btn) { btn.closest('tr').remove(); renumber(); recalcTotal(); }
        });
        const addBtn = document.getElementById('addRowBtn');
        if (addBtn) addBtn.addEventListener('click', () => { buildRow({}); renumber(); recalcTotal(); });

        // Initial render
        (initialRows.length ? initialRows : [{}]).forEach(r => buildRow(r));
        renumber();
        recalcTotal();
    })();
</script>
@endsection
