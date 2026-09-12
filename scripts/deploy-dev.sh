#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DEPLOYMENT OTOMATIS STAGING / DEV - BIMBEL NO NAME
# Domain: dev.bimbelnoname.com
# ==============================================================================
set -e

APP_DIR="/var/www/bimbel-dev"

echo "======================================================"
echo "🧪 [$(date '+%Y-%m-%d %H:%M:%S')] Memulai Deployment Staging/Dev..."
echo "📂 Target: $APP_DIR"
echo "======================================================"

cd "$APP_DIR" || { echo "❌ Direktori $APP_DIR tidak ditemukan!"; exit 1; }

# 1. Ambil perubahan terbaru dari Git
echo "📥 Melakukan git pull origin main..."
git fetch origin main
git reset --hard origin/main

# 2. Install/Update Composer dependencies
echo "📦 Menginstall dependency Composer..."
composer install --optimize-autoloader --no-interaction

# 3. Jalankan migrasi database
echo "🗄️  Menjalankan migrasi database staging..."
php artisan migrate --force

# 4. Build frontend Vite
echo "⚡ Mengompilasi asset frontend (Vite)..."
npm install --no-audit --prefer-offline
npm run build

# 5. Clear Cache Laravel
echo "🧹 Membersihkan cache Laravel..."
php artisan optimize:clear

# 6. Pastikan storage link tersedia
echo "🔗 Memverifikasi storage link..."
php artisan storage:link || true

# 7. Set hak akses permission direktori
echo "🔒 Memperbarui permission direktori storage & cache..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "======================================================"
echo "✅ [$(date '+%Y-%m-%d %H:%M:%S')] DEPLOYMENT STAGING/DEV SUKSES!"
echo "🌐 URL: https://dev.bimbelnoname.com"
echo "======================================================"
