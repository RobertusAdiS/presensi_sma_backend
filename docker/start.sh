#!/usr/bin/env bash
set -e

# Ambil PORT dari Railway, fallback ke 8080 jika lokal
APP_PORT="${PORT:-8080}"

# Substitusi port Nginx secara dinamis
sed -i "s/__PORT__/${APP_PORT}/g" /etc/nginx/sites-available/default

# Cache Laravel
php artisan config:cache
php artisan route:cache

# Jalankan Supervisor
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
