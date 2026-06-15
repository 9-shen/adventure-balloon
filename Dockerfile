# ─── Stage 0: Composer deps ────────────────────────────────────────────────────
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist \
        --ignore-platform-reqs

# ─── Stage 1: Node/Vite Asset Build ────────────────────────────────────────────
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps
COPY . .
COPY --from=deps /app/vendor ./vendor
RUN npm run build

# ─── Stage 2: PHP Production Image ─────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS production

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk add --no-cache nginx supervisor curl git unzip \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=builder --chown=www-data:www-data /app/public/build ./public/build
COPY --from=deps --chown=www-data:www-data /app/vendor ./vendor

RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache \
             storage/logs \
             bootstrap/cache

RUN cp .env.example .env && \
    echo "APP_KEY=base64:dGVtcEJ1aWxkS2V5MTIzNDU2Nzg5MDEyMzQ1Njc=" >> .env && \
    php artisan package:discover --ansi && \
    rm .env

COPY docker/nginx.conf       /etc/nginx/http.d/default.conf
COPY docker/php.ini          /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["/start.sh"]
