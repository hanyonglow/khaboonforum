<?php
/**
 * Installation Script for KhaboonForum
 * 
 * Run this script once to set up the database and directory structure.
 * Delete this file after successful installation for security.
 */

// Disable error display in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int)$_POST['step'];
    
    if ($step === 2) {
        // Step 2: Test database connection
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;charset=utf8mb4",
                $db_user,
                $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Check if database exists, create if not
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Store credentials in session for next step
            session_start();
            $_SESSION['install_db'] = [
                'host' => $db_host,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass
            ];
            
            $step = 3;
            
        } catch (PDOException $e) {
            $error = "Database connection failed: " . $e->getMessage();
        }
        
    } elseif ($step === 3) {
        // Step 3: Create tables
        session_start();
        $db_config = $_SESSION['install_db'] ?? null;
        
        if (!$db_config) {
            $error = "Session expired. Please start over.";
            $step = 2;
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
                    $db_config['user'],
                    $db_config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
                
                // Create tables
                $sql = "
                -- Posts table
                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_name VARCHAR(100) NOT NULL DEFAULT 'Anonymous',
                    content TEXT NOT NULL,
                    image_path VARCHAR(255) NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                
                -- Comments table
                CREATE TABLE IF NOT EXISTS comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    post_id INT NOT NULL,
                    user_name VARCHAR(100) NOT NULL DEFAULT 'Anonymous',
                    content TEXT NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    INDEX idx_post_id (post_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                
                -- Reactions table
                CREATE TABLE IF NOT EXISTS reactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    post_id INT NOT NULL,
                    reaction_type ENUM('like', 'love', 'haha', 'wow', 'sad', 'angry') NOT NULL,
                    user_identifier VARCHAR(64) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_reaction (post_id, user_identifier, reaction_type),
                    INDEX idx_post_id (post_id),
                    INDEX idx_reaction_type (reaction_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                
                $pdo->exec($sql);
                
                // Update config file
                $config_content = "<?php
/**
 * Database Configuration
 * 
 * Update these values with your Hostinger MySQL credentials
 */

// Database connection settings
define('DB_HOST', '{$db_config['host']}');
define('DB_NAME', '{$db_config['name']}');
define('DB_USER', '{$db_config['user']}');
define('DB_PASSWORD', '{$db_config['pass']}');

// Application settings
define('SITE_NAME', 'KhaboonForum');
define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['PHP_SELF']));
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB max file size
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);

// Create database connection
function get_db_connection() {
    try {
        \$pdo = new PDO(
            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return \$pdo;
    } catch (PDOException \$e) {
        error_log(\"Database connection failed: \" . \$e->getMessage());
        return null;
    }
}

// Sanitize input
function sanitize(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generate_csrf_token() {
    if (!isset(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

// Validate CSRF token
function validate_csrf_token(\$token) {
    return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$token);
}

// Rate limiting function
function check_rate_limit(\$key, \$limit = 5, \$time_window = 60) {
    session_start();
    \$current_time = time();
    
    if (!isset(\$_SESSION['rate_limit'][\$key])) {
        \$_SESSION['rate_limit'][\$key] = [
            'count' => 1,
            'start_time' => \$current_time
        ];
        return true;
    }
    
    \$data = \$_SESSION['rate_limit'][\$key];
    
    if (\$current_time - \$data['start_time'] > \$time_window) {
        \$_SESSION['rate_limit'][\$key] = [
            'count' => 1,
            'start_time' => \$current_time
        ];
        return true;
    }
    
    if (\$data['count'] >= \$limit) {
        return false;
    }
    
    \$_SESSION['rate_limit'][\$key]['count']++;
    return true;
}
?>";
                
                file_put_contents(__DIR__ . '/config/database.php', $config_content);
                
                // Create uploads directory
                $uploads_dir = __DIR__ . '/uploads';
                if (!file_exists($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                    file_put_contents($uploads_dir . '/index.html', '<!-- Directory listing disabled -->');
                    file_put_contents($uploads_dir . '/.htaccess', "Options -Indexes\nDeny from all");
                }
                
                // Create .htaccess for root
                $htaccess = "Options -Indexes\nErrorDocument 404 /index.php\nErrorDocument 403 /index.php";
                file_put_contents(__DIR__ . '/.htaccess', $htaccess);
                
                $step = 4;
                
            } catch (PDOException $e) {
                $error = "Failed to create tables: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KhaboonForum Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .install-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .install-content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }
        
        .step.active .step-number {
            background: #4f46e5;
            color: white;
        }
        
        .step.completed .step-number {
            background: #10b981;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            text-align: center;
        }
        
        .step.active .step-label {
            color: #4f46e5;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
        }
        
        .form-help {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        
        .alert i {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: #4f46e5;
            border: 2px solid #4f46e5;
        }
        
        .btn-outline:hover {
            background: #f5f3ff;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        
        .requirements {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .requirements h3 {
            margin-bottom: 16px;
            color: #374151;
        }
        
        .requirement-list {
            list-style: none;
        }
        
        .requirement-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #6b7280;
        }
        
        .requirement-list li i {
            color: #10b981;
        }
        
        .requirement-list li.unmet i {
            color: #dc2626;
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-message i {
            font-size: 64px;
            color: #10b981;
            margin-bottom: 20px;
        }
        
        .success-message h2 {
            color: #374151;
            margin-bottom: 16px;
        }
        
        .success-message p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .code-block {
            background: #1f2937;
            color: #e5e7eb;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 20px 0;
            overflow-x: auto;
        }
        
        .warning {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .warning i {
            color: #f59e0b;
            margin-right: 8px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-comments"></i> KhaboonForum</h1>
            <p>Simple Social Media Platform Installation</p>
        </div>
        
        <div class="install-content">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'completed' : ($step == 1 ? 'active' : ''); ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">Requirements</div>
                </div>
                <div class="step <?php echo $step >= 2 ? 'completed' : ($step == 2 ? 'active' : ''); ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step <?php echo $step >= 3 ? 'completed' : ($step == 3 ? 'active' : ''); ?>                    <div class="step-number">3</div>
                    <div class="step-label">Setup</div>
                </div>
                <div class="step <?php echo $step >= 4 ? 'completed' : ($step == 4 ? 'active' : ''); ?>">
                    <div class="step-number">4</div>
                    <div class="step-label">Complete</div>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Requirements Check -->
            <?php if ($step == 1): ?>
                <div class="requirements">
                    <h3><i class="fas fa-clipboard-check"></i> System Requirements</h3>
                    <ul class="requirement-list">
                        <?php
                        $requirements = [
                            'PHP 7.4+' => version_compare(PHP_VERSION, '7.4.0', '>='),
                            'MySQLi/PDO' => extension_loaded('pdo_mysql'),
                            'GD Library' => extension_loaded('gd'),
                            'File Uploads' => ini_get('file_uploads'),
                            'Session Support' => extension_loaded('session'),
                            'JSON Support' => extension_loaded('json'),
                            'MBString' => extension_loaded('mbstring'),
                        ];
                        
                        foreach ($requirements as $req => $met):
                        ?>
                            <li class="<?php echo $met ? '' : 'unmet'; ?>">
                                <i class="fas fa-<?php echo $met ? 'check' : 'times'; ?>"></i>
                                <?php echo htmlspecialchars($req); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php
                $all_met = !in_array(false, $requirements, true);
                if ($all_met):
                ?>
                    <form method="POST" action="install.php">
                        <input type="hidden" name="step" value="2">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right"></i> Continue to Database Setup
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Some requirements are not met.</strong> Please contact your hosting provider to enable the missing PHP extensions.
                    </div>
                    <form method="GET" action="install.php">
                        <input type="hidden" name="step" value="1">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-outline">
                                <i class="fas fa-redo"></i> Re-check Requirements
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            
            <!-- Step 2: Database Configuration -->
            <?php elseif ($step == 2): ?>
                <form method="POST" action="install.php">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label for="db_host">
                            <i class="fas fa-server"></i> Database Host
                        </label>
                        <input type="text" 
                               id="db_host" 
                               name="db_host" 
                               value="localhost" 
                               required>
                        <small class="form-help">Usually "localhost" on Hostinger</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">
                            <i class="fas fa-database"></i> Database Name
                        </label>
                        <input type="text" 
                               id="db_name" 
                               name="db_name" 
                               placeholder="e.g., khaboonforum"
                               required>
                        <small class="form-help">Create this database in your Hostinger control panel first</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">
                            <i class="fas fa-user"></i> Database Username
                        </label>
                        <input type="text" 
                               id="db_user" 
                               name="db_user" 
                               placeholder="Your MySQL username"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">
                            <i class="fas fa-key"></i> Database Password
                        </label>
                        <input type="password" 
                               id="db_pass" 
                               name="db_pass" 
                               placeholder="Your MySQL password"
                               required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plug"></i> Test Connection & Continue
                        </button>
                        <a href="install.php?step=1" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            
            <!-- Step 3: Setup Confirmation -->
            <?php elseif ($step == 3): ?>
                <div class="warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Ready to install!</strong> This will create the database tables and configure your application.
                </div>
                
                <p>Click "Install Now" to:</p>
                <ul style="margin: 20px 0 20px 20px; color: #6b7280;">
                    <li>Create database tables (posts, comments, reactions)</li>
                    <li>Set up uploads directory with proper permissions</li>
                    <li>Configure application settings</li>
                    <li>Create .htaccess file for security</li>
                </ul>
                
                <form method="POST" action="install.php">
                    <input type="hidden" name="step" value="3">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Install Now
                        </button>
                        <a href="install.php?step=2" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            
            <!-- Step 4: Installation Complete -->
            <?php elseif ($step == 4): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h2>Installation Complete!</h2>
                    <p>KhaboonForum has been successfully installed on your server.</p>
                    
                    <div class="warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Important Security Step:</strong> Delete or rename the <code>install.php</code> file immediately!
                    </div>
                    
                    <div class="code-block">
                        # Delete installation script for security<br>
                        rm install.php<br>
                        <br>
                        # Or rename it<br>
                        mv install.php install.php.bak
                    </div>
                    
                    <div style="margin: 30px 0;">
                        <a href="index.php" class="btn btn-primary" style="margin-right: 10px;">
                            <i class="fas fa-home"></i> Go to Your Forum
                        </a>
                        <a href="create.php" class="btn btn-outline">
                            <i class="fas fa-plus"></i> Create First Post
                        </a>
                    </div>
                    
                    <div style="text-align: left; background: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 30px;">
                        <h3 style="margin-bottom: 10px; color: #374151;">Next Steps:</h3>
                        <ol style="margin-left: 20px; color: #6b7280;">
                            <li>Test the forum by creating a post</li>
                            <li>Upload an image to test file uploads</li>
                            <li>Add comments and reactions</li>
                            <li>Share the link with friends!</li>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>