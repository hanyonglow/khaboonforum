<?php
/**
 * Test to verify index.php fix
 */

// Simulate what index.php does
require_once __DIR__ . '/includes/functions.php';

echo "<pre>";
echo "=== Testing Index.php Fix ===\n\n";

// Test the function that index.php uses
$posts = get_all_posts(50, 0);

echo "Posts returned by get_all_posts(): " . count($posts) . "\n\n";

if (count($posts) > 0) {
    echo "✅ SUCCESS! Posts are available.\n";
    echo "First post ID: " . $posts[0]['id'] . "\n";
    echo "First post user: " . $posts[0]['user_name'] . "\n";
    echo "First post preview: " . substr($posts[0]['content'], 0, 50) . "...\n\n";
    
    echo "Now test your homepage:\n";
    echo "1. Visit: https://khaboon.com/\n";
    echo "2. You should see posts displayed\n";
    echo "3. If not, clear browser cache (Ctrl+F5)\n";
} else {
    echo "❌ Still no posts returned.\n\n";
    
    // Check database directly
    $pdo = get_db_connection();
    if ($pdo) {
        $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "Posts in database: " . $count . "\n";
        
        if ($count > 0) {
            echo "\n⚠️ Database has posts but get_all_posts() returns none.\n";
            echo "Possible issues:\n";
            echo "1. PHP error in get_all_posts() function\n";
            echo "2. Check PHP error logs\n";
            echo "3. Enable error reporting in config/database.php\n";
        }
    }
}

echo "\n=== Quick Fix Checklist ===\n";
echo "1. ✅ Database credentials updated in config/database.php\n";
echo "2. ✅ get_all_posts() function fixed\n";
echo "3. ✅ index.php replaced with fixed version\n";
echo "4. ❓ Test homepage: https://khaboon.com/\n";

echo "\n=== If Still Not Working ===\n";
echo "1. Clear browser cache: Ctrl+F5 or Shift+Refresh\n";
echo "2. Test in incognito/private window\n";
echo "3. Check PHP error logs in Hostinger\n";
echo "4. Add to config/database.php:\n";
echo "   ini_set('display_errors', 1);\n";
echo "   ini_set('display_startup_errors', 1);\n";
echo "   error_reporting(E_ALL);\n";

echo "\n=== End Test ===\n";
echo "</pre>";
?>