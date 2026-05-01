# ─── Stage 0: PHP Composer Dependencies ─────────────────────────────────────────
# Runs first so vendor/ is available to the Vite build (theme CSS imports vendor/filament)
FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist \
        --ignore-platform-reqs \
        --no-security-blocking

# ─── Stage 1: Node/Vite Asset Build ─────────────────────────────────────────────
FROM node:20-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci --legacy-peer-deps

COPY . .
# Copy vendor from composer stage so Vite can resolve @import paths in theme CSS
# (e.g. @import '../../../../vendor/filament/filament/resources/css/theme.css')
COPY --from=deps /app/vendor ./vendor

RUN npm run build

# ─── Stage 2: PHP Production Image ─────────────────────────────────────────────
# Using Ubuntu 24.04 so PHP 8.3 and all extensions install as pre-built .deb packages
# This COMPLETELY bypasses compilation from C source — cuts build time from ~30min to ~15 seconds.
# cache-bust: 2026-04-30-v3-ubuntu
FROM ubuntu:24.04 AS production

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Force cache invalidation — remove this label after first successful build
LABEL org.opencontainers.image.revision="ubuntu-fix-v4"

# ── System dependencies & PHP 8.3 ───────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    ca-certificates \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-zip \
    php8.3-intl \
    php8.3-curl \
    php8.3-redis \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create directory for PHP-FPM socket
RUN mkdir -p /run/php && chown -R www-data:www-data /run/php

# ── Composer ────────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Application source ──────────────────────────────────────────────────────────
# Using --chown here is MUCH faster than a separate RUN chown -R later.
COPY --chown=www-data:www-data . .
COPY --from=builder --chown=www-data:www-data /app/public/build ./public/build

# ── Install PHP dependencies ────────────────────────────────────────────────────
# Run composer as root; we will fix the vendor folder ownership in the next step.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist

# ── Storage & cache dirs ────────────────────────────────────────────────────────
# Create them and ensure they are owned by www-data immediately.
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data vendor storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Package discover (requires a temporary .env with APP_KEY) ───────────────────
RUN cp .env.example .env && \
    echo "APP_KEY=base64:dGVtcEJ1aWxkS2V5MTIzNDU2Nzg5MDEyMzQ1Njc=" >> .env && \
    php artisan package:discover --ansi && \
    rm .env

# ── Nginx config (Debian uses sites-enabled, not http.d) ───────────────────────
RUN mkdir -p /etc/nginx/sites-enabled
COPY docker/nginx.conf       /etc/nginx/sites-enabled/default
COPY docker/php.ini          /etc/php/8.3/fpm/conf.d/99-custom.ini
COPY docker/php.ini          /etc/php/8.3/cli/conf.d/99-custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

# ── Final cleanup ──────────────────────────────────────────────────────────────
# We removed the RUN chown -R /var/www/html because it was timing out.
# All files are already owned by www-data thanks to COPY --chown and specific RUN chowns above.

EXPOSE 80

CMD ["/start.sh"]
