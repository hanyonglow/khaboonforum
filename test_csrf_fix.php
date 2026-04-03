<?php
/**
 * Test CSRF token fix
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

echo "<pre>";
echo "=== Testing CSRF Token Fix ===\n\n";

// Check if session has CSRF token
if (isset($_SESSION['csrf_token'])) {
    echo "✅ CSRF token in session: " . substr($_SESSION['csrf_token'], 0, 20) . "...\n";
} else {
    echo "❌ No CSRF token in session\n";
    // Generate one
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    echo "✅ Generated new CSRF token\n";
}

// Check if token is in HTML
echo "\n=== Checking HTML for CSRF token ===\n";
echo "Token should be in: <input type=\"hidden\" id=\"csrf_token\" value=\"TOKEN_HERE\">\n";

// Test JavaScript can access it
echo "\n=== JavaScript Access Test ===\n";
echo "Run this in browser console:\n";
echo "1. document.getElementById('csrf_token')?.value\n";
echo "2. Should return the token\n";

// Test API endpoint
echo "\n=== API Endpoint Test ===\n";
echo "API endpoints that need CSRF:\n";
echo "1. /api/add_reaction.php\n";
echo "2. /api/add_comment.php\n";
echo "3. /api/create_post.php\n";
echo "4. /api/load_posts.php (uses X-CSRF-Token header)\n";

echo "\n=== Quick Fix Summary ===\n";
echo "1. ✅ Added session_start() to API endpoints\n";
echo "2. ✅ Better error messages\n";
echo "3. ✅ Multiple ways to get token in JavaScript\n";
echo "4. ❓ Test reactions on your site\n";

echo "\n=== To Test ===\n";
echo "1. Go to: https://khaboon.com/\n";
echo "2. Click any reaction emoji\n";
echo "3. Should work without 'invalid security token' error\n";
echo "4. If still error, check browser console (F12)\n";

echo "\n=== If Still Not Working ===\n";
echo "1. Clear browser cache: Ctrl+F5\n";
echo "2. Check browser console for errors\n";
echo "3. Enable error reporting in config/database.php:\n";
echo "   ini_set('display_errors', 1);\n";
echo "   ini_set('display_startup_errors', 1);\n";
echo "   error_reporting(E_ALL);\n";

echo "\n=== Current Session Info ===\n";
print_r($_SESSION);

echo "\n=== End Test ===\n";
echo "</pre>";
?>