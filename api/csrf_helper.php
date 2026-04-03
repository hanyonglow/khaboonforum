<?php
/**
 * CSRF Helper for API endpoints
 */

function validate_api_csrf_token() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['csrf_token'])) {
        return ['valid' => false, 'error' => 'CSRF token missing'];
    }
    
    // Validate token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
        return ['valid' => false, 'error' => 'Invalid security token. Please refresh the page.'];
    }
    
    return ['valid' => true, 'token' => $input['csrf_token']];
}

function api_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function api_error_response($message, $status_code = 400) {
    api_json_response(['success' => false, 'error' => $message], $status_code);
}

function api_success_response($data = []) {
    api_json_response(array_merge(['success' => true], $data));
}
?>