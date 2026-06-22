#!/bin/bash
set -e

# Buat .env dari .env.example kalau belum ada
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[Entrypoint] .env created from .env.example"
fi

echo "[Entrypoint] Waiting for MySQL..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    echo "[Entrypoint] MySQL not ready, retrying in 3s..."
    sleep 3
done
echo "[Entrypoint] MySQL is ready!"

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force 2>/dev/null || true

echo "[Entrypoint] Laravel ready, starting server..."
exec "$@"
