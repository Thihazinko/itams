@csrf
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-tag text-primary"></i><strong>General</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Software / Contract Name <span class="text-danger">*</span></label>
                <input type="text" name="software_name" value="{{ old('software_name', $item->software_name ?? '') }}" class="form-control @error('software_name') is-invalid @enderror" placeholder="e.g. Microsoft 365 Business Premium" required>
                @error('software_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @foreach(['Active', 'Pending', 'Expired', 'Terminated'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $item->status ?? 'Active') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vendor</label>
                <input type="text" name="vendor_name" value="{{ old('vendor_name', $item->vendor_name ?? '') }}" class="form-control" placeholder="e.g. Microsoft, Adobe">
            </div>
            <div class="col-12">
                <label class="form-label">License / Serial / Invoice Info</label>
                <textarea name="license_info" class="form-control" rows="3" placeholder="License key, serial number, invoice reference…">{{ old('license_info', $item->license_info ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-arrow-repeat text-primary"></i><strong>Renewal</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Renewal Type</label>
                <select name="renewal_type" class="form-select">
                    @foreach(['Yearly', 'Monthly', 'Pay as you go', 'One Time'] as $t)
                        <option value="{{ $t }}" @selected(old('renewal_type', $item->renewal_type ?? 'Yearly') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Last Renewal</label>
                <input type="date" name="last_renewal_date" value="{{ old('last_renewal_date', isset($item->last_renewal_date) ? $item->last_renewal_date->format('Y-m-d') : '') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Expire Date <span class="text-danger">*</span></label>
                @php($isPermanent = (bool) old('expire_permanent', $item->expire_permanent ?? false))
                <input type="date" name="expire_date" data-expire-date value="{{ old('expire_date', isset($item->expire_date) ? $item->expire_date->format('Y-m-d') : '') }}" class="form-control @error('expire_date') is-invalid @enderror" @required(!$isPermanent) @disabled($isPermanent)>
                <small class="text-muted">Reminder fires 30 days before this date.</small>
                @error('expire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-check mt-1">
                    <input type="hidden" name="expire_permanent" value="0">
                    <input type="checkbox" name="expire_permanent" value="1" id="expire_permanent" data-expire-permanent class="form-check-input" @checked($isPermanent)>
                    <label for="expire_permanent" class="form-check-label small">Permanent (no expiry — disables reminders)</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-currency-exchange text-primary"></i>
        <strong>Pricing</strong>
        <span class="text-muted small ms-2">Price-change indicator on the list is computed from previous vs renewal cost.</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select">
                    @foreach(\App\Models\LicenseContract::CURRENCIES as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency', $item->currency ?? 'MMK') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Previous Cost</label>
                <input type="number" step="0.01" min="0" name="previous_cost" value="{{ old('previous_cost', $item->previous_cost ?? '') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Renewal Cost</label>
                <input type="number" step="0.01" min="0" name="renewal_cost" value="{{ old('renewal_cost', $item->renewal_cost ?? '') }}" class="form-control">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-sticky text-primary"></i><strong>Notes</strong>
    </div>
    <div class="card-body">
        <label class="form-label visually-hidden">Remarks</label>
        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes about this license or contract">{{ old('remarks', $item->remarks ?? '') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('licenses-contracts.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const permanent = document.querySelector('[data-expire-permanent]');
        const expireDate = document.querySelector('[data-expire-date]');
        if (!permanent || !expireDate) return;

        const sync = () => {
            expireDate.disabled = permanent.checked;
            expireDate.required = !permanent.checked;
            if (permanent.checked) expireDate.value = '';
        };

        permanent.addEventListener('change', sync);
        sync();
    })();
</script>
