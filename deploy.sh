#!/bin/bash
set -e

echo "Starting deployment..."

# Pull latest changes from main branch
git pull origin main

# Install PHP dependencies (production only)
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build frontend assets
npm ci --prefer-offline
npm run build

# Back up the database before running migrations (requires spatie/laravel-backup)
if php artisan list | grep -q "backup:run"; then
    php artisan backup:run --only-db
else
    echo "Warning: backup:run command not available, skipping database backup."
fi

# Run database migrations
php artisan migrate --force

# Clear stale caches then rebuild application caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart Laravel queue workers if Supervisor is managing them
if command -v supervisorctl &> /dev/null; then
    if supervisorctl status laravel-worker:* &> /dev/null; then
        supervisorctl restart laravel-worker:*
        echo "Queue workers restarted."
    else
        echo "No laravel-worker supervisor group found, skipping worker restart."
    fi
fi

echo "Deployment complete!"
