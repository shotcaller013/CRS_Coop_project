#!/usr/bin/env bash
# deploy.sh — provision & deploy CRS on a bare Ubuntu 22/24 droplet (no Docker)
# Run as root or a sudo user:  bash deploy.sh

set -euo pipefail

###############################################################################
# CONFIG — edit these before running
###############################################################################
DB_NAME="crs_coop"
DB_USER="crs_user"
DB_PASS="change_me_strong_password"
APP_DIR="/var/www/crs-laravel"
BACKEND_DIR="$APP_DIR/backend"
FRONTEND_DIR="$APP_DIR/frontend"
DOMAIN=""          # leave empty to use server IP, or set e.g. "api.example.com"
###############################################################################

info()  { echo -e "\n\033[1;34m▶ $*\033[0m"; }
ok()    { echo -e "\033[1;32m✓ $*\033[0m"; }
die()   { echo -e "\033[1;31m✗ $*\033[0m" >&2; exit 1; }

# ── System packages ──────────────────────────────────────────────────────────
info "Installing system packages"
apt-get update -qq
apt-get install -y -qq \
    curl git unzip software-properties-common \
    mysql-server \
    apache2 libapache2-mod-php \
    php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml \
    php8.3-mbstring php8.3-zip php8.3-curl php8.3-gd php8.3-bcmath \
    nodejs npm
ok "System packages installed"

# ── Composer ─────────────────────────────────────────────────────────────────
if ! command -v composer &>/dev/null; then
    info "Installing Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    ok "Composer installed"
fi

# ── MySQL ─────────────────────────────────────────────────────────────────────
info "Configuring MySQL"
systemctl enable --now mysql
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
ok "MySQL database & user ready"

# ── Code ─────────────────────────────────────────────────────────────────────
info "Copying application files to $APP_DIR"
mkdir -p "$APP_DIR"
# If running from the repo root, rsync instead of git clone
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' \
    "$SCRIPT_DIR/" "$APP_DIR/"

# ── Laravel backend ───────────────────────────────────────────────────────────
info "Setting up Laravel backend"
cd "$BACKEND_DIR"

# .env
if [ ! -f .env ]; then
    cp .env.example .env
    SERVER_IP=$(hostname -I | awk '{print $1}')
    DISPLAY_HOST="${DOMAIN:-$SERVER_IP}"
    sed -i "s|APP_URL=.*|APP_URL=http://$DISPLAY_HOST|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|"   .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|"   .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|"   .env
    sed -i "s|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=$DISPLAY_HOST|" .env
    sed -i "s|SESSION_DOMAIN=.*|SESSION_DOMAIN=$DISPLAY_HOST|" .env
fi

# Composer install
composer install --no-dev --optimize-autoloader --no-interaction

# App key
php artisan key:generate --force

# Publish vendor migrations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force

# Migrate & seed
php artisan migrate --force
php artisan db:seed --force

# Caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chown -R www-data:www-data "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache"
chmod -R 775 "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache"

ok "Laravel backend ready"

# ── Vue frontend ──────────────────────────────────────────────────────────────
info "Building Vue frontend"
cd "$FRONTEND_DIR"
npm ci --silent
npm run build
ok "Frontend built → $FRONTEND_DIR/dist"

# ── Apache ────────────────────────────────────────────────────────────────────
info "Configuring Apache"
a2enmod rewrite headers php8.3 proxy proxy_fcgi

SERVER_IP=$(hostname -I | awk '{print $1}')
DISPLAY_HOST="${DOMAIN:-$SERVER_IP}"

cat > /etc/apache2/sites-available/crs.conf <<APACHE
<VirtualHost *:80>
    ServerName $DISPLAY_HOST

    # Vue frontend (SPA — served from dist/)
    DocumentRoot $FRONTEND_DIR/dist
    <Directory $FRONTEND_DIR/dist>
        Options -Indexes
        AllowOverride All
        Require all granted
        # SPA fallback: any non-file request → index.html
        FallbackResource /index.html
    </Directory>

    # Laravel API — proxy /api/* to PHP-FPM serving backend/public/
    Alias /api $BACKEND_DIR/public
    <Directory $BACKEND_DIR/public>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  \${APACHE_LOG_DIR}/crs_error.log
    CustomLog \${APACHE_LOG_DIR}/crs_access.log combined
</VirtualHost>
APACHE

a2dissite 000-default.conf 2>/dev/null || true
a2ensite crs.conf
systemctl restart apache2

ok "Apache configured at http://$DISPLAY_HOST"

# ── Cron for overdue detection ────────────────────────────────────────────────
info "Installing cron job"
CRON_LINE="0 2 * * * www-data php $BACKEND_DIR/artisan coop:detect-overdue >> $BACKEND_DIR/storage/logs/overdue.log 2>&1"
(crontab -l 2>/dev/null | grep -v 'coop:detect-overdue'; echo "$CRON_LINE") | crontab -
ok "Cron job installed (daily at 02:00)"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║              CRS DEPLOYMENT COMPLETE             ║"
echo "╠══════════════════════════════════════════════════╣"
echo "║  Frontend : http://$DISPLAY_HOST"
echo "║  API      : http://$DISPLAY_HOST/api/v1"
echo "║"
echo "║  Login credentials (change after first login!):"
echo "║    admin@crs.com   / crs2026  (super-admin)"
echo "║    officer@crs.com / crs2026  (loan-officer)"
echo "║    staff@crs.com   / crs2026  (staff)"
echo "╚══════════════════════════════════════════════════╝"
