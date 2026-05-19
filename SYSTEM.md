# Infra Ninja — IT Assets Management System (ITAMS)

A Laravel web application for tracking the IT estate of a small-to-mid organization: PC inventory, peripheral devices, recurring subscriptions, and software licenses / contracts. Includes built-in expiry reminders, granular per-user permissions, and a styled multi-channel notification system.

---

## Tech stack

| Layer | What's used |
|---|---|
| Backend | PHP 8.2+, Laravel 11.31 |
| Database | MySQL / MariaDB |
| Frontend | Blade templates + Bootstrap 5 + Bootstrap Icons 1.11.3 |
| Build | Vite + Tailwind PostCSS (`vite.config.js`, `tailwind.config.js`) |
| Charts | Chart.js (CDN) |
| Excel | `maatwebsite/excel` for import / export of every module |
| Mail | Laravel Mail (SMTP / log / sendmail), runtime-overridable from DB |
| Scheduler | Laravel scheduler (run via `php artisan schedule:run`, e.g. Windows Task Scheduler) |
| Local environment | XAMPP on Windows (per the included `xampp_toolchain.md` memory) |

---

## Modules

Each module has full CRUD, search, filter, import (Excel), export (Excel), bulk delete, and is gated by per-user view / edit permissions.

| Module | Route prefix | What it tracks |
|---|---|---|
| **PC Master** (`PcAsset`) | `/pc-assets` | Workstations: ID, hostname, employee, brand/model, status (Active / Free / Damage / Retirement / Low Performance), serial, OS, encrypted credentials, purchased date, warranty |
| **Device Master** (`Device`) | `/devices` | Peripherals & shared inventory: item name, qty, vendor, serial, location, delivery date, warranty, status (Active / Free / Damage / Retirement / Lost) |
| **Subscriptions** (`Subscription`) | `/subscriptions` | Recurring services: SSL / domain / hosting / cloud / subscription; status, period, vendor, costs, currency, expire date, renewal type and status |
| **License & Contract** (`LicenseContract`) | `/licenses-contracts` | Software licenses & vendor contracts: software name, vendor, last-renewal date, expire date, costs, status (Active / Expired / Terminated / Pending) |

Sensitive PC fields (`admin_password`, `username`, `password`) are stored with Laravel's `encrypted` cast.

---

## Users & permissions

### Two roles
- **admin** — full access; bypasses every module-permission check.
- **user** — non-privileged; sees only what an admin grants.

