@extends('layouts.app')

@section('title', 'Edit P.O. ' . $renewal->po_number)

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Purchase Order</h1>
        <div class="page-subtitle">
            {{ $renewal->po_number }} &middot;
            {{ $renewal->subscription->subscription_name }}
            <span class="text-muted">({{ $renewal->subscription->service_type }})</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('subscriptions.renewals.pdf', $renewal) }}" target="_blank" class="quick-action">
            <i class="bi bi-file-earmark-pdf"></i> Current PDF
        </a>
        <a href="{{ route('purchase-orders.index') }}" class="quick-action">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the errors below:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">

        <div class="alert alert-info small mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Editing is only available while the P.O. is a draft or awaiting first approval. Saving regenerates the PDF;
            no email is sent. Use the mail icons on the Renewal Process list to dispatch approval requests.
        </div>

        <form method="POST" action="{{ route('purchase-orders.update', $renewal) }}">
            @csrf
            @method('PUT')

            <h6 class="text-muted text-uppercase small mb-2">P.O. details</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control" required maxlength="255"
                           value="{{ old('subject', $renewal->subject) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" maxlength="255"
                           value="{{ old('reference', $renewal->reference) }}">
                </div>
            </div>

            <h6 class="text-muted text-uppercase small mb-2">Vendor</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Vendor company</label>
                    <input type="text" name="vendor_company" class="form-control" maxlength="255"
                           value="{{ old('vendor_company', $renewal->vendor_company) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contact name</label>
                    <input type="text" name="vendor_name" class="form-control" maxlength="255"
                           value="{{ old('vendor_name', $renewal->vendor_name) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone / Email</label>
                    <input type="text" name="vendor_phone_email" class="form-control" maxlength="255"
                           value="{{ old('vendor_phone_email', $renewal->vendor_phone_email) }}">
                </div>
            </div>

            <h6 class="text-muted text-uppercase small mb-2">Approver</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-12">
                    <label class="form-label">Pick a registered user (optional)</label>
                    <select name="approver_user_id" class="form-select" id="poEditApproverSelect">
                        <option value="">— External recipient —</option>
                        @foreach($approverChoices as $u)
                            <option value="{{ $u->id }}"
                                    data-name="{{ $u->name }}"
                                    data-email="{{ $u->email }}"
                                    @selected(old('approver_user_id', $renewal->approver_user_id) == $u->id)>
                                {{ $u->name }} &lt;{{ $u->email }}&gt;
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Approver name <span class="text-danger">*</span></label>
                    <input type="text" name="approver_name" class="form-control" required maxlength="255"
                           value="{{ old('approver_name', $renewal->approver_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Approver email <span class="text-danger">*</span></label>
                    <input type="email" name="approver_email" class="form-control" required maxlength="255"
                           value="{{ old('approver_email', $renewal->approver_email) }}">
                </div>
            </div>

            <h6 class="text-muted text-uppercase small mb-2">Pricing</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="1" required
                           id="poEditQty" value="{{ old('quantity', $renewal->quantity) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit price <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" min="0" step="0.01" required
                           id="poEditUnit" value="{{ old('unit_price', $renewal->unit_price) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency <span class="text-danger">*</span></label>
                    <select name="currency" class="form-select" required>
                        @foreach(['MMK', 'JPY', 'USD'] as $c)
                            <option value="{{ $c }}" @selected(old('currency', $renewal->currency) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" id="poEditTotal" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" maxlength="2000">{{ old('notes', $renewal->notes) }}</textarea>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-link">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save changes
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('poEditApproverSelect');
    const nameInput = document.querySelector('[name="approver_name"]');
    const emailInput = document.querySelector('[name="approver_email"]');
    if (sel) {
        sel.addEventListener('change', () => {
            const opt = sel.selectedOptions[0];
            if (!opt || !opt.value) return;
            if (opt.dataset.name) nameInput.value = opt.dataset.name;
            if (opt.dataset.email) emailInput.value = opt.dataset.email;
        });
    }

    function recalc() {
        const q = parseFloat(document.getElementById('poEditQty').value || '0');
        const u = parseFloat(document.getElementById('poEditUnit').value || '0');
        document.getElementById('poEditTotal').value = (q * u).toFixed(2);
    }
    document.getElementById('poEditQty')?.addEventListener('input', recalc);
    document.getElementById('poEditUnit')?.addEventListener('input', recalc);
    recalc();
})();
</script>
@endpush
