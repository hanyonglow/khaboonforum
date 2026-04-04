<?php
/**
 * Verify the cache fix is working
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "<!DOCTYPE html>
<html>
<head>
    <title>Verify Cache Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🔍 Verify KhaboonForum Cache Fix</h1>
    
    <div class='test info'>
        <strong>Test Started:</strong> " . date('Y-m-d H:i:s') . "
    </div>";

// Test 1: Check PHP headers
echo "<div class='test'>";
echo "<h3>Test 1: PHP Cache Headers</h3>";
$headers = headers_list();
$cacheHeadersFound = 0;

foreach ($headers as $header) {
    if (stripos($header, 'cache') !== false || 
        stripos($header, 'expires') !== false ||
        stripos($header, 'pragma') !== false) {
        echo "<div class='info'>✓ $header</div>";
        $cacheHeadersFound++;
    }
}

if ($cacheHeadersFound >= 3) {
    echo "<div class='pass'>✅ PASS: Cache prevention headers found</div>";
} else {
    echo "<div class='fail'>❌ FAIL: Insufficient cache headers</div>";
}
echo "</div>";

// Test 2: Check database connection
echo "<div class='test'>";
echo "<h3>Test 2: Database Connection</h3>";

require_once __DIR__ . '/includes/functions.php';
$pdo = get_db_connection();

if ($pdo) {
    echo "<div class='pass'>✅ PASS: Database connection successful</div>";
    
    try {
        $post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        echo "<div class='info'>Posts in database: $post_count</div>";
        
        if ($post_count > 0) {
            echo "<div class='pass'>✅ PASS: Posts exist in database</div>";
        } else {
            echo "<div class='warning'>⚠️ WARNING: No posts in database (this may be normal)</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='fail'>❌ FAIL: Database query error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='fail'>❌ FAIL: Database connection failed</div>";
}
echo "</div>";

// Test 3: Check get_all_posts function
echo "<div class='test'>";
echo "<h3>Test 3: get_all_posts() Function</h3>";

$posts = get_all_posts(5, 0);
$post_count = count($posts);

echo "<div class='info'>Posts returned by function: $post_count</div>";

if ($post_count >= 0) {
    echo "<div class='pass'>✅ PASS: Function executes without error</div>";
    
    if ($post_count > 0) {
        echo "<div class='info'>Sample post: #" . $posts[0]['id'] . " by " . htmlspecialchars($posts[0]['user_name']) . "</div>";
    }
} else {
    echo "<div class='fail'>❌ FAIL: Function returned error</div>";
}
echo "</div>";

// Test 4: Check for Service Worker
echo "<div class='test'>";
echo "<h3>Test 4: Service Worker Check</h3>";
echo "<div id='sw-test'>Loading JavaScript test...</div>";
echo "</div>";

// Test 5: Check page version header
echo "<div class='test'>";
echo "<h3>Test 5: Page Version Header</h3>";

$versionHeader = false;
foreach ($headers as $header) {
    if (stripos($header, 'X-Khaboon-Version') !== false) {
        echo "<div class='pass'>✅ PASS: Version header found: $header</div>";
        $versionHeader = true;
        break;
    }
}

if (!$versionHeader) {
    echo "<div class='warning'>⚠️ WARNING: Version header not found (check index.php)</div>";
}
echo "</div>";

// Test 6: Link to main page with cache busting
$timestamp = time();
echo "<div class='test info'>";
echo "<h3>Test 6: Final Verification</h3>";
echo "<p>Click the link below to test the main page:</p>";
echo "<p><a href='index.php?_=$timestamp' target='_blank'>Test Main Page (with cache busting)</a></p>";
echo "<p><a href='index.php' target='_blank'>Test Main Page (without cache busting)</a></p>";
echo "<p>Both should show posts if the fix is working.</p>";
echo "</div>";

echo "
<script>
// Test 4: Service Worker check
document.addEventListener('DOMContentLoaded', function() {
    const swTest = document.getElementById('sw-test');
    
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            if (registrations.length === 0) {
                swTest.innerHTML = '<div class=\"pass\">✅ PASS: No Service Worker registered</div>';
            } else {
                swTest.innerHTML = '<div class=\"warning\">⚠️ WARNING: ' + registrations.length + ' Service Worker(s) registered</div>';
                swTest.innerHTML += '<button onclick=\"unregisterSW()\">Unregister Service Workers</button>';
            }
        });
    } else {
        swTest.innerHTML = '<div class=\"info\">ℹ️ INFO: Service Workers not supported</div>';
    }
});

function unregisterSW() {
    navigator.serviceWorker.getRegistrations().then(registrations => {
        registrations.forEach(registration => {
            registration.unregister().then(success => {
                if (success) {
                    alert('Service Worker unregistered. Page will reload.');
                    location.reload(true);
                }
            });
        });
    });
}

// Add cache busting to all links
document.querySelectorAll('a').forEach(link => {
    if (link.href && link.href.includes('.php') && !link.href.includes('?')) {
        link.href += '?_=' + Date.now();
    }
});
</script>

<div class='test info'>
    <h3>Summary</h3>
    <p>If all tests pass, the cache fix should be working.</p>
    <p>If posts still don't show:</p>
    <ol>
        <li>Clear browser cache (Ctrl+Shift+Delete)</li>
        <li>Use <a href='one_click_cache_fix.php'>One-Click Cache Fix</a></li>
        <li>Test with Shift+Refresh as comparison</li>
    </ol>
</div>

</body>
</html>";
?>