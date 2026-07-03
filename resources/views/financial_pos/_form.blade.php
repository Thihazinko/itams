@php
    $po = $po ?? null;
    $action = $po ? route('financial-pos.update', $po) : route('financial-pos.store');
    $categories = ['PC', 'Laptop', 'UPS', 'Hardware', 'Printer', 'Networking', 'Peripheral', 'Server', 'Other'];
    // The source selector (and its pickers) is only offered when creating a PO.
    // Editing is limited to one-time POs, so the source never changes there.
    $showSource = ! $po;
    $subscriptions = $subscriptions ?? collect();
    $licenses = $licenses ?? collect();
    $selSource = old('source', 'manual');
    $selCurrency = old('currency', $po->currency ?? 'MMK');
    $sourceOptions = [
        'manual'           => ['label' => 'One-Time Purchase', 'icon' => 'bi-cart-plus',    'accent' => 'success', 'desc' => 'PC, UPS, hardware — entered by hand'],
        'subscription'     => ['label' => 'Subscription',       'icon' => 'bi-arrow-repeat', 'accent' => 'primary', 'desc' => 'Link a registered subscription'],
        'license_contract' => ['label' => 'License & Contract',  'icon' => 'bi-key',          'accent' => 'warning', 'desc' => 'Link a registered license'],
    ];
@endphp

@if($showSource)
<style>
    .source-option { border:1px solid var(--bs-border-color); border-radius:.6rem; cursor:pointer; transition:border-color .15s, background-color .15s, box-shadow .15s; position:relative; }
    .source-option:hover { border-color:var(--bs-secondary-color); }
    .source-option .source-check { position:absolute; top:.55rem; right:.6rem; opacity:0; transition:opacity .15s; }
    .btn-check:checked + .source-option .source-check { opacity:1; }
    .btn-check:focus-visible + .source-option { box-shadow:0 0 0 .2rem rgba(13,110,253,.25); }
    .btn-check:checked + .source-option[data-accent="success"] { border-color:var(--bs-success); background:rgba(25,135,84,.07);  box-shadow:inset 0 0 0 1px var(--bs-success); }
    .btn-check:checked + .source-option[data-accent="primary"] { border-color:var(--bs-primary); background:rgba(13,110,253,.07);  box-shadow:inset 0 0 0 1px var(--bs-primary); }
    .btn-check:checked + .source-option[data-accent="warning"] { border-color:var(--bs-warning); background:rgba(255,193,7,.10);   box-shadow:inset 0 0 0 1px var(--bs-warning); }
