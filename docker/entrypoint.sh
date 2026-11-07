#!/bin/bash
set -e

# Port configuration (Render uses $PORT, fallback to 80)
PORT="${PORT:-80}"
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Ensure storage and bootstrap permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run database migration automatically if DB is configured
if [ -n "$DB_HOST" ]; then
    echo ">> Menjalankan migrasi database otomatis..."
    php artisan migrate --force || true
    echo ">> Menjalankan seeder awal jika tabel masih baru..."
    php artisan db:seed --force || true
fi

# Ensure storage symlink exists
php artisan storage:link || true

# Optimize cache
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo ">> Menjalankan Apache di port $PORT..."
exec apache2-foreground

