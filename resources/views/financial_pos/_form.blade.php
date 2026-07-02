@php
    $po = $po ?? null;
    $action = $po ? route('financial-pos.update', $po) : route('financial-pos.store');
    $categories = ['PC', 'Laptop', 'UPS', 'Hardware', 'Printer', 'Networking', 'Peripheral', 'Server', 'Other'];
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($po) @method('PUT') @endif

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
            <input type="text" name="vendor_name" value="{{ old('vendor_name', $po->vendor_name ?? '') }}"
                   class="form-control @error('vendor_name') is-invalid @enderror" placeholder="Supplier / shop name">
            @error('vendor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">PO Date <span class="text-danger">*</span></label>
            <input type="date" name="po_date"
                   value="{{ old('po_date', optional($po->po_date ?? null)->format('Y-m-d') ?: now()->format('Y-m-d')) }}"
                   class="form-control @error('po_date') is-invalid @enderror" required>
            @error('po_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">PO Number</label>
            <input type="text" name="po_number" value="{{ old('po_number', $po->po_number ?? '') }}"
                   class="form-control @error('po_number') is-invalid @enderror" placeholder="Auto-generated if blank">
            @error('po_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Currency <span class="text-danger">*</span></label>
            <select name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                @foreach($currencies as $cur)
                    <option value="{{ $cur }}" @selected(old('currency', $po->currency ?? 'MMK') === $cur)>{{ \App\Models\FinancialPo::CURRENCIES[$cur] ?? $cur }}</option>
                @endforeach
            </select>
            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" inputmode="decimal" name="total_amount"
                   value="{{ old('total_amount', $po->total_amount ?? '') }}"
                   class="form-control @error('total_amount') is-invalid @enderror" required>
            @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                      placeholder="Specs, serial numbers, warranty, etc.">{{ old('notes', $po->notes ?? '') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> {{ $po ? 'Save Changes' : 'Add Purchase Order' }}</button>
        <a href="{{ $po ? route('financial-pos.show', $po) : route('financial-pos.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