</style>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if($po) @method('PUT') @endif

    @if($showSource)
    <div class="mb-4">
        <div class="text-uppercase small fw-semibold text-muted mb-2" style="letter-spacing:.04em;">
            <i class="bi bi-diagram-3 me-1"></i>Purchase Source <span class="text-danger">*</span>
        </div>
        <div class="row g-2" id="poSourceGroup">
            @foreach($sourceOptions as $val => $opt)
            <div class="col-md-4">
                <input type="radio" class="btn-check" name="source" id="src_{{ $val }}" value="{{ $val }}" autocomplete="off" @checked($selSource === $val)>
                <label class="source-option d-block h-100 p-3" for="src_{{ $val }}" data-accent="{{ $opt['accent'] }}">
                    <i class="bi bi-check-circle-fill text-{{ $opt['accent'] }} source-check"></i>
                    <i class="bi {{ $opt['icon'] }} fs-4 text-{{ $opt['accent'] }}"></i>
                    <span class="fw-semibold d-block mt-1">{{ $opt['label'] }}</span>
                    <span class="small text-muted">{{ $opt['desc'] }}</span>
                </label>
            </div>
            @endforeach
        </div>
        @error('source')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('subscription_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('license_contract_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

        {{-- Linked-record panels: shown only for the matching source. --}}
        <div class="mt-3 d-none" data-source-panel="subscription">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 rounded-3 border border-primary-subtle bg-primary-subtle">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-repeat fs-5 text-primary"></i>
                    <div>
                        <div class="small text-uppercase fw-semibold text-primary-emphasis" style="letter-spacing:.03em;">Linked Subscription</div>
                        <div class="fw-semibold" data-linked-name="subscription">No subscription selected yet</div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#subscriptionPickerModal">
                    <i class="bi bi-search"></i> Choose
                </button>
            </div>
            <input type="hidden" name="subscription_id" value="{{ old('subscription_id') }}">
        </div>

        <div class="mt-3 d-none" data-source-panel="license_contract">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 rounded-3 border border-warning-subtle bg-warning-subtle">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-key fs-5 text-warning-emphasis"></i>
                    <div>
                        <div class="small text-uppercase fw-semibold text-warning-emphasis" style="letter-spacing:.03em;">Linked License &amp; Contract</div>
                        <div class="fw-semibold" data-linked-name="license_contract">No license selected yet</div>
                    </div>
                </div>
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#licensePickerModal">
                    <i class="bi bi-search"></i> Choose
                </button>
            </div>
            <input type="hidden" name="license_contract_id" value="{{ old('license_contract_id') }}">
        </div>
    </div>

    <hr class="my-4">
    @endif

    <div class="text-uppercase small fw-semibold text-muted mb-2" style="letter-spacing:.04em;">
        <i class="bi bi-info-circle me-1"></i>Purchase Details
    </div>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Subject / Item <span class="text-danger">*</span></label>
            <input type="text" name="subject" value="{{ old('subject', $po->subject ?? '') }}"
                   class="form-control @error('subject') is-invalid @enderror"
                   placeholder="e.g. Dell OptiPlex PC, APC UPS 1100VA" required>
            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Category</label>
            <input type="text" name="category" list="poCategories" value="{{ old('category', $po->category ?? '') }}"
                   class="form-control @error('category') is-invalid @enderror" placeholder="e.g. PC, UPS, Hardware">
            <datalist id="poCategories">
                @foreach($categories as $c)<option value="{{ $c }}">@endforeach
            </datalist>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Vendor</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent"><i class="bi bi-shop text-muted"></i></span>
                <input type="text" name="vendor_name" value="{{ old('vendor_name', $po->vendor_name ?? '') }}"
                       class="form-control @error('vendor_name') is-invalid @enderror" placeholder="Supplier / shop name">
                @error('vendor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">PO Date <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-transparent"><i class="bi bi-calendar3 text-muted"></i></span>
                <input type="date" name="po_date"
                       value="{{ old('po_date', optional($po->po_date ?? null)->format('Y-m-d') ?: now()->format('Y-m-d')) }}"
                       class="form-control @error('po_date') is-invalid @enderror" required>
                @error('po_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">PO Number</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent"><i class="bi bi-hash text-muted"></i></span>
                <input type="text" name="po_number" value="{{ old('po_number', $po->po_number ?? '') }}"
                       class="form-control @error('po_number') is-invalid @enderror" placeholder="Auto if blank">
                @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-text">Leave blank to generate automatically.</div>
        </div>
    </div>

    <div class="text-uppercase small fw-semibold text-muted mt-4 mb-2" style="letter-spacing:.04em;">
        <i class="bi bi-cash-stack me-1"></i>Cost
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Currency <span class="text-danger">*</span></label>
            <select name="currency" id="poCurrency" class="form-select @error('currency') is-invalid @enderror" required>
                @foreach($currencies as $cur)
                    <option value="{{ $cur }}" @selected($selCurrency === $cur)>{{ \App\Models\FinancialPo::CURRENCIES[$cur] ?? $cur }}</option>
                @endforeach
            </select>
            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text fw-semibold" id="amountCurrency">{{ $selCurrency }}</span>
                <input type="number" step="0.01" min="0" inputmode="decimal" name="total_amount"
                       value="{{ old('total_amount', $po->total_amount ?? '') }}"
                       class="form-control @error('total_amount') is-invalid @enderror" placeholder="0.00" required>
                @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="text-uppercase small fw-semibold text-muted mt-4 mb-2" style="letter-spacing:.04em;">
        <i class="bi bi-journal-text me-1"></i>Notes
    </div>
    <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
              placeholder="Specs, serial numbers, warranty, etc.">{{ old('notes', $po->notes ?? '') }}</textarea>
    @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

    <div class="d-flex gap-2 mt-4 pt-3 border-top">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> {{ $po ? 'Save Changes' : 'Add Purchase Order' }}</button>
        <a href="{{ $po ? route('financial-pos.show', $po) : route('financial-pos.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

{{-- Keep the Amount prefix in sync with the currency (both create and edit). --}}
@push('scripts')
<script>
    (function () {
        const sel = document.getElementById('poCurrency');
        const badge = document.getElementById('amountCurrency');
        if (!sel || !badge) return;
        const sync = function () { badge.textContent = sel.value; };
        sel.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush

@if($showSource)
{{-- ===== Subscription picker ===== --}}
<div class="modal fade" id="subscriptionPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat text-primary"></i> Choose a Subscription
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $subscriptions->count() }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" placeholder="Search subscriptions…" data-picker-search="subscriptionPickerList">
                </div>
                <div class="list-group" id="subscriptionPickerList">
                    @forelse($subscriptions as $s)
                        @php $name = $s->subscription_name ?: ($s->service_type ?: 'Untitled subscription'); @endphp
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                data-picker-item
                                data-id="{{ $s->id }}"
                                data-name="{{ $name }}"
                                data-vendor="{{ $s->vendor_name }}"
                                data-currency="{{ $s->currency }}"
                                data-amount="{{ $s->renewal_cost }}"
                                data-search="{{ strtolower($name . ' ' . $s->vendor_name . ' ' . $s->service_type) }}">
                            <span>
                                <span class="fw-semibold">{{ $name }}</span>
                                @if($s->vendor_name)<span class="text-muted small ms-1">· {{ $s->vendor_name }}</span>@endif
                            </span>
                            @if($s->renewal_cost !== null)
                                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $s->currency }} {{ number_format((float) $s->renewal_cost, 2) }}</span>
                            @endif
                        </button>
                    @empty
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;"></i> No subscriptions registered.</div>
                    @endforelse
                </div>
                <div class="text-center text-muted py-4 d-none" data-picker-empty="subscriptionPickerList">No subscriptions match your search.</div>
            </div>
        </div>
    </div>
</div>

{{-- ===== License & Contract picker (permanent licenses excluded) ===== --}}
<div class="modal fade" id="licensePickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key text-warning"></i> Choose a License &amp; Contract
                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $licenses->count() }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" placeholder="Search licenses…" data-picker-search="licensePickerList">
                </div>
                <div class="list-group" id="licensePickerList">
                    @forelse($licenses as $l)
                        @php $name = $l->software_name ?: 'Untitled license'; @endphp
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                data-picker-item
                                data-id="{{ $l->id }}"
                                data-name="{{ $name }}"
                                data-vendor="{{ $l->vendor_name }}"
                                data-currency="{{ $l->currency }}"
                                data-amount="{{ $l->renewal_cost }}"
                                data-search="{{ strtolower($name . ' ' . $l->vendor_name) }}">
                            <span>
                                <span class="fw-semibold">{{ $name }}</span>
                                @if($l->vendor_name)<span class="text-muted small ms-1">· {{ $l->vendor_name }}</span>@endif
                            </span>
                            @if($l->renewal_cost !== null)
                                <span class="badge bg-warning-subtle text-warning-emphasis">{{ $l->currency }} {{ number_format((float) $l->renewal_cost, 2) }}</span>
                            @endif
                        </button>
                    @empty
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;"></i> No (non-permanent) licenses registered.</div>
                    @endforelse
                </div>
                <div class="text-center text-muted py-4 d-none" data-picker-empty="licensePickerList">No licenses match your search.</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const form = document.querySelector('form[action="{{ $action }}"]');
        if (!form) return;

        const panels = form.querySelectorAll('[data-source-panel]');
        const fieldFor = (name) => form.querySelector('[name="' + name + '"]');
        const currencySel = document.getElementById('poCurrency');

        // Show only the panel matching the chosen source, and clear the link id
        // of any source that isn't active so a stale value can't be submitted.
        function applySource(source) {
            panels.forEach(function (panel) {
                const match = panel.dataset.sourcePanel === source;
                panel.classList.toggle('d-none', !match);
                if (!match) {
                    const hidden = panel.querySelector('input[type="hidden"]');
                    if (hidden) hidden.value = '';
                    const label = panel.querySelector('[data-linked-name]');
                    if (label) label.textContent = label.dataset.placeholder || label.textContent;
                }
            });
        }

        form.querySelectorAll('input[name="source"]').forEach(function (radio) {
            radio.addEventListener('change', function () { applySource(this.value); });
        });

        // Fill the PO fields from a picked record.
        function choose(source, item) {
            const panel = form.querySelector('[data-source-panel="' + source + '"]');
            if (!panel) return;
            const hidden = panel.querySelector('input[type="hidden"]');
            if (hidden) hidden.value = item.dataset.id;
            const label = panel.querySelector('[data-linked-name]');
            if (label) label.textContent = item.dataset.name;

            const subject = fieldFor('subject');
            if (subject) subject.value = item.dataset.name || '';

            const vendor = fieldFor('vendor_name');
            if (vendor && item.dataset.vendor) vendor.value = item.dataset.vendor;

            if (currencySel && item.dataset.currency) { currencySel.value = item.dataset.currency; currencySel.dispatchEvent(new Event('change')); }

            const amount = fieldFor('total_amount');
            if (amount && item.dataset.amount) amount.value = item.dataset.amount;
        }

        document.querySelectorAll('#subscriptionPickerModal [data-picker-item]').forEach(function (item) {
            item.addEventListener('click', function () {
                choose('subscription', item);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionPickerModal')).hide();
            });
        });
        document.querySelectorAll('#licensePickerModal [data-picker-item]').forEach(function (item) {
            item.addEventListener('click', function () {
                choose('license_contract', item);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('licensePickerModal')).hide();
            });
        });

        // Live filter inside each picker modal.
        document.querySelectorAll('[data-picker-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                const list = document.getElementById(input.dataset.pickerSearch);
                const empty = document.querySelector('[data-picker-empty="' + input.dataset.pickerSearch + '"]');
                const term = this.value.trim().toLowerCase();
                let shown = 0;
                list.querySelectorAll('[data-picker-item]').forEach(function (item) {
                    const hit = !term || (item.dataset.search || '').indexOf(term) !== -1;
                    item.classList.toggle('d-none', !hit);
                    if (hit) shown++;
                });
                if (empty) empty.classList.toggle('d-none', shown !== 0);
            });
        });

        // Autofocus the search box when a picker opens.
        ['subscriptionPickerModal', 'licensePickerModal'].forEach(function (id) {
            const modal = document.getElementById(id);
            if (modal) modal.addEventListener('shown.bs.modal', function () {
                const search = modal.querySelector('[data-picker-search]');
                if (search) search.focus();
            });
        });

        // Remember the placeholder text so clearing a panel can restore it.
        form.querySelectorAll('[data-linked-name]').forEach(function (el) {
            el.dataset.placeholder = el.textContent;
        });

        // Restore the correct panel on load (e.g. after a validation error).
        const checked = form.querySelector('input[name="source"]:checked');
        applySource(checked ? checked.value : 'manual');

        // Re-label the active panel from its retained link id (old input).
        panels.forEach(function (panel) {
            if (panel.classList.contains('d-none')) return;
            const hidden = panel.querySelector('input[type="hidden"]');
            if (!hidden || !hidden.value) return;
            const modalId = panel.dataset.sourcePanel === 'subscription' ? 'subscriptionPickerModal' : 'licensePickerModal';
            const item = document.querySelector('#' + modalId + ' [data-picker-item][data-id="' + hidden.value + '"]');
            const label = panel.querySelector('[data-linked-name]');
            if (item && label) label.textContent = item.dataset.name;
        });
    })();
</script>
@endpush
@endif
