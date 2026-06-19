@csrf
@php
    $a = $account ?? null;
    $currentType = old('type', $a->type ?? ($type ?? 'Gmail'));
    $currentStatus = old('status', $a->status ?? 'Active');
@endphp
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-envelope-at text-primary"></i><strong>Account Details</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    @foreach(\App\Models\EmailAccount::TYPES as $t)
                        <option value="{{ $t }}" @selected($currentType === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach(\App\Models\EmailAccount::STATUSES as $s)
                        <option value="{{ $s }}" @selected($currentStatus === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $a->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. John Doe" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <input type="text" name="department" value="{{ old('department', $a->department ?? '') }}" class="form-control @error('department') is-invalid @enderror" placeholder="e.g. IT">
                @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Address <span class="text-danger">*</span></label>
                <input type="text" name="address" value="{{ old('address', $a->address ?? '') }}" class="form-control @error('address') is-invalid @enderror" placeholder="e.g. john.doe@company.com" required>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="{{ old('username', $a->username ?? '') }}" class="form-control @error('username') is-invalid @enderror" placeholder="e.g. john.doe">
                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" id="emFormPassword" value="{{ old('password', $a->password ?? '') }}" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" placeholder="Account password">
                    <button type="button" class="btn btn-outline-secondary" id="emFormPwToggle" tabindex="-1" title="Show / hide"><i class="bi bi-eye" id="emFormPwIcon"></i></button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <small class="text-muted">Stored encrypted.</small>
            </div>
            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3">{{ old('remark', $a->remark ?? '') }}</textarea>
                @error('remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('email-master.index', ['tab' => $currentType === 'Gmail' ? 'gmail' : 'email']) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const pw = document.getElementById('emFormPassword');
        const btn = document.getElementById('emFormPwToggle');
        const icon = document.getElementById('emFormPwIcon');
        if (btn && pw && icon) {
            btn.addEventListener('click', () => {
                const hidden = pw.type === 'password';
                pw.type = hidden ? 'text' : 'password';
                icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }
    })();
</script>
