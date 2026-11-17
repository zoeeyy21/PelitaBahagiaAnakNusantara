#!/bin/bash
# Startup command Azure App Service (Linux, PHP 8.4).
# Dijalankan setiap kali container start. Isi kolom Startup Command dengan:
#   bash /home/site/wwwroot/deploy/azure/startup.sh
set -e

APP_DIR=/home/site/wwwroot
DATA_DIR=/home/data/pelita
cd "$APP_DIR"

# 1. Arahkan nginx ke folder public/ Laravel.
cp "$APP_DIR/deploy/azure/nginx.conf" /etc/nginx/sites-available/default
service nginx reload

# 2. Upload CMS disimpan di /home/data (persisten, tidak terhapus saat deploy ulang).
mkdir -p "$DATA_DIR/public-storage"
rm -rf "$APP_DIR/storage/app/public"
ln -s "$DATA_DIR/public-storage" "$APP_DIR/storage/app/public"
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
php artisan storage:link --force

# 3. Database.
php artisan config:clear
php artisan migrate --force

# 4. Data demo hanya sekali (aktifkan dengan app setting SEED_ON_START=true).
if [ "${SEED_ON_START:-false}" = "true" ] && [ ! -f "$DATA_DIR/.seeded" ]; then
    php artisan db:seed --force
    touch "$DATA_DIR/.seeded"
fi

# 5. Cache untuk performa.
php artisan config:cache
php artisan route:cache
php artisan view:cache
