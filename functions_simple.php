<?php
/**
 * Simplified functions for testing
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Simple version of get_all_posts
 */
function get_all_posts_simple($limit = 50, $offset = 0) {
    $pdo = get_db_connection();
    if (!$pdo) {
        error_log("Database connection failed in get_all_posts_simple");
        return [];
    }
    
    try {
        // Simple query without complex joins
        $stmt = $pdo->prepare("
            SELECT p.* 
            FROM posts p
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        // Get comment counts separately
        foreach ($posts as &$post) {
            $stmt2 = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
            $stmt2->execute([$post['id']]);
            $post['comment_count'] = $stmt2->fetch()['count'];
            
            $stmt3 = $pdo->prepare("SELECT COUNT(*) as count FROM reactions WHERE post_id = ?");
            $stmt3->execute([$post['id']]);
            $post['reaction_count'] = $stmt3->fetch()['count'];
            
            $post['reactions'] = [];
            $post['reaction_counts'] = [];
        }
        
        return $posts;
        
    } catch (PDOException $e) {
        error_log("Error in get_all_posts_simple: " . $e->getMessage());
        return [];
    }
}

/**
 * Test if database connection works
 */
function test_db_connection() {
    $pdo = get_db_connection();
    if (!$pdo) {
        return "Database connection failed";
    }
    
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        return "Database connected. Posts in database: " . $count;
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}
?>