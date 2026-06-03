#!/usr/bin/env bash
###############################################################################
# install-rocky9.sh
#
# All-in-one installer to deploy the ITAMS / infra-ninja Laravel 11 application
# on Rocky Linux 9 using Docker containers (nginx + php-fpm + mysql + queue).
#
# Run AS ROOT (or with sudo) from inside the project directory:
#     sudo bash install-rocky9.sh
#
# What it does:
#   1.  Verifies the host is Rocky Linux 9 and you are root.
#   2.  Installs Docker CE + Compose plugin from the official docker repo.
#   3.  Enables/starts the docker service and opens firewalld for HTTP(S).
#   4.  Handles SELinux (sets the right booleans + uses :Z on bind mounts).
#   5.  Generates all Docker files in the current directory:
#         Dockerfile, docker-compose.yml, .dockerignore,
#         docker/nginx/default.conf, docker/php/php.ini, docker/entrypoint.sh
#   6.  Creates a production .env with strong, randomly generated passwords
#       (backed up to /root/infra-ninja-credentials.txt, mode 600).
#   7.  Builds the images and brings the stack up.
#   8.  Generates Laravel APP_KEY and runs migrations.
#   9.  Prints status + the URL.
#
# Re-runnable: yes. Existing files are backed up with .bak.<timestamp>.
###############################################################################

set -euo pipefail

#=============================================================================
# Colours / helpers
#=============================================================================
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'
BOLD='\033[1m'; NC='\033[0m'

log()   { printf "${BLUE}[*]${NC} %s\n" "$*"; }
ok()    { printf "${GREEN}[OK]${NC} %s\n" "$*"; }
warn()  { printf "${YELLOW}[!]${NC} %s\n" "$*"; }
err()   { printf "${RED}[X]${NC} %s\n" "$*" >&2; }
die()   { err "$*"; exit 1; }

step()  { printf "\n${BOLD}${BLUE}==> %s${NC}\n" "$*"; }

backup_if_exists() {
    local f="$1"
    if [[ -e "$f" ]]; then
        local ts; ts="$(date +%Y%m%d-%H%M%S)"
        cp -a "$f" "${f}.bak.${ts}"
        warn "Existing ${f} backed up to ${f}.bak.${ts}"
    fi
}

rand_pw() {
    # 128 bits of entropy as a hex string — URL- and shell-safe.
    # Why not `tr </dev/urandom | head -c N`: under `set -euo pipefail`,
    # /dev/urandom is unbounded, so when `head` closes the pipe after N bytes
    # `tr` dies of SIGPIPE (exit 141), pipefail propagates it, and set -e
    # aborts the script silently right after the URL prompt.
    openssl rand -hex 16
}

#=============================================================================
# Pre-flight
#=============================================================================
step "Pre-flight checks"

[[ $EUID -eq 0 ]] || die "Please run as root (sudo bash $0)"

if [[ -r /etc/os-release ]]; then
    . /etc/os-release
    log "Detected: ${PRETTY_NAME:-unknown}"
    if [[ "${ID:-}" != "rocky" ]]; then
        warn "OS is not Rocky Linux (ID=${ID:-?}). Continuing anyway in 5s..."
        sleep 5
    elif [[ "${VERSION_ID%%.*}" != "9" ]]; then
        warn "Rocky version is ${VERSION_ID}, not 9. Continuing in 5s..."
        sleep 5
    fi
else
    die "/etc/os-release not found; cannot identify OS"
fi

PROJECT_DIR="$(pwd)"
log "Project directory: ${PROJECT_DIR}"

[[ -f "${PROJECT_DIR}/artisan" && -f "${PROJECT_DIR}/composer.json" ]] || \
    die "This does not look like the Laravel project root (no artisan/composer.json). cd into the project first."

#=============================================================================
# Collect deployment settings
#=============================================================================
step "Deployment settings"

# Allow non-interactive override via env vars.
DOMAIN="${DOMAIN:-}"
HTTP_PORT="${HTTP_PORT:-80}"
DB_NAME="${DB_NAME:-rrs_system}"
DB_USER="${DB_USER:-infraninja}"

if [[ -z "$DOMAIN" ]]; then
    read -rp "Public URL (e.g. https://itams.example.com) [http://$(hostname -I | awk '{print $1}'):${HTTP_PORT}]: " DOMAIN
    DOMAIN="${DOMAIN:-http://$(hostname -I | awk '{print $1}'):${HTTP_PORT}}"
fi

DB_PASSWORD="$(rand_pw)"
DB_ROOT_PASSWORD="$(rand_pw)"

log "APP_URL       : ${DOMAIN}"
log "Database name : ${DB_NAME}"
log "Database user : ${DB_USER}"
log "Database pass : (auto-generated, saved to /root/infra-ninja-credentials.txt)"

#=============================================================================
# Install Docker CE + Compose plugin
#=============================================================================
step "Installing Docker Engine + Compose plugin"

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    ok "Docker and Compose plugin already installed: $(docker --version)"
else
    log "Installing prerequisites"
    dnf install -y dnf-plugins-core curl

    log "Adding Docker CE repository"
    if [[ ! -f /etc/yum.repos.d/docker-ce.repo ]]; then
        dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    fi

    log "Installing docker-ce + docker-compose-plugin"
    dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    ok "Docker installed: $(docker --version)"
