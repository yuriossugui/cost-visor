#!/bin/sh

set -e

echo "Waiting for database..."
while [ ! -f /var/www/database/database.sqlite ]; do
    sleep 1
done

echo "Running migrations..."
php artisan migrate --force

echo "Generating JWT secret if not exists..."
php artisan jwt:secret --force 2>/dev/null || true

echo "Clearing and caching config..."
php artisan config:clear
php artisan config:cache
php artisan route:cache

echo "Setting permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 755 /var/www/storage /var/www/bootstrap/cache

echo "Starting PHP-FPM..."
exec php-fpm