<?php
/**
 * Debug the get_all_posts() function specifically
 */

require_once __DIR__ . '/includes/functions.php';

echo "<pre>";
echo "=== Debug get_all_posts() Function ===\n\n";

// Test the function
$posts = get_all_posts(50, 0);

echo "Number of posts returned: " . count($posts) . "\n\n";

if (count($posts) > 0) {
    echo "First post details:\n";
    print_r($posts[0]);
} else {
    echo "No posts returned by get_all_posts()\n\n";
    
    // Let's debug the SQL query directly
    $pdo = get_db_connection();
    if ($pdo) {
        echo "Testing the SQL query directly:\n";
        
        $sql = "
            SELECT p.*, 
                   COUNT(DISTINCT c.id) as comment_count,
                   COUNT(DISTINCT r.id) as reaction_count,
                   GROUP_CONCAT(DISTINCT r.reaction_type) as reactions
            FROM posts p
            LEFT JOIN comments c ON p.id = c.post_id
            LEFT JOIN reactions r ON p.id = r.post_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT 50
        ";
        
        try {
            $stmt = $pdo->query($sql);
            $direct_results = $stmt->fetchAll();
            
            echo "Direct query returned: " . count($direct_results) . " posts\n";
            
            if (count($direct_results) > 0) {
                echo "First result from direct query:\n";
                print_r($direct_results[0]);
            } else {
                echo "⚠️ Direct query also returned no results!\n";
                echo "Possible issues:\n";
                echo "1. GROUP BY might be grouping incorrectly\n";
                echo "2. LEFT JOIN might be causing issues\n";
                echo "3. MySQL mode might be strict\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ SQL Error: " . $e->getMessage() . "\n";
            
            // Try a simpler version
            echo "\nTrying simpler query:\n";
            $simple_sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT 50";
            try {
                $stmt = $pdo->query($simple_sql);
                $simple_results = $stmt->fetchAll();
                echo "Simple query returned: " . count($simple_results) . " posts\n";
            } catch (PDOException $e2) {
                echo "Even simple query failed: " . $e2->getMessage() . "\n";
            }
        }
    }
}

echo "\n=== Checking MySQL Mode ===\n";
$pdo = get_db_connection();
if ($pdo) {
    $mode = $pdo->query("SELECT @@sql_mode")->fetchColumn();
    echo "SQL Mode: " . $mode . "\n";
    
    // Check if ONLY_FULL_GROUP_BY is enabled
    if (strpos($mode, 'ONLY_FULL_GROUP_BY') !== false) {
        echo "⚠️ ONLY_FULL_GROUP_BY is enabled! This can break GROUP BY queries.\n";
    }
}

echo "\n=== End Debug ===\n";
echo "</pre>";
?>