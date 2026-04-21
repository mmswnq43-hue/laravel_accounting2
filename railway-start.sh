#!/usr/bin/env bash
set -e

echo "=== Railway Laravel Startup ==="

# Create required directories
mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache
chmod -R a+rw storage bootstrap/cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed demo data only if tables are empty
echo "Seeding demo data if empty..."
php artisan app:seed-demo-if-empty || echo "Seeding skipped or already seeded."

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Final optimize
php artisan optimize:clear
php artisan optimize

echo "=== Starting FrankenPHP Server ==="
exec docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1
