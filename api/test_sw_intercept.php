<?php
/**
 * Test if Service Worker intercepts requests
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Add header to identify source
header("X-Served-By: PHP-Server");
header("X-Timestamp: " . date('Y-m-d H:i:s'));
header("X-Random: " . bin2hex(random_bytes(8)));

echo json_encode([
    'success' => true,
    'message' => 'This request was served directly by PHP',
    'timestamp' => date('Y-m-d H:i:s'),
    'random' => bin2hex(random_bytes(8)),
    'headers_received' => [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'accept' => $_SERVER['HTTP_ACCEPT'] ?? 'Unknown',
        'x_test_time' => $_SERVER['HTTP_X_TEST_TIME'] ?? 'Not set'
    ]
]);
?>