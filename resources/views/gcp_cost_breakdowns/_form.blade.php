@php
    $breakdown = $breakdown ?? null;
    $action = $breakdown ? route('gcp-costs.update', $breakdown) : route('gcp-costs.store');

    // Which currency this breakdown is being entered under. Drives which cost
    // column is the "primary" one to fill (¥ for JPY, $ for USD) and whether the
    // JPY→USD auto-derive runs. USD breakdowns keep the yen cost blank so they
    // classify under the USD tab.
    $currency = strtoupper($currency ?? 'JPY') === 'USD' ? 'USD' : 'JPY';
    $isUsd = $currency === 'USD';

    // Rows to pre-render: old input (after a validation error) wins; otherwise the
    // model's existing lines; otherwise a single blank row to start.
    $rows = old('lines');
    if ($rows === null) {
        $rows = $breakdown
            ? $breakdown->lines->map(fn ($l) => [
                'account_type'         => $l->account_type,
                'project_name'         => $l->project_name,
                'usage'                => $l->usage,
                'billing_account_name' => $l->billing_account_name,
                'project_id'           => $l->project_id,
                'usage_start_date'     => optional($l->usage_start_date)->format('Y-m-d'),
                'usage_end_date'       => optional($l->usage_end_date)->format('Y-m-d'),
                'billing_card'         => $l->billing_card,
                'card_setting'         => $l->card_setting,
                'cost_jpy'             => $l->cost_jpy,
                'cost_usd'             => $l->cost_usd,
                'status'               => $l->status,
            ])->toArray()
            : [];
    }
    if (empty($rows)) {
        $rows = [[]];
    }

    $val = fn ($row, $key) => e($row[$key] ?? '');
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($breakdown) @method('PUT') @endif

    {{-- ===== Header ===== --}}
    <div class="row g-3 mb-2">
        <div class="col-md-3">
            <label class="form-label">Period Start</label>
            <input type="date" name="period_start" value="{{ old('period_start', optional($breakdown->period_start ?? null)->format('Y-m-d')) }}"
                   class="form-control @error('period_start') is-invalid @enderror">
            @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Period End</label>
            <input type="date" name="period_end" value="{{ old('period_end', optional($breakdown->period_end ?? null)->format('Y-m-d')) }}"
                   class="form-control @error('period_end') is-invalid @enderror">
            @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Exchange Rate</label>
            <input type="number" step="0.000001" min="0" name="exchange_rate" value="{{ old('exchange_rate', $breakdown->exchange_rate ?? '') }}"
                   class="form-control @error('exchange_rate') is-invalid @enderror" placeholder="e.g. 159.555">
            @error('exchange_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Reported By</label>
            <input type="text" name="reported_by" value="{{ old('reported_by', $breakdown->reported_by ?? '') }}"
                   class="form-control @error('reported_by') is-invalid @enderror">
            @error('reported_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Billing Account Name</label>
            <input type="text" name="billing_account_name" value="{{ old('billing_account_name', $breakdown->billing_account_name ?? '') }}"
                   class="form-control @error('billing_account_name') is-invalid @enderror" placeholder="e.g. My Billing Account">
            @error('billing_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" value="{{ old('notes', $breakdown->notes ?? '') }}"
                   class="form-control @error('notes') is-invalid @enderror">
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- ===== Project lines ===== --}}
    <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
        <h6 class="mb-0"><i class="bi bi-table text-primary"></i> Project Cost Lines</h6>
        <button type="button" id="gcpAddRow" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Add Row</button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle gcp-lines-table" style="min-width: 1820px;">
            <thead class="table-light">
                <tr>
                    <th style="width:42px;">No</th>
                    <th style="min-width:160px;">Project Name</th>
                    <th style="min-width:220px;">Usage</th>
                    <th style="min-width:160px;">Billing Account</th>
                    <th style="min-width:150px;">Project ID</th>
                    <th style="min-width:140px;">Usage Start</th>
                    <th style="min-width:140px;">Usage End</th>
                    <th style="min-width:120px;">Billing Card</th>
                    <th style="min-width:120px;">Card Setting</th>
                    <th style="min-width:150px;" @if($isUsd)class="d-none"@endif>Cost (¥) @unless($isUsd)<span class="badge bg-primary-subtle text-primary-emphasis">primary</span>@endunless</th>
                    <th style="min-width:140px;" @unless($isUsd)class="d-none"@endunless>Cost ($) @if($isUsd)<span class="badge bg-primary-subtle text-primary-emphasis">primary</span>@endif</th>
                    <th style="min-width:120px;">Status</th>
                    <th style="width:44px;"></th>
                </tr>
            </thead>
            <tbody id="gcpLinesBody" data-currency="{{ $currency }}">
                @foreach($rows as $i => $row)
                <tr>
                    <td class="gcp-row-no text-muted">{{ $loop->iteration }}</td>
                    <td><input type="text" name="lines[{{ $i }}][project_name]" value="{{ $val($row, 'project_name') }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[{{ $i }}][usage]" value="{{ $val($row, 'usage') }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[{{ $i }}][billing_account_name]" value="{{ $val($row, 'billing_account_name') }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[{{ $i }}][project_id]" value="{{ $val($row, 'project_id') }}" class="form-control form-control-sm"></td>
                    <td><input type="date" name="lines[{{ $i }}][usage_start_date]" value="{{ $val($row, 'usage_start_date') }}" class="form-control form-control-sm"></td>
                    <td><input type="date" name="lines[{{ $i }}][usage_end_date]" value="{{ $val($row, 'usage_end_date') }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[{{ $i }}][billing_card]" value="{{ $val($row, 'billing_card') }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="lines[{{ $i }}][card_setting]" value="{{ $val($row, 'card_setting') }}" class="form-control form-control-sm"></td>
                    <td @if($isUsd)class="d-none"@endif><input type="number" step="0.000001" name="lines[{{ $i }}][cost_jpy]" value="{{ $val($row, 'cost_jpy') }}" class="form-control form-control-sm text-end gcp-cost-jpy" @disabled($isUsd) @if($isUsd)title="Yen cost is disabled for USD breakdowns"@endif></td>
                    <td @unless($isUsd)class="d-none"@endunless><input type="number" step="0.01" name="lines[{{ $i }}][cost_usd]" value="{{ $val($row, 'cost_usd') }}" class="form-control form-control-sm text-end gcp-cost-usd" @readonly(! $isUsd) @unless($isUsd)title="Auto-derived from the yen cost"@endunless></td>
                    <td><input type="text" name="lines[{{ $i }}][status]" value="{{ $val($row, 'status') }}" class="form-control form-control-sm" placeholder="e.g. Terminated"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger gcp-remove-row" title="Remove"><i class="bi bi-x-lg"></i></button></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @error('lines')<div class="text-danger small">{{ $message }}</div>@enderror

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> {{ $breakdown ? 'Save Changes' : 'Add Breakdown' }}</button>
        <a href="{{ $breakdown ? route('gcp-costs.show', $breakdown) : route('gcp-costs.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

{{-- Template row for the JS repeater (uses __INDEX__ placeholder). --}}
<template id="gcpLineTemplate">
    <tr>
        <td class="gcp-row-no text-muted"></td>
        <td><input type="text" name="lines[__INDEX__][project_name]" class="form-control form-control-sm"></td>
        <td><input type="text" name="lines[__INDEX__][usage]" class="form-control form-control-sm"></td>
        <td><input type="text" name="lines[__INDEX__][billing_account_name]" class="form-control form-control-sm"></td>
        <td><input type="text" name="lines[__INDEX__][project_id]" class="form-control form-control-sm"></td>
        <td><input type="date" name="lines[__INDEX__][usage_start_date]" class="form-control form-control-sm"></td>
        <td><input type="date" name="lines[__INDEX__][usage_end_date]" class="form-control form-control-sm"></td>
        <td><input type="text" name="lines[__INDEX__][billing_card]" class="form-control form-control-sm"></td>
        <td><input type="text" name="lines[__INDEX__][card_setting]" class="form-control form-control-sm"></td>
        <td @if($isUsd)class="d-none"@endif><input type="number" step="0.000001" name="lines[__INDEX__][cost_jpy]" class="form-control form-control-sm text-end gcp-cost-jpy" @disabled($isUsd) @if($isUsd)title="Yen cost is disabled for USD breakdowns"@endif></td>
        <td @unless($isUsd)class="d-none"@endunless><input type="number" step="0.01" name="lines[__INDEX__][cost_usd]" class="form-control form-control-sm text-end gcp-cost-usd" @readonly(! $isUsd) @unless($isUsd)title="Auto-derived from the yen cost"@endunless></td>
        <td><input type="text" name="lines[__INDEX__][status]" class="form-control form-control-sm" placeholder="e.g. Terminated"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger gcp-remove-row" title="Remove"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('gcpLinesBody');
        const tpl  = document.getElementById('gcpLineTemplate');
        const addBtn = document.getElementById('gcpAddRow');
        if (!body || !tpl || !addBtn) return;

        let nextIndex = {{ count($rows) }};

        function renumber() {
            body.querySelectorAll('tr').forEach(function (tr, i) {
                const cell = tr.querySelector('.gcp-row-no');
                if (cell) cell.textContent = i + 1;
            });
        }

        addBtn.addEventListener('click', function () {
            const html = tpl.innerHTML.replace(/__INDEX__/g, nextIndex++);
            body.insertAdjacentHTML('beforeend', html);
            renumber();
        });

        body.addEventListener('click', function (e) {
            const btn = e.target.closest('.gcp-remove-row');
            if (!btn) return;
            const rows = body.querySelectorAll('tr');
            if (rows.length <= 1) {
                // Keep at least one row — just clear it instead of removing.
                btn.closest('tr').querySelectorAll('input').forEach(i => i.value = '');
            } else {
                btn.closest('tr').remove();
            }
            renumber();
        });

        // Convenience: when a JPY cost is entered and the row's USD cell is still
        // empty, derive USD from the breakdown's exchange rate (JPY per USD).
        // USD breakdowns enter dollars directly and keep the yen cost blank, so
        // the derive is skipped there (otherwise the line would classify as JPY).
        const currency = body.dataset.currency || 'JPY';
        const rateInput = document.querySelector('input[name="exchange_rate"]');
        body.addEventListener('input', function (e) {
            if (currency !== 'JPY') return;
            const jpy = e.target.closest('.gcp-cost-jpy');
            if (!jpy) return;
            const rate = parseFloat(rateInput && rateInput.value);
            const usd = jpy.closest('tr').querySelector('.gcp-cost-usd');
            // Recompute read-only (auto-derived) cells freely; never clobber a
            // value the user typed into an editable USD cell.
            if (!usd || (!usd.readOnly && usd.value !== '') || !rate || rate <= 0) return;
            const amount = parseFloat(jpy.value);
            if (!isNaN(amount)) {
                usd.value = (amount / rate).toFixed(2);
            }
        });

        renumber();
    })();
</script>
@endpush
