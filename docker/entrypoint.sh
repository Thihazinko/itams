#!/bin/sh
set -e

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
done
echo "MySQL is up."

chown -R www-data:www-data storage bootstrap/cache
[ -L public/storage ] || php artisan storage:link || true

# --isolated takes a lock via the (database) cache store so that when the app,
# queue, and scheduler containers boot together only one actually runs the
# migrations; the others skip straight through instead of racing on the DDL.
php artisan migrate --isolated --force

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
