<?php
/**
 * Main Page - Lists all posts
 */
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';

// Get posts
$posts = get_all_posts(50, 0);
$reaction_types = get_reaction_types();
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
    
    <?php if (count($posts) >= 50): ?>
        <div class="load-more">
            <button id="load-more-btn" class="btn btn-outline">
                <i class="fas fa-sync"></i> Load More Posts
            </button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
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
                    const postCard = this.closest('.post-card');
                    const reactionCounts = postCard.querySelector('.reaction-counts');
                    
                    // Find or create reaction count element
                    let reactionElement = reactionCounts.querySelector(`[data-reaction="${reaction}"]`);
                    
                    if (!reactionElement) {
                        reactionElement = document.createElement('span');
                        reactionElement.className = 'reaction-count';
                        reactionElement.dataset.reaction = reaction;
                        reactionElement.innerHTML = `${this.textContent.trim()} <span class="count">0</span>`;
                        reactionCounts.appendChild(reactionElement);
                    }
                    
                    // Update count
                    const countSpan = reactionElement.querySelector('.count');
                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                    
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
    
    // Load more posts
    document.getElementById('load-more-btn')?.addEventListener('click', async function() {
        const currentPosts = document.querySelectorAll('.post-card').length;
        const csrfToken = document.getElementById('csrf_token').value;
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        this.disabled = true;
        
        try {
            const response = await fetch(`api/load_posts.php?offset=${currentPosts}`, {
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.posts.length > 0) {
                // Add new posts to the grid
                const postsGrid = document.querySelector('.posts-grid');
                
                result.posts.forEach(post => {
                    // Create post card HTML (simplified for example)
                    const postCard = document.createElement('article');
                    postCard.className = 'post-card';
                    postCard.innerHTML = `
                        <div class="post-header">
                            <div class="post-author">
                                <div class="avatar">${post.user_name.charAt(0)}</div>
                                <div class="author-info">
                                    <strong>${escapeHtml(post.user_name)}</strong>
                                    <span class="post-time">
                                        <i class="far fa-clock"></i> ${post.created_at}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>${escapeHtml(post.content).replace(/\n/g, '<br>')}</p>
                        </div>
                        <div class="post-actions">
                            <a href="post.php?id=${post.id}" class="btn btn-outline">
                                <i class="fas fa-comment"></i> View & Comment
                            </a>
                        </div>
                    `;
                    
                    postsGrid.appendChild(postCard);
                });
                
                // Hide load more button if no more posts
                if (result.posts.length < 50) {
                    this.style.display = 'none';
                }
            } else {
                this.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to load more posts');
        } finally {
            this.innerHTML = '<i class="fas fa-sync"></i> Load More Posts';
            this.disabled = false;
        }
    });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>