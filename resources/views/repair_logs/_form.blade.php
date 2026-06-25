@csrf
@php($log = $log ?? null)
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-tools text-primary"></i><strong>Repair Details</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">PC ID <span class="text-danger">*</span></label>
                <select name="computer_id" id="rlPcSelect" class="form-select @error('computer_id') is-invalid @enderror" required>
                    <option value="">— Select a PC —</option>
                    @foreach($pcOptions as $pc)
                        <option value="{{ $pc->computer_id }}"
                                data-employee="{{ $pc->employee_name }}"
                                data-department="{{ $pc->department }}"
                                @selected(old('computer_id', $log->computer_id ?? '') === $pc->computer_id)>{{ $pc->computer_id }}</option>
                    @endforeach
                </select>
                @error('computer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Picking a PC fills in its current employee and department.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="repair_date" value="{{ old('repair_date', isset($log->repair_date) ? $log->repair_date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="form-control @error('repair_date') is-invalid @enderror" required>
                @error('repair_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    @foreach(\App\Models\RepairLog::STATUSES as $s)
                        <option value="{{ $s }}" @selected(old('status', $log->status ?? 'In Progress') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Employee</label>
                <input type="text" name="employee_name" id="rlEmployee" value="{{ old('employee_name', $log->employee_name ?? '') }}" class="form-control" placeholder="Auto-filled from PC; editable">
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <select name="department" id="rlDepartment" class="form-select @error('department') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach(\App\Models\PcAsset::DEPARTMENTS as $d)
                        <option value="{{ $d }}" @selected(old('department', $log->department ?? '') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
                @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Repair Process <span class="text-danger">*</span></label>
                <textarea name="repair_process" rows="3" class="form-control @error('repair_process') is-invalid @enderror" placeholder="Describe what was done…" required>{{ old('repair_process', $log->repair_process ?? '') }}</textarea>
                @error('repair_process')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" rows="2" class="form-control">{{ old('remark', $log->remark ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('repair-logs.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const select = document.getElementById('rlPcSelect');
        const employee = document.getElementById('rlEmployee');
        const department = document.getElementById('rlDepartment');
        if (!select) return;

        select.addEventListener('change', () => {
            const opt = select.options[select.selectedIndex];
            if (!opt) return;
            // Only overwrite when the chosen PC actually carries a value, so we
            // don't wipe a manually-entered employee/department by accident.
            const emp = opt.dataset.employee || '';
            const dep = opt.dataset.department || '';
            if (employee) employee.value = emp;
            if (department) department.value = dep;
        });
    })();
</script>
