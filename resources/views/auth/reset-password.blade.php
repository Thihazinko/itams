@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<div class="fp-bg">
    {{-- Decorative background blobs --}}
    <div class="fp-blob fp-blob--a"></div>
    <div class="fp-blob fp-blob--b"></div>
    <div class="fp-blob fp-blob--c"></div>

    <div class="fp-shell fp-shell--wide">
        <div class="fp-card">

            <div class="fp-hero">
                <div class="fp-hero-icon fp-hero-icon--secure">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="fp-hero-ring"></span>
                </div>
            </div>

            <h1 class="fp-title">Choose a new password</h1>
            <p class="fp-sub">For your admin account.</p>

            <div class="fp-email-chip">
                <i class="bi bi-envelope"></i>
                <span>{{ old('email', $email) }}</span>
            </div>

            @if($errors->any())
                @php $err = $errors->first(); @endphp
                <div class="fp-alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <div>{{ $err }}</div>
                        @if(str_contains(strtolower($err), 'token'))
                            <div class="mt-1">
                                <a href="{{ route('password.request') }}">Request a new reset link</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" id="resetForm" class="fp-form" autocomplete="off">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ old('email', $email) }}">

                <label class="fp-float fp-float--has-eye">
                    <input type="password" id="password" name="password"
                           placeholder=" "
                           minlength="6" required autocomplete="new-password">
                    <span class="fp-float-label">New password</span>
                    <i class="bi bi-lock fp-float-icon"></i>
                    <button type="button" class="fp-float-eye" data-target="password" tabindex="-1" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </label>

                <div class="fp-strength" id="strengthMeter" aria-live="polite">
                    <div class="fp-strength-bars">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="fp-strength-label">Enter a password</div>
                </div>

                <label class="fp-float fp-float--has-eye mt-3">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           placeholder=" "
                           minlength="6" required autocomplete="new-password">
                    <span class="fp-float-label">Confirm new password</span>
                    <i class="bi bi-lock-fill fp-float-icon"></i>
                    <button type="button" class="fp-float-eye" data-target="password_confirmation" tabindex="-1" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </label>

                <div class="fp-match d-none" id="matchHint"></div>

                <ul class="fp-reqs" id="pwReqs">
                    <li data-req="length"><i class="bi bi-circle"></i> At least 6 characters</li>
                    <li data-req="mixed"><i class="bi bi-circle"></i> Mix of letters and numbers</li>
                    <li data-req="match"><i class="bi bi-circle"></i> Both fields match</li>
                </ul>

                <button type="submit" class="fp-btn fp-btn--primary fp-submit mt-3" id="resetSubmit">
                    <span class="default"><i class="bi bi-check2-circle"></i> Reset password</span>
                    <span class="loading d-none"><span class="spinner-border spinner-border-sm me-1"></span> Saving…</span>
                </button>
            </form>

            <a href="{{ route('login') }}" class="fp-link-back">
                <i class="bi bi-arrow-left"></i> Back to sign in
            </a>
        </div>

        <div class="fp-foot">{{ config('app.name', 'ITAMS') }} &middot; &copy; {{ date('Y') }}</div>
    </div>
</div>

@include('auth._fp-shell')

