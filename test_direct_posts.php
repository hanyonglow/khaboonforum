<?php
/**
 * Test direct PHP function call
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/includes/functions.php';

echo "=== Direct PHP Function Test ===\n\n";

// Test database connection
$pdo = get_db_connection();
if (!$pdo) {
    echo "❌ Database connection failed!\n";
    exit;
}
echo "✅ Database connection successful\n\n";

// Test get_all_posts function
echo "Testing get_all_posts() function:\n";
$posts = get_all_posts(10, 0);

echo "Number of posts returned: " . count($posts) . "\n\n";

if (count($posts) > 0) {
    echo "First 3 posts:\n";
    for ($i = 0; $i < min(3, count($posts)); $i++) {
        $post = $posts[$i];
        echo "  Post #" . $post['id'] . ":\n";
        echo "    User: " . $post['user_name'] . "\n";
        echo "    Content: " . substr($post['content'], 0, 50) . "...\n";
        echo "    Comments: " . $post['comment_count'] . "\n";
        echo "    Reactions: " . $post['reaction_count'] . "\n";
        echo "    Created: " . $post['created_at'] . "\n\n";
    }
} else {
    echo "⚠️ No posts returned by get_all_posts() function\n";
}

// Test raw SQL query
echo "\n=== Raw SQL Query Test ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $result = $stmt->fetch();
    echo "Total posts in database (raw query): " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT id, user_name, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
        $posts = $stmt->fetchAll();
        
        echo "\nLatest posts from raw query:\n";
        foreach ($posts as $post) {
            echo "  - #" . $post['id'] . " by " . $post['user_name'] . " at " . $post['created_at'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Raw query failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Random: " . bin2hex(random_bytes(4)) . "\n";
?>