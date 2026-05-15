@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<div class="auth-bg">
    <div class="auth-stack">
        <div class="auth-card">
            <div class="auth-card-header">
                <span class="auth-brand-mark"><i class="bi bi-hdd-stack-fill"></i></span>
                <div class="auth-title">Welcome back</div>
                <p class="auth-subtitle">Sign in to manage your IT assets</p>
            </div>

            <div class="auth-body">
                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start gap-2 py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-group input-group-auth">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="you@company.com" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label d-flex justify-content-between align-items-center" for="password">
                            <span>Password</span>
                        </label>
                        <div class="input-group input-group-auth">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary auth-reveal" id="togglePassword" tabindex="-1" aria-label="Show password" title="Show / hide password">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check d-flex align-items-center gap-2 mb-3">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input m-0">
                        <label for="remember" class="form-check-label small text-muted m-0">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 auth-submit">
                        <span class="default"><i class="bi bi-box-arrow-in-right"></i> Sign in</span>
                        <span class="loading d-none"><span class="spinner-border spinner-border-sm me-1"></span> Signing in…</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="auth-footer">
            <span class="d-block">{{ config('app.name', 'ITAMS') }}</span>
            <span class="d-block opacity-75">&copy; {{ date('Y') }} &middot; v1.0</span>
        </div>
    </div>

    <button type="button" class="auth-theme-toggle" id="authThemeToggle" title="Toggle light / dark mode" aria-label="Toggle theme">
        <i class="bi" id="authThemeIcon"></i>
    </button>
</div>

<style>
    .auth-bg {
        min-height: 100vh;
        background: #f5f7fb;
        background-image:
            radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.18), transparent 35%),
            radial-gradient(circle at 100% 100%, rgba(13, 110, 253, 0.14), transparent 40%),
            radial-gradient(circle at 50% 100%, rgba(34, 197, 94, 0.08), transparent 50%);
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        position: relative;
        font-size: .875rem;
    }
    [data-bs-theme="dark"] .auth-bg {
        background: #0f141b;
        background-image:
            radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.22), transparent 35%),
            radial-gradient(circle at 100% 100%, rgba(13, 110, 253, 0.18), transparent 40%),
            radial-gradient(circle at 50% 100%, rgba(34, 197, 94, 0.1), transparent 50%);
    }

    .auth-stack { width: 100%; max-width: 420px; }

    .auth-card {
        width: 100%;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(22px) saturate(180%);
        -webkit-backdrop-filter: blur(22px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 1rem;
        box-shadow: 0 24px 64px rgba(31, 38, 135, 0.12);
        overflow: hidden;
    }
    [data-bs-theme="dark"] .auth-card {
        background: rgba(30, 36, 48, 0.75);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
    }

    .auth-card-header { padding: 2rem 2rem 1rem; text-align: center; }
    .auth-brand-mark {
        width: 56px;
        height: 56px;
        border-radius: .85rem;
        margin: 0 auto .85rem;
        background: linear-gradient(135deg, #4f7cff 0%, #6f3cff 100%);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 10px 24px rgba(79, 124, 255, 0.35);
    }
    .auth-title { font-size: 1.4rem; font-weight: 700; margin: 0 0 .15rem; color: #1f2d3d; }
    .auth-subtitle { font-size: .82rem; color: #6c757d; margin: 0; }
    [data-bs-theme="dark"] .auth-title { color: #f1f5f9; }
    [data-bs-theme="dark"] .auth-subtitle { color: #94a3b8; }

    .auth-body { padding: 0 2rem 1.75rem; }

    .input-group-auth .input-group-text {
        background: transparent;
        border-right: 0;
        color: #94a3b8;
        padding-right: .5rem;
    }
    .input-group-auth .form-control {
        border-left: 0;
        padding-left: .25rem;
        background: transparent;
    }
    .input-group-auth .form-control:focus {
        box-shadow: none;
        border-color: var(--bs-primary, #0d6efd);
    }
    .input-group-auth:focus-within .input-group-text {
        color: var(--bs-primary, #0d6efd);
        border-color: var(--bs-primary, #0d6efd);
    }
    .input-group-auth:focus-within .form-control { border-color: var(--bs-primary, #0d6efd); }
    .input-group-auth .auth-reveal {
        border-left: 0;
        color: #94a3b8;
        background: transparent;
    }
    .input-group-auth .auth-reveal:hover { color: var(--bs-primary, #0d6efd); background: transparent; }
    [data-bs-theme="dark"] .input-group-auth .input-group-text { color: #64748b; }
    [data-bs-theme="dark"] .input-group-auth .form-control { color: #e9ecef; }

    .auth-submit {
        padding: .55rem 1rem;
        font-weight: 600;
        box-shadow: 0 6px 16px rgba(13, 110, 253, 0.25);
    }
    .auth-submit:hover { box-shadow: 0 10px 24px rgba(13, 110, 253, 0.3); transform: translateY(-1px); }
    .auth-submit:active { transform: translateY(0); }
    .auth-submit[disabled] { opacity: .85; cursor: progress; transform: none; }

    .auth-footer {
        text-align: center;
        margin-top: 1.25rem;
        font-size: .72rem;
        color: #94a3b8;
        letter-spacing: .03em;
    }

    .auth-theme-toggle {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 36px;
        height: 36px;
        border-radius: .55rem;
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(31, 38, 135, 0.1);
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1rem;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .auth-theme-toggle:hover {
        background: #fff;
        color: #0d6efd;
        border-color: rgba(13, 110, 253, 0.25);
    }
    [data-bs-theme="dark"] .auth-theme-toggle {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.08);
        color: #cfd8dc;
    }
    [data-bs-theme="dark"] .auth-theme-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #93c5fd;
        border-color: rgba(147, 197, 253, 0.3);
    }
</style>

<script>
    (function () {
        // Password reveal toggle
        const pw   = document.getElementById('password');
        const btn  = document.getElementById('togglePassword');
        const icon = document.getElementById('togglePasswordIcon');
        if (btn && pw && icon) {
            btn.addEventListener('click', () => {
                const isHidden = pw.type === 'password';
                pw.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        }

        // Submit loading state
        const form = document.getElementById('loginForm');
        const submit = form?.querySelector('.auth-submit');
        if (form && submit) {
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.querySelector('.default')?.classList.add('d-none');
                submit.querySelector('.loading')?.classList.remove('d-none');
            });
        }

        // Theme toggle (mirrors topbar logic)
        const root = document.documentElement;
        const tBtn = document.getElementById('authThemeToggle');
        const tIcn = document.getElementById('authThemeIcon');
        function syncIcon() {
            if (!tIcn) return;
            const dark = root.getAttribute('data-bs-theme') === 'dark';
            tIcn.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
        syncIcon();
        if (tBtn) {
            tBtn.addEventListener('click', () => {
                const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-bs-theme', next);
                localStorage.setItem('rrs.theme', next);
                syncIcon();
            });
        }
    })();
</script>
@endsection
