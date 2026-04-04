<?php
/**
 * Test script to verify timezone fix
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Timezone Fix Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>🕒 Timezone Fix Test</h1>
    
    <div class='test info'>
        <strong>Current Server Time:</strong> " . date('Y-m-d H:i:s') . "<br>
        <strong>Timezone:</strong> " . date_default_timezone_get() . "<br>
        <strong>Singapore Time (GMT+8):</strong> Should be current time<br>
    </div>";

// Test 1: Check PHP timezone
echo "<div class='test'>";
echo "<h3>Test 1: PHP Timezone Configuration</h3>";
$timezone = date_default_timezone_get();
if ($timezone === 'Asia/Singapore') {
    echo "<div class='pass'>✅ PASS: Timezone set to Asia/Singapore</div>";
} else {
    echo "<div class='fail'>❌ FAIL: Timezone is $timezone, should be Asia/Singapore</div>";
}
echo "</div>";

// Test 2: Test format_date function
echo "<div class='test'>";
echo "<h3>Test 2: format_date() Function</h3>";

// Create test timestamps
$now = date('Y-m-d H:i:s');
$one_minute_ago = date('Y-m-d H:i:s', strtotime('-1 minute'));
$one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
$one_day_ago = date('Y-m-d H:i:s', strtotime('-1 day'));
$one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));

echo "<div class='info'>";
echo "Current time: $now<br>";
echo "1 minute ago: " . format_date($one_minute_ago) . "<br>";
echo "1 hour ago: " . format_date($one_hour_ago) . "<br>";
echo "1 day ago: " . format_date($one_day_ago) . "<br>";
echo "1 week ago: " . format_date($one_week_ago) . "<br>";
echo "</div>";

// Test with UTC timestamp (simulating MySQL timestamp)
$utc_timestamp = gmdate('Y-m-d H:i:s');
echo "<div class='info'>";
echo "UTC timestamp (simulating MySQL): $utc_timestamp<br>";
echo "Formatted: " . format_date($utc_timestamp) . "<br>";
echo "</div>";

echo "</div>";

// Test 3: Test exact datetime function
echo "<div class='test'>";
echo "<h3>Test 3: format_exact_datetime() Function</h3>";

if (function_exists('format_exact_datetime')) {
    $test_time = '2026-04-04 12:00:00'; // UTC time
    echo "<div class='info'>";
    echo "Test UTC time: $test_time<br>";
    echo "Singapore time (GMT+8): " . format_exact_datetime($test_time) . "<br>";
    echo "Should show: Apr 4, 2026 8:00 PM (12:00 UTC + 8 hours)<br>";
    echo "</div>";
    
    // Check if conversion is correct
    $sg_time = format_exact_datetime($test_time);
    if (strpos($sg_time, '8:00 PM') !== false || strpos($sg_time, '20:00') !== false) {
        echo "<div class='pass'>✅ PASS: Timezone conversion appears correct</div>";
    } else {
        echo "<div class='fail'>❌ FAIL: Timezone conversion may be incorrect</div>";
    }
} else {
    echo "<div class='fail'>❌ FAIL: format_exact_datetime() function not found</div>";
}
echo "</div>";

// Test 4: Database timestamp test
echo "<div class='test'>";
echo "<h3>Test 4: Database Connection Test</h3>";

$pdo = get_db_connection();
if ($pdo) {
    echo "<div class='pass'>✅ PASS: Database connection successful</div>";
    
    try {
        // Get server time from database
        $stmt = $pdo->query("SELECT NOW() as db_time, UTC_TIMESTAMP() as utc_time");
        $result = $stmt->fetch();
        
        echo "<div class='info'>";
        echo "Database server time: " . $result['db_time'] . "<br>";
        echo "Database UTC time: " . $result['utc_time'] . "<br>";
        echo "PHP time (Asia/Singapore): " . date('Y-m-d H:i:s') . "<br>";
        echo "</div>";
        
        // Check if database is using UTC
        $db_time = strtotime($result['db_time']);
        $utc_time = strtotime($result['utc_time']);
        $php_time = time();
        
        $diff_db_utc = abs($db_time - $utc_time);
        $diff_db_php = abs($db_time - $php_time);
        
        if ($diff_db_utc < 60) { // Within 1 minute
            echo "<div class='info'>Database appears to be using local time, not UTC</div>";
        } else {
            echo "<div class='info'>Database appears to be using UTC (difference: " . ($diff_db_utc/3600) . " hours)</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='fail'>❌ Database query error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='fail'>❌ FAIL: Database connection failed</div>";
}
echo "</div>";

// Test 5: Theme toggle test
echo "<div class='test'>";
echo "<h3>Test 5: Dark Theme Default</h3>";
echo "<div class='info'>";
echo "Dark theme should be enabled by default.<br>";
echo "Check if localStorage has 'khaboon_theme' set to 'dark'<br>";
echo "Theme toggle should switch between sun/moon icons<br>";
echo "</div>";
echo "</div>";

echo "
<script>
// Test theme functionality
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('khaboon_theme');
    const themeTest = document.createElement('div');
    themeTest.className = 'test info';
    themeTest.innerHTML = '<h4>Theme Status</h4>' +
                         'Saved theme in localStorage: ' + (savedTheme || 'not set') + '<br>' +
                         'Body has dark-theme class: ' + document.body.classList.contains('dark-theme') + '<br>' +
                         'Expected: dark (default)';
    
    document.body.appendChild(themeTest);
    
    // Test toast notification
    setTimeout(function() {
        if (typeof showToast === 'function') {
            showToast('Timezone test completed!', 'success');
        }
    }, 1000);
});

// Add cache busting
document.querySelectorAll('a').forEach(link => {
    if (link.href && link.href.includes('.php') && !link.href.includes('?')) {
        link.href += '?_=' + Date.now();
    }
});
</script>

<div class='test info'>
    <h3>Summary</h3>
    <p>If all tests pass:</p>
    <ul>
        <li>✅ Timestamps should show correct Singapore time (GMT+8)</li>
        <li>✅ Posts showing '1 minute ago' should actually be 1 minute ago</li>
        <li>✅ Dark theme should be enabled by default</li>
        <li>✅ Theme toggle should work with notifications</li>
        <li>✅ Timezone indicator should show in header</li>
    </ul>
    
    <p><a href='index.php?_=" . time() . "'>Test Main Page</a></p>
    <p><a href='one_click_cache_fix.php?_=" . time() . "'>Clear Cache & Test</a></p>
</div>

</body>
</html>";
?>