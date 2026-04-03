<?php
/**
 * Test the fixed get_all_posts() function
 */

require_once __DIR__ . '/includes/functions.php';

echo "<pre>";
echo "=== Testing Fixed get_all_posts() Function ===\n\n";

// Test the fixed function
$posts = get_all_posts(50, 0);

echo "Number of posts returned: " . count($posts) . "\n\n";

if (count($posts) > 0) {
    echo "✅ SUCCESS! Posts are now being returned.\n\n";
    echo "First post details:\n";
    echo "ID: " . $posts[0]['id'] . "\n";
    echo "User: " . $posts[0]['user_name'] . "\n";
    echo "Content: " . substr($posts[0]['content'], 0, 100) . "...\n";
    echo "Comment count: " . $posts[0]['comment_count'] . "\n";
    echo "Reaction count: " . $posts[0]['reaction_count'] . "\n";
    echo "Reactions: " . implode(', ', $posts[0]['reactions']) . "\n";
    
    echo "\nAll posts:\n";
    foreach ($posts as $post) {
        echo "- ID {$post['id']}: {$post['user_name']} - " . substr($post['content'], 0, 50) . "...\n";
    }
} else {
    echo "❌ Still no posts returned.\n\n";
    
    // Check database directly
    $pdo = get_db_connection();
    if ($pdo) {
        $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "Posts in database: " . $count . "\n";
        
        if ($count > 0) {
            $all_posts = $pdo->query("SELECT * FROM posts")->fetchAll();
            echo "\nPosts in database:\n";
            foreach ($all_posts as $post) {
                echo "- ID {$post['id']}: {$post['user_name']}\n";
            }
        }
    }
}

echo "\n=== Testing get_post_by_id() ===\n";
$single_post = get_post_by_id(1);
if ($single_post) {
    echo "✅ Single post retrieved successfully\n";
    echo "Post ID: " . $single_post['id'] . "\n";
    echo "Title/Content: " . substr($single_post['content'], 0, 50) . "...\n";
} else {
    echo "❌ Could not retrieve single post\n";
}

echo "\n=== End Test ===\n";
echo "</pre>";
?>