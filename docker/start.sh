#!/bin/sh
echo "============================================"
echo "  🎈 Booklix — Starting Production Server"
echo "============================================"
echo ""

echo "▶ Creating storage directories..."
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
echo ""

echo "▶ Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
echo ""

echo "▶ Clearing all stale caches first..."
php artisan config:clear  || echo "⚠ Config clear failed, continuing..."
php artisan route:clear   || echo "⚠ Route clear failed, continuing..."
php artisan view:clear    || echo "⚠ View clear failed, continuing..."
php artisan cache:clear   || echo "⚠ Cache clear failed, continuing..."
echo ""

echo "▶ Running database migrations..."
php artisan migrate --force || echo "⚠ Migration failed, continuing..."
echo ""

echo "▶ Running database seeders..."
php artisan db:seed --force || echo "⚠ Seeding failed, continuing..."
echo ""

echo "▶ Linking storage..."
php artisan storage:link --force || echo "⚠ Storage link failed, continuing..."
echo ""

echo "▶ Publishing Filament assets..."
php artisan filament:assets || echo "⚠ Filament assets failed, continuing..."
echo ""

echo "▶ Caching configuration..."
php artisan config:cache || echo "⚠ Config cache failed, continuing..."
echo ""

echo "▶ Caching routes..."
php artisan route:cache || echo "⚠ Route cache failed, continuing..."
echo ""

echo "============================================"
echo "  ✅ Bootstrap complete — Starting Supervisor"
echo "============================================"
exec /usr/bin/supervisord -n -c /etc/supervisord.conf