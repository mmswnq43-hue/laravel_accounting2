#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Create .env if missing
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

# Set defaults
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"

# Create required directories
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate app key if missing
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Seed demo data only if tables are empty
php artisan app:seed-demo-if-empty || true

# Clear and cache config/routes/views
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP server
exec php -S 0.0.0.0:8080 -t public
