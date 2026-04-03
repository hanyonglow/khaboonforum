#!/bin/bash

# KhaboonForum Setup Verification Script
# Run this script to verify all files are present and properly configured

echo "🔍 KhaboonForum Setup Verification"
echo "=================================="
echo ""

# Check PHP syntax
echo "1. Checking PHP syntax..."
echo "-------------------------"

php_files=$(find . -name "*.php" -type f)
error_count=0

for file in $php_files; do
    if php -l "$file" > /dev/null 2>&1; then
        echo "✅ $file"
    else
        echo "❌ $file"
        error_count=$((error_count + 1))
    fi
done

echo ""
echo "PHP syntax errors: $error_count"
echo ""

# Check required files
echo "2. Checking required files..."
echo "-----------------------------"

required_files=(
    "index.php"
    "create.php"
    "post.php"
    "config/database.php"
    "includes/functions.php"
    "includes/header.php"
    "includes/footer.php"
    "css/style.css"
    "js/main.js"
    ".htaccess"
    "README.md"
)

missing_files=0
for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (MISSING)"
        missing_files=$((missing_files + 1))
    fi
done

echo ""
echo "Missing files: $missing_files"
echo ""

# Check file permissions
echo "3. Checking file permissions..."
echo "-------------------------------"

# Check if uploads directory exists and is writable
if [ -d "uploads" ]; then
    if [ -w "uploads" ]; then
        echo "✅ uploads/ directory is writable"
    else
        echo "⚠️  uploads/ directory is not writable (run: chmod 755 uploads/)"
    fi
else
    echo "ℹ️  uploads/ directory will be created during installation"
fi

# Check .htaccess
if [ -f ".htaccess" ]; then
    if grep -q "Options -Indexes" ".htaccess"; then
        echo "✅ .htaccess has security settings"
    else
        echo "⚠️  .htaccess may be missing security settings"
    fi
fi

echo ""

# Check database configuration
echo "4. Checking database configuration..."
echo "-------------------------------------"

if [ -f "config/database.php" ]; then
    if grep -q "define('DB_HOST'" "config/database.php"; then
        echo "✅ Database configuration template found"
        echo "   Remember to update with your Hostinger credentials!"
    else
        echo "⚠️  Database configuration may be incomplete"
    fi
fi

echo ""

# Check installation script
echo "5. Checking installation script..."
echo "----------------------------------"

if [ -f "install.php" ]; then
    file_size=$(stat -f%z "install.php" 2>/dev/null || stat -c%s "install.php" 2>/dev/null)
    if [ "$file_size" -gt 10000 ]; then
        echo "✅ Installation script is complete ($file_size bytes)"
    else
        echo "⚠️  Installation script may be incomplete"
    fi
else
    echo "ℹ️  Installation script not found (already removed?)"
fi

echo ""

# Summary
echo "📊 VERIFICATION SUMMARY"
echo "======================"
echo ""

if [ "$error_count" -eq 0 ] && [ "$missing_files" -eq 0 ]; then
    echo "🎉 All checks passed! Your KhaboonForum is ready for deployment."
    echo ""
    echo "Next steps:"
    echo "1. Upload all files to your Hostinger public_html directory"
    echo "2. Run the installation script at: http://yourdomain.com/install.php"
    echo "3. Follow the setup wizard"
    echo "4. Delete install.php after successful installation"
else
    echo "⚠️  Some issues were found:"
    echo "   - PHP syntax errors: $error_count"
    echo "   - Missing files: $missing_files"
    echo ""
    echo "Please fix these issues before deployment."
fi

echo ""
echo "📚 Documentation:"
echo "   - README.md for basic information"
echo "   - DEPLOYMENT.md for Hostinger deployment guide"
echo "   - SETUP_GITHUB.md for GitHub repository setup"
echo "   - PROJECT_SUMMARY.md for technical overview"
echo ""

# Check total file count
total_files=$(find . -type f | wc -l)
echo "📁 Total files in project: $total_files"

# Check total size
total_size=$(du -sh . | cut -f1)
echo "💾 Total project size: $total_size"

echo ""
echo "✅ Verification complete!"