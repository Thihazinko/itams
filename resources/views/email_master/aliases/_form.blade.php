@csrf
@php
    $al = $alias ?? null;
    $existingMembers = old('members', $al ? $al->members->pluck('address')->all() : []);
    if (empty($existingMembers)) {
        $existingMembers = ['']; // always render at least one empty row
    }
@endphp
<div class="card mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <i class="bi bi-arrow-left-right text-primary"></i><strong>Alias Details</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Main Email <span class="text-danger">*</span></label>
                <input type="text" name="main_email" value="{{ old('main_email', $al->main_email ?? '') }}" class="form-control @error('main_email') is-invalid @enderror" placeholder="e.g. team@company.com" required>
                @error('main_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Mailing Addresses</label>
                <div class="text-muted small mb-2">Member addresses that mail to the main email is forwarded to.</div>
                <div id="aliasMembers">
                    @foreach($existingMembers as $address)
                        <div class="input-group mb-2 alias-member-row">
                            <span class="input-group-text bg-transparent"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="text" name="members[]" value="{{ $address }}" class="form-control" placeholder="member@company.com">
                            <button type="button" class="btn btn-outline-danger alias-member-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="aliasMemberAdd"><i class="bi bi-plus-lg"></i> Add address</button>
                @error('members.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3">{{ old('remark', $al->remark ?? '') }}</textarea>
                @error('remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('email-master.index', ['tab' => 'alias']) }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    (function () {
        const list = document.getElementById('aliasMembers');
        const addBtn = document.getElementById('aliasMemberAdd');

        function rowTemplate() {
            const row = document.createElement('div');
            row.className = 'input-group mb-2 alias-member-row';
            row.innerHTML =
                '<span class="input-group-text bg-transparent"><i class="bi bi-envelope text-muted"></i></span>' +
                '<input type="text" name="members[]" class="form-control" placeholder="member@company.com">' +
                '<button type="button" class="btn btn-outline-danger alias-member-remove" title="Remove"><i class="bi bi-x-lg"></i></button>';
            return row;
        }

        if (addBtn && list) {
            addBtn.addEventListener('click', () => {
                const row = rowTemplate();
                list.appendChild(row);
                row.querySelector('input')?.focus();
            });
        }

        document.addEventListener('click', (e) => {
            const rm = e.target.closest('.alias-member-remove');
            if (!rm || !list) return;
            const rows = list.querySelectorAll('.alias-member-row');
            if (rows.length <= 1) {
                // keep at least one row; just clear it
                rm.closest('.alias-member-row').querySelector('input').value = '';
            } else {
                rm.closest('.alias-member-row').remove();
            }
        });
    })();
</script>
