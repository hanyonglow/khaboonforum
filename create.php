<?php
/**
 * Create New Post Page
 */
$page_title = 'Create Post';
require_once __DIR__ . '/includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } elseif (!check_rate_limit('create_post', 3, 300)) { // 3 posts per 5 minutes
        $error_message = 'Please wait before creating another post.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $content = $_POST['content'] ?? '';
        
        // Validate content
        $validation = validate_post_content($content);
        if (!$validation['valid']) {
            $error_message = $validation['error'];
        } else {
            $content = $validation['content'];
            
            // Handle file upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handle_file_upload($_FILES['image'], 0); // 0 for temp ID
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    $error_message = $upload_result['error'];
                }
            }
            
            if (!isset($error_message)) {
                // Create post
                if (create_post($name, $content, $image_path)) {
                    // Get the last inserted ID
                    $pdo = get_db_connection();
                    $post_id = $pdo->lastInsertId();
                    
                    // Update image path with actual post ID if needed
                    if ($image_path && $post_id) {
                        $new_filename = 'uploads/post_' . $post_id . '_' . basename($image_path);
                        rename($image_path, $new_filename);
                        
                        $stmt = $pdo->prepare("UPDATE posts SET image_path = :path WHERE id = :id");
                        $stmt->execute([':path' => $new_filename, ':id' => $post_id]);
                        $image_path = $new_filename;
                    }
                    
                    // Save name to cookie
                    if ($name) {
                        setcookie('khaboon_name', $name, time() + (60*60*24*30), '/'); // 30 days
                    }
                    
                    // Redirect to the new post
                    header("Location: post.php?id=" . $post_id . "&created=1");
                    exit;
                } else {
                    $error_message = 'Failed to create post. Please try again.';
                }
            }
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-pen"></i> Create New Post</h2>
    <a href="index.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
</div>

<div class="create-post-form">
    <form action="create.php" method="POST" enctype="multipart/form-data" id="post-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label for="name">
                <i class="fas fa-user"></i> Your Name (optional)
            </label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   placeholder="Anonymous or enter your name"
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : $cached_name; ?>"
                   maxlength="50">
            <small class="form-help">Leave blank to post as "Anonymous". Name will be saved in your browser for next time.</small>
        </div>
        
        <div class="form-group">
            <label for="content">
                <i class="fas fa-comment"></i> What's on your mind?
            </label>
            <textarea id="content" 
                      name="content" 
                      rows="6" 
                      placeholder="Share your thoughts, ideas, or questions..."
                      maxlength="5000"
                      required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            <div class="char-counter">
                <span id="char-count">0</span> / 5000 characters
            </div>
            <small class="form-help">Be respectful. No hate speech, harassment, or illegal content.</small>
        </div>
        
        <div class="form-group">
            <label for="image">
                <i class="fas fa-image"></i> Add an Image (optional)
            </label>
            <div class="file-upload">
                <input type="file" 
                       id="image" 
                       name="image" 
                       accept="image/*"
                       onchange="previewImage(this)">
                <label for="image" class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Choose an image</span>
                </label>
                <small class="form-help">Max size: 5MB. Allowed: JPG, PNG, GIF, WebP</small>
            </div>
            <div id="image-preview" class="image-preview" style="display: none;">
                <img id="preview-img" src="#" alt="Preview">
                <button type="button" class="btn-remove-image" onclick="removeImage()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane"></i> Post
            </button>
            <button type="reset" class="btn btn-outline">
                <i class="fas fa-redo"></i> Clear
            </button>
        </div>
    </form>
</div>

<script>
    // Character counter
    const contentTextarea = document.getElementById('content');
    const charCount = document.getElementById('char-count');
    
    contentTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        
        if (this.value.length > 4900) {
            charCount.style.color = '#e74c3c';
        } else if (this.value.length > 4500) {
            charCount.style.color = '#f39c12';
        } else {
            charCount.style.color = '';
        }
    });
    
    // Trigger initial count
    contentTextarea.dispatchEvent(new Event('input'));
    
    // Image preview
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function removeImage() {
        const input = document.getElementById('image');
        const preview = document.getElementById('image-preview');
        
        input.value = '';
        preview.style.display = 'none';
    }
    
    // Form validation
    document.getElementById('post-form').addEventListener('submit', function(e) {
        const content = document.getElementById('content').value.trim();
        const imageInput = document.getElementById('image');
        
        if (!content) {
            e.preventDefault();
            alert('Please enter some content for your post.');
            document.getElementById('content').focus();
            return;
        }
        
        if (content.length > 5000) {
            e.preventDefault();
            alert('Content is too long. Maximum 5000 characters.');
            return;
        }
        
        // Validate file size
        if (imageInput.files.length > 0) {
            const file = imageInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File is too large. Maximum size is 5MB.');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                e.preventDefault();
                alert('Invalid file type. Please upload an image (JPG, PNG, GIF, or WebP).');
                return;
            }
        }
        
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>