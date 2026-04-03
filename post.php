<?php
/**
 * Single Post View Page
 */
require_once __DIR__ . '/includes/header.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    header("Location: index.php");
    exit;
}

// Get post data
$post = get_post_by_id($post_id);

if (!$post) {
    $page_title = 'Post Not Found';
    ?>
    <div class="empty-state">
        <i class="fas fa-exclamation-circle"></i>
        <h3>Post Not Found</h3>
        <p>The post you're looking for doesn't exist or has been removed.</p>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = 'Post by ' . htmlspecialchars($post['user_name']);
$comments = get_comments_for_post($post_id);
$reaction_types = get_reaction_types();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } elseif (!check_rate_limit('add_comment_' . $post_id, 10, 300)) { // 10 comments per 5 minutes per post
        $error_message = 'Please wait before adding another comment.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $content = $_POST['content'] ?? '';
        
        // Validate content
        $validation = validate_post_content($content);
        if (!$validation['valid']) {
            $error_message = $validation['error'];
        } else {
            $content = $validation['content'];
            
            if (add_comment($post_id, $name, $content)) {
                // Save name to cookie
                if ($name) {
                    setcookie('khaboon_name', $name, time() + (60*60*24*30), '/'); // 30 days
                }
                
                $success_message = 'Comment added successfully!';
                $comments = get_comments_for_post($post_id); // Refresh comments
            } else {
                $error_message = 'Failed to add comment. Please try again.';
            }
        }
    }
}

// Check if post was just created
$just_created = isset($_GET['created']) && $_GET['created'] == 1;
?>

<?php if ($just_created): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Your post has been created successfully!
    </div>
<?php endif; ?>

