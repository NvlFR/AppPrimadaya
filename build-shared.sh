#!/bin/bash
# Script untuk membuat file ZIP deployment siap upload ke Shared Hosting

echo "🚀 Menyiapkan build untuk Shared Hosting..."

# 1. Pastikan vendor bersih dari dev dependencies
echo "📦 Menginstall PHP dependencies (Tanpa Dev)..."
composer install --optimize-autoloader --no-dev

# 2. Build Frontend (Vite)
echo "🎨 Mem-build Frontend Assets (Vue/Inertia)..."
npm run build

# 3. Hapus zip lama jika ada
if [ -f primadaya-deploy.zip ]; then
    rm primadaya-deploy.zip
fi

# 4. ZIP folder menjadi primadaya-deploy.zip
echo "🗜️ Membuat archive primadaya-deploy.zip..."
zip -q -r primadaya-deploy.zip . -x "*.git*" "*node_modules*" "*tests*" "*.vscode*" "*.env*" "*primadaya-deploy.zip*" "*.log"

# 5. Restore composer ke versi development (Agar bisa koding lagi)
echo "🔄 Mengembalikan composer dependencies seperti semula..."
composer install

echo "✅ SELESAI! File 'primadaya-deploy.zip' siap diupload ke File Manager."
