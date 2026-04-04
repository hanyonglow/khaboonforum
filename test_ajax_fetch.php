<?php
/**
 * Test AJAX fetching of posts
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "<!DOCTYPE html>
<html>
<head>
    <title>AJAX Fetch Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; }
        button { margin: 5px; padding: 10px; }
    </style>
</head>
<body>
    <h1>AJAX Fetch Test for KhaboonForum</h1>
    
    <div class='section'>
        <h2>Test 1: Direct PHP Function Call</h2>
        <button onclick='testDirectPHP()'>Test get_all_posts() function</button>
        <div id='direct-php-result'></div>
    </div>
    
    <div class='section'>
        <h2>Test 2: Fetch via AJAX (API)</h2>
        <button onclick='testAjaxFetch()'>Test AJAX fetch to load_posts.php</button>
        <div id='ajax-result'></div>
    </div>
    
    <div class='section'>
        <h2>Test 3: Fetch with Cache Headers</h2>
        <button onclick='testFetchWithCache()'>Test fetch with cache: no-cache</button>
        <button onclick='testFetchWithCacheReload()'>Test fetch with cache: reload</button>
        <div id='cache-fetch-result'></div>
    </div>
    
    <div class='section'>
        <h2>Test 4: Service Worker Interception</h2>
        <button onclick='testServiceWorker()'>Test if Service Worker intercepts fetch</button>
        <div id='sw-test-result'></div>
    </div>
    
    <script>
        function testDirectPHP() {
            const resultDiv = document.getElementById('direct-php-result');
            resultDiv.innerHTML = '<em>Loading...</em>';
            
            fetch('test_direct_posts.php?t=' + Date.now())
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = '<pre>' + data + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<span class=\"error\">Error: ' + error + '</span>';
                });
        }
        
        function testAjaxFetch() {
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '<em>Loading...</em>';
            
            fetch('api/load_posts.php?offset=0&t=' + Date.now())
                .then(response => {
                    resultDiv.innerHTML = '<p>Status: ' + response.status + ' ' + response.statusText + '</p>';
                    resultDiv.innerHTML += '<p>Headers:</p><pre>';
                    
                    for (const [key, value] of response.headers.entries()) {
                        resultDiv.innerHTML += key + ': ' + value + '\\n';
                    }
                    
                    resultDiv.innerHTML += '</pre>';
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML += '<p>Response data:</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML += '<span class=\"error\">Error: ' + error + '</span>';
                });
        }
        
        function testFetchWithCache() {
            const resultDiv = document.getElementById('cache-fetch-result');
            resultDiv.innerHTML = '<em>Loading with cache: no-cache...</em>';
            
            fetch('api/load_posts.php?offset=0', {
                cache: 'no-cache',
                headers: {
                    'X-Test-Time': Date.now()
                }
            })
            .then(response => {
                resultDiv.innerHTML = '<p>Cache: no-cache</p>';
                resultDiv.innerHTML += '<p>Status: ' + response.status + '</p>';
                return response.json();
            })
            .then(data => {
                resultDiv.innerHTML += '<p>Posts returned: ' + (data.posts ? data.posts.length : 0) + '</p>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<span class=\"error\">Error: ' + error + '</span>';
            });
        }
        
        function testFetchWithCacheReload() {
            const resultDiv = document.getElementById('cache-fetch-result');
            resultDiv.innerHTML = '<em>Loading with cache: reload...</em>';
            
            fetch('api/load_posts.php?offset=0', {
                cache: 'reload',
                headers: {
                    'X-Test-Time': Date.now()
                }
            })
            .then(response => {
                resultDiv.innerHTML += '<p>Cache: reload</p>';
                resultDiv.innerHTML += '<p>Status: ' + response.status + '</p>';
                return response.json();
            })
            .then(data => {
                resultDiv.innerHTML += '<p>Posts returned: ' + (data.posts ? data.posts.length : 0) + '</p>';
            })
            .catch(error => {
                resultDiv.innerHTML = '<span class=\"error\">Error: ' + error + '</span>';
            });
        }
        
        function testServiceWorker() {
            const resultDiv = document.getElementById('sw-test-result');
            
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistration().then(registration => {
                    if (registration) {
                        resultDiv.innerHTML = '<span class=\"warning\">⚠️ Service Worker is registered and may intercept fetch requests</span>';
                        resultDiv.innerHTML += '<p>Scope: ' + registration.scope + '</p>';
                        
                        // Test if fetch goes through service worker
                        fetch('api/test_sw_intercept.php?t=' + Date.now())
                            .then(response => {
                                const viaSW = response.headers.get('X-Served-By') === 'Service-Worker';
                                resultDiv.innerHTML += '<p>Fetch intercepted by Service Worker: ' + (viaSW ? 'YES' : 'NO') + '</p>';
                            });
                    } else {
                        resultDiv.innerHTML = '<span class=\"success\">✅ No Service Worker registered</span>';
                    }
                });
            } else {
                resultDiv.innerHTML = '<span class=\"success\">✅ Service Workers not supported</span>';
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('AJAX Fetch Test initialized');
            
            // Check if we're online
            if (!navigator.onLine) {
                alert('⚠️ You appear to be offline. Some tests may fail.');
            }
        });
    </script>
</body>
</html>";
?>