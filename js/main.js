/**
 * KhaboonForum - Main JavaScript
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initThemeToggle();
    initNameCaching();
    initCharacterCounters();
    initFormValidation();
    initImageUpload();
    initReactions();
    initLoadMore();
    
    console.log('KhaboonForum initialized successfully!');
});

/**
 * Theme Toggle Functionality
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;
    
    themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleTheme();
    });
    
    // Load saved theme
    const savedTheme = localStorage.getItem('khaboon_theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        updateThemeIcon('dark');
    }
}

function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.contains('dark-theme');
    
    if (isDark) {
        body.classList.remove('dark-theme');
        localStorage.setItem('khaboon_theme', 'light');
        updateThemeIcon('light');
    } else {
        body.classList.add('dark-theme');
        localStorage.setItem('khaboon_theme', 'dark');
        updateThemeIcon('dark');
    }
}

function updateThemeIcon(theme) {
    const icon = document.querySelector('#theme-toggle i');
    if (!icon) return;
    
    if (theme === 'dark') {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    }
}

/**
 * Name Caching Functionality
 */
function initNameCaching() {
    const nameInputs = document.querySelectorAll('input[name="name"], input[name="user_name"]');
    const cachedName = getCachedName();
    
    nameInputs.forEach(input => {
        // Fill with cached name if empty
        if (!input.value && cachedName) {
            input.value = cachedName;
        }
        
        // Save name when typing
        input.addEventListener('input', debounce(function() {
            if (this.value.trim()) {
                cacheName(this.value.trim());
            }
        }, 500));
    });
}

function getCachedName() {
    return localStorage.getItem('khaboon_name') || '';
}

function cacheName(name) {
    localStorage.setItem('khaboon_name', name);
    
    // Also set cookie for server-side access
    document.cookie = `khaboon_name=${encodeURIComponent(name)}; path=/; max-age=${60*60*24*30}`; // 30 days
}

/**
 * Character Counters
 */
function initCharacterCounters() {
    const textareas = document.querySelectorAll('textarea[data-maxlength], textarea[maxlength]');
    
    textareas.forEach(textarea => {
        const maxLength = textarea.maxLength || textarea.dataset.maxlength || 5000;
        const counterId = textarea.id + '-counter';
        
        // Create counter if it doesn't exist
        if (!document.getElementById(counterId)) {
            const counter = document.createElement('div');
            counter.id = counterId;
            counter.className = 'char-counter';
            counter.innerHTML = `<span>0</span> / ${maxLength} characters`;
            
            textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        }
        
        // Update counter on input
        textarea.addEventListener('input', function() {
            const counter = document.getElementById(counterId);
            const countSpan = counter.querySelector('span');
            const length = this.value.length;
            
            countSpan.textContent = length;
            
            // Update color based on length
            if (length > maxLength * 0.9) {
                countSpan.style.color = '#ef4444'; // Red
            } else if (length > maxLength * 0.8) {
                countSpan.style.color = '#f59e0b'; // Orange
            } else {
                countSpan.style.color = '';
            }
        });
        
        // Trigger initial update
        textarea.dispatchEvent(new Event('input'));
    });
}

/**
 * Form Validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const errors = [];
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            errors.push(`${field.name || field.id} is required`);
            
            // Focus first error
            if (errors.length === 1) {
                field.focus();
            }
        } else {
            field.classList.remove('error');
        }
    });
    
    // Validate file uploads
    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        if (input.files.length > 0) {
            const file = input.files[0];
            const maxSize = input.dataset.maxSize || 5 * 1024 * 1024; // 5MB default
            
            if (file.size > maxSize) {
                isValid = false;
                input.classList.add('error');
                errors.push(`File "${file.name}" is too large. Maximum size: ${formatBytes(maxSize)}`);
            }
            
            // Validate file type
            const allowedTypes = input.accept ? input.accept.split(',').map(t => t.trim()) : [];
            if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
                isValid = false;
                input.classList.add('error');
                errors.push(`File "${file.name}" has invalid type. Allowed: ${allowedTypes.join(', ')}`);
            }
        }
    });
    
    // Show errors if any
    if (errors.length > 0) {
        showFormErrors(form, errors);
    }
    
    return isValid;
}

function showFormErrors(form, errors) {
    // Remove existing error messages
    const existingErrors = form.querySelectorAll('.form-error');
    existingErrors.forEach(error => error.remove());
    
    // Create error container
    const errorContainer = document.createElement('div');
    errorContainer.className = 'alert alert-error form-error';
    errorContainer.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <strong>Please fix the following errors:</strong>
            <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                ${errors.map(error => `<li>${error}</li>`).join('')}
            </ul>
        </div>
    `;
    
    // Insert at the beginning of the form
    form.insertBefore(errorContainer, form.firstChild);
    
    // Scroll to errors
    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Image Upload Preview
 */
function initImageUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    fileInputs.forEach(input => {
        const previewId = input.id + '-preview';
        const removeBtnId = input.id + '-remove';
        
        // Create preview container if it doesn't exist
        if (!document.getElementById(previewId)) {
            const previewContainer = document.createElement('div');
            previewContainer.id = previewId;
            previewContainer.className = 'image-preview';
            previewContainer.style.display = 'none';
            previewContainer.innerHTML = `
                <img id="${previewId}-img" src="#" alt="Preview">
                <button type="button" class="btn-remove-image" id="${removeBtnId}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            input.parentNode.insertBefore(previewContainer, input.nextSibling);
        }
        
        // Handle file selection
        input.addEventListener('change', function() {
            const previewContainer = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId + '-img');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.style.display = 'none';
            }
        });
        
        // Handle remove button
        const removeBtn = document.getElementById(removeBtnId);
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                input.value = '';
                const previewContainer = document.getElementById(previewId);
                previewContainer.style.display = 'none';
            });
        }
    });
}

/**
 * Reactions System
 */
function initReactions() {
    const reactionButtons = document.querySelectorAll('.reaction-btn');
    
    reactionButtons.forEach(btn => {
        btn.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const reactionType = this.dataset.reaction;
            const csrfToken = document.getElementById('csrf_token').value;
            
            if (!postId || !reactionType || !csrfToken) {
                console.error('Missing reaction data');
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
                    updateReactionCounts(postId, result.reaction_counts);
                    
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

function updateReactionCounts(postId, reactionCounts) {
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

/**
 * Load More Posts
 */
function initLoadMore() {
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (!loadMoreBtn) return;
    
    let isLoading = false;
    let offset = document.querySelectorAll('.post-card').length;
    
    loadMoreBtn.addEventListener('click', async function() {
        if (isLoading) return;
        
        isLoading = true;
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        this.disabled = true;
        
        try {
            const csrfToken = document.getElementById('csrf_token').value;
            const response = await fetch(`/api/load_posts.php?offset=${offset}`, {
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.posts.length > 0) {
                // Add new posts to the grid
                const postsGrid = document.querySelector('.posts-grid');
                
                result.posts.forEach(post => {
                    const postCard = createPostCard(post);
                    postsGrid.appendChild(postCard);
                });
                
                // Update offset
                offset += result.posts.length;
                
                // Hide button if no more posts
                if (result.posts.length < 20) {
                    this.style.display = 'none';
                }
                
                // Re-initialize reactions for new posts
                initReactions();
            } else {
                this.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading more posts:', error);
            showToast('Failed to load more posts', 'error');
        } finally {
            isLoading = false;
            this.innerHTML = originalText;
            this.disabled = false;
        }
    });
}

function createPostCard(post) {
    const emojiMap = {
        'like': '👍',
        'love': '❤️',
        'haha': '😂',
        'wow': '😮',
        'sad': '😢',
        'angry': '😠'
    };
    
    // Create reaction buttons HTML
    const reactionButtons = Object.entries(emojiMap).map(([type, emoji]) => `
        <button class="reaction-btn" 
                data-reaction="${type}"
                data-post-id="${post.id}"
                title="${type.charAt(0).toUpperCase() + type.slice(1)}">
            ${emoji}
        </button>
    `).join('');
    
    // Create reaction counts HTML
    const reactionCounts = Object.entries(post.reaction_counts || {}).map(([type, count]) => `
        <span class="reaction-count">
            ${emojiMap[type] || '👍'}
            <span class="count">${count}</span>
        </span>
    `).join('');
    
    return document.createRange().createContextualFragment(`
        <article class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <div class="post-author">
                    <div class="avatar">
                        ${post.user_name.charAt(0).toUpperCase()}
                    </div>
                    <div class="author-info">
                        <strong>${escapeHtml(post.user_name)}</strong>
                        <span class="post-time">
                            <i class="far fa-clock"></i> ${post.created_at}
                        </span>
                    </div>
                </div>
                
                <div class="post-stats">
                    <span class="stat">
                        <i class="fas fa-comment"></i> ${post.comment_count}
                    </span>
                    <span class="stat">
                        <i class="fas fa-heart"></i> ${post.reaction_count}
                    </span>
                </div>
            </div>
            
            <div class="post-content">
                <p>${escapeHtml(post.content).replace(/\n/g, '<br>')}</p>
                
                ${post.image_path ? `
                    <div class="post-image">
                        <a href="${escapeHtml(post.image_path)}" target="_blank">
                            <img src="${escapeHtml(post.image_path)}" 
                                 alt="Post image" 
                                 loading="lazy"
                                 onerror="this.style.display='none'">
                        </a>
                    </div>
                ` : ''}
            </div>
            
            <div class="post-reactions">
                <div class="reaction-counts">
                    ${reactionCounts}
                </div>
                
                <div class="reaction-buttons">
                    ${reactionButtons}
                </div>
            </div>
            
            <div class="post-actions">
                <a href="post.php?id=${post.id}" class="btn btn-outline">
                    <i class="fas fa-comment"></i> View & Comment
                </a>
            </div>
        </article>
    `).firstElementChild;
}

/**
 * Utility Functions
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add styles if not already present
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                background: var(--bg-color);
                color: var(--text-color);
                box-shadow: var(--shadow-xl);
                display: flex;
                align-items: center;
                gap: 0.75rem;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
                border-left: 4px solid;
                max-width: 350px;
            }
            
            .toast-success {
                border-left-color: var(--success-color);
            }
            
            .toast-error {
                border-left-color: var(--error-color);
            }
            
            .toast-info {
                border-left-color: var(--primary-color);
            }
            
            .toast i {
                font-size: 1.25rem;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * CSRF Token Management
 */
function getCsrfToken() {
    return document.getElementById('csrf_token')?.value || '';
}

function setCsrfToken(token) {
    const input = document.getElementById('csrf_token');
    if (input) {
        input.value = token;
    }
}

/**
 * API Helper Functions
 */
async function apiRequest(endpoint, data = {}, method = 'POST') {
    const csrfToken = getCsrfToken();
    
    try {
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: method !== 'GET' ? JSON.stringify({
                ...data,
                csrf_token: csrfToken
            }) : undefined
        });
        
        return await response.json();
    } catch (error) {
        console.error(`API request failed (${endpoint}):`, error);
        return {
            success: false,
            error: 'Network error. Please check your connection.'
        };
    }
}

/**
 * Offline Detection
 */
function initOfflineDetection() {
    // Update online/offline status
    function updateOnlineStatus() {
        if (navigator.onLine) {
            document.body.classList.remove('offline');
            showToast('Back online!', 'success');
        } else {
            document.body.classList.add('offline');
            showToast('You are offline. Some features may not work.', 'warning');
        }
    }
    
    // Add offline styles
    const style = document.createElement('style');
    style.textContent = `
        body.offline::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--warning-color);
            z-index: 9999;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        body.offline .requires-online {
            opacity: 0.5;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
    
    // Listen for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    
    // Initial check
    updateOnlineStatus();
}

/**
 * Service Worker Registration (Progressive Web App)
 */
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('ServiceWorker registered:', registration);
                })
                .catch(error => {
                    console.log('ServiceWorker registration failed:', error);
                });
        });
    }
}

/**
 * Initialize all features
 */
function initializeAll() {
    // Register service worker for PWA
    registerServiceWorker();
    
    // Initialize offline detection
    initOfflineDetection();
    
    // Add loading state to buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn[type="submit"], .btn-primary')) {
            const btn = e.target;
            if (!btn.disabled) {
                btn.classList.add('loading');
            }
        }
    });
    
    // Add loading styles
    const loadingStyles = document.createElement('style');
    loadingStyles.textContent = `
        .btn.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-top: -10px;
            margin-left: -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        .btn-outline.loading::after {
            border: 2px solid rgba(79, 70, 229, 0.3);
            border-top-color: var(--primary-color);
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(loadingStyles);
    
    // Mark forms that require online connection
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.action.includes('api/')) {
            form.classList.add('requires-online');
        }
    });
}

// Start initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll);
} else {
    initializeAll();
}

// Export functions for debugging
if (typeof window !== 'undefined') {
    window.KhaboonForum = {
        toggleTheme,
        cacheName,
        getCachedName,
        showToast,
        apiRequest,
        escapeHtml
    };
}