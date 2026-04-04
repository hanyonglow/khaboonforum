<?php
/**
 * Debug version of index.php with enhanced logging
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent ALL caching aggressively
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Accel-Expires: 0");

// Add debugging headers
header("X-Khaboon-Debug: " . date('Y-m-d H:i:s'));
header("X-Khaboon-Random: " . bin2hex(random_bytes(8)));

// Start session early
session_start();

// Log request
error_log("=== INDEX.PHP REQUEST ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("IP: " . $_SERVER['REMOTE_ADDR']);
error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Session ID: " . session_id());

$page_title = 'Home - Debug';
require_once __DIR__ . '/includes/header.php';

// Get posts with timing
$start_time = microtime(true);
$posts = get_all_posts(50, 0);
$end_time = microtime(true);
$query_time = round(($end_time - $start_time) * 1000, 2);

$reaction_types = get_reaction_types();

// Log results
error_log("Query time: {$query_time}ms");
error_log("Posts returned: " . count($posts));
error_log("Session data: " . json_encode($_SESSION));
?>

<div class="page-header">
    <h2><i class="fas fa-stream"></i> Recent Posts (Debug Version)</h2>
    <div class="debug-info">
        <small>
            Loaded: <?php echo date('Y-m-d H:i:s'); ?> | 
            Query: <?php echo $query_time; ?>ms | 
            Posts: <?php echo count($posts); ?> |
            Session: <?php echo session_id(); ?>
        </small>
    </div>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Post
    </a>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <i class="fas fa-comment-slash"></i>
        <h3>No posts yet</h3>
        <p>Be the first to share something!</p>
        <p><strong>Debug Info:</strong> get_all_posts() returned empty array.</p>
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

<div class="debug-section">
    <h3>Debug Information</h3>
    <pre>
Request Time: <?php echo date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']); ?>

Headers Sent:
<?php 
foreach (headers_list() as $header) {
    echo htmlspecialchars($header) . "\n";
}
?>

Session:
<?php print_r($_SESSION); ?>

PHP Info:
- PHP Version: <?php echo phpversion(); ?>

- Memory Usage: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?>MB
- Peak Memory: <?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?>MB

Database Query:
- Time: <?php echo $query_time; ?>ms
- Posts Found: <?php echo count($posts); ?>

User Agent:
<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?>
    </pre>
    
    <button onclick="location.reload(true)" class="btn btn-outline">
        <i class="fas fa-sync-alt"></i> Force Refresh
    </button>
    
    <button onclick="clearCache()" class="btn btn-outline">
        <i class="fas fa-trash"></i> Clear Browser Cache
    </button>
    
    <a href="test_caching_issue.php" class="btn btn-outline">
        <i class="fas fa-vial"></i> Run Cache Tests
    </a>
</div>

<script>
    // Clear browser cache
    function clearCache() {
        if ('caches' in window) {
            caches.keys().then(cacheNames => {
                return Promise.all(cacheNames.map(name => caches.delete(name)));
            }).then(() => {
                alert('Browser caches cleared. Please refresh.');
                location.reload(true);
            });
        } else {
            alert('Cache API not supported. Please use Shift+Refresh to clear cache.');
            location.reload(true);
        }
    }
    
    // Check if Service Worker is active
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            if (registrations.length > 0) {
                console.warn('Service Worker is active. This may cause caching issues.');
                const debugSection = document.querySelector('.debug-section');
                if (debugSection) {
                    debugSection.innerHTML += '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Service Worker is active and may cache pages.</div>';
                }
            }
        });
    }
    
    // Log JavaScript console
    console.log('KhaboonForum Debug Page Loaded');
    console.log('Timestamp:', new Date().toISOString());
    console.log('Posts in DOM:', document.querySelectorAll('.post-card').length);
    
    // Check for JavaScript errors
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
        const debugSection = document.querySelector('.debug-section');
        if (debugSection) {
            debugSection.innerHTML += '<div class="alert alert-error"><i class="fas fa-bug"></i> JavaScript Error: ' + e.message + '</div>';
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>