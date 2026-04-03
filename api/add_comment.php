<?php
/**
 * API Endpoint: Add Comment
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

// Validate required fields
if (!isset($input['post_id']) || !isset($input['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$post_id = (int)$input['post_id'];
$name = isset($input['name']) ? sanitize($input['name']) : '';
$content = $input['content'];

// Check rate limit
if (!check_rate_limit('api_add_comment_' . $post_id, 10, 300)) { // 10 comments per 5 minutes per post
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please wait.']);
    exit;
}

// Validate content
$validation = validate_post_content($content);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit;
}

$content = $validation['content'];

// Verify post exists
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = :id");
$stmt->execute([':id' => $post_id]);

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
}

// Add comment
if (add_comment($post_id, $name, $content)) {
    // Save name to cookie
    if ($name) {
        setcookie('khaboon_name', $name, time() + (60*60*24*30), '/');
    }
    
    // Get the new comment
    $comment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = :id");
    $stmt->execute([':id' => $comment_id]);
    $comment = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $comment['id'],
            'user_name' => $comment['user_name'],
            'content' => $comment['content'],
            'created_at' => format_date($comment['created_at']),
            'avatar' => substr($comment['user_name'], 0, 1)
        ],
        'message' => 'Comment added successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
}
?>