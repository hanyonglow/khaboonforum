<?php
/**
 * Fixed version of index.php with cache prevention
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';

// Debug: Check if functions.php is loaded
if (!function_exists('get_all_posts')) {
    echo "<div style='background: #ffcccc; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>";
    echo "<h3>❌ ERROR: get_all_posts function not found!</h3>";
    echo "<p>The functions.php file may not be loading correctly.</p>";
    echo "</div>";
}

// Get posts
$posts = get_all_posts(50, 0);
$reaction_types = get_reaction_types();

// Debug output at the top
if (empty($posts)) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ffc107;'>";
    echo "<h3>⚠️ Debug Info: No posts returned</h3>";
    
    // Check database directly
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            echo "<p>Posts in database: <strong>" . $count . "</strong></p>";
            
            if ($count > 0) {
                echo "<p>But get_all_posts() returned 0 posts. Possible issues:</p>";
                echo "<ul>";
                echo "<li>Function error in get_all_posts()</li>";
                echo "<li>Database connection issue</li>";
                echo "<li>PHP error (check error logs)</li>";
                echo "</ul>";
                
                // Show first few posts from database
                $db_posts = $pdo->query("SELECT id, user_name, created_at FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
                echo "<p>First 5 posts from database:</p>";
                echo "<pre>";
                print_r($db_posts);
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<p>Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Database connection failed!</p>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb;'>";
    echo "<p>✅ Debug: get_all_posts() returned " . count($posts) . " posts</p>";
    echo "</div>";
}
?>

<div class="page-header">
    <h2><i class="fas fa-stream"></i> Recent Posts</h2>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Post
    </a>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <i class="fas fa-comment-slash"></i>
        <h3>No posts yet</h3>
        <p>Be the first to share something!</p>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create First Post
        </a>
    </div>
<?php else: ?>
    <div class="posts-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card" data-post-id="<?php echo $post['id']; ?>">
                <div class="post-header">
                    <div class="post-author">
                        <div class="avatar">
                            <?php echo substr($post['user_name'], 0, 1); ?>
                        </div>
                        <div class="author-info">
                            <strong><?php echo htmlspecialchars($post['user_name']); ?></strong>
                            <span class="post-time">
                                <i class="far fa-clock"></i> <?php echo format_date($post['created_at']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="post-stats">
                        <span class="stat">
                            <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                        </span>
                        <span class="stat">
                            <i class="fas fa-heart"></i> <?php echo $post['reaction_count']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="post-content">
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    
                    <?php if ($post['image_path']): ?>
                        <div class="post-image">
                            <a href="<?php echo htmlspecialchars($post['image_path']); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="Post image" 
                                     loading="lazy"
                                     onerror="this.style.display='none'">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="post-reactions">
                    <div class="reaction-counts">
                        <?php foreach ($post['reaction_counts'] as $reaction => $count): ?>
                            <span class="reaction-count">
                                <?php 
                                $emoji_map = [
                                    'like' => '👍',
                                    'love' => '❤️',
                                    'haha' => '😂',
                                    'wow' => '😮',
                                    'sad' => '😢',
                                    'angry' => '😠'
                                ];
                                echo $emoji_map[$reaction] ?? '👍';
                                ?>
                                <span class="count"><?php echo $count; ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="reaction-buttons">
                        <?php foreach ($reaction_types as $key => $label): ?>
                            <button class="reaction-btn" 
                                    data-reaction="<?php echo $key; ?>"
                                    data-post-id="<?php echo $post['id']; ?>"
                                    title="<?php echo htmlspecialchars($label); ?>">
                                <?php echo explode(' ', $label)[0]; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="post-actions">
                    <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-comment"></i> View & Comment
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>