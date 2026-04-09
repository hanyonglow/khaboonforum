<?php
/**
 * Main Page - Lists all posts
 * PWA FIXED VERSION - Proper caching for PWA, network-first for dynamic content
 * 
 * Changes from original:
 * 1. REMOVED Service Worker unregistration
 * 2. ADDED proper PWA registration
 * 3. KEPT cache prevention headers for dynamic content
 * 4. Network-first strategy for PHP pages
 */

// ============================================
// PART 1: CACHE PREVENTION FOR DYNAMIC CONTENT
// ============================================
// These MUST come before ANY output, including whitespace

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set cache prevention headers for dynamic content
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0, no-transform");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Accel-Expires: 0");

// Prevent proxy caching
header("Surrogate-Control: no-store");
header("CDN-Cache-Control: no-cache");

// Add version headers for debugging
$page_version = date('YmdHis') . '_' . bin2hex(random_bytes(4));
header("X-Khaboon-Version: {$page_version}");
header("X-Khaboon-Generated: " . date('Y-m-d H:i:s'));

// ============================================
// PART 2: PWA CONFIGURATION
// ============================================
// We'll add PWA registration instead of unregistration

$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';

// Get posts
$posts = get_all_posts(50, 0);
$reaction_types = get_reaction_types();

// Generate cache-busting parameter for all URLs
$cache_buster = 't=' . time();
?>

<!-- ============================================ -->
<!-- PART 3: HTML META TAGS FOR PWA -->
<!-- ============================================ -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="robots" content="noindex, nofollow">

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- ============================================ -->
<!-- PART 4: JAVASCRIPT FOR PWA REGISTRATION -->
<!-- ============================================ -->
<script>
// Register Service Worker for PWA functionality
(function() {
    if ('serviceWorker' in navigator) {
        // Check if we're in a PWA context
        const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                     window.navigator.standalone ||
                     document.referrer.includes('android-app://');
        
        console.log('PWA Status:', {
            isPWA: isPWA,
            displayMode: window.matchMedia('(display-mode: standalone)').matches,
            standalone: window.navigator.standalone,
            referrer: document.referrer
        });
        
        // Register Service Worker with updated caching strategy
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw-fixed.js')
                .then(function(registration) {
                    console.log('Service Worker registered successfully:', registration.scope);
                    
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('Service Worker update found:', newWorker.state);
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('New Service Worker installed, reload to activate');
                                // You could show a "Update available" notification here
                            }
                        });
                    });
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        });
        
        // Listen for controller changes
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('Service Worker controller changed');
        });
    }
})();

// Add cache-busting parameter to all PHP links (for dynamic content)
document.addEventListener('DOMContentLoaded', function() {
    const timestamp = Date.now();
    
    // Add to all PHP links
    document.querySelectorAll('a[href*=".php"]').forEach(function(link) {
        try {
            const url = new URL(link.href, window.location.origin);
            if (url.origin === window.location.origin && url.pathname.endsWith('.php')) {
                url.searchParams.set('_', timestamp);
                link.href = url.toString();
            }
        } catch (e) {
            // Ignore invalid URLs
        }
    });
    
    // Add to forms
    document.querySelectorAll('form[action*=".php"]').forEach(function(form) {
        try {
            const url = new URL(form.action, window.location.origin);
            if (url.origin === window.location.origin && url.pathname.endsWith('.php')) {
                url.searchParams.set('_', timestamp);
                form.action = url.toString();
            }
        } catch (e) {
            // Ignore invalid URLs
        }
    });
    
    // Log for debugging
    console.log('Page loaded with PWA support:', {
        version: '<?php echo $page_version; ?>',
        timestamp: timestamp,
        postsCount: document.querySelectorAll('.post-card').length,
        url: window.location.href,
        hasServiceWorker: 'serviceWorker' in navigator
    });
});

// Check for posts and show appropriate message
window.addEventListener('load', function() {
    const postCards = document.querySelectorAll('.post-card');
    const emptyState = document.querySelector('.empty-state');
    
    if (postCards.length === 0 && emptyState) {
        // Check if we should have posts by looking for "No posts yet" message
        const emptyText = emptyState.textContent || '';
        if (emptyText.includes('No posts yet')) {
            // This is normal - no posts in database
            console.log('No posts in database - normal empty state');
        } else {
            // Might be a network issue (PWA offline)
            console.log('No posts displayed - could be offline or network issue');
            
            // Check if we're online
            if (!navigator.onLine) {
                const warning = document.createElement('div');
                warning.className = 'alert alert-info';
                warning.innerHTML = `
                    <i class="fas fa-wifi-slash"></i>
                    <div>
                        <strong>You are offline</strong>
                        <p>Posts will load when you reconnect to the internet.</p>
                        <button onclick="location.reload()" class="btn btn-sm">
                            <i class="fas fa-sync-alt"></i> Retry
                        </button>
                    </div>
                `;
                
                // Insert at the top of the page
                const firstElement = document.body.firstChild;
                if (firstElement) {
                    document.body.insertBefore(warning, firstElement);
                }
            }
        }
    }
    
    // Add install prompt for PWA
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Show install button
        const installBtn = document.createElement('button');
        installBtn.className = 'btn btn-success btn-sm';
        installBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
        installBtn.style.position = 'fixed';
        installBtn.style.bottom = '20px';
        installBtn.style.right = '20px';
        installBtn.style.zIndex = '1000';
        
        installBtn.addEventListener('click', () => {
            // Show the install prompt
            deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        });
        
        document.body.appendChild(installBtn);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (installBtn.parentNode) {
                installBtn.style.opacity = '0';
                setTimeout(() => {
                    if (installBtn.parentNode) {
                        installBtn.parentNode.removeChild(installBtn);
                    }
                }, 500);
            }
        }, 10000);
    });
});

// Online/offline detection
window.addEventListener('online', () => {
    console.log('App is online');
    // You could show a "Back online" notification
});

window.addEventListener('offline', () => {
    console.log('App is offline');
    // You could show an "Offline" notification
});
</script>

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
                            <span class="post-time" data-relative-time="<?php echo get_js_timestamp($post['created_at']); ?>">
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
                    <p><?php echo nl2br(make_links_clickable(htmlspecialchars($post['content']))); ?></p>
                    
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