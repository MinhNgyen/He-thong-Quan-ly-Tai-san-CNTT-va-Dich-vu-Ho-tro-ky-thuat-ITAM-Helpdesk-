#!/bin/sh
set -eu
cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Các thư mục Laravel cần ghi (zip/Git không giữ thư mục rỗng)
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

composer install --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear || true
exec php artisan serve --host=0.0.0.0 --port=8000
