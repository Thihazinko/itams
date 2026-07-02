#!/bin/sh
set -e

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
done
echo "MySQL is up."

chown -R www-data:www-data storage bootstrap/cache
[ -L public/storage ] || php artisan storage:link || true

# Only the primary app container runs migrations. The queue and scheduler
# containers share this image + entrypoint, but docker-compose sets
# RUN_MIGRATIONS=false on them so they don't race each other on the DDL — they
# just wait for the app container to bring the schema up to date. We avoid
# `migrate --isolated` on purpose: it would tie migration success to the cache
# lock backend being reachable at boot, which is an unnecessary failure mode.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
