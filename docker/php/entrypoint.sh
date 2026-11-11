#!/bin/sh
set -eu
cd /var/www/html

# N?u ch?a c? .env, t?o file .env t? .env.example
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ghi ?? APP_KEY t? bi?n m?i tr??ng c?a Render v?o file .env (n?u c? truy?n v?o)
if [ -n "${APP_KEY:-}" ]; then
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
fi

# N?u ch?a c? APP_KEY h?p l? th? t? sinh m?i
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# T?o th? m?c v? file SQLite n?u d?ng SQLite
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache database
touch database/database.sqlite
chmod -R 777 storage bootstrap/cache database

composer install --no-interaction --prefer-dist

php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear || true

exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
