<?php
/**
 * API Endpoint: Create Post
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Validate CSRF token
if (!isset($input['csrf_token']) || !validate_csrf_token($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Check rate limit
if (!check_rate_limit('api_create_post', 5, 300)) { // 5 posts per 5 minutes
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please wait.']);
    exit;
}

// Validate input
$name = isset($input['name']) ? sanitize($input['name']) : '';
$content = $input['content'] ?? '';

// Validate content
$validation = validate_post_content($content);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit;
}

$content = $validation['content'];

// Handle file upload if present
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_result = handle_file_upload($_FILES['image'], 0);
    if (!$upload_result['success']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $upload_result['error']]);
        exit;
    }
    $image_path = $upload_result['path'];
}

// Create post
if (create_post($name, $content, $image_path)) {
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
        setcookie('khaboon_name', $name, time() + (60*60*24*30), '/');
    }
    
    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'message' => 'Post created successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create post']);
}
?>