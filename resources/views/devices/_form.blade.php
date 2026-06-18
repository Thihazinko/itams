@csrf
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-tag text-primary"></i><strong>Identification</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                <input type="text" name="item_name" value="{{ old('item_name', $device->item_name ?? '') }}" class="form-control @error('item_name') is-invalid @enderror" placeholder="e.g. Logitech MX Master 3" required>
                @error('item_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <input type="text" name="category" value="{{ old('category', $device->category ?? '') }}" class="form-control @error('category') is-invalid @enderror" placeholder="e.g. Peripheral, Networking">
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Serial Number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number ?? '') }}" class="form-control @error('serial_number') is-invalid @enderror" placeholder="e.g. SN-12345">
                @error('serial_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Qty <span class="text-danger">*</span></label>
                <input type="number" min="1" step="1" name="qty" value="{{ old('qty', $device->qty ?? 1) }}" class="form-control @error('qty') is-invalid @enderror" required>
                @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach(\App\Models\Device::STATUSES as $s)
                        <option value="{{ $s }}" @selected(old('status', $device->status ?? 'Free') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Brief description of the device or its purpose">{{ old('description', $device->description ?? '') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-truck text-primary"></i><strong>Logistics</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Location</label>
                <input type="text" name="location" value="{{ old('location', $device->location ?? '') }}" class="form-control @error('location') is-invalid @enderror" placeholder="e.g. Server Room A">
                @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Delivery Date</label>
                <input type="date" name="delivery_date" value="{{ old('delivery_date', isset($device->delivery_date) ? $device->delivery_date->format('Y-m-d') : '') }}" class="form-control @error('delivery_date') is-invalid @enderror">
                @error('delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Delivery Location</label>
                <input type="text" name="delivery_location" value="{{ old('delivery_location', $device->delivery_location ?? '') }}" class="form-control @error('delivery_location') is-invalid @enderror">
                @error('delivery_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check text-primary"></i><strong>Lifecycle</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Vendor</label>
                <input type="text" name="vendor" value="{{ old('vendor', $device->vendor ?? '') }}" class="form-control @error('vendor') is-invalid @enderror">
                @error('vendor')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Purchased Date</label>
                <input type="date" name="purchased_date" value="{{ old('purchased_date', isset($device->purchased_date) ? $device->purchased_date->format('Y-m-d') : '') }}" class="form-control @error('purchased_date') is-invalid @enderror">
                @error('purchased_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Warranty</label>
                <input type="text" name="warranty" value="{{ old('warranty', $device->warranty ?? '') }}" class="form-control @error('warranty') is-invalid @enderror" placeholder="e.g. 2 years">
                @error('warranty')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3">{{ old('remark', $device->remark ?? '') }}</textarea>
                @error('remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
