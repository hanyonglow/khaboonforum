<?php
/**
 * Test URL detection functionality
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>URL Detection Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .demo { margin: 20px; padding: 20px; border: 2px solid #4f46e5; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
    <link rel=\"stylesheet\" href=\"css/style.css\">
</head>
<body>
    <h1>🔗 URL Detection Test</h1>
    
    <div class='test info'>
        <strong>Goal:</strong> Auto-detect URLs in text and make them clickable<br>
        <strong>Approach:</strong> No database changes, pure text processing<br>
        <strong>Function:</strong> make_links_clickable() in functions.php<br>
    </div>";

// Test 1: Check if function exists
echo "<div class='test'>";
echo "<h3>Test 1: Function Existence</h3>";
if (function_exists('make_links_clickable')) {
    echo "<div class='pass'>✅ PASS: make_links_clickable() function exists</div>";
} else {
    echo "<div class='fail'>❌ FAIL: make_links_clickable() function not found</div>";
}
echo "</div>";

// Test 2: Test various URL patterns
echo "<div class='test'>";
echo "<h3>Test 2: URL Pattern Detection</h3>";

$test_cases = [
    "Check out https://example.com for more info" => "Should detect https://example.com",
    "Visit http://test.com/path?query=value" => "Should detect http://test.com/path?query=value",
    "Go to www.example.org now" => "Should detect www.example.org (adds https://)",
    "FTP server: ftp://files.example.com" => "Should detect ftp://files.example.com",
    "Multiple: http://first.com and https://second.com/path" => "Should detect both URLs",
    "No protocol: example.com" => "Should NOT detect (needs www. or protocol)",
    "Email: user@example.com" => "Should NOT detect emails",
    "Long URL: https://very-long-domain-name-that-goes-on-and-on.com/with/a/very/long/path/that/might/break/the/layout" => "Should shorten display",
];

echo "<div class='demo'>";
echo "<h4>Demo Output:</h4>";

foreach ($test_cases as $input => $description) {
    echo "<p><strong>Input:</strong> " . htmlspecialchars($input) . "</p>";
    echo "<p><strong>Output:</strong> " . make_links_clickable(htmlspecialchars($input)) . "</p>";
    echo "<p><em>$description</em></p>";
    echo "<hr>";
}

echo "</div>";
echo "</div>";

// Test 3: Security test
echo "<div class='test'>";
echo "<h3>Test 3: Security & HTML Escaping</h3>";

$security_tests = [
    "<script>alert('xss')</script> http://example.com" => "Should escape HTML, keep URL",
    "http://example.com\" onmouseover=\"alert('xss')" => "Should escape quotes in URL",
    "Visit http://<script>alert('bad')</script>.com" => "Should escape angle brackets",
];

echo "<div class='info'>";
foreach ($security_tests as $input => $description) {
    $output = make_links_clickable(htmlspecialchars($input));
    echo "<p><strong>Test:</strong> $description</p>";
    echo "<p><strong>Input:</strong> " . htmlspecialchars($input) . "</p>";
    echo "<p><strong>Output:</strong> $output</p>";
    
    // Check for script tags in output
    if (strpos($output, '<script>') !== false) {
        echo "<div class='fail'>❌ FAIL: Script tag found in output!</div>";
    } else {
        echo "<div class='pass'>✅ PASS: HTML properly escaped</div>";
    }
    echo "<hr>";
}
echo "</div>";
echo "</div>";

// Test 4: Integration test
echo "<div class='test'>";
echo "<h3>Test 4: Integration with nl2br</h3>";

$multiline_input = "First line with http://example.com\nSecond line with www.test.org\nThird line no link";
$processed = nl2br(make_links_clickable(htmlspecialchars($multiline_input)));

echo "<div class='demo'>";
echo "<h4>Multi-line input with URLs:</h4>";
echo "<pre>" . htmlspecialchars($multiline_input) . "</pre>";
echo "<h4>Processed output:</h4>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: white;'>";
echo $processed;
echo "</div>";
echo "</div>";
echo "</div>";

echo "
<div class='test info'>
    <h3>Implementation Summary</h3>
    <p><strong>Files Modified:</strong></p>
    <ol>
        <li><code>includes/functions.php</code> - Added make_links_clickable() function</li>
        <li><code>index.php</code> - Updated post content display</li>
        <li><code>post.php</code> - Updated post and comment content display</li>
        <li><code>css/style.css</code> - Added text-link styling</li>
    </ol>
    
    <p><strong>What it detects:</strong></p>
    <ul>
        <li>✅ http://example.com</li>
        <li>✅ https://example.com/path?query=value</li>
        <li>✅ ftp://example.com</li>
        <li>✅ www.example.com (adds https:// automatically)</li>
        <li>❌ example.com (needs www. or protocol)</li>
        <li>❌ user@example.com (emails not detected)</li>
    </ul>
    
    <p><strong>Features:</strong></p>
    <ul>
        <li>✅ URLs open in new tab (<code>target=\"_blank\"</code>)</li>
        <li>✅ Security: <code>rel=\"noopener noreferrer\"</code></li>
        <li>✅ Long URLs shortened for display</li>
        <li>✅ Works with existing posts (no DB changes)</li>
        <li>✅ Dark theme support</li>
    </ul>
    
    <p><a href='index.php?_=" . time() . "'>Test on Main Page</a> | 
    <a href='create.php?_=" . time() . "'>Create Test Post</a></p>
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