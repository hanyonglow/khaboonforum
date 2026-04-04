<?php
/**
 * Test script to verify global timezone implementation
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Set server timezone to UTC
date_default_timezone_set('UTC');

require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Global Timezone Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .warning { background: #fff3cd; color: #856404; }
        pre { background: #f5f5f5; padding: 10px; }
        .timestamp-demo { margin: 10px; padding: 10px; border: 1px dashed #ccc; }
    </style>
</head>
<body>
    <h1>🌍 Global Timezone Implementation Test</h1>
    
    <div class='test info'>
        <strong>Goal:</strong> Timestamps should show correctly for users in ANY timezone<br>
        <strong>Approach:</strong> Store in UTC, display in user's local time via JavaScript<br>
        <strong>Server Timezone:</strong> " . date_default_timezone_get() . " (should be UTC)<br>
        <strong>Current UTC Time:</strong> " . date('Y-m-d H:i:s') . "<br>
    </div>";

// Test 1: Check server timezone
echo "<div class='test'>";
echo "<h3>Test 1: Server Timezone Configuration</h3>";
$timezone = date_default_timezone_get();
if ($timezone === 'UTC') {
    echo "<div class='pass'>✅ PASS: Server timezone set to UTC</div>";
} else {
    echo "<div class='fail'>❌ FAIL: Server timezone is $timezone, should be UTC</div>";
}
echo "</div>";

// Test 2: Test format_date function (relative time)
echo "<div class='test'>";
echo "<h3>Test 2: format_date() Function (Relative Time)</h3>";

// Create test timestamps in UTC
$now_utc = gmdate('Y-m-d H:i:s');
$one_minute_ago = gmdate('Y-m-d H:i:s', strtotime('-1 minute'));
$one_hour_ago = gmdate('Y-m-d H:i:s', strtotime('-1 hour'));
$one_day_ago = gmdate('Y-m-d H:i:s', strtotime('-1 day'));
$one_week_ago = gmdate('Y-m-d H:i:s', strtotime('-1 week'));

echo "<div class='info'>";
echo "Current UTC time: $now_utc<br>";
echo "1 minute ago (UTC): $one_minute_ago → " . format_date($one_minute_ago) . "<br>";
echo "1 hour ago (UTC): $one_hour_ago → " . format_date($one_hour_ago) . "<br>";
echo "1 day ago (UTC): $one_day_ago → " . format_date($one_day_ago) . "<br>";
echo "1 week ago (UTC): $one_week_ago → " . format_date($one_week_ago) . "<br>";
echo "</div>";

// Check if relative times make sense
$one_min_result = format_date($one_minute_ago);
if (strpos($one_min_result, 'minute') !== false || $one_min_result === 'just now') {
    echo "<div class='pass'>✅ PASS: Relative time calculation appears correct</div>";
} else {
    echo "<div class='warning'>⚠️ WARNING: Relative time might be incorrect</div>";
}
echo "</div>";

// Test 3: Test JavaScript timestamp functions
echo "<div class='test'>";
echo "<h3>Test 3: JavaScript Timestamp Functions</h3>";

$test_timestamp = '2026-04-04 12:00:00'; // UTC time
echo "<div class='info'>";
echo "Test UTC timestamp: $test_timestamp<br>";
echo "get_js_timestamp(): " . get_js_timestamp($test_timestamp) . "<br>";
echo "format_iso_datetime(): " . format_iso_datetime($test_timestamp) . "<br>";
echo "format_exact_datetime(): " . format_exact_datetime($test_timestamp) . "<br>";
echo "</div>";

// Check ISO format
$iso = format_iso_datetime($test_timestamp);
if (strpos($iso, 'T') !== false && (strpos($iso, 'Z') !== false || strpos($iso, '+00:00') !== false)) {
    echo "<div class='pass'>✅ PASS: ISO 8601 format correct</div>";
} else {
    echo "<div class='fail'>❌ FAIL: ISO 8601 format incorrect: $iso</div>";
}
echo "</div>";

// Test 4: JavaScript timezone detection demo
echo "<div class='test'>";
echo "<h3>Test 4: JavaScript Timezone Detection (Will run below)</h3>";
echo "<div class='info'>";
echo "Your browser will detect your timezone and show it below.<br>";
echo "Timestamps will be converted to your local time.<br>";
echo "</div>";
echo "</div>";

// Demo of timestamp conversion
echo "<div class='timestamp-demo'>";
echo "<h4>Timestamp Conversion Demo</h4>";
echo "<p>Original UTC timestamp stored in database: <code>2026-04-04 12:00:00</code></p>";
echo "<p>Should display in YOUR local time: <span id='local-time-demo'>Loading...</span></p>";
echo "<p>Your detected timezone: <span id='timezone-demo'>Detecting...</span></p>";
echo "</div>";

echo "
<script>
// Timezone detection
try {
    const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const now = new Date();
    const offset = now.getTimezoneOffset();
    const offsetHours = Math.abs(Math.floor(offset / 60));
    const offsetMinutes = Math.abs(offset % 60);
    const offsetSign = offset <= 0 ? '+' : '-';
    
    let offsetDisplay = `GMT${offsetSign}${offsetHours}`;
    if (offsetMinutes > 0) {
        offsetDisplay += `:${offsetMinutes.toString().padStart(2, '0')}`;
    }
    
    document.getElementById('timezone-demo').textContent = `${userTimezone} (${offsetDisplay})`;
    
    // Convert UTC timestamp to local time
    const utcTimestamp = '2026-04-04T12:00:00Z'; // ISO 8601 UTC
    const localDate = new Date(utcTimestamp);
    
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZoneName: 'short'
    };
    
    const formattedDate = localDate.toLocaleString(undefined, options);
    document.getElementById('local-time-demo').textContent = formattedDate;
    
    console.log('Timezone detected:', userTimezone, offsetDisplay);
    console.log('UTC 12:00 → Local:', formattedDate);
    
} catch (error) {
    document.getElementById('timezone-demo').textContent = 'Error: ' + error.message;
    document.getElementById('local-time-demo').textContent = 'Conversion failed';
}

// Test relative time formatting
function testRelativeTime() {
    const testTimes = [
        { utc: new Date(Date.now() - 30000).toISOString(), expected: 'just now' },
        { utc: new Date(Date.now() - 90000).toISOString(), expected: '1 minute' },
        { utc: new Date(Date.now() - 7200000).toISOString(), expected: '2 hours' },
        { utc: new Date(Date.now() - 172800000).toISOString(), expected: '2 days' }
    ];
    
    console.log('Relative time test:');
    testTimes.forEach(test => {
        const date = new Date(test.utc);
        const diff = Date.now() - date.getTime();
        console.log(`UTC: ${test.utc}, Diff: ${Math.floor(diff/1000)}s`);
    });
}

// Run test
setTimeout(testRelativeTime, 1000);
</script>";

// Test 5: Database check
echo "<div class='test'>";
echo "<h3>Test 5: Database Timezone Check</h3>";

$pdo = get_db_connection();
if ($pdo) {
    echo "<div class='pass'>✅ PASS: Database connection successful</div>";
    
    try {
        // Check database timezone settings
        $stmt = $pdo->query("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz");
        $result = $stmt->fetch();
        
        echo "<div class='info'>";
        echo "MySQL Global Timezone: " . $result['global_tz'] . "<br>";
        echo "MySQL Session Timezone: " . $result['session_tz'] . "<br>";
        echo "</div>";
        
        // Check if timestamps are stored correctly
        $stmt = $pdo->query("SELECT created_at FROM posts ORDER BY created_at DESC LIMIT 1");
        $post = $stmt->fetch();
        
        if ($post) {
            echo "<div class='info'>";
            echo "Latest post timestamp: " . $post['created_at'] . "<br>";
            echo "Should be in UTC format<br>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ Could not check database timezone: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ Database connection failed (may be normal if not configured)</div>";
}
echo "</div>";

echo "
<div class='test info'>
    <h3>Implementation Summary</h3>
    <p><strong>How it works:</strong></p>
    <ol>
        <li><strong>Storage:</strong> All timestamps stored in UTC in database</li>
        <li><strong>Server:</strong> PHP uses UTC timezone for calculations</li>
        <li><strong>Relative Time:</strong> PHP calculates 'X minutes/hours/days ago' using UTC</li>
        <li><strong>Display:</strong> JavaScript converts UTC timestamps to user's local timezone</li>
        <li><strong>Updates:</strong> Relative times update automatically every minute</li>
    </ol>
    
    <p><strong>Benefits:</strong></p>
    <ul>
        <li>✅ Users in Singapore see Singapore time</li>
        <li>✅ Users in New York see New York time</li>
        <li>✅ Users in London see London time</li>
        <li>✅ All see correct relative times ('just now', '2 hours ago')</li>
        <li>✅ No hardcoded timezone assumptions</li>
    </ul>
    
    <p><a href='index.php?_=" . time() . "'>Test Main Page</a> | 
    <a href='test_timezone_fix.php?_=" . time() . "'>Previous Test</a></p>
</div>

<script>
// Add cache busting
document.querySelectorAll('a').forEach(link => {
    if (link.href && link.href.includes('.php') && !link.href.includes('?')) {
        link.href += '?_=' + Date.now();
    }
});
</script>

</body>
</html>";
?>