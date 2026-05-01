# ─── Stage 1: Node/Vite Asset Build ────────────────────────────────────────────
FROM node:20-alpine AS builder

WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps

COPY . .
RUN npm run build

# ─── Stage 2: PHP Production Image ─────────────────────────────────────────────
# serversideup/php:8.2-fpm-nginx has ALL extensions pre-compiled + nginx built in.
# Zero C compilation = build completes in ~2 min instead of 30 min.
FROM serversideup/php:8.2-fpm-nginx AS production

USER root

# ── Composer ────────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Application source ──────────────────────────────────────────────────────────
COPY --chown=www-data:www-data . .
COPY --from=builder --chown=www-data:www-data /app/public/build ./public/build

# ── Install PHP dependencies ────────────────────────────────────────────────────
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist

# ── Storage & cache dirs ────────────────────────────────────────────────────────
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

# ── Config files ────────────────────────────────────────────────────────────────
# serversideup uses Debian-style paths (sites-enabled) + system php-fpm path
COPY docker/nginx.conf       /etc/nginx/sites-enabled/default
COPY docker/php.ini          /etc/php/8.2/fpm/conf.d/99-custom.ini
COPY docker/php.ini          /etc/php/8.2/cli/conf.d/99-custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh         /start.sh
RUN chmod +x /start.sh

# ── Final permissions ────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["/start.sh"]
