<?php
/**
 * Debug version of index.php
 */
$page_title = 'Home - Debug';
require_once __DIR__ . '/includes/header.php';

echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>";
echo "<h3>🔍 Debug Information</h3>";

// Get posts with debugging
$posts = get_all_posts(50, 0);
$reaction_types = get_reaction_types();

echo "<p><strong>Number of posts returned:</strong> " . count($posts) . "</p>";

if (count($posts) > 0) {
    echo "<p><strong>First post ID:</strong> " . $posts[0]['id'] . "</p>";
    echo "<p><strong>First post content preview:</strong> " . substr($posts[0]['content'], 0, 50) . "...</p>";
    
    echo "<h4>All posts:</h4>";
    echo "<ul>";
    foreach ($posts as $post) {
        echo "<li>ID {$post['id']}: {$post['user_name']} - " . substr($post['content'], 0, 30) . "...</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>⚠️ No posts returned by get_all_posts()</strong></p>";
    
    // Check database directly
    $pdo = get_db_connection();
    if ($pdo) {
        $count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "<p>Posts in database: " . $count . "</p>";
        
        if ($count > 0) {
            $all_posts = $pdo->query("SELECT * FROM posts")->fetchAll();
            echo "<h4>Posts in database:</h4>";
            echo "<ul>";
            foreach ($all_posts as $post) {
                echo "<li>ID {$post['id']}: {$post['user_name']}</li>";
            }
            echo "</ul>";
        }
    }
}

echo "</div>";

// Now show the normal page
?>

<div class="page-header">
    <h2><i class="fas fa-stream"></i> Recent Posts (Debug Version)</h2>
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