fi

log "Enabling and starting docker.service"
systemctl enable --now docker
ok "docker.service: $(systemctl is-active docker)"

#=============================================================================
# Firewalld
#=============================================================================
step "Configuring firewalld"

if systemctl is-active --quiet firewalld; then
    log "Opening port ${HTTP_PORT}/tcp (http)"
    firewall-cmd --permanent --add-port=${HTTP_PORT}/tcp >/dev/null
    if [[ "$DOMAIN" == https://* ]]; then
        log "Opening port 443/tcp (https)"
        firewall-cmd --permanent --add-service=https >/dev/null
    fi
    firewall-cmd --reload >/dev/null
    ok "firewalld reloaded"
else
    warn "firewalld is not running; skipping firewall rules"
fi

#=============================================================================
# SELinux
#=============================================================================
step "Configuring SELinux for container access"

if command -v getenforce >/dev/null 2>&1; then
    SE_MODE="$(getenforce)"
    log "SELinux mode: ${SE_MODE}"
    if [[ "$SE_MODE" == "Enforcing" ]]; then
        log "Allowing containers to manage cgroups + use host network paths"
        setsebool -P container_manage_cgroup on || true
        log "Bind mounts in compose files will use :Z labels to relabel for container access"
    fi
else
    warn "SELinux tools not present; skipping"
fi

#=============================================================================
# Verify committed Docker configuration files exist
#=============================================================================
step "Verifying Docker configuration files (now committed to the repo)"

REQUIRED_FILES=(
    "Dockerfile"
    "docker-compose.yml"
    ".dockerignore"
    "docker/nginx/default.conf"
    "docker/php/php.ini"
    "docker/entrypoint.sh"
)
for f in "${REQUIRED_FILES[@]}"; do
    [[ -f "${PROJECT_DIR}/${f}" ]] || die "Missing ${f} — fetch the repo at a revision that includes the Docker manifest."
done

# Normalize entrypoint to LF + executable in case git config core.autocrlf interfered.
sed -i 's/\r$//' "${PROJECT_DIR}/docker/entrypoint.sh"
chmod +x "${PROJECT_DIR}/docker/entrypoint.sh"
ok "All Docker config files present"

#=============================================================================
# Write .env
#=============================================================================
step "Writing .env (with generated passwords)"

backup_if_exists "${PROJECT_DIR}/.env"
cat > "${PROJECT_DIR}/.env" <<EOF
APP_NAME="ITAMS - IT Assets Management System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Yangon
APP_URL=${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
# Only enable once HTTPS is live in front of the stack.
SESSION_SECURE_COOKIE=$( [[ "${DOMAIN}" == https://* ]] && echo true || echo false )

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MAIL_MAILER=log
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@rrs.local"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"

# Host port that docker-compose.yml publishes for the nginx container.
HTTP_PORT=${HTTP_PORT}
EOF
chmod 600 "${PROJECT_DIR}/.env"
ok "Wrote .env (mode 0600)"

# Save credentials separately, root-only
cat > /root/infra-ninja-credentials.txt <<EOF
Infra-Ninja / ITAMS — generated credentials ($(date))
Host: $(hostname)
APP_URL: ${DOMAIN}

DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

Project directory: ${PROJECT_DIR}
EOF
chmod 600 /root/infra-ninja-credentials.txt
ok "Credentials saved to /root/infra-ninja-credentials.txt (mode 0600)"

#=============================================================================
# Build images
#=============================================================================
step "Building Docker images (first build can take ~5 minutes)"

cd "${PROJECT_DIR}"
docker compose build

#=============================================================================
# Generate APP_KEY using a one-shot container
#=============================================================================
step "Generating Laravel APP_KEY"

APP_KEY="$(docker compose run --rm --no-deps app php artisan key:generate --show)"
[[ -n "$APP_KEY" ]] || die "Failed to generate APP_KEY"
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" "${PROJECT_DIR}/.env"
ok "APP_KEY set"

#=============================================================================
# Bring it up
#=============================================================================
step "Starting the stack (docker compose up -d)"

docker compose up -d

log "Waiting up to 60s for services to be healthy..."
for i in $(seq 1 30); do
    if docker compose ps --services --filter status=running | wc -l | grep -q '4'; then
        ok "All 4 services running"
        break
    fi
    sleep 2
done

#=============================================================================
# Summary
#=============================================================================
step "Deployment summary"

docker compose ps

echo
ok "Application URL : ${DOMAIN}"
ok "Credentials file: /root/infra-ninja-credentials.txt"
ok "Project dir     : ${PROJECT_DIR}"
echo
cat <<HINTS
Useful commands:
  docker compose ps                          # service status
  docker compose logs -f app                 # tail PHP-FPM logs
  docker compose logs -f nginx               # tail nginx logs
  docker compose logs -f queue               # tail queue worker logs
  docker compose exec app php artisan tinker # Laravel REPL
  docker compose exec db mysql -uroot -p\${DB_ROOT_PASSWORD} ${DB_NAME}
  docker compose restart                     # restart all services
  docker compose down                        # stop (KEEP volumes/data)
  docker compose down -v                     # stop + DELETE all data

To update the app after a code change (git pull):
  docker compose build && docker compose up -d

If you change docker/nginx/default.conf:
  docker compose restart nginx
HINTS

echo
ok "Done."
