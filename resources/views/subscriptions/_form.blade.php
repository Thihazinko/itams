@csrf
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-tag text-primary"></i><strong>Service Info</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Service Type <span class="text-danger">*</span></label>
                <input type="text" name="service_type" value="{{ old('service_type', $subscription->service_type ?? '') }}" class="form-control @error('service_type') is-invalid @enderror" maxlength="100" placeholder="e.g. Domain, SSL, Hosting, Cloud Service" required>
                @error('service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" name="project_name" value="{{ old('project_name', $subscription->project_name ?? '') }}" class="form-control @error('project_name') is-invalid @enderror" required>
                @error('project_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Subscription Name <span class="text-danger">*</span></label>
                <input type="text" name="subscription_name" value="{{ old('subscription_name', $subscription->subscription_name ?? '') }}" class="form-control @error('subscription_name') is-invalid @enderror" placeholder="e.g. company.com, Wildcard SSL" required>
                @error('subscription_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Vendor Name</label>
                <input type="text" name="vendor_name" value="{{ old('vendor_name', $subscription->vendor_name ?? '') }}" class="form-control" placeholder="e.g. GoDaddy, AWS, Google">
            </div>
            <div class="col-md-4">
                <label class="form-label">Period</label>
                <input type="text" name="period" value="{{ old('period', $subscription->period ?? '') }}" class="form-control" placeholder="e.g. 1 Year">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Active" @selected(old('status', $subscription->status ?? 'Active') === 'Active')>Active</option>
                    <option value="Terminated" @selected(old('status', $subscription->status ?? '') === 'Terminated')>Terminated</option>
                </select>
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
                <label class="form-label">Expire Date <span class="text-danger">*</span></label>
                <input type="date" name="expire_date" value="{{ old('expire_date', isset($subscription->expire_date) ? $subscription->expire_date->format('Y-m-d') : '') }}" class="form-control @error('expire_date') is-invalid @enderror" required>
                <small class="text-muted">Reminder fires 30 days before this date.</small>
                @error('expire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Start Using Date</label>
                <input type="date" name="start_using_date" value="{{ old('start_using_date', isset($subscription->start_using_date) ? $subscription->start_using_date->format('Y-m-d') : '') }}" class="form-control">
                <small class="text-muted">When this subscription was first put to use.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Renewal Type</label>
                <select name="renewal_type" class="form-select">
                    @foreach(['Yearly', 'Monthly', 'Pay as you go', 'One Time'] as $t)
                        <option value="{{ $t }}" @selected(old('renewal_type', $subscription->renewal_type ?? 'Yearly') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Renewal Status</label>
                <select name="renewal_status" class="form-select">
                    @foreach(['Pending', 'Renewed', 'Expired', 'Cancelled'] as $s)
                        <option value="{{ $s }}" @selected(old('renewal_status', $subscription->renewal_status ?? 'Pending') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
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
                    @foreach(\App\Models\Subscription::CURRENCIES as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency', $subscription->currency ?? 'MMK') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Previous Cost</label>
                <input type="number" step="0.01" min="0" name="previous_cost" value="{{ old('previous_cost', $subscription->previous_cost ?? '') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Renewal Cost</label>
                <input type="number" step="0.01" min="0" name="renewal_cost" value="{{ old('renewal_cost', $subscription->renewal_cost ?? '') }}" class="form-control">
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
        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes about this subscription">{{ old('remarks', $subscription->remarks ?? '') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
