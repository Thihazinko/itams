# syntax=docker/dockerfile:1.6

# ---------- Stage 1: build frontend assets (Vite + Tailwind) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: PHP runtime (php-fpm) ----------
# Pin the Alpine version so the base (and its apk mirror URLs) don't float from
# under us — bump this deliberately rather than letting `-alpine` drift.
FROM php:8.2-fpm-alpine3.20 AS app

# Alpine package mirror. Defaults to the upstream CDN; override at build time if
# that CDN is unreachable from the build host, e.g.:
#   docker compose build --build-arg ALPINE_MIRROR=https://uk.alpinelinux.org/alpine app
ARG ALPINE_MIRROR=https://dl-cdn.alpinelinux.org/alpine

# Point apk at the chosen mirror (no-op when it's the default), then install the
# build deps with a few retries so a transient network/TLS blip doesn't fail the
# whole image build.
RUN set -eux; \
    sed -i "s#https://dl-cdn.alpinelinux.org/alpine#${ALPINE_MIRROR}#g" /etc/apk/repositories; \
    for i in 1 2 3 4 5; do \
        apk add --no-cache \
            bash git curl libpng-dev libxml2-dev oniguruma-dev \
            icu-dev zip libzip-dev mysql-client && break; \
        [ "$i" = 5 ] && echo "apk add failed after $i attempts" && exit 1; \
        echo "apk add failed (attempt $i/5); retrying in 5s..."; sleep 5; \
    done; \
    docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer deps first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Full source + built assets from stage 1
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
