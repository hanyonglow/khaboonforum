<?php
/**
 * API Endpoint: Load More Posts
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate CSRF token from header
$headers = getallheaders();
$csrf_token = $headers['X-CSRF-Token'] ?? '';

if (!$csrf_token || !validate_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Get offset
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 20;

// Get posts
$posts = get_all_posts($limit, $offset);

if (empty($posts)) {
    echo json_encode([
        'success' => true,
        'posts' => [],
        'message' => 'No more posts'
    ]);
    exit;
}

// Format posts for JSON response
$formatted_posts = [];
foreach ($posts as $post) {
    $formatted_posts[] = [
        'id' => $post['id'],
        'user_name' => $post['user_name'],
        'content' => $post['content'],
        'image_path' => $post['image_path'],
        'created_at' => format_date($post['created_at']),
        'comment_count' => $post['comment_count'],
        'reaction_count' => $post['reaction_count'],
        'reaction_counts' => $post['reaction_counts']
    ];
}

echo json_encode([
    'success' => true,
    'posts' => $formatted_posts,
    'count' => count($formatted_posts)
]);
?>