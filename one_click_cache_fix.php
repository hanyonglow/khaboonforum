<?php
/**
 * One-Click Cache Fix for KhaboonForum
 * 
 * This script will:
 * 1. Clear browser caches
 * 2. Disable Service Worker
 * 3. Redirect to main page with cache busting
 */

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$timestamp = time();
?>
<!DOCTYPE html>
<html>
<head>
    <title>KhaboonForum - One-Click Cache Fix</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .step {
            background: #f8fafc;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4f46e5;
            border-radius: 4px;
        }
        .step-number {
            display: inline-block;
            background: #4f46e5;
            color: white;
            width: 24px;
            height: 24px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #4338ca;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .log {
            background: #1f2937;
            color: #e5e7eb;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            max-height: 200px;
            overflow-y: auto;
            margin: 15px 0;
            display: none;
        }
        .checkbox {
            margin: 10px 0;
        }
        .checkbox label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .checkbox input {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 KhaboonForum Cache Fix</h1>
        
        <p><strong>Problem:</strong> Posts and comments not showing without Shift+Refresh</p>
        <p><strong>Solution:</strong> Clear all browser caches and disable Service Worker</p>
        
        <div class="step">
            <span class="step-number">1</span>
            <strong>Select what to clear:</strong>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="clear-cache" checked>
                    Clear browser cache (images, CSS, JS)
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="clear-storage" checked>
                    Clear localStorage & sessionStorage
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="clear-indexeddb">
                    Clear IndexedDB databases
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="disable-sw" checked>
                    Disable Service Worker
                </label>
            </div>
        </div>
        
        <div class="step">
            <span class="step-number">2</span>
            <strong>Run the fix:</strong>
            <p>Click the button below to clear selected caches.</p>
            
            <button class="btn btn-danger" onclick="runCacheFix()">
                🚀 Run Cache Fix Now
            </button>
            
            <button class="btn" onclick="runQuickFix()">
                ⚡ Quick Fix (Recommended)
            </button>
        </div>
        
        <div class="step">
            <span class="step-number">3</span>
            <strong>Status:</strong>
            <div id="status" class="status"></div>
            <div id="log" class="log"></div>
        </div>
        
        <div class="step">
            <span class="step-number">4</span>
            <strong>Test the fix:</strong>
            <p>After clearing caches, test if posts are loading correctly.</p>
            
            <a href="index.php?_=<?php echo $timestamp; ?>" class="btn" target="_blank">
                📝 Test Main Page
            </a>
            
            <a href="test_caching_issue.php?_=<?php echo $timestamp; ?>" class="btn" target="_blank">
                🔧 Run Cache Tests
            </a>
            
            <button class="btn btn-success" onclick="location.reload(true)">
                🔄 Reload This Page
            </button>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <h3>Need More Help?</h3>
            <p>If posts still don't show after clearing caches:</p>
            <ol>
                <li>Try <strong>Shift+Refresh</strong> on the main page</li>
                <li>Check browser extensions (disable ad blockers temporarily)</li>
                <li>Try a different browser</li>
                <li>Check <a href="CACHE_FIX_README.md">CACHE_FIX_README.md</a> for detailed instructions</li>
            </ol>
        </div>
    </div>
    
    <script>
        const statusEl = document.getElementById('status');
        const logEl = document.getElementById('log');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const line = `[${timestamp}] ${message}`;
            
            if (!logEl.style.display || logEl.style.display === 'none') {
                logEl.style.display = 'block';
            }
            
            logEl.innerHTML += line + '\\n';
            logEl.scrollTop = logEl.scrollHeight;
            
            console.log(`[CacheFix] ${message}`);
        }
        
        function showStatus(message, type = 'info') {
            statusEl.textContent = message;
            statusEl.className = 'status status-' + type;
            statusEl.style.display = 'block';
        }
        
        async function clearCache() {
            if (!document.getElementById('clear-cache').checked) {
                log('Skipping cache clearance (not selected)');
                return { success: true, message: 'Skipped' };
            }
            
            log('Clearing browser cache...');
            
            if ('caches' in window) {
                try {
                    const cacheNames = await caches.keys();
                    log(`Found ${cacheNames.length} cache(s)`);
                    
                    for (const cacheName of cacheNames) {
                        await caches.delete(cacheName);
                        log(`Deleted cache: ${cacheName}`);
                    }
                    
                    return { success: true, message: `Cleared ${cacheNames.length} cache(s)` };
                } catch (error) {
                    log(`Error clearing caches: ${error.message}`, 'error');
                    return { success: false, message: `Error: ${error.message}` };
                }
            } else {
                log('Cache API not supported');
                return { success: true, message: 'Cache API not supported' };
            }
        }
        
        function clearStorage() {
            if (!document.getElementById('clear-storage').checked) {
                log('Skipping storage clearance (not selected)');
                return { success: true, message: 'Skipped' };
            }
            
            log('Clearing localStorage and sessionStorage...');
            
            try {
                const localStorageCount = localStorage.length;
                const sessionStorageCount = sessionStorage.length;
                
                localStorage.clear();
                sessionStorage.clear();
                
                log(`Cleared ${localStorageCount} localStorage items`);
                log(`Cleared ${sessionStorageCount} sessionStorage items`);
                
                return { 
                    success: true, 
                    message: `Cleared ${localStorageCount} localStorage and ${sessionStorageCount} sessionStorage items` 
                };
            } catch (error) {
                log(`Error clearing storage: ${error.message}`, 'error');
                return { success: false, message: `Error: ${error.message}` };
            }
        }
        
        async function clearIndexedDB() {
            if (!document.getElementById('clear-indexeddb').checked) {
                log('Skipping IndexedDB clearance (not selected)');
                return { success: true, message: 'Skipped' };
            }
            
            log('Clearing IndexedDB...');
            
            if (!window.indexedDB) {
                log('IndexedDB not supported');
                return { success: true, message: 'IndexedDB not supported' };
            }
            
            try {
                if (indexedDB.databases) {
                    const dbs = await indexedDB.databases();
                    log(`Found ${dbs.length} IndexedDB database(s)`);
                    
                    for (const db of dbs) {
                        try {
                            await indexedDB.deleteDatabase(db.name);
                            log(`Deleted database: ${db.name}`);
                        } catch (error) {
                            log(`Could not delete database ${db.name}: ${error.message}`);
                        }
                    }
                    
                    return { 
                        success: true, 
                        message: `Cleared ${dbs.length} IndexedDB database(s)` 
                    };
                } else {
                    log('IndexedDB.databases() not supported');
                    return { success: true, message: 'Limited IndexedDB support' };
                }
            } catch (error) {
                log(`Error clearing IndexedDB: ${error.message}`, 'error');
                return { success: false, message: `Error: ${error.message}` };
            }
        }
        
        async function disableServiceWorker() {
            if (!document.getElementById('disable-sw').checked) {
                log('Skipping Service Worker disable (not selected)');
                return { success: true, message: 'Skipped' };
            }
            
            log('Disabling Service Worker...');
            
            if (!('serviceWorker' in navigator)) {
                log('Service Workers not supported');
                return { success: true, message: 'Service Workers not supported' };
            }
            
            try {
                const registrations = await navigator.serviceWorker.getRegistrations();
                log(`Found ${registrations.length} Service Worker registration(s)`);
                
                for (const registration of registrations) {
                    const success = await registration.unregister();
                    if (success) {
                        log(`Unregistered: ${registration.scope}`);
                    } else {
                        log(`Failed to unregister: ${registration.scope}`, 'warning');
                    }
                }
                
                return { 
                    success: true, 
                    message: `Unregistered ${registrations.length} Service Worker(s)` 
                };
            } catch (error) {
                log(`Error disabling Service Worker: ${error.message}`, 'error');
                return { success: false, message: `Error: ${error.message}` };
            }
        }
        
        async function runCacheFix() {
            // Clear previous logs
            logEl.innerHTML = '';
            logEl.style.display = 'none';
            
            showStatus('Starting cache fix...', 'warning');
            log('=== Starting Cache Fix ===');
            
            try {
                // Run all cleanup operations
                const results = await Promise.all([
                    clearCache(),
                    clearStorage(),
                    clearIndexedDB(),
                    disableServiceWorker()
                ]);
                
                // Show summary
                log('\\n=== Cache Fix Complete ===');
                
                let allSuccess = true;
                let summary = 'Cache fix completed:\\n';
                
                results.forEach((result, index) => {
                    const operations = ['Cache', 'Storage', 'IndexedDB', 'Service Worker'];
                    log(`${operations[index]}: ${result.message}`);
                    summary += `\\n• ${operations[index]}: ${result.message}`;
                    
                    if (!result.success) {
                        allSuccess = false;
                    }
                });
                
                if (allSuccess) {
                    showStatus('✅ Cache fix completed successfully!', 'success');
                    log('All operations completed successfully');
                    
                    // Auto-redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = `index.php?_=${Date.now()}`;
                    }, 3000);
                } else {
                    showStatus('⚠️ Cache fix completed with some warnings', 'warning');
                    log('Some operations had issues');
                }
                
                // Show summary to user
                alert(summary + '\\n\\nPage will redirect to main page in 3 seconds...');
                
            } catch (error) {
                showStatus(`❌ Error: ${error.message}`, 'error');
                log(`Fatal error: ${error.message}`, 'error');
            }
        }
        
        function runQuickFix() {
            // Select only the essential options
            document.getElementById('clear-cache').checked = true;
            document.getElementById('clear-storage').checked = true;
            document.getElementById('clear-indexeddb').checked = false;
            document.getElementById('disable-sw').checked = true;
            
            // Run the fix
            runCacheFix();
        }
        
        // Auto-check for Service Worker on load
        document.addEventListener('DOMContentLoaded', async () => {
            if ('serviceWorker' in navigator) {
                const registrations = await navigator.serviceWorker.getRegistrations();
                if (registrations.length > 0) {
                    showStatus(`⚠️ Found ${registrations.length} active Service Worker(s) - this is likely causing the caching issue`, 'warning');
                }
            }
            
            // Check if we came from a redirect with success
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('fixed')) {
                showStatus('✅ Cache was successfully cleared!', 'success');
            }
        });
    </script>
</body>
</html>