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
FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache \
        bash git curl libpng-dev libxml2-dev oniguruma-dev \
        icu-dev zip libzip-dev mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip

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
