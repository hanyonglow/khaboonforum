<?php
/**
 * Main Page - Fixed version with aggressive cache prevention
 */

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AGGRESSIVE cache prevention - these must come before ANY output
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Accel-Expires: 0");
header("X-Khaboon-Version: " . date('YmdHis'));

// Add a unique ID to prevent any proxy caching
$unique_id = bin2hex(random_bytes(8));
header("X-Khaboon-Unique: " . $unique_id);

$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';

// Get posts
$posts = get_all_posts(50, 0);
$reaction_types = get_reaction_types();
?>

<!-- Add meta tags to prevent caching in browsers -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<!-- Add a timestamp to all URLs to prevent caching -->
<script>
    // Add cache-busting parameter to all internal links
    document.addEventListener('DOMContentLoaded', function() {
        const timestamp = Date.now();
        const links = document.querySelectorAll('a[href*=".php"]:not([href*="?"])');
        
        links.forEach(link => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('_', timestamp);
            link.href = url.toString();
        });
        
        // Also add to form actions
        const forms = document.querySelectorAll('form[action*=".php"]');
        forms.forEach(form => {
            const url = new URL(form.action, window.location.origin);
            url.searchParams.set('_', timestamp);
            form.action = url.toString();
        });
        
        // Unregister any Service Worker that might be causing issues
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                registrations.forEach(registration => {
                    console.log('Unregistering Service Worker:', registration.scope);
                    registration.unregister();
                });
            });
        }
    });
</script>

<div class="page-header">
    <h2><i class="fas fa-stream"></i> Recent Posts</h2>
    <div class="cache-info">
        <small>Page ID: <?php echo $unique_id; ?> | Loaded: <?php echo date('H:i:s'); ?></small>
    </div>
    <a href="create.php?_=<?php echo time(); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Post
    </a>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <i class="fas fa-comment-slash"></i>
        <h3>No posts yet</h3>
        <p>Be the first to share something!</p>
        <p><small>If you expected to see posts, try <button onclick="location.reload(true)" class="btn-link">Shift+Refresh</button> or clear your browser cache.</small></p>
        <a href="create.php?_=<?php echo time(); ?>" class="btn btn-primary">
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
                    <a href="post.php?id=<?php echo $post['id']; ?>&_=<?php echo time(); ?>" class="btn btn-outline">
                        <i class="fas fa-comment"></i> View & Comment
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="cache-control-panel">
    <details>
        <summary>Cache Control Tools</summary>
        <div class="cache-tools">
            <p><small>If posts are not showing, try these tools:</small></p>
            <button onclick="location.reload(true)" class="btn btn-sm btn-outline">
                <i class="fas fa-sync-alt"></i> Force Refresh (Shift+Refresh)
            </button>
            <button onclick="clearAllCaches()" class="btn btn-sm btn-outline">
                <i class="fas fa-trash-alt"></i> Clear Browser Caches
            </button>
            <button onclick="disableServiceWorker()" class="btn btn-sm btn-outline">
                <i class="fas fa-power-off"></i> Disable Service Worker
            </button>
            <a href="test_caching_issue.php?_=<?php echo time(); ?>" class="btn btn-sm btn-outline">
                <i class="fas fa-vial"></i> Run Cache Tests
            </a>
        </div>
    </details>
</div>

<script>
    // Cache control functions
    function clearAllCaches() {
        if (confirm('Clear all browser caches? This will log you out of some sites.')) {
            // Clear localStorage and sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear IndexedDB (simplified)
            if (window.indexedDB) {
                indexedDB.databases().then(dbs => {
                    dbs.forEach(db => {
                        indexedDB.deleteDatabase(db.name);
                    });
                });
            }
            
            // Clear caches
            if ('caches' in window) {
                caches.keys().then(cacheNames => {
                    return Promise.all(cacheNames.map(name => caches.delete(name)));
                }).then(() => {
                    alert('Caches cleared. Page will reload.');
                    location.reload(true);
                });
            } else {
                alert('Local storage cleared. Page will reload.');
                location.reload(true);
            }
        }
    }
    
    function disableServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                if (registrations.length === 0) {
                    alert('No Service Worker found.');
                    return;
                }
                
                registrations.forEach(registration => {
                    registration.unregister().then(success => {
                        if (success) {
                            alert('Service Worker disabled. Page will reload.');
                            location.reload(true);
                        } else {
                            alert('Failed to disable Service Worker.');
                        }
                    });
                });
            });
        } else {
            alert('Service Workers not supported in this browser.');
        }
    }
    
    // Check for Service Worker on load
    document.addEventListener('DOMContentLoaded', function() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                if (registrations.length > 0) {
                    console.warn('Service Worker active - may cause caching issues');
                    const panel = document.querySelector('.cache-control-panel');
                    if (panel) {
                        panel.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Service Worker is active and may cache pages. <button onclick="disableServiceWorker()" class="btn-link">Click here to disable it.</button></div>' + panel.innerHTML;
                    }
                }
            });
        }
        
        // Log for debugging
        console.log('Page loaded with cache-busting:', {
            timestamp: Date.now(),
            posts: document.querySelectorAll('.post-card').length,
            url: window.location.href
        });
    });
    
    // Intercept all link clicks to add cache-busting
    document.addEventListener('click', function(e) {
        let target = e.target;
        while (target && target.tagName !== 'A') {
            target = target.parentElement;
        }
        
        if (target && target.tagName === 'A' && target.href) {
            const url = new URL(target.href);
            
            // Only add cache-busting to our own PHP pages
            if (url.origin === window.location.origin && 
                url.pathname.endsWith('.php') && 
                !url.searchParams.has('_')) {
                url.searchParams.set('_', Date.now());
                target.href = url.toString();
            }
        }
    }, true);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>