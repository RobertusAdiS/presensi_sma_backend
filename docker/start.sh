#!/usr/bin/env bash
set -e

# Ambil port dari variabel Railway, fallback ke 8080
APP_PORT="${PORT:-8080}"

# Ganti port pada file konfig Nginx
sed -i "s/listen 8080;/listen ${APP_PORT};/g" /etc/nginx/sites-available/default

# Jalankan supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
