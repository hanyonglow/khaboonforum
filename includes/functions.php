<?php
/**
 * Helper Functions for KhaboonForum
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all posts with their reaction counts
 */
function get_all_posts($limit = 50, $offset = 0) {
    $pdo = get_db_connection();
    if (!$pdo) {
        error_log("Database connection failed in get_all_posts");
        return [];
    }
    
    try {
        // First, get all posts
        $stmt = $pdo->prepare("
            SELECT * 
            FROM posts 
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        if (empty($posts)) {
            return [];
        }
        
        // Get comment counts for each post
        $post_ids = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        
        // Get comment counts
        $stmt = $pdo->prepare("
            SELECT post_id, COUNT(*) as count 
            FROM comments 
            WHERE post_id IN ($placeholders)
            GROUP BY post_id
        ");
        $stmt->execute($post_ids);
        $comment_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Get reaction counts
        $stmt = $pdo->prepare("
            SELECT post_id, COUNT(*) as count 
            FROM reactions 
            WHERE post_id IN ($placeholders)
            GROUP BY post_id
        ");
        $stmt->execute($post_ids);
        $reaction_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Get reaction types
        $stmt = $pdo->prepare("
            SELECT post_id, reaction_type, COUNT(*) as count
            FROM reactions 
            WHERE post_id IN ($placeholders)
            GROUP BY post_id, reaction_type
        ");
        $stmt->execute($post_ids);
        $reaction_details = $stmt->fetchAll();
        
        // Organize reaction details by post
        $post_reactions = [];
        foreach ($reaction_details as $detail) {
            $post_id = $detail['post_id'];
            if (!isset($post_reactions[$post_id])) {
                $post_reactions[$post_id] = [];
            }
            $post_reactions[$post_id][$detail['reaction_type']] = $detail['count'];
        }
        
        // Combine all data
        foreach ($posts as &$post) {
            $post_id = $post['id'];
            
            $post['comment_count'] = $comment_counts[$post_id] ?? 0;
            $post['reaction_count'] = $reaction_counts[$post_id] ?? 0;
            $post['reactions'] = array_keys($post_reactions[$post_id] ?? []);
            $post['reaction_counts'] = $post_reactions[$post_id] ?? [];
        }
        
        return $posts;
        
    } catch (PDOException $e) {
        error_log("Error in get_all_posts: " . $e->getMessage());
        
        // Fallback: return posts without counts
        try {
            $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $posts = $stmt->fetchAll();
            
            foreach ($posts as &$post) {
                $post['comment_count'] = 0;
                $post['reaction_count'] = 0;
                $post['reactions'] = [];
                $post['reaction_counts'] = [];
            }
            
            return $posts;
        } catch (PDOException $e2) {
            error_log("Fallback also failed: " . $e2->getMessage());
            return [];
        }
    }
}

/**
 * Get a single post by ID
 */
function get_post_by_id($post_id) {
    $pdo = get_db_connection();
    if (!$pdo) return null;
    
    try {
        // Get the post
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
        $stmt->bindValue(':id', (int)$post_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $post = $stmt->fetch();
        
        if (!$post) {
            return null;
        }
        
        // Get comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = :id");
        $stmt->bindValue(':id', (int)$post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post['comment_count'] = $stmt->fetch()['count'];
        
        // Get reaction count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reactions WHERE post_id = :id");
        $stmt->bindValue(':id', (int)$post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post['reaction_count'] = $stmt->fetch()['count'];
        
        // Get reaction details
        $stmt = $pdo->prepare("SELECT reaction_type, COUNT(*) as count FROM reactions WHERE post_id = :id GROUP BY reaction_type");
        $stmt->bindValue(':id', (int)$post_id, PDO::PARAM_INT);
        $stmt->execute();
        $reaction_details = $stmt->fetchAll();
        
        $post['reactions'] = [];
        $post['reaction_counts'] = [];
        
        foreach ($reaction_details as $detail) {
            $post['reactions'][] = $detail['reaction_type'];
            $post['reaction_counts'][$detail['reaction_type']] = $detail['count'];
        }
        
        return $post;
        
    } catch (PDOException $e) {
        error_log("Error in get_post_by_id: " . $e->getMessage());
        
        // Fallback: just get the post without counts
        try {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
            $stmt->bindValue(':id', (int)$post_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $post = $stmt->fetch();
            
            if ($post) {
                $post['comment_count'] = 0;
                $post['reaction_count'] = 0;
                $post['reactions'] = [];
                $post['reaction_counts'] = [];
            }
            
            return $post;
        } catch (PDOException $e2) {
            error_log("Fallback also failed: " . $e2->getMessage());
            return null;
        }
    }
}

/**
 * Get comments for a post
 */
function get_comments_for_post($post_id) {
    $pdo = get_db_connection();
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("
        SELECT * FROM comments 
        WHERE post_id = :post_id 
        ORDER BY created_at ASC
    ");
    
    $stmt->bindValue(':post_id', (int)$post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Create a new post
 */
function create_post($name, $content, $image_path = null) {
    $pdo = get_db_connection();
    if (!$pdo) return false;
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_name, content, image_path, ip_address, user_agent)
        VALUES (:name, :content, :image_path, :ip, :ua)
    ");
    
    return $stmt->execute([
        ':name' => $name ?: 'Anonymous',
        ':content' => $content,
        ':image_path' => $image_path,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Add a comment to a post
 */
function add_comment($post_id, $name, $content) {
    $pdo = get_db_connection();
    if (!$pdo) return false;
    
    $stmt = $pdo->prepare("
        INSERT INTO comments (post_id, user_name, content, ip_address, user_agent)
        VALUES (:post_id, :name, :content, :ip, :ua)
    ");
    
    return $stmt->execute([
        ':post_id' => $post_id,
        ':name' => $name ?: 'Anonymous',
        ':content' => $content,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Add a reaction to a post
 */
function add_reaction($post_id, $reaction_type, $user_identifier) {
    $pdo = get_db_connection();
    if (!$pdo) return false;
    
    // Check if user already reacted with this type
    $check_stmt = $pdo->prepare("
        SELECT id FROM reactions 
        WHERE post_id = :post_id AND user_identifier = :user_id AND reaction_type = :type
    ");
    
    $check_stmt->execute([
        ':post_id' => $post_id,
        ':user_id' => $user_identifier,
        ':type' => $reaction_type
    ]);
    
    if ($check_stmt->fetch()) {
        // User already reacted with this type
        return false;
    }
    
    // Add new reaction
    $stmt = $pdo->prepare("
        INSERT INTO reactions (post_id, reaction_type, user_identifier, ip_address)
        VALUES (:post_id, :type, :user_id, :ip)
    ");
    
    return $stmt->execute([
        ':post_id' => $post_id,
        ':type' => $reaction_type,
        ':user_id' => $user_identifier,
        ':ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

/**
 * Get reaction counts for a post
 */
function get_reaction_counts($post_id) {
    $pdo = get_db_connection();
    if (!$pdo) return [];
    
    $stmt = $pdo->prepare("
        SELECT reaction_type, COUNT(*) as count 
        FROM reactions 
        WHERE post_id = :post_id 
        GROUP BY reaction_type
    ");
    
    $stmt->bindValue(':post_id', (int)$post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetchAll();
    $counts = [];
    
    foreach ($result as $row) {
        $counts[$row['reaction_type']] = $row['count'];
    }
    
    return $counts;
}

/**
 * Handle file upload
 */
function handle_file_upload($file, $post_id) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Maximum size: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'];
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'post_' . $post_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Create thumbnail for large images
        create_thumbnail($filepath, $upload_dir . '/thumb_' . $filename);
        
        return [
            'success' => true, 
            'path' => 'uploads/' . $filename,
            'thumb_path' => 'uploads/thumb_' . $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * Create thumbnail for uploaded image
 */
function create_thumbnail($source_path, $dest_path, $max_width = 300, $max_height = 300) {
    list($orig_width, $orig_height, $type) = getimagesize($source_path);
    
    if ($orig_width <= $max_width && $orig_height <= $max_height) {
        // Image is already small enough
        copy($source_path, $dest_path);
        return true;
    }
    
    // Calculate new dimensions
    $ratio = $orig_width / $orig_height;
    
    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_width = $max_width;
        $new_height = $max_width / $ratio;
    }
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    // Create thumbnail
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    // Resize image
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    // Save thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $dest_path, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $dest_path, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $dest_path);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $dest_path, 85);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}

/**
 * Format date for display
 */
function format_date($timestamp) {
    $now = time();
    $diff = $now - strtotime($timestamp);
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($timestamp));
    }
}

/**
 * Get available reaction types
 */
function get_reaction_types() {
    return [
        'like' => '👍 Like',
        'love' => '❤️ Love',
        'haha' => '😂 Haha',
        'wow' => '😮 Wow',
        'sad' => '😢 Sad',
        'angry' => '😠 Angry'
    ];
}

/**
 * Get user identifier for reactions (based on IP and User-Agent)
 */
function get_user_identifier() {
    return md5($_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

/**
 * Validate and sanitize post content
 */
function validate_post_content($content) {
    $content = trim($content);
    
    if (empty($content)) {
        return ['valid' => false, 'error' => 'Content cannot be empty'];
    }
    
    if (strlen($content) > 5000) {
        return ['valid' => false, 'error' => 'Content too long (max 5000 characters)'];
    }
    
    // Basic spam check (very simple)
    $spam_words = ['http://', 'https://', 'www.', '.com', '.net', '.org'];
    $spam_count = 0;
    foreach ($spam_words as $word) {
        if (stripos($content, $word) !== false) {
            $spam_count++;
        }
    }
    
    if ($spam_count > 3) {
        return ['valid' => false, 'error' => 'Content appears to be spam'];
    }
    
    return ['valid' => true, 'content' => $content];
}
?>