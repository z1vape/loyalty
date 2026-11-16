#!/bin/sh
set -e

# Make sure SQLite database exists in the persistent directory
if [ ! -f /app/database_persistent/database.sqlite ]; then
    echo "Creating SQLite database..."
    touch /app/database_persistent/database.sqlite
fi

# Ensure public storage symlink exists (selfie photos, uploads)
mkdir -p /app/storage/app/public
if [ -e /app/public/storage ] && [ ! -L /app/public/storage ]; then
    rm -rf /app/public/storage
fi
php artisan storage:link --force || true

# Cache config, routes, and views for production performance
echo "Caching Laravel configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Sync historical accounting journals
echo "Syncing historical accounting journals..."
php artisan accounting:sync-history

# Start FrankenPHP php-server
echo "Starting FrankenPHP..."
exec frankenphp php-server --listen :8080 --root public/
