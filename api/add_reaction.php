<?php
/**
 * API Endpoint: Add Reaction
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
if (!isset($input['post_id']) || !isset($input['reaction_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$post_id = (int)$input['post_id'];
$reaction_type = sanitize($input['reaction_type']);

// Validate reaction type
$reaction_types = get_reaction_types();
if (!array_key_exists($reaction_type, $reaction_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid reaction type']);
    exit;
}

// Check rate limit
if (!check_rate_limit('api_add_reaction_' . $post_id, 20, 60)) { // 20 reactions per minute per post
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please wait.']);
    exit;
}

// Verify post exists
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = :id");
$stmt->execute([':id' => $post_id]);

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
}

// Get user identifier
$user_identifier = get_user_identifier();

// Add reaction
if (add_reaction($post_id, $reaction_type, $user_identifier)) {
    // Get updated reaction counts
    $reaction_counts = get_reaction_counts($post_id);
    
    echo json_encode([
        'success' => true,
        'reaction_counts' => $reaction_counts,
        'message' => 'Reaction added successfully'
    ]);
} else {
    // User already reacted with this type
    echo json_encode([
        'success' => false,
        'error' => 'You have already reacted with this emoji'
    ]);
}
?>