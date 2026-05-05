#!/bin/bash
# Script untuk membuat file ZIP deployment siap upload ke Shared Hosting

set -euo pipefail

VERSION_COUNTER_FILE=".deploy-version"
DEPLOY_INFO_FILE="public/deploy-info.json"
DEFAULT_DEPLOY_COUNT=5

CURRENT_DEPLOY_COUNT="$DEFAULT_DEPLOY_COUNT"
if [ -f "$VERSION_COUNTER_FILE" ]; then
    CURRENT_DEPLOY_COUNT="$(tr -dc '0-9' < "$VERSION_COUNTER_FILE")"
    if [ -z "$CURRENT_DEPLOY_COUNT" ]; then
        CURRENT_DEPLOY_COUNT="$DEFAULT_DEPLOY_COUNT"
    fi
fi

NEXT_DEPLOY_COUNT=$((CURRENT_DEPLOY_COUNT + 1))
VERSION_TAG="$(printf 'v%03d' "$NEXT_DEPLOY_COUNT")"
VERSIONED_ZIP="primadaya-update-${VERSION_TAG}.zip"
BUILD_TIMESTAMP="$(date '+%Y-%m-%d %H:%M:%S %Z')"
GIT_COMMIT="$(git rev-parse --short HEAD 2>/dev/null || echo 'no-git')"

echo "🚀 Menyiapkan build untuk Shared Hosting..."
echo "🏷️ Versi deploy: ${VERSION_TAG} (update ke-${NEXT_DEPLOY_COUNT})"

cat > "$DEPLOY_INFO_FILE" <<EOF
{
  "app_name": "AppPrimadaya",
  "deploy_count": ${NEXT_DEPLOY_COUNT},
  "version": "${VERSION_TAG}",
  "built_at": "${BUILD_TIMESTAMP}",
  "git_commit": "${GIT_COMMIT}"
}
EOF

echo "$NEXT_DEPLOY_COUNT" > "$VERSION_COUNTER_FILE"

# 1. Pastikan vendor bersih dari dev dependencies
echo "📦 Menginstall PHP dependencies (Tanpa Dev)..."
composer install --optimize-autoloader --no-dev

# 2. Build Frontend (Vite)
echo "🎨 Mem-build Frontend Assets (Vue/Inertia)..."
npm run build

# 3. Hapus zip lama jika ada
rm -f primadaya-update.zip "$VERSIONED_ZIP"

# 4. ZIP folder menjadi archive versi + latest
echo "🗜️ Membuat archive ${VERSIONED_ZIP}..."
zip -q -r "$VERSIONED_ZIP" . -x "*.git*" "*node_modules*" "*tests*" "*.vscode*" "*.env*" "*primadaya-update.zip*" "*primadaya-update-v*.zip*" "*.log" "*storage/*" "*public/storage*"
cp "$VERSIONED_ZIP" primadaya-update.zip

# 5. Restore composer ke versi development (Agar bisa koding lagi)
echo "🔄 Mengembalikan composer dependencies seperti semula..."
composer install

echo "✅ SELESAI!"
echo "📁 File versi: ${VERSIONED_ZIP}"
echo "📁 File latest: primadaya-update.zip"
echo "🧾 Metadata deploy tersimpan di: ${DEPLOY_INFO_FILE}"
