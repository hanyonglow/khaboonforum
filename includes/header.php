<?php
/**
 * Common Header Template
 */
session_start();
require_once __DIR__ . '/functions.php';

// Set default timezone
date_default_timezone_set('Asia/Singapore');

// Get cached username from cookie
$cached_name = isset($_COOKIE['khaboon_name']) ? htmlspecialchars($_COOKIE['khaboon_name']) : '';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>KhaboonForum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <meta name="description" content="A simple anonymous social media forum where you can share pictures and thoughts.">
    <meta name="keywords" content="forum, social media, anonymous, pictures, comments, reactions">
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💬</text></svg>">
</head>
<body>
    <div class="container">
        <header class="main-header">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-comments"></i>
                        <h1>KhaboonForum</h1>
                    </a>
                    <p class="tagline">Share your thoughts, anonymously or not</p>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a></li>
                        <li><a href="create.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> New Post
                        </a></li>
                        <li><a href="#" id="theme-toggle">
                            <i class="fas fa-moon"></i> Theme
                        </a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="content">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- CSRF Token for forms -->
            <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Cached name for JavaScript -->
            <input type="hidden" id="cached_name" value="<?php echo $cached_name; ?>">