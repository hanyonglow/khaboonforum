<?php
/**
 * Debug script to check posts in database
 */

require_once __DIR__ . '/includes/functions.php';

echo "<pre>";
echo "=== KhaboonForum Debug ===\n\n";

// Check database connection
$pdo = get_db_connection();
if (!$pdo) {
    echo "❌ Database connection failed!\n";
    exit;
}
echo "✅ Database connection successful\n\n";

// Check if posts table exists
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database:\n";
print_r($tables);
echo "\n";

// Check posts count
try {
    $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    echo "Total posts in database: " . $post_count . "\n\n";
    
    if ($post_count > 0) {
        // Get all posts
        $posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo "Posts in database:\n";
        print_r($posts);
        echo "\n";
        
        // Test the get_all_posts function
        echo "Testing get_all_posts() function:\n";
        $function_posts = get_all_posts(50, 0);
        echo "Posts returned by function: " . count($function_posts) . "\n";
        print_r($function_posts);
    } else {
        echo "⚠️ No posts found in database. Make sure you're inserting posts correctly.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Check PHP error reporting
echo "\n=== PHP Configuration ===\n";
echo "Error reporting: " . ini_get('error_reporting') . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "Log errors: " . ini_get('log_errors') . "\n";

// Check file permissions
echo "\n=== File Permissions ===\n";
$files_to_check = [
    'index.php',
    'includes/functions.php',
    'config/database.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "$file: $perms\n";
    } else {
        echo "$file: NOT FOUND\n";
    }
}

echo "\n=== Session Info ===\n";
session_start();
print_r($_SESSION);

echo "\n=== Done ===\n";
echo "</pre>";
?>