<style>
    /* ===== Reset-page-only additions: strength meter, match hint, requirements ===== */
    .fp-strength {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem; margin-top: -.35rem;
    }
    .fp-strength-bars { display: inline-flex; gap: 3px; flex-grow: 1; }
    .fp-strength-bars span {
        flex: 1; height: 4px;
        border-radius: 2px;
        background: rgba(15, 23, 42, 0.1);
        transition: background .15s ease;
    }
    .fp-strength-label {
        font-size: .7rem; font-weight: 600;
        white-space: nowrap;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    [data-bs-theme="dark"] .fp-strength-bars span { background: rgba(255, 255, 255, 0.08); }

    .fp-strength[data-score="1"] .fp-strength-bars span:nth-child(1) { background: #ef4444; }
    .fp-strength[data-score="2"] .fp-strength-bars span:nth-child(-n+2) { background: #f59e0b; }
    .fp-strength[data-score="3"] .fp-strength-bars span:nth-child(-n+3) { background: #84cc16; }
    .fp-strength[data-score="4"] .fp-strength-bars span { background: #16a34a; }
    .fp-strength[data-score="1"] .fp-strength-label { color: #b91c1c; }
    .fp-strength[data-score="2"] .fp-strength-label { color: #b45309; }
    .fp-strength[data-score="3"] .fp-strength-label { color: #4d7c0f; }
    .fp-strength[data-score="4"] .fp-strength-label { color: #15803d; }

    .fp-match {
        font-weight: 600;
        font-size: .76rem;
        margin: -.35rem 0 0;
        display: flex; align-items: center; gap: .3rem;
    }
    .fp-match.is-match    { color: #15803d; }
    .fp-match.is-mismatch { color: #b91c1c; }
    [data-bs-theme="dark"] .fp-match.is-match    { color: #6ee7b7; }
    [data-bs-theme="dark"] .fp-match.is-mismatch { color: #fca5a5; }

    .fp-reqs {
        list-style: none;
        padding: .65rem .85rem;
        margin: .85rem 0 0;
        background: rgba(15, 23, 42, 0.03);
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: .65rem;
        font-size: .76rem;
        color: #64748b;
    }
    .fp-reqs li {
        display: flex; align-items: center; gap: .4rem;
        padding: .15rem 0;
        transition: color .12s ease;
    }
    .fp-reqs li i { font-size: .85rem; color: #cbd5e1; transition: color .12s ease, transform .12s ease; }
    .fp-reqs li.is-met { color: #15803d; }
    .fp-reqs li.is-met i { color: #16a34a; transform: scale(1.1); }
    [data-bs-theme="dark"] .fp-reqs {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .fp-reqs li i { color: #475569; }
    [data-bs-theme="dark"] .fp-reqs li.is-met,
    [data-bs-theme="dark"] .fp-reqs li.is-met i { color: #6ee7b7; }
</style>

<script>
    (function () {
        // Dual show/hide
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

        const pw     = document.getElementById('password');
        const cf     = document.getElementById('password_confirmation');
        const meter  = document.getElementById('strengthMeter');
        const label  = meter?.querySelector('.fp-strength-label');
        const match  = document.getElementById('matchHint');
        const reqs   = document.getElementById('pwReqs');
        const reqLen = reqs?.querySelector('[data-req="length"]');
        const reqMix = reqs?.querySelector('[data-req="mixed"]');
        const reqMat = reqs?.querySelector('[data-req="match"]');
        const submit = document.getElementById('resetSubmit');

        function scoreOf(p) {
            if (!p) return 0;
            let s = 0;
            if (p.length >= 6) s++;
            if (p.length >= 10) s++;
            if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
            if (/\d/.test(p) && /[a-zA-Z]/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            return Math.min(4, s);
        }
        const SCORE_LABELS = ['Enter a password', 'Weak', 'Fair', 'Good', 'Strong'];

        function setReq(el, met) {
            if (!el) return;
            el.classList.toggle('is-met', !!met);
            const i = el.querySelector('i');
            if (i) i.className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        }

        function updateStrength() {
            const p = pw?.value || '';
            const s = scoreOf(p);
            if (meter) {
                meter.dataset.score = String(s);
                if (label) label.textContent = SCORE_LABELS[s];
            }
            setReq(reqLen, p.length >= 6);
            setReq(reqMix, /[a-zA-Z]/.test(p) && /\d/.test(p));
        }

        function updateMatch() {
            const p = pw?.value || '';
            const c = cf?.value || '';
            const isMatch    = p && c && p === c;
            const isMismatch = p && c && p !== c;
            if (match) {
                match.classList.toggle('d-none', !c);
                match.classList.toggle('is-match', isMatch);
                match.classList.toggle('is-mismatch', isMismatch);
                match.innerHTML = isMatch
                    ? '<i class="bi bi-check2"></i> Passwords match'
                    : (isMismatch ? '<i class="bi bi-x-circle"></i> Passwords do not match' : '');
            }
            setReq(reqMat, isMatch);
        }

        pw?.addEventListener('input', () => { updateStrength(); updateMatch(); });
        cf?.addEventListener('input', updateMatch);

        const form = document.getElementById('resetForm');
        if (form && submit) {
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.querySelector('.default')?.classList.add('d-none');
                submit.querySelector('.loading')?.classList.remove('d-none');
            });
        }
    })();
</script>
@endsection
