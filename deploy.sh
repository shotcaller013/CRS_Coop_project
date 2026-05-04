#!/bin/bash

set -e

PROJECT_DIR="/var/www/CRS_Coop_project"
BRANCH="main"

echo "🚀 Starting deployment..."

cd "$PROJECT_DIR"

echo "📥 Pulling latest code..."
git pull origin "$BRANCH"

echo "📦 Installing backend dependencies..."
cd "$PROJECT_DIR/backend"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

echo "⚙️ Laravel setup..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🎨 Building frontend..."
cd "$PROJECT_DIR/frontend"
npm install
npm run build

echo "🔐 Fixing permissions..."
cd "$PROJECT_DIR"
chown -R www-data:www-data backend/storage backend/bootstrap/cache
chmod -R 775 backend/storage backend/bootstrap/cache

echo "🔁 Restarting services..."
systemctl restart php8.3-fpm
systemctl reload nginx

echo "✅ Deployment completed successfully!"