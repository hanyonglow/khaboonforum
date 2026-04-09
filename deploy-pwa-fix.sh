#!/bin/bash
# KhaboonForum PWA Fix Deployment Script
# This script deploys the PWA fixes to make the forum work as a PWA with visible images

set -e  # Exit on error

echo "========================================="
echo "KhaboonForum PWA Fix Deployment"
echo "========================================="
echo "Date: $(date)"
echo ""

# Check if we're in the khaboonforum directory
if [ ! -f "index.php" ] && [ ! -f "index-pwa.php" ]; then
    echo "❌ ERROR: This script must be run from the khaboonforum directory"
    echo "Current directory: $(pwd)"
    exit 1
fi

echo "📋 Pre-deployment checks..."
echo ""

# Check for required files
REQUIRED_FILES=("sw-fixed.js" "index-pwa.php" "post-pwa.php" "PWA_FIX_IMPLEMENTATION.md")
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ Found: $file"
    else
        echo "❌ Missing: $file"
        exit 1
    fi
done

echo ""
echo "📦 Creating backups of original files..."
echo ""

# Create backup directory
BACKUP_DIR="backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup original files if they exist
BACKUP_FILES=("sw.js" "index.php" "post.php")
for file in "${BACKUP_FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/$file.backup"
        echo "✅ Backed up: $file → $BACKUP_DIR/$file.backup"
    else
        echo "⚠️  No backup needed: $file (not found)"
    fi
done

echo ""
echo "🚀 Deploying PWA fixes..."
echo ""

# Deploy new files
echo "1. Deploying fixed Service Worker..."
cp sw-fixed.js sw.js
echo "   ✅ sw-fixed.js → sw.js"

echo ""
echo "2. Deploying fixed main page..."
cp index-pwa.php index.php
echo "   ✅ index-pwa.php → index.php"

echo ""
echo "3. Deploying fixed post page..."
cp post-pwa.php post.php
echo "   ✅ post-pwa.php → post.php"

echo ""
echo "4. Setting file permissions..."
chmod 644 sw.js index.php post.php
echo "   ✅ Permissions set to 644"

echo ""
echo "✅ Deployment complete!"
echo ""

echo "📋 Post-deployment checklist:"
echo "   1. ✅ Backups created in: $BACKUP_DIR"
echo "   2. ✅ Service Worker updated: sw-fixed.js → sw.js"
echo "   3. ✅ Main page updated: index-pwa.php → index.php"
echo "   4. ✅ Post page updated: post-pwa.php → post.php"
echo ""

echo "🔧 Next steps:"
echo ""
echo "   1. Clear browser cache and Service Worker:"
echo "      - Open DevTools (F12)"
echo "      - Application → Service Workers → Unregister"
echo "      - Application → Clear Storage → Clear site data"
echo ""
echo "   2. Test the forum:"
echo "      - Load index.php (should show posts immediately)"
echo "      - Check browser console for 'Service Worker registered'"
echo "      - Upload an image and verify it displays"
echo "      - Go offline (DevTools → Network → Offline)"
echo "      - Verify cached images still load"
echo ""
echo "   3. Test PWA installation:"
echo "      - Look for install button (bottom-right)"
echo "      - Install as PWA"
echo "      - Test in standalone mode"
echo ""
echo "📚 Documentation:"
echo "   - See PWA_FIX_IMPLEMENTATION.md for detailed information"
echo "   - See PWA_TESTING_CHECKLIST.md for testing procedures"
echo ""
echo "⚠️  Troubleshooting:"
echo "   If posts don't show:"
echo "   1. Check browser console for errors"
echo "   2. Verify Service Worker is registered"
echo "   3. Clear all caches and retry"
echo ""
echo "   If images don't cache offline:"
echo "   1. Check Service Worker fetch events"
echo "   2. Verify images are in /uploads/ directory"
echo "   3. Check CORS headers"
echo ""
echo "========================================="
echo "Deployment completed at: $(date)"
echo "========================================="