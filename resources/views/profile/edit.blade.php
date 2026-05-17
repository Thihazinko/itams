@extends('layouts.app')
@section('title', 'My Account')
@section('content')
@php
    $modules = \App\Models\User::MODULES;
    $moduleIcons = [
        'pc_assets'          => 'bi-pc-display',
        'devices'            => 'bi-hdd-network',
        'subscriptions'      => 'bi-calendar-event',
        'licenses_contracts' => 'bi-file-earmark-text',
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">My Account</h1>
        <div class="page-subtitle">Update your profile, change your password, or upload a new photo.</div>
    </div>
</div>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle text-primary"></i><strong>Profile Photo</strong>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar-preview mb-3" id="avatarPreview">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="Profile" id="avatarImg">
                        @else
                            <span class="gradient-avatar-large" id="avatarFallback">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <label class="btn btn-outline-primary btn-sm" for="avatarInput">
                        <i class="bi bi-upload"></i> {{ $user->avatar ? 'Replace photo' : 'Upload photo' }}
                    </label>
                    <input type="file" name="avatar" id="avatarInput" class="d-none @error('avatar') is-invalid @enderror" accept="image/*">
                    <div class="text-muted small mt-2">JPG / PNG / WebP, max 2 MB.</div>
                    @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Read-only access info --}}
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary"></i><strong>Your Access</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted small">Role</span>
                        @if($user->isAdmin())
                            <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-shield-lock"></i> Admin</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-person"></i> User</span>
                        @endif
                    </div>

                    <div class="text-muted small mb-2">Module access</div>
                    @if($user->isAdmin())
                        <div class="text-muted small fst-italic">Admins have full access to all modules.</div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($modules as $key => $label)
                                @php
                                    $canEdit = (bool) $user->{"can_edit_{$key}"};
                                    $canView = (bool) $user->{"can_view_{$key}"} || $canEdit;
                                    if ($canEdit)      { $tag = 'View + Edit'; $cls = 'bg-success-subtle text-success-emphasis'; }
                                    elseif ($canView)  { $tag = 'View only';   $cls = 'bg-primary-subtle text-primary-emphasis'; }
                                    else               { $tag = 'No access';   $cls = 'bg-light text-muted border'; }
                                @endphp
                                <div class="profile-module-row">
                                    <span class="profile-module-name">
                                        <i class="bi {{ $moduleIcons[$key] ?? 'bi-box' }}"></i> {{ $label }}
                                    </span>
                                    <span class="badge {{ $cls }}" style="font-size:.65rem;">{{ $tag }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="text-muted small mt-3 fst-italic">
                        <i class="bi bi-info-circle"></i> Contact an administrator to change your role or module access.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-card-text text-primary"></i><strong>Account Information</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="nameInput"
                                   value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Full name" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email"
                                       value="{{ old('email', $user->email) }}"
                                       class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                                       placeholder="you@company.com" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <small class="text-muted">You'll use this email to sign in.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-transparent d-flex align-items-center gap-2">
                    <i class="bi bi-lock text-primary"></i><strong>Change Password</strong>
                    <span class="text-muted small ms-2 d-none d-md-inline">Leave blank to keep your current password.</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Current password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                                <input type="password" name="current_password" id="currentPassword"
                                       class="form-control border-start-0 ps-0 @error('current_password') is-invalid @enderror"
                                       placeholder="Required only when changing password"
                                       autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary" data-toggle-pw="currentPassword" tabindex="-1" title="Show / hide password"><i class="bi bi-eye"></i></button>
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" id="newPassword"
                                       class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror"
                                       placeholder="At least 6 characters"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" data-toggle-pw="newPassword" tabindex="-1" title="Show / hide password"><i class="bi bi-eye"></i></button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm new password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input type="password" name="password_confirmation" id="confirmPassword"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Re-enter new password"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" data-toggle-pw="confirmPassword" tabindex="-1" title="Show / hide password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<style>
    .profile-avatar-preview {
        display: inline-block;
        width: 130px;
        height: 130px;
        border-radius: 50%;
        overflow: hidden;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        text-align: center;
    }
    .profile-avatar-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gradient-avatar-large {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%; height: 100%;
        font-size: 3rem;
        font-weight: 700;
    }

    .profile-module-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .35rem .55rem;
        border-radius: .45rem;
        background: rgba(15, 23, 42, 0.03);
    }
    .profile-module-name {
        font-size: .82rem;
        font-weight: 500;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .profile-module-name i { color: #0d6efd; font-size: .9rem; }
    [data-bs-theme="dark"] .profile-module-row { background: rgba(255, 255, 255, 0.04); }
    [data-bs-theme="dark"] .profile-module-name { color: #cbd5e0; }
    [data-bs-theme="dark"] .profile-module-name i { color: #93c5fd; }
</style>

<script>
    (function () {
        // Show / hide on any password input that has a toggle button next to it
        document.querySelectorAll('[data-toggle-pw]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePw);
                if (!input) return;
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        // Avatar preview on file pick
        const fileInput = document.getElementById('avatarInput');
        const preview   = document.getElementById('avatarPreview');
        const nameInput = document.getElementById('nameInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const f = e.target.files?.[0];
                if (!f) return;
                const url = URL.createObjectURL(f);
                preview.innerHTML = `<img src="${url}" alt="Preview">`;
            });
        }
        if (nameInput) {
            nameInput.addEventListener('input', () => {
                if (preview?.querySelector('img')) return; // user already uploaded a real image
                const initial = (nameInput.value || '?').trim().charAt(0).toUpperCase() || '?';
                preview.innerHTML = `<span class="gradient-avatar-large">${initial}</span>`;
            });
        }
    })();
</script>
@endsection
