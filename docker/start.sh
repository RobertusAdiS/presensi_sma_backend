#!/usr/bin/env bash

set -e

# ============================================================
# Railway PORT
# ============================================================

APP_PORT="${PORT:-8080}"

echo "Starting Laravel application..."
echo "PORT=${APP_PORT}"


# ============================================================
# Configure Nginx dynamically
# ============================================================

sed -i \
    "s/__PORT__/${APP_PORT}/g" \
    /etc/nginx/sites-available/default


# ============================================================
# Laravel permissions
# ============================================================

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache


# ============================================================
# Cache configuration
#
# Migration is NOT executed here.
# Migration runs through Railway preDeployCommand.
# ============================================================

php artisan config:cache

php artisan route:cache

php artisan view:cache


# ============================================================
# Start Supervisor
# ============================================================

exec /usr/bin/supervisord \
    -n \
    -c /etc/supervisor/conf.d/supervisord.conf
