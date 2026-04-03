<?php
/**
 * Simple test to check database connection and basic query
 */

require_once __DIR__ . '/config/database.php';

echo "<pre>";
echo "=== Simple Database Test ===\n\n";

try {
    // Try to connect
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Database connection successful\n\n";
    
    // Simple query without GROUP BY
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll();
    
    echo "Posts found: " . count($posts) . "\n\n";
    
    if (count($posts) > 0) {
        echo "First post details:\n";
        print_r($posts[0]);
        
        echo "\nAll posts:\n";
        foreach ($posts as $post) {
            echo "ID: " . $post['id'] . " | ";
            echo "User: " . $post['user_name'] . " | ";
            echo "Content: " . substr($post['content'], 0, 50) . "... | ";
            echo "Created: " . $post['created_at'] . "\n";
        }
    } else {
        echo "⚠️ No posts found. Check if:\n";
        echo "1. You created a post successfully\n";
        echo "2. The post was inserted into the correct database\n";
        echo "3. You're connecting to the right database\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
    
    echo "Debug info:\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DB_PASSWORD: " . (DB_PASSWORD ? "*** (set)" : "NOT SET") . "\n";
}

echo "\n=== End Test ===\n";
echo "</pre>";
?>