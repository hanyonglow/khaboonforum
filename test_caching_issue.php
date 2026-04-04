<?php
/**
 * Test script to diagnose caching issues
 */

// Prevent all caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Accel-Expires: 0");

// Add headers to identify this as a test
header("X-Khaboon-Test: Caching-Diagnostic");

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cache Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>KhaboonForum Cache Diagnostic</h1>
    
    <div class='section'>
        <h2>PHP Headers Sent</h2>
        <pre>";

foreach (headers_list() as $header) {
    echo htmlspecialchars($header) . "\n";
}

echo "</pre>
    </div>
    
    <div class='section'>
        <h2>Server Information</h2>
        <ul>
            <li>PHP Version: " . phpversion() . "</li>
            <li>Server Time: " . date('Y-m-d H:i:s') . "</li>
            <li>Request Time: " . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "</li>
            <li>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</li>
            <li>User Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</li>
        </ul>
    </div>
    
    <div class='section'>
        <h2>Cache Control Test</h2>
        <p>This page should NEVER be cached by browsers or proxies.</p>
        <p>Timestamp: <strong>" . microtime(true) . "</strong></p>
        <p>Random value: <strong>" . bin2hex(random_bytes(8)) . "</strong></p>
        <p>If you see the same timestamp and random value on refresh, caching is happening!</p>
        <button onclick='location.reload()'>Refresh Normally</button>
        <button onclick='location.reload(true)'>Force Refresh (Shift+Refresh)</button>
    </div>
    
    <div class='section'>
        <h2>Service Worker Status</h2>
        <div id='sw-status'>Checking...</div>
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(registrations => {
                    const statusDiv = document.getElementById('sw-status');
                    if (registrations.length > 0) {
                        statusDiv.innerHTML = '<span class=\"warning\">⚠️ Service Worker ACTIVE</span><br>';
                        registrations.forEach(reg => {
                            statusDiv.innerHTML += 'Scope: ' + reg.scope + '<br>';
                        });
                        statusDiv.innerHTML += '<button onclick=\"unregisterSW()\">Unregister Service Worker</button>';
                    } else {
                        statusDiv.innerHTML = '<span class=\"success\">✅ No Service Worker registered</span>';
                    }
                });
            } else {
                document.getElementById('sw-status').innerHTML = '<span class=\"success\">✅ Service Workers not supported</span>';
            }
            
            function unregisterSW() {
                navigator.serviceWorker.getRegistrations().then(registrations => {
                    registrations.forEach(registration => {
                        registration.unregister();
                    });
                    alert('Service Worker unregistered. Please refresh.');
                    location.reload(true);
                });
            }
        </script>
    </div>
    
    <div class='section'>
        <h2>Cache Storage Test</h2>
        <div id='cache-status'>Checking...</div>
        <script>
            if ('caches' in window) {
                caches.keys().then(cacheNames => {
                    const cacheDiv = document.getElementById('cache-status');
                    if (cacheNames.length > 0) {
                        cacheDiv.innerHTML = '<span class=\"warning\">⚠️ Browser Caches Found:</span><br><ul>';
                        cacheNames.forEach(name => {
                            cacheDiv.innerHTML += '<li>' + name + '</li>';
                        });
                        cacheDiv.innerHTML += '</ul><button onclick=\"clearCaches()\">Clear All Caches</button>';
                    } else {
                        cacheDiv.innerHTML = '<span class=\"success\">✅ No browser caches found</span>';
                    }
                });
            } else {
                document.getElementById('cache-status').innerHTML = '<span class=\"success\">✅ Cache API not supported</span>';
            }
            
            function clearCaches() {
                caches.keys().then(cacheNames => {
                    return Promise.all(cacheNames.map(name => caches.delete(name)));
                }).then(() => {
                    alert('All caches cleared. Please refresh.');
                    location.reload(true);
                });
            }
        </script>
    </div>
    
    <div class='section'>
        <h2>Database Connection Test</h2>
        <pre>";

// Test database connection
require_once __DIR__ . '/includes/functions.php';
$pdo = get_db_connection();

if ($pdo) {
    echo "<span class='success'>✅ Database connection successful</span>\n";
    
    try {
        $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "Total posts in database: " . $post_count . "\n";
        
        if ($post_count > 0) {
            $posts = $pdo->query("SELECT id, user_name, created_at FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
            echo "Latest posts:\n";
            foreach ($posts as $post) {
                echo "  - #" . $post['id'] . " by " . $post['user_name'] . " at " . $post['created_at'] . "\n";
            }
        } else {
            echo "<span class='warning'>⚠️ No posts in database</span>\n";
        }
    } catch (PDOException $e) {
        echo "<span class='error'>❌ Database query failed: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    }
} else {
    echo "<span class='error'>❌ Database connection failed</span>\n";
}

echo "</pre>
    </div>
    
    <div class='section'>
        <h2>Test Main Page</h2>
        <p><a href='index.php' target='_blank'>Open index.php</a> - Check if posts load</p>
        <p><a href='index.php?nocache=' . time() . "' target='_blank'>Open index.php with cache busting</a></p>
    </div>
    
    <div class='section'>
        <h2>HTTP Request Headers</h2>
        <pre>";

foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo htmlspecialchars(substr($key, 5) . ': ' . $value) . "\n";
    }
}

echo "</pre>
    </div>
    
    <script>
        // Add cache busting to all links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                if (link.href && !link.href.includes('?')) {
                    link.href += '?t=' + Date.now();
                }
            });
        });
    </script>
</body>
</html>";
?>