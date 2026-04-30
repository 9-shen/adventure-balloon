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
# Using Debian (not Alpine) so PHP extensions install as pre-built .deb packages
# instead of being compiled from C source — cuts build time from ~30min to ~2min.
# cache-bust: 2026-04-30-v2
FROM php:8.2-fpm AS production

# Force cache invalidation — remove this label after first successful build
LABEL org.opencontainers.image.revision="debian-fix-v2"

# ── System dependencies ─────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Redis PHP extension ─────────────────────────────────────────────────────────
RUN pecl install redis \
    && docker-php-ext-enable redis

# ── Composer ────────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Application source ──────────────────────────────────────────────────────────
COPY . .
COPY --from=builder /app/public/build ./public/build

# ── Install PHP dependencies ────────────────────────────────────────────────────
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist

# ── Storage & cache dirs must exist BEFORE package:discover ─────────────────────
# BladeIconsServiceProvider::boot() needs storage/framework/views for Blade compiler
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# ── Package discover (requires a temporary .env with APP_KEY) ───────────────────
RUN cp .env.example .env && \
    echo "APP_KEY=base64:dGVtcEJ1aWxkS2V5MTIzNDU2Nzg5MDEyMzQ1Njc=" >> .env && \
    php artisan package:discover --ansi && \
    rm .env

# ── Nginx config (Debian uses sites-enabled, not http.d) ───────────────────────
RUN mkdir -p /etc/nginx/sites-enabled
COPY docker/nginx.conf       /etc/nginx/sites-enabled/default
COPY docker/php.ini          /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

# ── Final permissions ──────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]
