# ITAMS / infra-ninja — Installation Guide

Production deployment of the **ITAMS – IT Assets Management System** (Laravel 11, PHP 8.2, MySQL 8, Vite/Tailwind) on **Rocky Linux 9** using Docker.

> For the exhaustive reference (rationale, request lifecycle, glossary), see [`DOCKER_DEPLOYMENT.txt`](./DOCKER_DEPLOYMENT.txt). This document is the practical install path.

---

## Table of contents

1. [Architecture at a glance](#1-architecture-at-a-glance)
2. [Prerequisites](#2-prerequisites)
3. [Option A — Automated install (recommended)](#3-option-a--automated-install-recommended)
4. [Option B — Manual install](#4-option-b--manual-install)
5. [Putting TLS in front of the stack](#5-putting-tls-in-front-of-the-stack)
6. [Production hardening checklist](#6-production-hardening-checklist)
7. [Operations cheat sheet](#7-operations-cheat-sheet)
8. [Backup & restore](#8-backup--restore)
9. [Update & rollback](#9-update--rollback)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Architecture at a glance

```
                     Internet
                        │
                        │ HTTPS :443
                        ▼
              ┌───────────────────┐
              │   Host TLS proxy  │   Caddy / Nginx on the Rocky host
              └─────────┬─────────┘
                        │ HTTP, 127.0.0.1:80
                        ▼
  ┌─────────────────────────────────────────────────┐
  │              docker bridge network              │
  │                                                 │
  │   ┌────────┐  FastCGI   ┌──────────┐            │
  │   │ nginx  │──────────▶ │   app    │            │
  │   │ :alpine│  :9000     │ php-fpm  │            │
  │   └────────┘            └────┬─────┘            │
  │                              │ PDO              │
  │   ┌──────────┐               ▼                  │
  │   │  queue   │          ┌──────────┐            │
  │   │ artisan  │─────────▶│    db    │            │
  │   │queue:work│          │ mysql:8  │            │
  │   └──────────┘          └──────────┘            │
  └─────────────────────────────────────────────────┘
```

| Service | Image                       | Role                                  | Host port |
| ------- | --------------------------- | ------------------------------------- | --------- |
| `app`   | built from `Dockerfile`     | Laravel runtime (php-fpm)             | —         |
| `nginx` | `nginx:alpine`              | Static files + FastCGI proxy to `app` | **80**    |
| `queue` | same image as `app`         | `php artisan queue:work`              | —         |
| `db`    | `mysql:8.0`                 | Persistent data store                 | — *(internal only)* |

Volumes: `dbdata` (MySQL data) and `storage` (Laravel `storage/`).

---

## 2. Prerequisites

- Rocky Linux 9 host (physical, VM, or VPS).
- Root or `sudo` access.
- **2 vCPU / 2 GB RAM** minimum, 4 GB recommended.
- **20 GB** disk minimum.
- Outbound internet (to pull base images and packages).
- A DNS A record pointing your domain at the host (needed for TLS).

---

## 3. Option A — Automated install (recommended)

The repo ships an idempotent installer that performs every step in §4.

```bash
# 1. Get the source
sudo git clone <your-repo-url> /opt/infra-ninja
cd /opt/infra-ninja

# 2. Run the installer (asks for your public URL, then auto-generates passwords)
sudo bash install-rocky9.sh
```

What it does:

1. Installs Docker CE + Compose plugin from Docker's official repo.
2. Enables/starts `docker.service` and opens firewalld for HTTP(S).
3. Sets SELinux booleans (`container_manage_cgroup`).
4. Verifies the committed Docker files (`Dockerfile`, `docker-compose.yml`, `docker/**`, `.dockerignore`) are present.
5. Writes `.env` with strong, randomly-generated DB passwords (saved separately at `/root/infra-ninja-credentials.txt`, mode `0600`).
6. Builds the images, generates `APP_KEY`, runs `docker compose up -d`.

When it finishes, the app is reachable at the URL you entered. If that URL is HTTPS, jump to [§5](#5-putting-tls-in-front-of-the-stack) to install Caddy.

> **Non-interactive runs:** pre-set `DOMAIN`, `HTTP_PORT`, `DB_NAME`, `DB_USER` as env vars before invoking the script.

> **If the build step fails with `npm error code EUSAGE`** (`npm ci` can only install with an existing `package-lock.json`), the repo is missing its lockfile. Run the one-liner from [§4.6](#46-generate-package-lockjson-one-time-if-missing) on the host, then re-run the installer — it's idempotent.

---

## 4. Option B — Manual install

Use this if you want to understand every step or run a non-standard config.

### 4.1 Install Docker Engine + Compose plugin

```bash
sudo dnf install -y dnf-plugins-core curl git
sudo dnf config-manager --add-repo \
    https://download.docker.com/linux/centos/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io \
    docker-buildx-plugin docker-compose-plugin

sudo systemctl enable --now docker
docker --version
docker compose version
```

> The CentOS docker-ce repo is binary-compatible with Rocky 9; there is no separate "rocky" repo.

### 4.2 Open the firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 4.3 SELinux

```bash
sudo setsebool -P container_manage_cgroup on
```

The compose file already uses `:Z` on bind mounts so Docker relabels them with the container context. No further action needed.

### 4.4 Get the source

```bash
sudo git clone <your-repo-url> /opt/infra-ninja
cd /opt/infra-ninja
```

The Docker config files (`Dockerfile`, `docker-compose.yml`, `docker/nginx/default.conf`, `docker/php/php.ini`, `docker/entrypoint.sh`, `.dockerignore`) are already in the repo — no need to create them.

### 4.5 Create `.env`

```bash
cp .env.docker.example .env
chmod 600 .env
sudo chown root:root .env
```

Generate strong passwords and paste them into the `FIXME_*` lines:

```bash
tr -dc 'A-Za-z0-9_.-' </dev/urandom | head -c 24 ; echo  # DB_PASSWORD
tr -dc 'A-Za-z0-9_.-' </dev/urandom | head -c 24 ; echo  # DB_ROOT_PASSWORD
```

Edit `.env` and set:

| Key                     | Value                                                            |
| ----------------------- | ---------------------------------------------------------------- |
| `APP_URL`               | Your real URL (e.g. `https://itams.example.com`)                 |
| `DB_PASSWORD`           | First generated password                                          |
| `DB_ROOT_PASSWORD`      | Second generated password                                         |
| `SESSION_SECURE_COOKIE` | `true` once HTTPS is live; `false` while still on plain HTTP     |

> **Store the passwords in a password manager.** They can never be recovered if lost — only reset by wiping the `dbdata` volume (data loss).

### 4.6 Generate `package-lock.json` (one-time, if missing)

The Dockerfile uses `npm ci` for the Vite asset build. `npm ci` **requires** `package-lock.json` and aborts with `npm error code EUSAGE` if it's missing. Check first:

```bash
ls package-lock.json 2>/dev/null && echo OK || echo MISSING
```

If it prints `MISSING`, generate it once using a throwaway Node container:

```bash
docker run --rm -v "$(pwd):/app" -w /app node:20-alpine npm install
ls -la package-lock.json
```

This pulls ~150 MB of packages into a local `node_modules/` (excluded from both git and the Docker build context — only the lockfile matters). Takes 30–90 seconds.

> **Commit it back to the repo** so future deploys never hit this step:
>
> ```bash
> git add package-lock.json
> git commit -m "Add package-lock.json for reproducible Docker builds"
> git push
> ```

### 4.7 Build the images

```bash
docker compose build
```

First build takes ~3–5 min (pulls base images, runs `npm ci` + `composer install`). Subsequent builds reuse layers.

### 4.8 Generate `APP_KEY`

A Laravel `APP_KEY` is literally `base64:` followed by the base64 of 32 random bytes — exactly what `openssl rand -base64 32` produces. So the easiest and most reliable path generates the key on the **host** with openssl. No Docker, no entrypoint, no DB needed at this step.

#### Option A — Generate on the host with openssl (recommended)

```bash
APP_KEY="base64:$(openssl rand -base64 32)"
echo "Got: $APP_KEY"
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
grep ^APP_KEY= .env
```

You should see one clean line:

```
APP_KEY=base64:abc...XYZ=
```

That's mathematically identical to what `php artisan key:generate --show` produces — and it sidesteps the issue described below.

#### Option B — Via artisan (only after `db` is up)

If you'd rather use artisan, you must start the `db` service first, because the `app` container's entrypoint waits for MySQL before letting any command run:

```bash
# 1. Start just the database
docker compose up -d db
sleep 10   # give MySQL a moment to be ready

# 2. Now the entrypoint can finish its wait; capture only the key
APP_KEY=$(docker compose run --rm -T app php artisan key:generate --show 2>/dev/null \
    | tr -d '\r' \
    | grep -oE 'base64:[A-Za-z0-9+/=]+' \
    | tail -n1)

# 3. Sanity-check, then write
echo "Got: $APP_KEY"
[ -n "$APP_KEY" ] || { echo "ERROR: APP_KEY extraction failed"; exit 1; }
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
grep ^APP_KEY= .env
```

> ⚠️ **What goes wrong with the naïve form** — `docker compose run --rm --no-deps app php artisan key:generate --show`:
> - `--no-deps` keeps `db` from starting.
> - The image's entrypoint runs first and tries `php -r "new PDO('mysql:host=db;…')"` to wait for MySQL.
> - With no `db` service in the network, that resolves to `getaddrinfo for db failed: Name does not resolve` and the wait loop spins forever (often spilling the PDO trace to stderr despite `2>/dev/null`, because PHP 8.2's `SensitiveParameterValue` exception handler doesn't always honour the redirect).
>
> Use Option A above to skip this whole problem.

### 4.9 First bring-up

```bash
docker compose up -d
docker compose ps
docker compose logs -f app
```

You should see, in order:

```
Waiting for MySQL at db:3306...
MySQL is up.
Migration table created successfully.
... migrations run ...
Application config cached successfully.
Routes cached successfully.
Views cached successfully.
NOTICE: ready to handle connections
```

Open `http://<host-ip>` — you should hit the Laravel login page.

---

## 5. Putting TLS in front of the stack

The `nginx` container only listens on plain HTTP port 80. Terminate TLS on the host with Caddy (auto-issues Let's Encrypt certs):

```bash
sudo dnf install -y 'dnf-command(copr)'
sudo dnf copr enable -y @caddy/caddy
sudo dnf install -y caddy
```

Edit `/etc/caddy/Caddyfile`:

```caddyfile
itams.example.com {
    reverse_proxy localhost:80
}
```

```bash
sudo systemctl enable --now caddy
sudo systemctl reload caddy
```

Once HTTPS resolves, flip `.env`:

```dotenv
APP_URL=https://itams.example.com
SESSION_SECURE_COOKIE=true
```

```bash
docker compose restart app queue
```

---

## 6. Production hardening checklist

- [ ] `APP_DEBUG=false` in `.env` (verified by `docker compose exec app php artisan about`).
- [ ] `LOG_LEVEL=warning` (not `debug`).
- [ ] `.env` is mode `0600`, owned by `root`.
- [ ] DB has no host-published port (`docker compose ps` shows no `3306` mapping).
- [ ] Caddy/Nginx is fronting the stack with TLS; `SESSION_SECURE_COOKIE=true`.
- [ ] Docker log rotation configured (`/etc/docker/daemon.json`):
  ```json
  { "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "5" } }
  ```
  Then `sudo systemctl restart docker`.
- [ ] Unattended security updates: `sudo dnf install -y dnf-automatic && sudo systemctl enable --now dnf-automatic.timer`.
- [ ] SSH: key-only auth, no root login, `fail2ban` enabled.
- [ ] Nightly DB backup cron in place (see [§8](#8-backup--restore)).
- [ ] Off-host copy of backups (S3, rsync, restic, …).

---

## 7. Operations cheat sheet

```bash
# Status / logs
docker compose ps
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f queue
docker compose logs --tail=200 db

# Laravel
docker compose exec app php artisan about
docker compose exec app php artisan tinker
docker compose exec app php artisan migrate:status
docker compose exec app php artisan queue:failed

# Composer
docker compose exec app composer install --no-dev
docker compose exec app composer require <pkg>

# MySQL shell (root password is in .env)
docker compose exec db mysql -uroot -p"$(grep ^DB_ROOT_PASSWORD .env | cut -d= -f2)" rrs_system

# Restart one service
docker compose restart nginx
docker compose restart queue

# Stop everything (KEEPS volumes/data)
docker compose down

# Stop and DELETE all data (DANGEROUS — wipes dbdata + storage)
docker compose down -v

# Disk usage
docker system df
docker image prune -f
```

---

## 8. Backup & restore

### Database

```bash
# One-off backup
docker compose exec -T db \
    mysqldump --single-transaction --quick \
    -uroot -p"$(grep ^DB_ROOT_PASSWORD .env | cut -d= -f2)" rrs_system \
    | gzip > /var/backups/itams/db_$(date +%F_%H%M).sql.gz
```

Nightly cron — `/etc/cron.d/itams-backup`:

```cron
0 2 * * *  root  cd /opt/infra-ninja && \
    docker compose exec -T db mysqldump --single-transaction --quick \
    -uroot -p"$(grep ^DB_ROOT_PASSWORD /opt/infra-ninja/.env | cut -d= -f2)" rrs_system \
    | gzip > /var/backups/itams/db_$(date +\%F).sql.gz
```

Restore:

```bash
gunzip < /var/backups/itams/db_2026-05-26.sql.gz | \
    docker compose exec -T db \
    mysql -uroot -p"$(grep ^DB_ROOT_PASSWORD .env | cut -d= -f2)" rrs_system
```

### Uploaded files (`storage` volume)

```bash
# Backup
docker run --rm \
    -v infra-ninja_storage:/data \
    -v /var/backups/itams:/out \
    alpine tar czf /out/storage_$(date +%F).tar.gz -C /data .

# Restore
docker run --rm \
    -v infra-ninja_storage:/data \
    -v /var/backups/itams:/in \
    alpine sh -c "cd /data && tar xzf /in/storage_2026-05-26.tar.gz"
```

Keep ≥14 days locally **and** ship copies off-host.

---

## 9. Update & rollback

**Deploy a new version:**

```bash
cd /opt/infra-ninja
git fetch --tags
git checkout <tag-or-commit>
docker compose build
docker compose up -d
docker compose logs -f app   # watch for "ready to handle connections"
```

The entrypoint runs `migrate --force` and re-caches config/routes/views on every boot.

**Rollback:**

```bash
cd /opt/infra-ninja
git checkout <previous-tag>
docker compose build
docker compose up -d
```

> If a migration was destructive, restore the DB from backup **before** bringing the older code up.

---

## 10. Troubleshooting

| Symptom | Cause / Fix |
| ------- | ----------- |
| `SQLSTATE[HY000] [2002] Connection refused` | `DB_HOST` must be `db` (the compose service name), not `127.0.0.1`. Check: `docker compose exec app env \| grep DB_HOST`. |
| 502 Bad Gateway from nginx | `app` container exited. `docker compose logs app` — usually a `.env` typo or DB-credential mismatch. |
| `exec: ./docker/entrypoint.sh: not found` or `bad interpreter: ^M` | CRLF line endings (file was re-saved on Windows). Fix: `sed -i 's/\r$//' docker/entrypoint.sh && docker compose build app`. The committed `.gitattributes` pins LF, so this only happens if someone bypassed it. |
| nginx `permission denied` reading `default.conf` | Missing `:Z` SELinux label on the bind mount. Confirm `docker-compose.yml` still has `…default.conf:ro,Z`, then `docker compose up -d --force-recreate nginx`. |
| `storage/` or `bootstrap/cache` permission denied at runtime | `docker compose exec app chown -R www-data:www-data storage bootstrap/cache`. |
| Vite assets 404 (`/build/*.js` missing) | Asset stage didn't run or didn't get copied. `docker compose build --no-cache app && docker compose up -d app`. |
| `npm error code EUSAGE` / `npm ci can only install with an existing package-lock.json` during `docker compose build` | `package-lock.json` is missing from the repo. Generate it once with `docker run --rm -v "$(pwd):/app" -w /app node:20-alpine npm install`, then commit it. See §4.6. |
| `sed: -e expression #1, char NN: unterminated 's' command` while setting `APP_KEY` | The captured key contained extra lines (docker compose progress UI). Re-run the §4.8 command — the updated form passes `-T`, strips CR, and `grep`s only the `base64:` token so multi-line output can't corrupt sed. Or use §4.8 Option B (copy/paste). |
| `WARN[0000] mount of type 'volume' should not define 'bind' option` when running `docker compose run` or `up` | An older `docker-compose.yml` had `:Z` (an SELinux *bind-mount* flag) on **named volume** mounts. Harmless in compose v2 today, will become an error in future versions. Fix: pull the current `docker-compose.yml` from the repo — `:Z` has been removed from the `storage` volume mounts and is kept only on the `nginx/default.conf` bind mount, where it belongs. |
| `APP_KEY=` line in `.env` is empty, garbled, or duplicated after §4.8 | The naïve `APP_KEY=$(docker compose run …)` form captured progress UI lines into the variable, and the subsequent `sed` either silently wrote junk or aborted. Edit `.env` manually so there is exactly one line `APP_KEY=base64:…=`, then continue. |
| `PDOException: SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for db failed: Name does not resolve` during §4.8 | The entrypoint's MySQL-wait loop is trying to connect to the `db` service, but `--no-deps` prevented it from starting, so the hostname doesn't exist in Docker's network. Use §4.8 **Option A** (openssl on the host) — it sidesteps Docker entirely for this step. |
| `db` restart-loops with `Access denied for user 'root'` | Password mismatch with the existing `dbdata` volume. Either align `.env` with the original password, or wipe the volume (**DATA LOSS**): back up first, then `docker compose down -v`. |
| Migrations don't run | Tail `docker compose logs app` — you should see `Waiting for MySQL...` then migration output. If not, the entrypoint failed earlier. |
| Queue jobs not processing | `docker compose ps` — is `queue` running? `docker compose logs -f queue`. Confirm dispatching code uses `QUEUE_CONNECTION=database` (matches the worker). |
| Disk filling up | `docker system df`, then `docker image prune -f`. Also check `/var/lib/docker/volumes` and rotate Laravel logs. |

---

## Need more detail?

Every section above maps to a section in [`DOCKER_DEPLOYMENT.txt`](./DOCKER_DEPLOYMENT.txt), which covers the *why* behind each choice (multi-stage build rationale, request lifecycle, layer caching, etc.).
