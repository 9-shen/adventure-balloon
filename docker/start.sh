#!/bin/sh
set -e

echo "╔══════════════════════════════════════════════╗"
echo "║   🎈 Booklix — Production Container Boot     ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# ── 1. Directory structure ──────────────────────────────────────────────────────
echo "▶ [1/9] Ensuring storage directories exist..."
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
echo "   ✔ Done"
echo ""

# ── 2. Clear stale/baked caches ────────────────────────────────────────────────
echo "▶ [2/9] Clearing stale caches..."
php artisan config:clear --no-interaction || true
php artisan route:clear  --no-interaction || true
php artisan view:clear   --no-interaction || true
php artisan cache:clear  --no-interaction || true
echo "   ✔ Done"
echo ""

# ── 3. Run migrations ──────────────────────────────────────────────────────────
echo "▶ [3/9] Running database migrations..."
php artisan migrate --force --no-interaction
echo "   ✔ Done"
echo ""

# ── 4. Seed database (idempotent — skips if already seeded) ────────────────────
echo "▶ [4/9] Seeding database..."
php artisan db:seed --force --no-interaction || echo "   ⚠ Seeding skipped or failed (non-fatal)"
echo ""

# ── 5. Storage link ────────────────────────────────────────────────────────────
echo "▶ [5/9] Linking public storage..."
php artisan storage:link --force --no-interaction || true
echo "   ✔ Done"
echo ""

# ── 6. Publish Filament assets ─────────────────────────────────────────────────
echo "▶ [6/9] Publishing Filament assets..."
php artisan filament:assets --no-interaction || true
echo "   ✔ Done"
echo ""

# ── 7. Cache config ────────────────────────────────────────────────────────────
echo "▶ [7/9] Caching configuration..."
php artisan config:cache --no-interaction
echo "   ✔ Done"
echo ""

# ── 8. Cache routes ────────────────────────────────────────────────────────────
echo "▶ [8/9] Caching routes..."
php artisan route:cache --no-interaction
echo "   ✔ Done"
echo ""

# ── 9. Cache views ─────────────────────────────────────────────────────────────
echo "▶ [9/9] Caching views..."
php artisan view:cache --no-interaction || true
echo "   ✔ Done"
echo ""

echo "╔══════════════════════════════════════════════╗"
echo "║   ✅ Bootstrap complete — Starting Supervisor ║"
echo "╚══════════════════════════════════════════════╝"

exec /usr/bin/supervisord -n -c /etc/supervisord.conf