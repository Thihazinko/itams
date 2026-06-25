<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <script>
        (function () {
            var t = localStorage.getItem('rrs.theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
            background-image:
                radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.08), transparent 35%),
                radial-gradient(circle at 100% 100%, rgba(13, 110, 253, 0.06), transparent 40%);
            background-attachment: fixed;
            min-height: 100vh;
            font-size: 0.875rem;
            color: #1f2d3d;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        h1 { font-size: 1.625rem; font-weight: 600; }
        h2 { font-size: 1.375rem; font-weight: 600; }
        h3 { font-size: 1.125rem; font-weight: 600; }
        h4 { font-size: 1rem;     font-weight: 600; }
        h5 { font-size: 0.95rem;  font-weight: 600; }
        h6 { font-size: 0.85rem;  font-weight: 600; }
        .form-control, .form-select, .btn, .table { font-size: 0.875rem; }
        .card { border: 1px solid rgba(31, 38, 135, 0.08); border-radius: 0.85rem; }
        [data-bs-theme="dark"] .card { border-color: rgba(255, 255, 255, 0.06); }
        .sidebar {
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            color: #1f2d3d;
            width: 240px;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            transition: transform .25s ease, width .2s ease, box-shadow .2s ease;
            z-index: 1030;
            border-right: 1px solid rgba(255, 255, 255, 0.65);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.08);
        }
        .sidebar .sidebar-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1rem 0 .5rem;
        }
        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .1rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(31, 38, 135, 0.08);
            margin-bottom: .35rem;
        }
        .sidebar .brand .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: .55rem;
            background: linear-gradient(135deg, #4f7cff 0%, #6f3cff 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 6px 14px rgba(79, 124, 255, 0.3);
            flex-shrink: 0;
        }
        .sidebar .brand-text { line-height: 1.15; }
        .sidebar .brand-name { font-weight: 700; font-size: 1.05rem; color: #1f2d3d; letter-spacing: .02em; }
        .sidebar .brand-sub { font-size: .65rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; font-weight: 600; margin-top: 1px; }
        [data-bs-theme="dark"] .sidebar .brand-name { color: #fff; }
        [data-bs-theme="dark"] .sidebar .brand-sub { color: #64748b; }

        .sidebar .nav-section {
            padding: .85rem 1.5rem .35rem;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #94a3b8;
        }
        .sidebar .nav-section:first-of-type { padding-top: .1rem; }
        [data-bs-theme="dark"] .sidebar .nav-section { color: #64748b; }

        .sidebar a {
            color: #334155;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .5rem .85rem;
            margin: .1rem .75rem;
            border-radius: .55rem;
            font-size: .875rem;
            font-weight: 500;
            position: relative;
            transition: background .15s ease, color .15s ease, padding-left .15s ease;
        }
        .sidebar a .bi { font-size: 1rem; opacity: .7; transition: opacity .15s ease, color .15s ease; }
        .sidebar a:hover {
            background: rgba(13, 110, 253, 0.06);
            color: #0d6efd;
        }
        .sidebar a:hover .bi { opacity: 1; }
        .sidebar a.active {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-weight: 600;
            padding-left: 1rem;
        }
        .sidebar a.active::before {
            content: '';
            position: absolute;
            left: -.4rem;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: #0d6efd;
            border-radius: 0 3px 3px 0;
        }
        .sidebar a.active .bi { opacity: 1; color: #0d6efd; }

        .sidebar-footer {
            padding: .75rem 1.25rem;
            border-top: 1px solid rgba(31, 38, 135, 0.08);
            font-size: .7rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
        }
        .sidebar-footer .version { font-weight: 600; }
        [data-bs-theme="dark"] .sidebar-footer { border-top-color: rgba(255, 255, 255, 0.06); color: #64748b; }
        .main { margin-left: 240px; transition: margin-left .25s ease; }
        .topbar {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            padding: .65rem 1.5rem;
            border-bottom: 1px solid rgba(31, 38, 135, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .topbar-right { display: flex; align-items: center; gap: .55rem; }

        .topbar-btn {
            width: 36px;
            height: 36px;
            border-radius: 0.55rem;
            border: 1px solid rgba(31, 38, 135, 0.1);
            background: rgba(255, 255, 255, 0.7);
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 1rem;
            line-height: 1;
            cursor: pointer;
            position: relative;
            text-decoration: none;
            transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .topbar-btn:hover {
            background: #fff;
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.25);
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.08);
            text-decoration: none;
        }
        .topbar-btn:focus-visible {
            outline: 2px solid rgba(13, 110, 253, 0.35);
            outline-offset: 2px;
        }
        .topbar-btn.active {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.3);
        }
        .topbar-btn .notify-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            color: #fff;
            font-size: 0.62rem;
            font-weight: 700;
            line-height: 1;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.95);
            background: #0d6efd;
            box-shadow: 0 1px 3px rgba(13, 110, 253, 0.3);
            /* Bulletproof centering regardless of border, padding, or digit count */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .topbar-btn .notify-dot--upcoming {
            background: #0d6efd;
            box-shadow: 0 1px 3px rgba(13, 110, 253, 0.3);
        }
        .topbar-btn .notify-dot--soon {
            background: #f59e0b;
            box-shadow: 0 1px 3px rgba(245, 158, 11, 0.35);
        }
        .topbar-btn .notify-dot--urgent {
            background: #ef4444;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
            animation: notify-pulse 2s ease-out infinite;
        }
        @keyframes notify-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55); }
            70%  { box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .topbar-btn .notify-dot--urgent { animation: none; box-shadow: 0 1px 3px rgba(239, 68, 68, 0.35); }
        }

        .user-menu-btn {
            width: auto;
            height: 36px;
            padding: 0.2rem 0.65rem 0.2rem 0.25rem;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #334155;
        }
        .user-menu-btn .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        .user-menu-btn .user-meta { line-height: 1.1; text-align: left; }
        .user-menu-btn .user-meta .name { font-weight: 600; font-size: 0.82rem; }
        .user-menu-btn .user-meta .role { font-size: 0.7rem; color: #64748b; }
        .user-menu-btn .caret { font-size: 0.75rem; color: #94a3b8; margin-left: 0.15rem; }
        @media (max-width: 576px) {
            .user-menu-btn .user-meta { display: none; }
            .user-menu-btn .caret { display: none; }
        }

        .dropdown-menu {
            border: 1px solid rgba(31, 38, 135, 0.08);
            border-radius: 0.7rem;
            box-shadow: 0 12px 32px rgba(31, 38, 135, 0.1);
            padding: 0.35rem;
            min-width: 240px;
        }
        .dropdown-menu .dropdown-item {
            border-radius: 0.45rem;
            padding: 0.45rem 0.65rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .dropdown-menu .dropdown-item:hover,
        .dropdown-menu .dropdown-item:focus {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
        }
        .dropdown-menu .dropdown-item.active {
            background: #0d6efd;
            color: #fff;
        }
        .dropdown-menu .dropdown-item.text-danger:hover,
        .dropdown-menu .dropdown-item.text-danger:focus {
            background: rgba(239, 68, 68, 0.08);
            color: #b91c1c;
        }
        .dropdown-menu hr.dropdown-divider { margin: 0.35rem 0; opacity: 0.5; }
        .user-menu-card {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.45rem 0.55rem 0.75rem;
        }
        .user-menu-card .user-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .user-menu-card .user-info { line-height: 1.2; }
        .user-menu-card .user-info .name { font-weight: 600; font-size: 0.88rem; }
        .user-menu-card .user-info .email { font-size: 0.72rem; color: #64748b; }
        .user-menu-card .user-info .role-tag {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            margin-top: 0.25rem;
            padding: 0.1rem 0.45rem;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-radius: 0.3rem;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        .user-menu-card .user-info .role-tag .bi { font-size: .7rem; }

        /* Quick-action row inside user dropdown */
        .user-dropdown { min-width: 260px; }
        .user-dropdown-quick {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.35rem;
            padding: 0.2rem 0.35rem 0.35rem;
        }
        .user-quick-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
            padding: 0.5rem 0.4rem;
            background: rgba(31, 38, 135, 0.04);
            border: 1px solid transparent;
            border-radius: 0.5rem;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s ease, color .15s ease, border-color .15s ease;
        }
        .user-quick-btn .bi { font-size: 1rem; }
        .user-quick-btn:hover {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.2);
        }

        .dropdown-section-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            font-weight: 700;
            padding: 0.5rem 0.7rem 0.25rem;
        }

        [data-bs-theme="dark"] .user-quick-btn {
            background: rgba(255, 255, 255, 0.04);
            color: #cfd8dc;
        }
        [data-bs-theme="dark"] .user-quick-btn:hover {
            background: rgba(147, 197, 253, 0.1);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.25);
        }
        [data-bs-theme="dark"] .dropdown-section-label { color: #64748b; }
        [data-bs-theme="dark"] .topbar-btn {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.08);
            color: #cfd8dc;
        }
        [data-bs-theme="dark"] .topbar-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.3);
        }
        [data-bs-theme="dark"] .topbar-btn.active {
            background: rgba(147, 197, 253, 0.15);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.35);
        }
        [data-bs-theme="dark"] .topbar-btn .notify-dot { border-color: #1a1f29; }
        [data-bs-theme="dark"] .user-menu-btn { color: #e9ecef; }
        [data-bs-theme="dark"] .user-menu-btn .user-meta .role,
        [data-bs-theme="dark"] .user-menu-btn .caret { color: #94a3b8; }
        [data-bs-theme="dark"] .dropdown-menu {
            background: #1a1f29;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
        }
        [data-bs-theme="dark"] .dropdown-menu .dropdown-item { color: #e9ecef; }
        [data-bs-theme="dark"] .dropdown-menu .dropdown-item:hover,
        [data-bs-theme="dark"] .dropdown-menu .dropdown-item:focus {
            background: rgba(147, 197, 253, 0.12);
            color: #93c5fd;
        }
        [data-bs-theme="dark"] .user-menu-card .user-info .email { color: #94a3b8; }
        [data-bs-theme="dark"] .user-menu-card .user-info .role-tag {
            background: rgba(147, 197, 253, 0.15);
            color: #93c5fd;
        }
        .content { padding: 1.5rem; }

        /* Modern dashboard cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(31, 38, 135, 0.06);
        }
        [data-bs-theme="dark"] .glass-card {
            background: rgba(30, 36, 48, 0.6);
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        .kpi-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(31, 38, 135, 0.08);
            border-radius: 0.85rem;
            background: #fff;
            color: #1f2d3d;
            transition: transform .15s ease, box-shadow .15s ease;
            box-shadow: 0 1px 3px rgba(31, 38, 135, 0.04);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--kpi-accent, #0d6efd);
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(31, 38, 135, 0.08); }
        .kpi-card .kpi-icon {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            font-size: 2.25rem;
            opacity: 0.18;
            line-height: 1;
            color: var(--kpi-accent, #0d6efd);
        }
        .kpi-card .kpi-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6c757d; font-weight: 600; }
        .kpi-card .kpi-value { font-size: 1.85rem; font-weight: 700; line-height: 1.15; color: #1f2d3d; margin-top: .25rem; }
        .kpi-card .kpi-foot  { font-size: 0.78rem; color: #6c757d; margin-top: .35rem; }
        .kpi-card a, .kpi-card a:hover { color: inherit; text-decoration: none; }
        [data-bs-theme="dark"] .kpi-card { background: rgba(30, 36, 48, 0.7); border-color: rgba(255, 255, 255, 0.06); color: #e9ecef; }
        [data-bs-theme="dark"] .kpi-card .kpi-value { color: #f1f5f9; }
        [data-bs-theme="dark"] .kpi-card .kpi-label,
        [data-bs-theme="dark"] .kpi-card .kpi-foot { color: #94a3b8; }

        .kpi-blue   { --kpi-accent: #0d6efd; }
        .kpi-green  { --kpi-accent: #10b981; }
        .kpi-amber  { --kpi-accent: #f59e0b; }
        .kpi-purple { --kpi-accent: #8b5cf6; }
        .kpi-teal   { --kpi-accent: #14b8a6; }
        .kpi-rose   { --kpi-accent: #f43f5e; }

        .activity-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
            margin-top: 6px;
        }

        /* Page header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .page-header .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
            color: #1f2d3d;
        }
        .page-header .page-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: .25rem;
        }
        [data-bs-theme="dark"] .page-header .page-title { color: #f1f5f9; }
        [data-bs-theme="dark"] .page-header .page-subtitle { color: #94a3b8; }

        /* Section title used inside glass-cards */
        .section-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: #6c757d;
            margin: 0;
        }
        [data-bs-theme="dark"] .section-title { color: #94a3b8; }

        /* Refined table inside glass-card */
        .glass-card .table { margin-bottom: 0; }
        .glass-card .table > thead > tr > th {
            background: transparent;
            color: #6c757d;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            border-bottom: 1px solid rgba(31, 38, 135, 0.1);
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
        [data-bs-theme="dark"] .glass-card .table > thead > tr > th {
            color: #94a3b8;
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        .glass-card .table > tbody > tr > td {
            border-color: rgba(31, 38, 135, 0.06);
            vertical-align: middle;
        }
        [data-bs-theme="dark"] .glass-card .table > tbody > tr > td {
            border-color: rgba(255, 255, 255, 0.05);
        }
        .glass-card .table > tbody > tr:hover > td {
            background: rgba(13, 110, 253, 0.04);
        }

        /* Activity feed item */
        .activity-item {
            display: flex;
            gap: .75rem;
            padding: .65rem .25rem;
            border-bottom: 1px solid rgba(31, 38, 135, 0.06);
        }
        .activity-item:last-child { border-bottom: 0; }
        [data-bs-theme="dark"] .activity-item { border-bottom-color: rgba(255, 255, 255, 0.05); }
        .activity-item .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: .5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .9rem;
        }
        .activity-item .activity-body { flex-grow: 1; min-width: 0; font-size: .8rem; line-height: 1.35; }
        .activity-item .activity-meta {
            font-size: .7rem;
            color: #6c757d;
            margin-top: .15rem;
        }
        [data-bs-theme="dark"] .activity-item .activity-meta { color: #94a3b8; }

        /* Horizontal stat row — single card with N segments for module index pages */
        .stat-row {
            background: #fff;
            border: 1px solid rgba(31, 38, 135, 0.08);
            border-radius: 0.85rem;
            box-shadow: 0 1px 3px rgba(31, 38, 135, 0.04);
            display: grid;
            grid-template-columns: repeat(var(--stat-cols, 4), 1fr);
            overflow: hidden;
        }
        .stat-cell {
            padding: .95rem 1.1rem;
            border-right: 1px solid rgba(31, 38, 135, 0.06);
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            position: relative;
            transition: background .15s ease;
        }
        .stat-cell:hover { background: rgba(13, 110, 253, 0.025); }
        .stat-cell:last-child { border-right: 0; }
        .stat-cell::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--stat-color, #0d6efd);
            opacity: 0;
            transition: opacity .15s ease;
        }
        .stat-cell:hover::before { opacity: 0.4; }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--stat-color, #0d6efd);
            background: color-mix(in srgb, var(--stat-color, #0d6efd) 12%, transparent);
            flex-shrink: 0;
        }
        @supports not (background: color-mix(in srgb, red, blue)) {
            .stat-icon { background: rgba(13, 110, 253, 0.1); }
        }

        .stat-body { flex-grow: 1; min-width: 0; }
        .stat-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.1rem;
        }
        .stat-value {
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.15;
            color: #1f2d3d;
        }
        .stat-foot {
            font-size: 0.72rem;
            color: #6c757d;
            margin-top: 0.2rem;
        }
        .stat-bar {
            height: 3px;
            background: rgba(0, 0, 0, 0.06);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 0.55rem;
        }
        .stat-bar > span {
            display: block;
            height: 100%;
            background: var(--stat-color, #0d6efd);
            border-radius: 999px;
            transition: width .35s ease;
        }

        @media (max-width: 992px) {
            .stat-row { grid-template-columns: repeat(2, 1fr) !important; }
            .stat-cell:nth-child(2n) { border-right: 0; }
            .stat-cell:nth-child(-n+2) { border-bottom: 1px solid rgba(31, 38, 135, 0.06); }
        }
        @media (max-width: 576px) {
            .stat-row { grid-template-columns: 1fr !important; }
            .stat-cell { border-right: 0; border-bottom: 1px solid rgba(31, 38, 135, 0.06); }
            .stat-cell:last-child { border-bottom: 0; }
        }

        [data-bs-theme="dark"] .stat-row {
            background: rgba(30, 36, 48, 0.7);
            border-color: rgba(255, 255, 255, 0.06);
        }
        [data-bs-theme="dark"] .stat-cell { border-right-color: rgba(255, 255, 255, 0.05); }
        [data-bs-theme="dark"] .stat-cell:hover { background: rgba(147, 197, 253, 0.04); }
        [data-bs-theme="dark"] .stat-value { color: #f1f5f9; }
        [data-bs-theme="dark"] .stat-label,
        [data-bs-theme="dark"] .stat-foot { color: #94a3b8; }
        [data-bs-theme="dark"] .stat-bar { background: rgba(255, 255, 255, 0.08); }
        @media (max-width: 992px) {
            [data-bs-theme="dark"] .stat-cell:nth-child(-n+2) { border-bottom-color: rgba(255, 255, 255, 0.05); }
        }
        @media (max-width: 576px) {
            [data-bs-theme="dark"] .stat-cell { border-bottom-color: rgba(255, 255, 255, 0.05); }
        }

        /* Clickable stat-cell variant — anchor that filters the list */
        a.stat-cell.stat-link {
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        a.stat-cell.stat-link:hover .stat-value { color: var(--stat-color); }
        .stat-cell.is-active {
            background: color-mix(in srgb, var(--stat-color, #0d6efd) 7%, transparent);
        }
        @supports not (background: color-mix(in srgb, red, blue)) {
            .stat-cell.is-active { background: rgba(13, 110, 253, 0.06); }
        }
        .stat-cell.is-active::before { opacity: 1 !important; }
        .stat-cell.is-active .stat-value { color: var(--stat-color); }
        [data-bs-theme="dark"] a.stat-cell.stat-link:hover .stat-value,
        [data-bs-theme="dark"] .stat-cell.is-active .stat-value { color: var(--stat-color); }

        /* Soft icon button for inline table actions */
        .btn-icon-soft {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid transparent;
            border-radius: 0.4rem;
            color: #475569;
            background: transparent;
            transition: background .12s ease, color .12s ease, border-color .12s ease;
            margin-left: 2px;
        }
        .btn-icon-soft:hover {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.2);
        }
        .btn-icon-soft.text-danger:hover {
            background: rgba(239, 68, 68, 0.08);
            color: #b91c1c !important;
            border-color: rgba(239, 68, 68, 0.25);
        }
        [data-bs-theme="dark"] .btn-icon-soft { color: #cfd8dc; }
        [data-bs-theme="dark"] .btn-icon-soft:hover {
            background: rgba(147, 197, 253, 0.12);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.3);
        }
        [data-bs-theme="dark"] .btn-icon-soft.text-danger:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #fca5a5 !important;
        }

        /* Quick-action pill button — works on <a> and <button> */
        .quick-action {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .85rem;
            border-radius: .65rem;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(31, 38, 135, 0.1);
            color: #1f2d3d;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            line-height: 1.25;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
        }
        button.quick-action { font-family: inherit; }
        .quick-action:hover {
            background: #fff;
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.25);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(31, 38, 135, 0.1);
            text-decoration: none;
        }
        .quick-action:focus-visible {
            outline: 2px solid rgba(13, 110, 253, 0.35);
            outline-offset: 2px;
        }
        .quick-action.show,
        .quick-action[aria-expanded="true"] {
            background: #fff;
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.3);
        }
        /* Bootstrap caret hidden — we use a Bootstrap Icon instead */
        .quick-action.dropdown-toggle::after { display: none; }

        .quick-action-primary {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
        .quick-action-primary:hover {
            background: #0b5ed7;
            color: #fff;
            border-color: #0b5ed7;
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] .quick-action {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.08);
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .quick-action:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.3);
        }
        [data-bs-theme="dark"] .quick-action.show,
        [data-bs-theme="dark"] .quick-action[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.08);
            color: #93c5fd;
            border-color: rgba(147, 197, 253, 0.35);
        }
        [data-bs-theme="dark"] .quick-action-primary {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        [data-bs-theme="dark"] .quick-action-primary:hover {
            background: #1d4ed8;
            color: #fff;
            border-color: #1d4ed8;
        }

        /* Mini progress bar (for KPI footers / status splits) */
        .mini-progress {
            height: 4px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.25);
            overflow: hidden;
            margin-top: .5rem;
        }
        .mini-progress > span {
            display: block;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 4px;
        }

        /* Desktop "rail" mode: shrink to icons-only, keep the brand mark visible */
        @media (min-width: 769px) {
            body.sidebar-hidden .sidebar {
                width: 64px;
                transform: none;
            }
            body.sidebar-hidden .main { margin-left: 64px; }

            body.sidebar-hidden .sidebar .brand {
                justify-content: center;
                padding-left: .5rem;
                padding-right: .5rem;
                gap: 0;
            }
            body.sidebar-hidden .sidebar .brand-text { display: none; }

            body.sidebar-hidden .sidebar .nav-section { display: none; }

            body.sidebar-hidden .sidebar a {
                margin: .15rem .5rem;
                padding: .55rem 0;
                gap: 0;
                justify-content: center;
                font-size: 0;
            }
            body.sidebar-hidden .sidebar a .bi {
                font-size: 1.1rem;
                opacity: 1;
            }
            body.sidebar-hidden .sidebar a.active { padding-left: 0; }
            body.sidebar-hidden .sidebar a.active::before { left: -.5rem; }

            body.sidebar-hidden .sidebar-footer {
                justify-content: center;
                padding: .65rem .5rem;
                font-size: 0;
            }
            body.sidebar-hidden .sidebar-footer .bi { font-size: .6rem; }
            body.sidebar-hidden .sidebar-footer .version { display: none; }

            /* Hover-expand: while collapsed, hovering overlays the full sidebar on top of the content */
            body.sidebar-hidden .sidebar:hover {
                width: 240px;
                box-shadow: 0 12px 40px rgba(31, 38, 135, 0.18);
            }
            body.sidebar-hidden .sidebar:hover .brand {
                justify-content: flex-start;
                padding-left: 1.25rem;
                padding-right: 1.25rem;
                gap: .65rem;
            }
            body.sidebar-hidden .sidebar:hover .brand-text { display: block; }
            body.sidebar-hidden .sidebar:hover .nav-section { display: block; }
            body.sidebar-hidden .sidebar:hover a {
                margin: .1rem .75rem;
                padding: .5rem .85rem;
                gap: .7rem;
                justify-content: flex-start;
                font-size: .875rem;
            }
            body.sidebar-hidden .sidebar:hover a .bi { font-size: 1rem; }
            body.sidebar-hidden .sidebar:hover a.active { padding-left: 1rem; }
            body.sidebar-hidden .sidebar:hover a.active::before { left: -.4rem; }
            body.sidebar-hidden .sidebar:hover .sidebar-footer {
                justify-content: space-between;
                padding: .75rem 1.25rem;
                font-size: .7rem;
            }
            body.sidebar-hidden .sidebar:hover .sidebar-footer .bi { font-size: .5rem; }
            body.sidebar-hidden .sidebar:hover .sidebar-footer .version { display: inline; }
        }

        /* Mobile: hamburger fully hides the sidebar (existing behavior) */
        @media (max-width: 768px) {
            body.sidebar-hidden .sidebar { transform: translateX(-100%); }
            body.sidebar-hidden .main { margin-left: 0; }
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            z-index: 1029;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; }
            body.sidebar-visible .sidebar { transform: translateX(0); }
            body.sidebar-visible .sidebar-backdrop { display: block; }
        }

        /* Dark mode overrides */
        [data-bs-theme="dark"] body {
            background: #0f141b;
            background-image:
                radial-gradient(circle at 12% 18%, rgba(99, 102, 241, 0.22), transparent 42%),
                radial-gradient(circle at 88% 28%, rgba(236, 72, 153, 0.16), transparent 45%),
                radial-gradient(circle at 50% 95%, rgba(34, 197, 94, 0.14), transparent 50%);
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .sidebar {
            background: rgba(25, 30, 40, 0.55);
            border-right-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .sidebar .brand {
            color: #fff;
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }
        [data-bs-theme="dark"] .sidebar a { color: #cfd8dc; }
        [data-bs-theme="dark"] .sidebar a:hover {
            background: rgba(147, 197, 253, 0.08);
            color: #93c5fd;
        }
        [data-bs-theme="dark"] .sidebar a.active {
            background: rgba(147, 197, 253, 0.12);
            color: #93c5fd;
        }
        [data-bs-theme="dark"] .sidebar a.active::before { background: #93c5fd; }
        [data-bs-theme="dark"] .sidebar a.active .bi { color: #93c5fd; }
        [data-bs-theme="dark"] .topbar {
            background: rgba(20, 25, 35, 0.7);
            border-bottom-color: rgba(255, 255, 255, 0.06);
            color: #e9ecef;
        }
        /* `.table-light` is a fixed light variant; remap to dark surfaces when the
           theme is dark so table headers don't render as a glaring white strip. */
        [data-bs-theme="dark"] .table-light,
        [data-bs-theme="dark"] .table-light > th,
        [data-bs-theme="dark"] .table-light > td {
            --bs-table-color: #e9ecef;
            --bs-table-bg: #1a1f29;
            --bs-table-border-color: rgba(255, 255, 255, 0.08);
            --bs-table-striped-bg: #1f2530;
            --bs-table-active-bg: #232a36;
            --bs-table-hover-bg: #232a36;
            color: #e9ecef;
            border-color: rgba(255, 255, 255, 0.08);
        }

        /* Shared confirm modal (window.appConfirm) */
        #appConfirmModal .modal-content {
            border: 0;
            border-radius: .9rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
            overflow: hidden;
        }
        #appConfirmModal .modal-body { padding: 1.75rem 1.75rem 1rem; }
        #appConfirmModal .modal-footer { padding: 1rem 1.75rem 1.5rem; }
        #appConfirmModal .app-confirm-icon {
            width: 64px; height: 64px;
            margin: 0 auto;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
        }
        #appConfirmModal .app-confirm-icon--danger  { background: rgba(239, 68, 68, 0.12);  color: #dc2626; }
        #appConfirmModal .app-confirm-icon--warning { background: rgba(245, 158, 11, 0.14); color: #b45309; }
        #appConfirmModal .app-confirm-icon--primary { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
        #appConfirmModal .app-confirm-icon--success { background: rgba(16, 185, 129, 0.14); color: #047857; }
        #appConfirmModal .app-confirm-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 .35rem;
        }
        #appConfirmModal .app-confirm-message { font-size: .9rem; color: #475569; margin: 0; }
        #appConfirmModal .app-confirm-message strong,
        #appConfirmModal .app-confirm-message code {
            color: #0f172a;
            background: rgba(15, 23, 42, 0.05);
            padding: .05rem .3rem;
            border-radius: .25rem;
            font-weight: 600;
        }
        #appConfirmModal .app-confirm-note {
            font-size: .78rem;
            color: #94a3b8;
            margin: .7rem 0 0;
        }
        #appConfirmModal .btn { font-weight: 600; min-width: 100px; }
        [data-bs-theme="dark"] #appConfirmModal .modal-content { background: #1a1f29; color: #e9ecef; }
        [data-bs-theme="dark"] #appConfirmModal .app-confirm-message { color: #cbd5e0; }
        [data-bs-theme="dark"] #appConfirmModal .app-confirm-message strong,
        [data-bs-theme="dark"] #appConfirmModal .app-confirm-message code {
            color: #f1f5f9; background: rgba(255, 255, 255, 0.08);
        }
        [data-bs-theme="dark"] #appConfirmModal .app-confirm-note { color: #64748b; }
    </style>
</head>
<body>
    @auth
    @php
        $user = auth()->user();
        $hasAssets = $user->canAccess('pc_assets')
            || $user->canAccess('subscriptions')
            || $user->canAccess('licenses_contracts')
            || $user->canAccess('devices')
            || $user->canAccess('email_master');
    @endphp
    <nav class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" style="text-decoration: none;">
            <span class="brand-mark"><i class="bi bi-hdd-stack-fill"></i></span>
            <span class="brand-text">
                <span class="d-block brand-name">ITAMS</span>
                <span class="d-block brand-sub">IT Assets Management</span>
            </span>
        </a>

        <div class="sidebar-scroll">
            <div class="nav-section">Overview</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            @if($hasAssets)
            <div class="nav-section">Assets Management</div>
            @if($user->canAccess('pc_assets'))
            <a href="{{ route('pc-assets.index') }}" class="{{ request()->routeIs('pc-assets.*') || request()->routeIs('repair-logs.*') ? 'active' : '' }}" title="PC Master">
                <i class="bi bi-pc-display"></i> PC Master
            </a>
            @endif
            @if($user->canAccess('devices'))
            <a href="{{ route('devices.index') }}" class="{{ request()->routeIs('devices.*') || request()->routeIs('device-repair-logs.*') ? 'active' : '' }}" title="Device Master">
                <i class="bi bi-hdd-network"></i> Device Master
            </a>
            @endif
            @if($user->canAccess('email_master'))
            <a href="{{ route('email-master.index') }}" class="{{ request()->routeIs('email-master.*') || request()->routeIs('email-accounts.*') || request()->routeIs('email-aliases.*') ? 'active' : '' }}" title="Email Master">
                <i class="bi bi-envelope-at"></i> Email Master
            </a>
            @endif
            @if($user->canAccess('subscriptions'))
            <a href="{{ route('subscriptions.index') }}" class="{{ request()->routeIs('subscriptions.*') ? 'active' : '' }}" title="Subscriptions">
                <i class="bi bi-calendar-event"></i> Subscriptions
            </a>
            @endif
            @if($user->canAccess('licenses_contracts'))
            <a href="{{ route('licenses-contracts.index') }}" class="{{ request()->routeIs('licenses-contracts.*') ? 'active' : '' }}" title="License &amp; Contract">
                <i class="bi bi-file-earmark-text"></i> License &amp; Contract
            </a>
            @endif
            @endif

            @if($user->isAdmin())
            <div class="nav-section">Setting</div>
            <a href="{{ route('purchase-orders.index') }}" class="{{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" title="Renewal Process">
                <i class="bi bi-file-earmark-ruled"></i> Renewal Process
            </a>
            <a href="{{ route('mail-settings.edit') }}" class="{{ request()->routeIs('mail-settings.*') ? 'active' : '' }}" title="Mail Settings">
                <i class="bi bi-envelope-fill"></i> Mail Settings
            </a>
            <a href="{{ route('notification-settings.edit') }}" class="{{ request()->routeIs('notification-settings.*') ? 'active' : '' }}" title="Notification Settings">
                <i class="bi bi-bell-fill"></i> Notification Settings
            </a>
            <a href="{{ route('activity-logs.index') }}" class="{{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" title="Activity Log">
                <i class="bi bi-clock-history"></i> Activity Log
            </a>
            @endif
        </div>

        <div class="sidebar-footer">
            <span><i class="bi bi-circle-fill text-success" style="font-size:.5rem;"></i> Online</span>
            <span class="version">v1.0</span>
        </div>
    </nav>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <div class="topbar">
            <button type="button" class="topbar-btn" id="sidebarToggle" aria-label="Toggle sidebar" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            @php
                $expirySummary = \App\Support\ExpiryNotificationCounter::summary(auth()->user());
                $expiryTotal   = (int) $expirySummary['total'];
                $expiryOverdue = (int) $expirySummary['overdue'];
                $expiryDueSoon = (int) $expirySummary['due_soon'];

                // Badge tone: red = something is overdue, amber = due in <=7d, blue = upcoming only.
                $expiryTone = $expiryOverdue > 0 ? 'urgent' : ($expiryDueSoon > 0 ? 'soon' : 'upcoming');

                $expiryParts = [];
                if ($expiryOverdue > 0) $expiryParts[] = "{$expiryOverdue} overdue";
                if ($expiryDueSoon > 0) $expiryParts[] = "{$expiryDueSoon} due this week";
                $upcomingOnly = $expiryTotal - $expiryOverdue - $expiryDueSoon;
                if ($upcomingOnly > 0) $expiryParts[] = "{$upcomingOnly} upcoming";
                $expiryTooltip = $expiryTotal === 0
                    ? 'Notifications — all caught up'
                    : 'Notifications — ' . implode(' · ', $expiryParts);

                $authUser = auth()->user();
                $initial = strtoupper(substr($authUser->name, 0, 1));
            @endphp
            <div class="topbar-right">
                <button type="button" class="topbar-btn" id="themeToggle" title="Toggle light/dark mode" aria-label="Toggle theme">
                    <i class="bi" id="themeIcon"></i>
                </button>
                <a href="{{ route('notifications.index') }}"
                   class="topbar-btn {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                   title="{{ $expiryTooltip }}"
                   aria-label="{{ $expiryTooltip }}">
                    <i class="bi {{ $expiryOverdue > 0 ? 'bi-bell-fill' : 'bi-bell' }}"></i>
                    @if($expiryTotal > 0)
                        <span class="notify-dot notify-dot--{{ $expiryTone }}">{{ $expiryTotal > 99 ? '99+' : $expiryTotal }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <button type="button" class="topbar-btn user-menu-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                        @if($authUser->avatar)
                            <img src="{{ asset('storage/' . $authUser->avatar) }}" alt="" class="user-avatar">
                        @else
                            <span class="user-avatar">{{ $initial }}</span>
                        @endif
                        <span class="user-meta">
                            <span class="d-block name">{{ $authUser->name }}</span>
                            <span class="d-block role">{{ ucfirst($authUser->role) }}</span>
                        </span>
                        <i class="bi bi-chevron-down caret"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li>
                            <div class="user-menu-card">
                                @if($authUser->avatar)
                                    <img src="{{ asset('storage/' . $authUser->avatar) }}" alt="" class="user-avatar">
                                @else
                                    <span class="user-avatar">{{ $initial }}</span>
                                @endif
                                <div class="user-info">
                                    <div class="name">{{ $authUser->name }}</div>
                                    <div class="email" title="{{ $authUser->email }}">{{ $authUser->email }}</div>
                                    <span class="role-tag">
                                        <i class="bi {{ $authUser->isAdmin() ? 'bi-shield-lock-fill' : 'bi-person-fill' }}"></i>
                                        {{ ucfirst($authUser->role) }}
                                    </span>
                                </div>
                            </div>
                        </li>

                        <li class="user-dropdown-quick">
                            <button type="button" class="user-quick-btn" id="themeQuickToggle" title="Toggle light / dark mode" aria-label="Toggle theme">
                                <i class="bi" id="themeQuickIcon"></i>
                                <span>Theme</span>
                            </button>
                            <button type="button" class="user-quick-btn" data-copy-email="{{ $authUser->email }}" title="Copy email address">
                                <i class="bi bi-clipboard" data-default-icon></i>
                                <span>Copy email</span>
                            </button>
                        </li>

                        <li>
                            <a class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-gear"></i> My Account
                            </a>
                        </li>

                        @if($authUser->isAdmin())
                        <li><div class="dropdown-section-label">Administration</div></li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="bi bi-people"></i> User Management
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('mail-settings.*') ? 'active' : '' }}" href="{{ route('mail-settings.edit') }}">
                                <i class="bi bi-envelope-fill"></i> Mail Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('notification-settings.*') ? 'active' : '' }}" href="{{ route('notification-settings.edit') }}">
                                <i class="bi bi-bell-fill"></i> Notification Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
                                <i class="bi bi-clock-history"></i> Activity Log
                            </a>
                        </li>
                        @endif

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
    @else
        @yield('content')
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const root = document.documentElement;
            const themeBtn = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const quickBtn  = document.getElementById('themeQuickToggle');
            const quickIcn  = document.getElementById('themeQuickIcon');
            function syncIcons() {
                const dark = root.getAttribute('data-bs-theme') === 'dark';
                const cls  = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                if (themeIcon) themeIcon.className = cls;
                if (quickIcn)  quickIcn.className  = cls;
            }
            function toggleTheme(e) {
                if (e && quickBtn && e.currentTarget === quickBtn) e.preventDefault();
                const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-bs-theme', next);
                localStorage.setItem('rrs.theme', next);
                syncIcons();
            }
            syncIcons();
            themeBtn?.addEventListener('click', toggleTheme);
            quickBtn?.addEventListener('click', toggleTheme);

            // Copy email button(s) in the user dropdown
            document.querySelectorAll('[data-copy-email]').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const email = btn.dataset.copyEmail;
                    try {
                        await navigator.clipboard.writeText(email);
                        const icon  = btn.querySelector('.bi');
                        const label = btn.querySelector('span');
                        const origIcon = icon.className;
                        const origText = label.textContent;
                        icon.className = 'bi bi-check2 text-success';
                        label.textContent = 'Copied!';
                        setTimeout(() => {
                            icon.className = origIcon;
                            label.textContent = origText;
                        }, 1400);
                    } catch (err) {
                        alert('Copy failed — select the email manually.');
                    }
                });
            });
        })();
        (function () {
            const body = document.body;
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!toggle) return;

            const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

            // Restore desktop preference (mobile always starts hidden).
            if (!isMobile() && localStorage.getItem('rrs.sidebarHidden') === '1') {
                body.classList.add('sidebar-hidden');
            }

            toggle.addEventListener('click', function () {
                if (isMobile()) {
                    body.classList.toggle('sidebar-visible');
                } else {
                    body.classList.toggle('sidebar-hidden');
                    localStorage.setItem('rrs.sidebarHidden', body.classList.contains('sidebar-hidden') ? '1' : '0');
                }
            });

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    body.classList.remove('sidebar-visible');
                });
            }
        })();
    </script>

    {{-- Shared confirmation modal: window.appConfirm({title, message, note, tone, icon, confirmLabel}) returns Promise<boolean>. --}}
    @auth
    <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-labelledby="appConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="app-confirm-icon app-confirm-icon--danger mb-3"><i class="bi bi-trash-fill"></i></div>
                    <h5 class="app-confirm-title" id="appConfirmTitle">Are you sure?</h5>
                    <p class="app-confirm-message"></p>
                    <p class="app-confirm-note d-none"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger app-confirm-action">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modalEl = document.getElementById('appConfirmModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            const iconBox  = modalEl.querySelector('.app-confirm-icon');
            const iconEl   = iconBox.querySelector('i');
            const titleEl  = modalEl.querySelector('.app-confirm-title');
            const msgEl    = modalEl.querySelector('.app-confirm-message');
            const noteEl   = modalEl.querySelector('.app-confirm-note');
            const cancelEl = modalEl.querySelector('[data-bs-dismiss="modal"]');
            const actionEl = modalEl.querySelector('.app-confirm-action');
            const bsModal  = bootstrap.Modal.getOrCreateInstance(modalEl);

            const TONE_CLASS = { danger: 'btn-danger', warning: 'btn-warning', primary: 'btn-primary', success: 'btn-success' };
            const DEFAULT_ICON = {
                danger: 'bi-exclamation-triangle-fill',
                warning: 'bi-exclamation-circle-fill',
                primary: 'bi-question-circle-fill',
                success: 'bi-check-circle-fill',
            };

            window.appHtmlEscape = function (s) {
                return String(s ?? '').replace(/[<>&"']/g, function (c) {
                    return { '<':'&lt;', '>':'&gt;', '&':'&amp;', '"':'&quot;', "'":'&#39;' }[c];
                });
            };

            window.appConfirm = function (opts) {
                opts = opts || {};
                const tone = opts.tone && TONE_CLASS[opts.tone] ? opts.tone : 'danger';

                titleEl.textContent = opts.title || 'Are you sure?';
                msgEl.innerHTML = opts.message || '';
                if (opts.note) { noteEl.textContent = opts.note; noteEl.classList.remove('d-none'); }
                else { noteEl.textContent = ''; noteEl.classList.add('d-none'); }

                iconBox.className = 'app-confirm-icon app-confirm-icon--' + tone + ' mb-3';
                iconEl.className = 'bi ' + (opts.icon || DEFAULT_ICON[tone]);

                actionEl.className = 'btn app-confirm-action ' + TONE_CLASS[tone];
                actionEl.textContent = opts.confirmLabel || (tone === 'danger' ? 'Delete' : 'Confirm');

                return new Promise(function (resolve) {
                    let settled = false;
                    const confirmHandler = function () {
                        settled = true;
                        actionEl.removeEventListener('click', confirmHandler);
                        bsModal.hide();
                        resolve(true);
                    };
                    const hiddenHandler = function () {
                        modalEl.removeEventListener('hidden.bs.modal', hiddenHandler);
                        actionEl.removeEventListener('click', confirmHandler);
                        if (!settled) resolve(false);
                    };
                    actionEl.addEventListener('click', confirmHandler);
                    modalEl.addEventListener('hidden.bs.modal', hiddenHandler);
                    modalEl.addEventListener('shown.bs.modal', function focusCancel () {
                        modalEl.removeEventListener('shown.bs.modal', focusCancel);
                        // Focus Cancel by default — Enter on the modal won't trigger destructive action.
                        cancelEl.focus();
                    });
                    bsModal.show();
                });
            };

            // Delegated handler: any <form data-app-confirm> intercepts submit and asks first.
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form.matches('[data-app-confirm]')) return;
                if (form.dataset.confirmed === '1') return; // already confirmed, let it through
                e.preventDefault();
                const label  = form.dataset.confirmLabel  || 'this item';
                const action = form.dataset.confirmAction || 'Delete';
                const tone   = form.dataset.confirmTone   || 'danger';
                appConfirm({
                    tone: tone,
                    title: form.dataset.confirmTitle   || (action + ' this item?'),
                    message: form.dataset.confirmMessage || ('You are about to ' + action.toLowerCase() + ' <strong>' + appHtmlEscape(label) + '</strong>.'),
                    note: form.dataset.confirmNote     || (tone === 'danger' ? 'This action cannot be undone.' : ''),
                    confirmLabel: action,
                }).then(function (ok) {
                    if (!ok) return;
                    form.dataset.confirmed = '1';
                    form.submit();
                });
            });
        })();
    </script>
    @endauth

    @stack('scripts')
</body>
</html>