### Per-module view / edit grants
Each non-admin user has two boolean flags per module (`pc_assets`, `subscriptions`, `licenses_contracts`, `devices`):
- `can_view_<module>` — sees the index, show, export, template endpoints.
- `can_edit_<module>` — additionally allowed to create, update, delete, import, bulk-delete.
- **Edit implies View** — enforced in the form (auto-checks View, locks it), the controller (`applyPermissions`), and the model (`canView()` OR's `can_edit_*` in).

The grants are managed at `/users/{id}/edit` (admin-only) or visible read-only at `/profile` for the user themselves.

### Route gating
- Read routes: `module:<name>,view` middleware.
- Write routes: `module:<name>,edit` middleware. The `EnsureUserCanAccessModule` middleware checks the action and aborts 403 with a human-readable label if denied.
- Admin-only routes (User Management, Mail Settings, Notification Settings, Activity Log) are wrapped in a separate `admin` middleware group.

---

## Authentication

| Page | Route | Purpose |
|---|---|---|
| Sign in | `GET /login` → `POST /login` | Email + password, "Keep me signed in", show/hide password |
| Forgot password | `GET /forgot-password` → `POST /forgot-password` | Email entry. **Branches by role**: admin → emailed a reset link; non-admin or unknown email → "contact your administrator" message with `mailto:` button addressed to the actual admin emails |
| Reset password | `GET /reset-password/{token}` → `POST /reset-password` | New password form with strength meter, live match indicator, requirements checklist. Even with a valid token, only admins are allowed to use it |
| Sign out | `POST /logout` | Invalidates session |

All three auth pages share a **modern glass-card shell** (`resources/views/auth/_fp-shell.blade.php`) with floating-gradient blob background, floating-label inputs, gradient CTAs, and dark-mode parity.

The login page also includes a floating theme toggle so users can pick light/dark before signing in (preference persists via `localStorage`).

### Self-service profile
Every authenticated user can visit `/profile` ("My Account" in the user dropdown) to:
- Change their name and email.
- Change their password (requires `current_password`, defends against session hijack).
- Upload / replace their avatar (JPG / PNG / WebP, max 2 MB).
- See (read-only) their role and per-module access.

---

## Dashboard

`GET /dashboard` shows:
- Four KPI cards: total PCs, total devices (rows + sum of qty), active subscriptions, expiring-soon count.
- Inventory chart (Chart.js bar): PC Master status counts vs Device Master status counts.
- Recent activity feed (last 8 entries from `activity_logs`).
- Two tables: subscriptions and licenses expiring within 30 days.

> **Note:** the dashboard uses a hardcoded 30-day "expiring soon" window and excludes already-overdue records. The bell badge / notifications page use the per-module `NotificationSetting.days_before_set` and include overdue items — so the two surfaces can show different numbers by design.

---

## Notifications

### Per-module settings (`/notification-settings`)
For each of the four modules, admins configure:
- **Enabled** (master on/off switch per module).
- **Reminder days before expiry** — three on/off toggles for **30 / 20 / 10** days. Multi-select; the widest selected window drives badge/list visibility, each selected value also drives a staggered email reminder.
- **Recipients** — free-text list (comma, semicolon, or newline separated). Empty falls back to all admin emails.

> Only Subscriptions and License & Contract currently support reminders. PC Master and Device Master tabs are stubbed as "coming soon" until they grow proper expiry-date columns.

### Live badge (topbar bell)
The bell in the topbar shows a count of records currently within their reminder window, **per user**:
- **Tone**: red + pulse if any overdue; amber if anything due in ≤ 7 days; blue otherwise.
- **Count**: subtracts items the current user has marked as read.
- **Tooltip**: breaks down "X overdue · Y this week · Z upcoming".

Implementation: `App\Support\ExpiryNotificationCounter::summary($user)`.

### Notifications page (`/notifications`)
Lists live items from Subscriptions and License & Contract (gated by `NotificationSetting`). Features:
- Filter chips: All / Unread / Read (counts shown inline).
- Per-module filter: `?module=subscriptions` or `?module=licenses_contracts`.
- Per-item "Mark as read" round button.
- Header "Mark all as read" (only visible when there are unread items).
- Module-colored icon (amber subs / green L&C) + urgency-colored status badge.
- Faded styling for read items.

### Mark-as-read semantics
Reads are stored in `notification_reads` keyed by `(user, module, notifiable_id)`. Each row stores a `read_signature` = `{expire_date}|{bucket}` snapshot. A record stays "read" only while the live signature matches — so a renewal (`expire_date` moves) or a bucket transition (`upcoming` → `due_soon` → `overdue`) **auto-re-surfaces** the item.

### Reminder emails (staggered)
The daily `app:check-expirations` console command:
1. Marks overdue Subscriptions as `renewal_status = Expired`.
2. For each enabled module, for each selected day-mark D, queries records where `expire_date = today + D` and sends **one digest** to recipients (`App\Mail\ExpiryReminderDigest`). For example, with `[30, 20, 10]` selected, a record receives exactly three reminders before expiring.

The digest is a single tabular HTML email shared across modules. The command is intended to run **once daily** — manual re-runs the same day will re-send the same digest.

---

## Mail settings (`/mail-settings`)

Stored in the `mail_settings` table (single-row), overridable at runtime via `AppServiceProvider::applyDbMailSettings()`. Fields:
- **Database SMTP settings** master toggle. When off, mail uses `.env` configuration.
- Mailer (`smtp` / `log` / `sendmail`), host, port, encryption, auth mode.
- Username, password (stored encrypted via `'password' => 'encrypted'` cast).
- From address, from name.
- Test-send to any email (verifies configuration).
- Status panel: active mailer, from address, recipient counts.

> The legacy `reminder_days_before` / `reminder_recipients` fields on `mail_settings` are kept for backwards compatibility but are **no longer used by the cron** — reminders are now driven by per-module `NotificationSetting`.

---

## Activity log (`/activity-logs`, admin-only)

Every meaningful action writes to `activity_logs` via `App\Support\ActivityLogger::log(...)`. Captured: `action`, `description`, `subject_type`, `subject_id`, `user_id`, `user_name`, `user_email`, `properties` (JSON), `ip_address`, `user_agent`, timestamps.

Actions you'll see:
- CRUD on every module: `created`, `updated`, `deleted`, `imported`.
- Subscriptions: `renewed`.
- Auth: `login`, `logout`, `login_failed`, `password_reset_requested`, `password_reset`, `password_reset_denied`.
- User mgmt: `mail_sent` (welcome email), `mail_test`, `profile_updated`.
- Mail / Notification settings: `updated`.

---

## Database

Key tables (see `database/migrations/`):

| Table | Notes |
|---|---|
| `users` | Includes `role`, `avatar`, and the 8 per-module flags (`can_view_X`, `can_edit_X`) |
| `pc_assets` | Workstations; sensitive fields encrypted |
| `devices` | Peripheral inventory |
| `subscriptions` | With `status` and a separate `renewal_status` (the cron flips the latter, never the former) |
| `licenses_contracts` | Software licenses & vendor contracts |
| `mail_settings` | Single-row, drives runtime mail config when enabled |
| `notification_settings` | One row per module; holds `enabled`, `days_before_set` (JSON), `recipients` |
| `notification_reads` | Per-user per-record read state; signature column auto-invalidates on renewal/bucket change |
| `activity_logs` | Audit trail |
| `password_reset_tokens` | Standard Laravel password broker storage |
| `sessions` | Database session driver |

---

## Setup

### Prerequisites
- PHP **8.2+** (XAMPP ships with this), MySQL/MariaDB, Composer.
- Node.js if you want to run `npm install && npm run dev` for asset hot-reload (production simply uses the pre-built assets).

### Install
```bash
git clone <repo> infra-ninja
cd infra-ninja
composer install
cp .env.example .env
php artisan key:generate
# Edit .env: APP_NAME, APP_URL, DB_*, MAIL_*
php artisan migrate
php artisan storage:link
npm install && npm run build   # optional, for asset rebuild
```

### XAMPP-specific (Windows)
With XAMPP at `C:\xampp\`:
- PHP: `C:\xampp\php\php.exe`
- MySQL: `C:\xampp\mysql\bin\mysql.exe` (root user, no password by default)
- Composer (manual install): `& C:\xampp\php\php.exe C:\xampp\composer.phar <args>`
- Make sure the PHP `zip` extension is enabled in `C:\xampp\php\php.ini` for Composer to extract archives.

### Schedule the daily reminder
Add to your scheduler (Windows Task Scheduler, cron, etc.) so that the Laravel scheduler runs every minute:
```bash
php artisan schedule:run
```
Make sure `app:check-expirations` is registered in the scheduler (typically in `routes/console.php` or `app/Console/Kernel.php`).

---

## Common workflows

### Add a new user
1. Admin signs in → user dropdown → User Management → Add User.
2. Fills name, email, password, role; per-module View/Edit toggles for non-admins.
3. On save, the new user **receives a welcome email** with their email and the temporary password they were just assigned, plus a sign-in link (`App\Mail\UserCredentialsMail`).

### Forgot password (admin)
1. From login page, click "Forgot password?".
2. Enter email. Admin → receives reset link, valid 60 min.
3. Clicks link → sets new password (strength meter, live match indicator, requirements checklist).
4. Redirected to login with success flash.

### Forgot password (non-admin)
Same form, same result page — but instead of an email, sees a "contact your administrator" panel with a `mailto:` button addressed to all admin emails (subject + body pre-filled). No email is sent and the system doesn't reveal whether the address exists.

### Mark expiring renewals as handled
1. Admin sees bell badge with count.
2. Opens `/notifications`, filters by Unread.
3. Reviews each item, opens its record to renew/extend/terminate, or clicks the round check button to acknowledge it as read.
4. When the underlying record is renewed (expire_date moves forward), it disappears from the list automatically.

### Tune reminders
1. `/notification-settings` → pick module tab.
2. Toggle the day-marks (30 / 20 / 10) you want to fire emails on.
3. Add recipients (or leave empty to fall back to all admin emails).
4. Save. Next cron run respects the new config.

---

## Project layout

```
app/
  Console/Commands/CheckExpirations.php   # daily reminder cron
  Http/Controllers/                       # one per module + Auth/ + ProfileController, etc.
  Http/Middleware/
    EnsureUserIsAdmin.php                  # admin alias
    EnsureUserCanAccessModule.php          # module:<name>,<view|edit> alias
  Mail/
    UserCredentialsMail.php                # welcome email to new users
    PasswordResetMail.php                  # admin password reset
    ExpiryReminderDigest.php               # per (module × day-mark) digest
  Models/                                 # User, PcAsset, Device, Subscription,
                                          # LicenseContract, MailSetting,
                                          # NotificationSetting, NotificationRead,
                                          # ActivityLog
  Support/
    ActivityLogger.php                     # single entry point for audit log
    ExpiryNotificationCounter.php          # source of truth for badge counts

resources/views/
  auth/                                   # login + forgot/reset + _fp-shell partial
  dashboard.blade.php
  pc_assets/, devices/, subscriptions/, licenses_contracts/
  users/                                  # admin user management
  profile/edit.blade.php                  # self-service profile
  mail_settings/, notification_settings/
  notifications/, activity_logs/
  emails/                                 # transactional templates
  layouts/app.blade.php                   # sidebar + topbar + flash messages

routes/web.php                            # all routes (~69)

database/migrations/                      # ordered schema history
```

---

## Conventions worth knowing

- **Activity logging is opt-in** in controllers (call `ActivityLogger::log(...)` after the action).
- **Avatars** live under `storage/app/public/avatars/` and are served via `storage/` symlink (`php artisan storage:link`).
- **Soft deletes are NOT used** — delete is permanent. Confirmation handled by the shared `window.appConfirm()` modal (`layouts/app.blade.php`).
- **Confirmation modal**: any `<form data-app-confirm data-confirm-label="...">` is auto-intercepted and asks before submitting. Bulk-delete and per-row delete handlers also use `appConfirm({...})` programmatically.
- **CSRF**: every state-changing form uses `@csrf`.
- **Theme**: light/dark managed by `data-bs-theme` attribute on `<html>`, persisted in `localStorage` under `rrs.theme`. Initialized in `layouts/app.blade.php`.
