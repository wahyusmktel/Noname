#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DEPLOYMENT OTOMATIS PRODUCTION - BIMBEL NO NAME
# Domain: bimbelnoname.com
# ==============================================================================
set -e

APP_DIR="/var/www/bimbel-prod"

echo "======================================================"
echo "🚀 [$(date '+%Y-%m-%d %H:%M:%S')] Memulai Deployment Production..."
echo "📂 Target: $APP_DIR"
echo "======================================================"

cd "$APP_DIR" || { echo "❌ Direktori $APP_DIR tidak ditemukan!"; exit 1; }

# 1. Aktifkan Maintenance Mode
echo "⏸️  Mengaktifkan maintenance mode..."
php artisan down --render="errors::503" --secret="deploy-bypass-key" || true

# 2. Ambil perubahan terbaru dari Git
echo "📥 Melakukan git pull origin main..."
git fetch origin main
git reset --hard origin/main

# 3. Install/Update Composer dependencies (Production)
echo "📦 Menginstall dependency Composer (No Dev)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Jalankan migrasi database
echo "🗄️  Menjalankan migrasi database production..."
php artisan migrate --force

# 5. Build frontend Vite (Production)
echo "⚡ Mengompilasi asset frontend (Vite)..."
npm install --no-audit --prefer-offline
npm run build

# 6. Cache & Optimasi Laravel
echo "🧹 Membersihkan & mengoptimasi cache Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Pastikan storage link tersedia
echo "🔗 Memverifikasi storage link..."
php artisan storage:link || true

# 8. Set hak akses permission direktori
echo "🔒 Memperbarui permission direktori storage & cache..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 9. Restart Queue Worker
echo "🔄 Merestart background queue workers..."
php artisan queue:restart || true

# 10. Nonaktifkan Maintenance Mode
echo "▶️  Menonaktifkan maintenance mode..."
php artisan up

echo "======================================================"
echo "✅ [$(date '+%Y-%m-%d %H:%M:%S')] DEPLOYMENT PRODUCTION SUKSES!"
echo "🌐 URL: https://bimbelnoname.com"
echo "======================================================"
