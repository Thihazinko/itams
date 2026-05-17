@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<div class="fp-bg">
    {{-- Decorative background blobs --}}
    <div class="fp-blob fp-blob--a"></div>
    <div class="fp-blob fp-blob--b"></div>
    <div class="fp-blob fp-blob--c"></div>

    <div class="fp-shell">
        <div class="fp-card">

            <div class="fp-hero">
                <div class="fp-hero-icon">
                    <i class="bi bi-hdd-stack-fill"></i>
                    <span class="fp-hero-ring"></span>
                </div>
            </div>

            <h1 class="fp-title">Welcome back</h1>
            <p class="fp-sub">Sign in to manage your IT assets.</p>

            @if(session('success'))
                <div class="fp-success-flash">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="fp-alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" class="fp-form">
                @csrf

                <label class="fp-float">
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           placeholder=" "
                           required autofocus autocomplete="username">
                    <span class="fp-float-label">Email</span>
                    <i class="bi bi-envelope fp-float-icon"></i>
                </label>

                <label class="fp-float fp-float--has-eye">
                    <input type="password" id="password" name="password"
                           placeholder=" "
                           required autocomplete="current-password">
                    <span class="fp-float-label">Password</span>
                    <i class="bi bi-lock fp-float-icon"></i>
                    <button type="button" class="fp-float-eye" data-target="password" tabindex="-1" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </label>

                <div class="fp-row-extras">
                    <label class="fp-check">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Keep me signed in</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="fp-link-inline">Forgot password?</a>
                </div>

                <button type="submit" class="fp-btn fp-btn--primary fp-submit">
                    <span class="default">Sign in</span>
                    <span class="loading d-none"><span class="spinner-border spinner-border-sm me-1"></span> Signing in…</span>
                </button>
            </form>
        </div>

        <div class="fp-foot">{{ config('app.name', 'ITAMS') }} &middot; &copy; {{ date('Y') }} &middot; v1.0</div>
    </div>

    {{-- Floating theme toggle (pre-login user can still switch themes; setting persists via localStorage) --}}
    <button type="button" class="fp-theme-toggle" id="loginThemeToggle" title="Toggle light / dark mode" aria-label="Toggle theme">
        <i class="bi" id="loginThemeIcon"></i>
    </button>
</div>

@include('auth._fp-shell')

<style>
    /* ===== Login-only additions: success flash, remember-me row, theme toggle ===== */
    .fp-success-flash {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        background: rgba(16, 185, 129, 0.1);
        color: #047857;
        border-radius: .65rem;
        padding: .55rem .75rem;
        font-size: .8rem;
        margin: 0 0 .85rem;
        text-align: left;
    }
    .fp-success-flash i { font-size: .9rem; margin-top: 1px; }
    [data-bs-theme="dark"] .fp-success-flash {
        background: rgba(16, 185, 129, 0.18);
        color: #6ee7b7;
    }

    .fp-row-extras {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        margin: -.25rem 0 .85rem;
    }
    .fp-check {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .82rem;
        color: #64748b;
        cursor: pointer;
        user-select: none;
        margin: 0;
    }
    .fp-check input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #0d6efd;
        cursor: pointer;
        margin: 0;
    }
    [data-bs-theme="dark"] .fp-check { color: #94a3b8; }
    [data-bs-theme="dark"] .fp-check input[type="checkbox"] { accent-color: #93c5fd; }

    .fp-link-inline {
        font-size: .82rem;
        font-weight: 500;
        color: #0d6efd;
        text-decoration: none;
        white-space: nowrap;
    }
    .fp-link-inline:hover { text-decoration: underline; }
    [data-bs-theme="dark"] .fp-link-inline { color: #93c5fd; }

    /* Floating theme toggle in top-right corner */
    .fp-theme-toggle {
        position: fixed;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(15, 23, 42, 0.08);
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.05rem;
        transition: background .15s ease, color .15s ease, transform .12s ease, box-shadow .15s ease;
        z-index: 2;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }
    .fp-theme-toggle:hover {
        background: #fff;
        color: #0d6efd;
        transform: scale(1.06);
        box-shadow: 0 10px 24px rgba(13, 110, 253, 0.18);
    }
    [data-bs-theme="dark"] .fp-theme-toggle {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.1);
        color: #cfd8dc;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
    }
    [data-bs-theme="dark"] .fp-theme-toggle:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #93c5fd;
        box-shadow: 0 10px 24px rgba(147, 197, 253, 0.18);
    }
</style>

<script>
    (function () {
        // Show / hide password
        document.querySelectorAll('.fp-float-eye').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                if (!input) return;
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            });
        });

        // Submit loading state
        const form   = document.getElementById('loginForm');
        const submit = form?.querySelector('.fp-submit');
        if (form && submit) {
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.querySelector('.default')?.classList.add('d-none');
                submit.querySelector('.loading')?.classList.remove('d-none');
            });
        }

        // Theme toggle — persists via localStorage, mirrored by layout init
        const root = document.documentElement;
        const tBtn = document.getElementById('loginThemeToggle');
        const tIcn = document.getElementById('loginThemeIcon');
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
