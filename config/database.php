<?php
/**
 * Database Configuration
 * 
 * Update these values with your Hostinger MySQL credentials
 */

// Database connection settings
define('DB_HOST', 'localhost');      // Usually 'localhost' on Hostinger
define('DB_NAME', 'khaboonforum');   // Your database name
define('DB_USER', 'your_username');  // Your MySQL username
define('DB_PASSWORD', 'your_password'); // Your MySQL password

// Application settings
define('SITE_NAME', 'KhaboonForum');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB max file size
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// Create database connection
function get_db_connection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log error but don't expose details to users
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting function
function check_rate_limit($key, $limit = 5, $time_window = 60) {
    session_start();
    $current_time = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'start_time' => $current_time
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    if ($current_time - $data['start_time'] > $time_window) {
        // Reset counter if time window has passed
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'start_time' => $current_time
        ];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false; // Rate limit exceeded
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?>