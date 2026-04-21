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
FROM php:8.2-fpm-alpine AS production

# ── System dependencies ─────────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    zlib \
    zlib-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# ── Redis PHP extension ─────────────────────────────────────────────────────────
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

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

# ── Docker config files ─────────────────────────────────────────────────────────
COPY docker/nginx.conf       /etc/nginx/http.d/default.conf
COPY docker/php.ini          /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

# ── Final permissions ──────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]