<div class="post-detail">
    <!-- Post Header -->
    <div class="post-header detail-header">
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Back to All Posts
        </a>
        
        <div class="post-meta">
            <span class="post-id">#<?php echo $post['id']; ?></span>
            <span class="post-time">
                <i class="far fa-clock"></i> <?php echo format_date($post['created_at']); ?>
            </span>
        </div>
    </div>
    
    <!-- Post Content -->
    <article class="post-card detail-card">
        <div class="post-header">
            <div class="post-author">
                <div class="avatar large">
                    <?php echo substr($post['user_name'], 0, 1); ?>
                </div>
                <div class="author-info">
                    <strong><?php echo htmlspecialchars($post['user_name']); ?></strong>
                    <span class="post-time">
                        <i class="far fa-clock"></i> <?php echo format_date($post['created_at']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            
            <?php if ($post['image_path']): ?>
                <div class="post-image detail-image">
                    <a href="<?php echo htmlspecialchars($post['image_path']); ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                             alt="Post image"
                             loading="lazy">
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="post-stats detail-stats">
            <div class="stat">
                <i class="fas fa-comment"></i>
                <span><?php echo $post['comment_count']; ?> comments</span>
            </div>
            <div class="stat">
                <i class="fas fa-heart"></i>
                <span><?php echo $post['reaction_count']; ?> reactions</span>
            </div>
        </div>
        
        <!-- Reactions -->
        <div class="post-reactions detail-reactions">
            <div class="reaction-counts">
                <?php 
                $reaction_counts = get_reaction_counts($post_id);
                foreach ($reaction_counts as $reaction => $count): 
                    $emoji_map = [
                        'like' => '👍',
                        'love' => '❤️',
                        'haha' => '😂',
                        'wow' => '😮',
                        'sad' => '😢',
                        'angry' => '😠'
                    ];
                ?>
                    <span class="reaction-count large" data-reaction="<?php echo $reaction; ?>">
                        <?php echo $emoji_map[$reaction] ?? '👍'; ?>
                        <span class="count"><?php echo $count; ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
            
            <div class="reaction-buttons">
                <?php foreach ($reaction_types as $key => $label): ?>
                    <button class="reaction-btn" 
                            data-reaction="<?php echo $key; ?>"
                            data-post-id="<?php echo $post_id; ?>"
                            title="<?php echo htmlspecialchars($label); ?>">
                        <?php echo explode(' ', $label)[0]; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </article>
    
    <!-- Comments Section -->
    <div class="comments-section">
        <h3>
            <i class="fas fa-comments"></i> 
            Comments (<?php echo count($comments); ?>)
        </h3>
        
        <!-- Add Comment Form -->
        <div class="add-comment">
            <form action="post.php?id=<?php echo $post_id; ?>" method="POST" id="comment-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="add_comment" value="1">
                
                <div class="form-group">
                    <label for="comment-name">
                        <i class="fas fa-user"></i> Your Name (optional)
                    </label>
                    <input type="text" 
                           id="comment-name" 
                           name="name" 
                           placeholder="Anonymous or enter your name"
                           value="<?php echo $cached_name; ?>"
                           maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="comment-content">
                        <i class="fas fa-comment"></i> Add a Comment
                    </label>
                    <textarea id="comment-content" 
                              name="content" 
                              rows="3" 
                              placeholder="Write your comment here..."
                              maxlength="2000"
                              required></textarea>
                    <div class="char-counter">
                        <span id="comment-char-count">0</span> / 2000 characters
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Post Comment
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Comments List -->
        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <div class="empty-comments">
                    <i class="far fa-comment"></i>
                    <p>No comments yet. Be the first to comment!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div class="comment-author">
                                <div class="avatar small">
                                    <?php echo substr($comment['user_name'], 0, 1); ?>
                                </div>
                                <div class="author-info">
                                    <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                                    <span class="comment-time">
                                        <i class="far fa-clock"></i> <?php echo format_date($comment['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comment-content">
                            <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Character counter for comment
    const commentTextarea = document.getElementById('comment-content');
    const commentCharCount = document.getElementById('comment-char-count');
    
    commentTextarea.addEventListener('input', function() {
        commentCharCount.textContent = this.value.length;
        
        if (this.value.length > 1900) {
            commentCharCount.style.color = '#e74c3c';
        } else if (this.value.length > 1500) {
            commentCharCount.style.color = '#f39c12';
        } else {
            commentCharCount.style.color = '';
        }
    });
    
    // Trigger initial count
    commentTextarea.dispatchEvent(new Event('input'));
    
    // Handle reactions
    document.querySelectorAll('.reaction-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const reaction = this.dataset.reaction;
            const csrfToken = document.getElementById('csrf_token').value;
            
            try {
                const response = await fetch('api/add_reaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        reaction_type: reaction,
                        csrf_token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update UI
                    const reactionCounts = document.querySelector('.reaction-counts');
                    
                    // Find or create reaction count element
                    let reactionElement = reactionCounts.querySelector(`[data-reaction="${reaction}"]`);
                    
                    if (!reactionElement) {
                        reactionElement = document.createElement('span');
                        reactionElement.className = 'reaction-count large';
                        reactionElement.dataset.reaction = reaction;
                        
                        const emojiMap = {
                            'like': '👍',
                            'love': '❤️',
                            'haha': '😂',
                            'wow': '😮',
                            'sad': '😢',
                            'angry': '😠'
                        };
                        
                        reactionElement.innerHTML = `${emojiMap[reaction] || '👍'} <span class="count">0</span>`;
                        reactionCounts.appendChild(reactionElement);
                    }
                    
                    // Update count
                    const countSpan = reactionElement.querySelector('.count');
                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                    
                    // Update total reaction count
                    const totalReactions = document.querySelector('.detail-stats .stat:nth-child(2) span');
                    totalReactions.textContent = (parseInt(totalReactions.textContent) + 1) + ' reactions';
                    
                    // Show success feedback
                    this.classList.add('reacted');
                    setTimeout(() => this.classList.remove('reacted'), 1000);
                } else {
                    alert(result.error || 'Failed to add reaction');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            }
        });
    });
    
    // Comment form submission
    document.getElementById('comment-form').addEventListener('submit', function(e) {
        const content = document.getElementById('comment-content').value.trim();
        
        if (!content) {
            e.preventDefault();
            alert('Please enter a comment.');
            document.getElementById('comment-content').focus();
            return;
        }
        
        if (content.length > 2000) {
            e.preventDefault();
            alert('Comment is too long. Maximum 2000 characters.');
            return;
        }
        
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    });
    
    // Auto-focus comment textarea if there's an error
    <?php if (isset($error_message) && strpos($error_message, 'comment') !== false): ?>
        document.getElementById('comment-content').focus();
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>