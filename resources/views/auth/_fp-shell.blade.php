{{-- Shared styles for the modern auth pages (forgot-password, reset-password). --}}
{{-- All selectors are .fp-* scoped so this is safe to @include anywhere. --}}
<style>
    /* ========== BACKGROUND ========== */
    .fp-bg {
        position: fixed;
        inset: 0;
        background: #f5f7fb;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        overflow: hidden;
        font-size: .9rem;
    }
    [data-bs-theme="dark"] .fp-bg { background: #0b0f17; }

    .fp-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .55;
        pointer-events: none;
        animation: fp-float 18s ease-in-out infinite;
    }
    .fp-blob--a { width: 420px; height: 420px; top: -120px; left: -120px;
        background: radial-gradient(circle, #6366f1 0%, transparent 70%); }
    .fp-blob--b { width: 360px; height: 360px; bottom: -100px; right: -80px;
        background: radial-gradient(circle, #0d6efd 0%, transparent 70%);
        animation-delay: -6s; }
    .fp-blob--c { width: 280px; height: 280px; bottom: 20%; left: 18%;
        background: radial-gradient(circle, #22c55e 0%, transparent 70%);
        animation-delay: -12s; opacity: .35; }
    [data-bs-theme="dark"] .fp-blob { opacity: .35; }
    @keyframes fp-float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50%      { transform: translate(20px, -20px) scale(1.06); }
    }
    @media (prefers-reduced-motion: reduce) { .fp-blob { animation: none; } }

    /* ========== SHELL ========== */
    .fp-shell {
        width: 100%;
        max-width: 380px;
        position: relative;
        z-index: 1;
    }
    .fp-shell--wide { max-width: 420px; }

    /* ========== CARD ========== */
    .fp-card {
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(28px) saturate(180%);
        -webkit-backdrop-filter: blur(28px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.85);
        border-radius: 1.4rem;
        padding: 2.25rem 1.85rem 1.85rem;
        box-shadow:
            0 30px 80px -20px rgba(15, 23, 42, 0.22),
            0 8px 24px -8px rgba(15, 23, 42, 0.08);
        text-align: center;
        animation: fp-pop .35s cubic-bezier(.2, .9, .3, 1.2);
    }
    [data-bs-theme="dark"] .fp-card {
        background: rgba(22, 28, 38, 0.78);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow:
            0 30px 80px -20px rgba(0, 0, 0, 0.6),
            0 8px 24px -8px rgba(0, 0, 0, 0.4);
    }
    @keyframes fp-pop {
        from { opacity: 0; transform: translateY(12px) scale(.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @media (prefers-reduced-motion: reduce) { .fp-card { animation: none; } }

    /* ========== HERO ICON ========== */
    .fp-hero { display: flex; justify-content: center; margin-bottom: 1.1rem; }
    .fp-hero-icon {
        position: relative;
        width: 66px; height: 66px;
        border-radius: 1.1rem;
        background: linear-gradient(135deg, #4f7cff 0%, #6f3cff 100%);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.85rem;
        box-shadow:
            0 14px 32px -6px rgba(79, 124, 255, 0.55),
            inset 0 -2px 0 rgba(0, 0, 0, 0.08);
    }
    .fp-hero-icon--success {
        background: linear-gradient(135deg, #16a34a 0%, #10b981 100%);
        box-shadow: 0 14px 32px -6px rgba(16, 185, 129, 0.55);
    }
    .fp-hero-icon--warning {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        box-shadow: 0 14px 32px -6px rgba(245, 158, 11, 0.55);
    }
    .fp-hero-icon--secure {
        background: linear-gradient(135deg, #6f3cff 0%, #0d6efd 100%);
        box-shadow: 0 14px 32px -6px rgba(111, 60, 255, 0.55);
    }
    .fp-hero-ring {
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        border: 1px solid rgba(79, 124, 255, 0.25);
        animation: fp-ring 3.2s ease-out infinite;
    }
    @keyframes fp-ring {
        0%   { opacity: .7; transform: scale(1); }
        80%  { opacity: 0;  transform: scale(1.35); }
        100% { opacity: 0;  transform: scale(1.35); }
    }
    @media (prefers-reduced-motion: reduce) { .fp-hero-ring { animation: none; opacity: .25; } }

    /* ========== TYPOGRAPHY ========== */
    .fp-title {
        font-size: 1.6rem;
        font-weight: 700;
        letter-spacing: -.01em;
        color: #0f172a;
        margin: 0 0 .35rem;
        line-height: 1.15;
    }
    .fp-sub {
        font-size: .85rem;
        color: #64748b;
        margin: 0 0 1.35rem;
        line-height: 1.45;
    }
    [data-bs-theme="dark"] .fp-title { color: #f1f5f9; }
    [data-bs-theme="dark"] .fp-sub   { color: #94a3b8; }

    /* ========== FORM (floating label) ========== */
    .fp-form { margin: 0 0 .9rem; text-align: left; }
    .fp-float {
        position: relative;
        display: block;
        margin-bottom: .85rem;
    }
    .fp-float input {
        width: 100%;
        height: 54px;
        padding: 1.05rem 1rem .45rem 2.6rem;
        border: 1.5px solid rgba(15, 23, 42, 0.12);
        border-radius: .85rem;
        background: rgba(255, 255, 255, 0.85);
        color: #0f172a;
        font-size: .95rem;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .fp-float input:hover:not(:read-only) { border-color: rgba(13, 110, 253, 0.35); }
    .fp-float input:focus {
        border-color: #0d6efd;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
    }
    .fp-float--has-eye input { padding-right: 3.1rem; }
    .fp-float-icon {
        position: absolute;
        left: .95rem; top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        transition: color .15s ease;
        pointer-events: none;
    }
    .fp-float input:focus ~ .fp-float-icon { color: #0d6efd; }
    .fp-float-label {
        position: absolute;
        left: 2.6rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .9rem;
        font-weight: 500;
        pointer-events: none;
        transition: transform .15s ease, font-size .15s ease, color .15s ease;
        background: transparent;
    }
    .fp-float input:focus ~ .fp-float-label,
    .fp-float input:not(:placeholder-shown) ~ .fp-float-label {
        transform: translateY(calc(-50% - 14px));
        font-size: .68rem;
        color: #0d6efd;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .fp-float-eye {
        position: absolute;
        right: .55rem; top: 50%;
        transform: translateY(-50%);
        width: 36px; height: 36px;
        border: 0;
        background: transparent;
        color: #94a3b8;
        border-radius: .5rem;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .95rem;
        transition: background .12s ease, color .12s ease;
    }
    .fp-float-eye:hover { background: rgba(13, 110, 253, 0.08); color: #0d6efd; }
    [data-bs-theme="dark"] .fp-float input {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: #f1f5f9;
    }
    [data-bs-theme="dark"] .fp-float input:focus { background: rgba(255, 255, 255, 0.08); border-color: #93c5fd; box-shadow: 0 0 0 4px rgba(147, 197, 253, 0.18); }
    [data-bs-theme="dark"] .fp-float-icon { color: #64748b; }
    [data-bs-theme="dark"] .fp-float input:focus ~ .fp-float-icon { color: #93c5fd; }
    [data-bs-theme="dark"] .fp-float-label { color: #64748b; }
    [data-bs-theme="dark"] .fp-float input:focus ~ .fp-float-label,
    [data-bs-theme="dark"] .fp-float input:not(:placeholder-shown) ~ .fp-float-label { color: #93c5fd; }
    [data-bs-theme="dark"] .fp-float-eye { color: #64748b; }
    [data-bs-theme="dark"] .fp-float-eye:hover { background: rgba(147, 197, 253, 0.12); color: #93c5fd; }

    /* ========== BUTTONS ========== */
    .fp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        width: 100%;
        height: 50px;
        padding: 0 1.1rem;
        border: 0;
        border-radius: .85rem;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: transform .12s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
    }
    .fp-btn--primary {
        background: linear-gradient(135deg, #0d6efd 0%, #4f7cff 100%);
        color: #fff;
        box-shadow: 0 10px 24px -6px rgba(13, 110, 253, 0.45);
    }
    .fp-btn--primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 30px -6px rgba(13, 110, 253, 0.55);
        color: #fff;
    }
    .fp-btn--primary:active { transform: translateY(0); }
    .fp-btn--primary:disabled { opacity: .85; cursor: progress; transform: none; }

    /* ========== EMAIL CHIP ========== */
    .fp-email-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: rgba(13, 110, 253, 0.1);
        color: #0a58ca;
        border-radius: 999px;
        padding: .45rem 1rem;
        font-weight: 600;
        font-size: .85rem;
        margin: 0 auto 1.1rem;
        max-width: 100%;
        word-break: break-all;
    }
    .fp-email-chip--muted { background: rgba(100, 116, 139, 0.12); color: #475569; }
    [data-bs-theme="dark"] .fp-email-chip { background: rgba(147, 197, 253, 0.14); color: #93c5fd; }
    [data-bs-theme="dark"] .fp-email-chip--muted { background: rgba(255, 255, 255, 0.06); color: #cbd5e0; }

    /* ========== FINEPRINT / HELPER ========== */
    .fp-fineprint {
        font-size: .78rem;
        color: #64748b;
        line-height: 1.55;
        margin: 0 0 1.1rem;
    }
    .fp-fineprint a { color: #0d6efd; font-weight: 600; text-decoration: none; }
    .fp-fineprint a:hover { text-decoration: underline; }
    [data-bs-theme="dark"] .fp-fineprint { color: #94a3b8; }
    [data-bs-theme="dark"] .fp-fineprint a { color: #93c5fd; }

    .fp-helper {
        font-size: .78rem;
        color: #64748b;
        margin: 0 0 1rem;
        text-align: center;
    }
    .fp-helper a { color: #0d6efd; font-weight: 600; text-decoration: none; }
    .fp-helper a:hover { text-decoration: underline; }
    [data-bs-theme="dark"] .fp-helper { color: #94a3b8; }
    [data-bs-theme="dark"] .fp-helper a { color: #93c5fd; }

    /* ========== DIVIDER ========== */
    .fp-divider {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 1rem 0 .85rem;
        color: #94a3b8;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .12em;
        font-weight: 600;
    }
    .fp-divider::before, .fp-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(15, 23, 42, 0.12), transparent);
    }
    [data-bs-theme="dark"] .fp-divider::before,
    [data-bs-theme="dark"] .fp-divider::after { background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1), transparent); }

    /* ========== BACK LINK ========== */
    .fp-link-back {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        margin-top: .65rem;
        font-size: .82rem;
        font-weight: 500;
        color: #64748b;
        text-decoration: none;
    }
    .fp-link-back:hover { color: #0d6efd; }
    [data-bs-theme="dark"] .fp-link-back { color: #94a3b8; }
    [data-bs-theme="dark"] .fp-link-back:hover { color: #93c5fd; }

    /* ========== ALERT ========== */
    .fp-alert {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        background: rgba(239, 68, 68, 0.08);
        color: #991b1b;
        border-radius: .65rem;
        padding: .55rem .75rem;
        font-size: .8rem;
        margin: 0 0 .85rem;
        text-align: left;
    }
    .fp-alert i { font-size: .9rem; margin-top: 1px; }
    .fp-alert a { color: inherit; font-weight: 600; text-decoration: underline; }
    [data-bs-theme="dark"] .fp-alert { background: rgba(239, 68, 68, 0.14); color: #fca5a5; }

    /* ========== FOOTER ========== */
    .fp-foot {
        text-align: center;
        margin-top: 1.25rem;
        font-size: .7rem;
        color: #94a3b8;
        letter-spacing: .04em;
    }
</style>
