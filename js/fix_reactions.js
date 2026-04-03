/**
 * Fixed reactions system with better CSRF token handling
 */

function initReactionsFixed() {
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    reactionButtons.forEach(btn => {
        btn.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const reactionType = this.dataset.reaction;
            
            if (!postId || !reactionType) {
                console.error('Missing reaction data');
                return;
            }
            
            // Get CSRF token - try multiple methods
            let csrfToken = getCsrfToken();
            
            if (!csrfToken) {
                showToast('Security token missing. Please refresh the page.', 'error');
                return;
            }
            
            // Visual feedback
            this.classList.add('reacted');
            
            try {
                const response = await fetch('/api/add_reaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        reaction_type: reactionType,
                        csrf_token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update reaction counts in UI
                    updateReactionCountsFixed(postId, result.reaction_counts);
                    
                    // Show success message
                    showToast('Reaction added!', 'success');
                } else {
                    // Show error message
                    showToast(result.error || 'Failed to add reaction', 'error');
                    this.classList.remove('reacted');
                }
            } catch (error) {
                console.error('Error adding reaction:', error);
                showToast('Network error. Please try again.', 'error');
                this.classList.remove('reacted');
            }
        });
    });
}

function getCsrfToken() {
    // Try multiple ways to get the CSRF token
    const tokenInput = document.getElementById('csrf_token');
    if (tokenInput && tokenInput.value) {
        return tokenInput.value;
    }
    
    // Check if token is in a meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.getAttribute('content')) {
        return metaToken.getAttribute('content');
    }
    
    // Check if token is in a data attribute on body
    const bodyToken = document.body.dataset.csrfToken;
    if (bodyToken) {
        return bodyToken;
    }
    
    console.error('CSRF token not found');
    return null;
}

function updateReactionCountsFixed(postId, reactionCounts) {
    // Find the post element
    const postElement = document.querySelector(`[data-post-id="${postId}"]`);
    if (!postElement) return;
    
    // Update reaction counts display
    const reactionCountsElement = postElement.querySelector('.reaction-counts');
    if (reactionCountsElement) {
        // Clear existing counts
        reactionCountsElement.innerHTML = '';
        
        // Add updated counts
        Object.entries(reactionCounts).forEach(([reaction, count]) => {
            const emojiMap = {
                'like': '👍',
                'love': '❤️',
                'haha': '😂',
                'wow': '😮',
                'sad': '😢',
                'angry': '😠'
            };
            
            const countElement = document.createElement('span');
            countElement.className = 'reaction-count';
            countElement.innerHTML = `${emojiMap[reaction] || '👍'} <span class="count">${count}</span>`;
            reactionCountsElement.appendChild(countElement);
        });
    }
    
    // Update total reaction count
    const totalReactions = Object.values(reactionCounts).reduce((a, b) => a + b, 0);
    const totalElement = postElement.querySelector('.stat:nth-child(2) span');
    if (totalElement) {
        totalElement.textContent = totalReactions;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initReactionsFixed();
});