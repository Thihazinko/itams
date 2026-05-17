@csrf
@php
    $editing = isset($user) && $user->exists;
    $currentRole = old('role', $user->role ?? 'user');
    $modules = \App\Models\User::MODULES;
    $moduleIcons = [
        'pc_assets'          => 'bi-pc-display',
        'devices'            => 'bi-hdd-network',
        'subscriptions'      => 'bi-calendar-event',
        'licenses_contracts' => 'bi-file-earmark-text',
    ];
@endphp

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-person-circle text-primary"></i><strong>Profile Photo</strong>
            </div>
            <div class="card-body text-center">
                <div class="user-avatar-preview mb-3" id="avatarPreview">
                    @if($editing && $user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="Profile" id="avatarImg">
                    @else
                        <span class="gradient-avatar-large" id="avatarFallback">{{ strtoupper(substr(old('name', $user->name ?? '?'), 0, 1)) }}</span>
                    @endif
                </div>
                <label class="btn btn-outline-primary btn-sm" for="avatarInput">
                    <i class="bi bi-upload"></i> {{ $editing && $user->avatar ? 'Replace photo' : 'Upload photo' }}
                </label>
                <input type="file" name="avatar" id="avatarInput" class="d-none @error('avatar') is-invalid @enderror" accept="image/*">
                <div class="text-muted small mt-2">JPG/PNG/WebP, max 2 MB.</div>
                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-card-text text-primary"></i><strong>Account</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="nameInput" value="{{ old('name', $user->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Full name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" placeholder="user@company.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Password @if($editing)<span class="text-muted small">(leave blank to keep)</span>@else <span class="text-danger">*</span>@endif</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="password" id="userPassword" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" placeholder="{{ $editing ? '••••••••' : 'Minimum 6 characters' }}" {{ $editing ? '' : 'required' }}>
                            <button type="button" class="btn btn-outline-secondary" id="togglePw" tabindex="-1" title="Show / hide password"><i class="bi bi-eye" id="togglePwIcon"></i></button>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="roleSelect" class="form-select">
                            <option value="user"  @selected($currentRole === 'user')>User</option>
                            <option value="admin" @selected($currentRole === 'admin')>Admin</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-transparent d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-primary"></i>
                <strong>Module Access</strong>
                <span class="text-muted small ms-2 d-none d-md-inline">View lets the user browse; Edit also lets them create, update, and delete.</span>
            </div>
            <div class="card-body">
                <div id="adminNotice" class="alert alert-warning d-flex align-items-center gap-2 small mb-3 {{ $currentRole === 'admin' ? '' : 'd-none' }}">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div>This account has the <strong>Admin</strong> role &mdash; module-access toggles below are ignored.</div>
                </div>
                <div class="row g-2" id="moduleGrid">
                    @foreach($modules as $key => $label)
                        @php
                            $viewField = "can_view_{$key}";
                            $editField = "can_edit_{$key}";
                            $icon  = $moduleIcons[$key] ?? 'bi-box';
                            $editChecked = (bool) old($editField, $editing ? $user->{$editField} : false);
                            $viewChecked = (bool) old($viewField, $editing ? $user->{$viewField} : false) || $editChecked;
                        @endphp
                        <div class="col-md-6">
                            <div class="module-card {{ $viewChecked || $editChecked ? 'is-on' : '' }}">
                                <span class="module-icon"><i class="bi {{ $icon }}"></i></span>
                                <div class="module-meta">
                                    <span class="module-name">{{ $label }}</span>
                                    <span class="module-state text-muted small"></span>
                                </div>
                                <div class="module-perms">
                                    <label class="perm-toggle" title="Allow viewing this module">
                                        <input type="hidden" name="{{ $viewField }}" value="0">
                                        <input type="checkbox"
                                               name="{{ $viewField }}"
                                               value="1"
                                               class="form-check-input perm-view"
                                               data-module="{{ $key }}"
                                               @checked($viewChecked)>
                                        <span>View</span>
                                    </label>
                                    <label class="perm-toggle" title="Allow create, update and delete">
                                        <input type="hidden" name="{{ $editField }}" value="0">
                                        <input type="checkbox"
                                               name="{{ $editField }}"
                                               value="1"
                                               class="form-check-input perm-edit"
                                               data-module="{{ $key }}"
                                               @checked($editChecked)>
                                        <span>Edit</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<style>
    .user-avatar-preview {
        display: inline-block;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        overflow: hidden;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        line-height: 110px;
        text-align: center;
    }
    .user-avatar-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gradient-avatar-large {
        display: inline-block;
        width: 100%; height: 100%;
        line-height: 110px;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .module-card {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .85rem;
        border-radius: .65rem;
        border: 1px solid rgba(31, 38, 135, 0.1);
        background: #fff;
        transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        margin: 0;
        position: relative;
    }
    .module-card:hover { border-color: rgba(13, 110, 253, 0.25); background: rgba(13, 110, 253, 0.02); }
    .module-card.is-on { border-color: rgba(13, 110, 253, 0.35); background: rgba(13, 110, 253, 0.05); }
    .module-card .module-icon {
        width: 32px; height: 32px;
        border-radius: .45rem;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .module-card .module-meta { flex-grow: 1; min-width: 0; line-height: 1.2; }
    .module-card .module-name { font-weight: 600; font-size: .88rem; display: block; }
    .module-card .module-state { font-size: .7rem; display: block; }
    .module-card .module-state::before { font-size: .7rem; }
    .module-card[data-state="none"] .module-state::before { content: 'No access'; color: #94a3b8; }
    .module-card[data-state="view"] .module-state::before { content: 'View only'; color: #0d6efd; }
    .module-card[data-state="edit"] .module-state::before { content: 'View + Edit'; color: #198754; }
    .module-card .module-perms {
        display: flex;
        flex-direction: column;
        gap: .25rem;
        flex-shrink: 0;
    }
    .module-card .perm-toggle {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .78rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        margin: 0;
        user-select: none;
    }
    .module-card .perm-toggle input[type="checkbox"] { margin: 0; cursor: pointer; }
    .module-card .perm-toggle input[type="checkbox"]:checked + span { color: #0d6efd; font-weight: 600; }
    .module-card.is-disabled { opacity: .5; }
    .module-card.is-disabled input { pointer-events: none; }
    .module-card.is-disabled .perm-toggle { cursor: not-allowed; }

    [data-bs-theme="dark"] .module-card { background: rgba(30, 36, 48, 0.7); border-color: rgba(255, 255, 255, 0.08); }
    [data-bs-theme="dark"] .module-card:hover { background: rgba(147, 197, 253, 0.05); border-color: rgba(147, 197, 253, 0.25); }
    [data-bs-theme="dark"] .module-card.is-on { background: rgba(147, 197, 253, 0.1); border-color: rgba(147, 197, 253, 0.35); }
    [data-bs-theme="dark"] .module-card .module-icon { background: rgba(147, 197, 253, 0.15); color: #93c5fd; }
    [data-bs-theme="dark"] .module-card .perm-toggle { color: #94a3b8; }
    [data-bs-theme="dark"] .module-card .perm-toggle input[type="checkbox"]:checked + span { color: #93c5fd; }
    [data-bs-theme="dark"] .module-card[data-state="view"] .module-state::before { color: #93c5fd; }
    [data-bs-theme="dark"] .module-card[data-state="edit"] .module-state::before { color: #6ee7b7; }
</style>

<script>
    (function () {
        // Password reveal
        const pw   = document.getElementById('userPassword');
        const btn  = document.getElementById('togglePw');
        const icon = document.getElementById('togglePwIcon');
        if (btn && pw && icon) {
            btn.addEventListener('click', () => {
                const isHidden = pw.type === 'password';
                pw.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        // Module card: Edit implies View. View is auto-checked and locked while Edit is on.
        function syncCard(card) {
            const viewInput = card.querySelector('.perm-view');
            const editInput = card.querySelector('.perm-edit');
            if (!viewInput || !editInput) return;
            if (editInput.checked) {
                viewInput.checked = true;
                viewInput.disabled = true;
            } else {
                viewInput.disabled = false;
            }
            let state = 'none';
            if (editInput.checked) state = 'edit';
            else if (viewInput.checked) state = 'view';
            card.dataset.state = state;
            card.classList.toggle('is-on', state !== 'none');
        }
        document.querySelectorAll('#moduleGrid .module-card').forEach(card => {
            const inputs = card.querySelectorAll('.perm-view, .perm-edit');
            inputs.forEach(i => i.addEventListener('change', () => syncCard(card)));
            syncCard(card);
        });

        // Role-driven disable of module checkboxes (admins implicitly get everything)
        const roleSel = document.getElementById('roleSelect');
        const adminNotice = document.getElementById('adminNotice');
        function syncRole() {
            const isAdmin = roleSel.value === 'admin';
            adminNotice.classList.toggle('d-none', !isAdmin);
            document.querySelectorAll('.module-card').forEach(c => {
                c.classList.toggle('is-disabled', isAdmin);
                c.querySelectorAll('.perm-view, .perm-edit').forEach(i => {
                    if (isAdmin) {
                        i.disabled = true;
                    } else {
                        // Re-enable, then re-apply edit-implies-view lock.
                        i.disabled = false;
                        syncCard(c);
                    }
                });
            });
        }
        if (roleSel) {
            roleSel.addEventListener('change', syncRole);
            syncRole();
        }

        // Avatar preview on file pick
        const fileInput  = document.getElementById('avatarInput');
        const preview    = document.getElementById('avatarPreview');
        const nameInput  = document.getElementById('nameInput');
        function paintInitial() {
            if (!preview) return;
            if (preview.querySelector('img')) return; // already has uploaded image
            const initial = (nameInput?.value || '?').trim().charAt(0).toUpperCase() || '?';
            preview.innerHTML = `<span class="gradient-avatar-large">${initial}</span>`;
        }
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const f = e.target.files?.[0];
                if (!f) return;
                const url = URL.createObjectURL(f);
                preview.innerHTML = `<img src="${url}" alt="Preview">`;
            });
        }
        if (nameInput) {
            nameInput.addEventListener('input', paintInitial);
        }
    })();
</script>
