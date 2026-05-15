# Renewal Reminding System (RRS) - Project Specification

This document serves as the primary technical specification for building the **Renewal Reminding System (RRS)**. This is a Laravel 11 web application designed for IT asset management and subscription tracking.

## 🛠 Tech Stack (Local Environment)
- **Framework:** Laravel 11 (PHP 8.2+)
- **OS:** Windows 11
- **Server Environment:** XAMPP (Apache)
- **Frontend:** Blade Templates + Bootstrap 5
- **Database:** MySQL / MariaDB (via XAMPP)
- **Task Scheduling:** Windows Task Scheduler (to run Laravel Scheduler)
- **Notifications:** Mail (SMTP)

---

## 🗄️ Database Schema & Data Models

### 1. `users` & `roles`
- **Roles:** `admin`, `user`
- **Permissions:** - `admin`: Full CRUD on all modules, user management.
    - `user`: View/Edit assets and subscriptions; no deletion or user management.

### 2. `pc_assets` (PC Master List)
| Field | Type | Details |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `computer_id` | String | Unique |
| `hostname` | String | |
| `employee_name` | String | |
| `status` | Enum | 'Free', 'Active', 'Damage', 'Retirement', 'Low Performance' |
| `department` | Enum | 'IT', 'HR', 'Finance', 'Contract' |
| `location` | Enum | 'Office', 'WFH' |
| `brand`, `model`, `serial_number` | String | |
| `cpu`, `ram`, `ssd`, `hdd`, `display` | String | |
| `operating_system` | String | |
| `admin_password` | String | Encrypted (Laravel Crypt) |
| `username`, `password` | String | Encrypted (Laravel Crypt) |
| `purchased_date` | Date | |
| `warranty_period` | String | |
| `remarks` | Text | Nullable |
| `modified_by` | String | Tracks last editor |

### 3. `subscriptions` (SSL / Domain / Services)
| Field | Type | Details |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `service_type` | Enum | 'Domain', 'SSL', 'Subscription', 'Hosting', 'Cloud Service' |
| `project_name` | String | |
| `subscription_name` | String | |
| `status` | Enum | 'Active', 'Terminated' |
| `period` | String | e.g., "1 Year" |
| `previous_cost` | Decimal(10,2) | |
| `expire_date` | Date | |
| `renewal_cost` | Decimal(10,2) | |
| `renewal_type` | Enum | 'Yearly', 'Monthly', 'Pay as you go', 'One Time' |
| `reminder_date` | Date | Auto-calculated (Expire Date - 30 days) |
| `renewal_status` | Enum | 'Pending', 'Renewed', 'Expired', 'Cancelled' |
| `remarks` | Text | Nullable |

---

## ⚙️ Business Logic & Workflows

### 1. Expiration Reminder Logic
- **Frequency:** Daily check via Laravel Scheduler.
- **Trigger:** If `expire_date` is within **30 days** and `renewal_status` is not 'Renewed'.
- **Action:** 1. Update `renewal_status` to 'Pending'.
    2. Create a notification record.
    3. Send an Email notification to all Admin users.

### 2. PC Asset Management
- Sensitive fields (Passwords) must be encrypted using `Crypt::encryptString`.

---

## 🎨 UI & Routing
- Sidebar navigation: Dashboard, PC Master, Subscriptions, User Management.
- Top bar: Profile, Logout.
- **Dashboard:** Show summary cards for Assets and a table of "Expiring Soon" services.

---

## 🚀 AI Implementation Instructions (Claude Code / AI Coding)
1. **Migrations:** Generate migrations for `pc_assets` and `subscriptions` with specified enums.
2. **Models:** Set up `PcAsset` and `Subscription` models with proper `$casts` for dates and encrypted fields.
3. **Command:** Create `app:check-expirations` console command for the daily logic.
4. **Views:** Build CRUD interfaces using Bootstrap 5 and Blade.

---

## 💻 Local Deployment & Setup (XAMPP on Windows 11)
- **Directory:** Place project in `C:/xampp/htdocs/rrs-system`.
- **Virtual Host:** Configure Apache `httpd-vhosts.conf` to point to the `public` directory.
- **Environment:** Update `.env` with `DB_HOST=127.0.0.1`, `DB_PORT=3306`, and SMTP mail credentials.
- **Scheduler:** Use Windows Task Scheduler to run `php artisan schedule:run` every minute.
