@csrf
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-tag text-primary"></i><strong>Identification</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Computer ID <span class="text-danger">*</span></label>
                <input type="text" name="computer_id" value="{{ old('computer_id', $asset->computer_id ?? '') }}" class="form-control @error('computer_id') is-invalid @enderror" placeholder="e.g. PC-001" required>
                @error('computer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Hostname <span class="text-danger">*</span></label>
                <input type="text" name="hostname" value="{{ old('hostname', $asset->hostname ?? '') }}" class="form-control @error('hostname') is-invalid @enderror" placeholder="e.g. IT-WS01" required>
                @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Employee Name</label>
                <input type="text" name="employee_name" value="{{ old('employee_name', $asset->employee_name ?? '') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach(['Free', 'Active', 'Damage', 'Retirement', 'Low Performance'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $asset->status ?? 'Free') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department" class="form-select">
                    <option value="">—</option>
                    @foreach(\App\Models\PcAsset::DEPARTMENTS as $d)
                        <option value="{{ $d }}" @selected(old('department', $asset->department ?? '') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <select name="location" class="form-select">
                    <option value="Office" @selected(old('location', $asset->location ?? 'Office') === 'Office')>Office</option>
                    <option value="WFH" @selected(old('location', $asset->location ?? '') === 'WFH')>WFH</option>
                    <option value="Other" @selected(old('location', $asset->location ?? '') === 'Other')>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Operating System</label>
                <input type="text" name="operating_system" value="{{ old('operating_system', $asset->operating_system ?? '') }}" class="form-control" placeholder="e.g. Windows 11 Pro">
            </div>
            <div class="col-md-4">
                <label class="form-label">License Key</label>
                <input type="text" name="license_key" value="{{ old('license_key', $asset->license_key ?? '') }}" class="form-control" placeholder="e.g. XXXXX-XXXXX-XXXXX-XXXXX-XXXXX">
            </div>
            <div class="col-md-2">
                <label class="form-label">Expire Date</label>
                @php($isPermanent = (bool) old('expire_permanent', $asset->expire_permanent ?? false))
                <input type="date" name="expire_date" data-expire-date value="{{ old('expire_date', isset($asset->expire_date) ? $asset->expire_date->format('Y-m-d') : '') }}" class="form-control" @disabled($isPermanent)>
                <div class="form-check mt-1">
                    <input type="hidden" name="expire_permanent" value="0">
                    <input type="checkbox" name="expire_permanent" value="1" id="expire_permanent" data-expire-permanent class="form-check-input" @checked($isPermanent)>
                    <label for="expire_permanent" class="form-check-label small">Permanent</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-cpu text-primary"></i><strong>Hardware</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Brand</label>
                <input type="text" name="brand" value="{{ old('brand', $asset->brand ?? '') }}" class="form-control" placeholder="e.g. Dell">
            </div>
            <div class="col-md-4">
                <label class="form-label">Model</label>
                <input type="text" name="model" value="{{ old('model', $asset->model ?? '') }}" class="form-control" placeholder="e.g. Latitude 5430">
            </div>
            <div class="col-md-4">
                <label class="form-label">Serial Number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}" class="form-control">
            </div>

            <div class="col-md-3"><label class="form-label">CPU</label><input type="text" name="cpu" value="{{ old('cpu', $asset->cpu ?? '') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">RAM</label><input type="text" name="ram" value="{{ old('ram', $asset->ram ?? '') }}" class="form-control" placeholder="16GB"></div>
            <div class="col-md-2"><label class="form-label">SSD</label><input type="text" name="ssd" value="{{ old('ssd', $asset->ssd ?? '') }}" class="form-control" placeholder="512GB"></div>
            <div class="col-md-2"><label class="form-label">HDD</label><input type="text" name="hdd" value="{{ old('hdd', $asset->hdd ?? '') }}" class="form-control" placeholder="1TB"></div>
            <div class="col-md-3"><label class="form-label">Display</label><input type="text" name="display" value="{{ old('display', $asset->display ?? '') }}" class="form-control" placeholder='14" FHD'></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-shield-lock text-primary"></i>
        <strong>Credentials</strong>
        <span class="text-muted small ms-2">Stored encrypted at rest. Leave blank to keep existing values.</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Admin Password</label>
                <input type="text" name="admin_password" value="{{ old('admin_password', isset($asset) ? $asset->admin_password : '') }}" class="form-control" placeholder="{{ isset($asset->id) ? 'leave blank to keep' : '' }}" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="{{ old('username', isset($asset) ? $asset->username : '') }}" class="form-control" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label">Password</label>
                <input type="text" name="password" value="{{ old('password', isset($asset) ? $asset->password : '') }}" class="form-control" placeholder="{{ isset($asset->id) ? 'leave blank to keep' : '' }}" autocomplete="off">
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
                <label class="form-label">Purchased Date</label>
                <input type="date" name="purchased_date" value="{{ old('purchased_date', isset($asset->purchased_date) ? $asset->purchased_date->format('Y-m-d') : '') }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Warranty Period</label>
                <input type="text" name="warranty_period" value="{{ old('warranty_period', $asset->warranty_period ?? '') }}" class="form-control" placeholder="e.g. 3 years">
            </div>
            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $asset->remarks ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

@php($softwareRows = old('software', isset($asset) ? $asset->software->toArray() : []))
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-box-seam text-primary"></i><strong>Software List</strong>
        <span class="text-muted small ms-2">Installed software, entered manually.</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-2" data-software-table>
                <thead>
                    <tr>
                        <th style="width: 38%;">Software</th>
                        <th style="width: 22%;">Version</th>
                        <th>Notes</th>
                        <th style="width: 42px;"></th>
                    </tr>
                </thead>
                <tbody data-software-rows>
                    @forelse($softwareRows as $i => $row)
                        <tr data-software-row>
                            <td><input type="text" name="software[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. Microsoft 365"></td>
                            <td><input type="text" name="software[{{ $i }}][version]" value="{{ $row['version'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. 2024"></td>
                            <td><input type="text" name="software[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. Vol. license"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-software-remove title="Remove"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-software-add><i class="bi bi-plus-lg"></i> Add software</button>
    </div>
</div>

<template data-software-template>
    <tr data-software-row>
        <td><input type="text" name="software[__INDEX__][name]" class="form-control form-control-sm" placeholder="e.g. Microsoft 365"></td>
        <td><input type="text" name="software[__INDEX__][version]" class="form-control form-control-sm" placeholder="e.g. 2024"></td>
        <td><input type="text" name="software[__INDEX__][notes]" class="form-control form-control-sm" placeholder="e.g. Vol. license"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-software-remove title="Remove"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('pc-assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const permanent = document.querySelector('[data-expire-permanent]');
        const expireDate = document.querySelector('[data-expire-date]');
        if (!permanent || !expireDate) return;

        const sync = () => {
            expireDate.disabled = permanent.checked;
            if (permanent.checked) expireDate.value = '';
        };

        permanent.addEventListener('change', sync);
        sync();
    })();

    (function () {
        const rows = document.querySelector('[data-software-rows]');
        const template = document.querySelector('[data-software-template]');
        const addBtn = document.querySelector('[data-software-add]');
        if (!rows || !template || !addBtn) return;

        let index = rows.querySelectorAll('[data-software-row]').length;

        const addRow = () => {
            const html = template.innerHTML.replace(/__INDEX__/g, index++);
            const tr = document.createElement('tbody');
            tr.innerHTML = html.trim();
            rows.appendChild(tr.firstChild);
        };

        addBtn.addEventListener('click', addRow);

        rows.addEventListener('click', (e) => {
            const remove = e.target.closest('[data-software-remove]');
            if (remove) remove.closest('[data-software-row]').remove();
        });

        // Start with one empty row when none exist yet.
        if (index === 0) addRow();
    })();
</script>
