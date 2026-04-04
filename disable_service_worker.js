/**
 * Script to disable Service Worker and clear caches
 * Run this in browser console or include in pages
 */

const CacheCleaner = {
    // Unregister all Service Workers
    async unregisterServiceWorkers() {
        if (!('serviceWorker' in navigator)) {
            console.log('Service Workers not supported');
            return { success: false, message: 'Service Workers not supported' };
        }
        
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            console.log(`Found ${registrations.length} Service Worker registration(s)`);
            
            for (const registration of registrations) {
                console.log(`Unregistering: ${registration.scope}`);
                const success = await registration.unregister();
                if (success) {
                    console.log(`Successfully unregistered: ${registration.scope}`);
                } else {
                    console.log(`Failed to unregister: ${registration.scope}`);
                }
            }
            
            return { 
                success: true, 
                message: `Unregistered ${registrations.length} Service Worker(s)` 
            };
        } catch (error) {
            console.error('Error unregistering Service Workers:', error);
            return { success: false, message: `Error: ${error.message}` };
        }
    },
    
    // Clear all caches
    async clearAllCaches() {
        if (!('caches' in window)) {
            console.log('Cache API not supported');
            return { success: false, message: 'Cache API not supported' };
        }
        
        try {
            const cacheNames = await caches.keys();
            console.log(`Found ${cacheNames.length} cache(s)`);
            
            for (const cacheName of cacheNames) {
                console.log(`Deleting cache: ${cacheName}`);
                await caches.delete(cacheName);
            }
            
            return { 
                success: true, 
                message: `Cleared ${cacheNames.length} cache(s)` 
            };
        } catch (error) {
            console.error('Error clearing caches:', error);
            return { success: false, message: `Error: ${error.message}` };
        }
    },
    
    // Clear localStorage and sessionStorage
    clearLocalStorage() {
        try {
            const localStorageCount = localStorage.length;
            const sessionStorageCount = sessionStorage.length;
            
            localStorage.clear();
            sessionStorage.clear();
            
            console.log(`Cleared ${localStorageCount} localStorage items`);
            console.log(`Cleared ${sessionStorageCount} sessionStorage items`);
            
            return { 
                success: true, 
                message: `Cleared ${localStorageCount} localStorage and ${sessionStorageCount} sessionStorage items` 
            };
        } catch (error) {
            console.error('Error clearing storage:', error);
            return { success: false, message: `Error: ${error.message}` };
        }
    },
    
    // Clear IndexedDB databases
    async clearIndexedDB() {
        if (!window.indexedDB) {
            console.log('IndexedDB not supported');
            return { success: false, message: 'IndexedDB not supported' };
        }
        
        try {
            // Note: This is a simplified approach
            // In a real app, you'd need to know database names
            const dbs = await indexedDB.databases ? await indexedDB.databases() : [];
            console.log(`Found ${dbs.length} IndexedDB database(s)`);
            
            for (const db of dbs) {
                try {
                    await indexedDB.deleteDatabase(db.name);
                    console.log(`Deleted database: ${db.name}`);
                } catch (error) {
                    console.log(`Could not delete database ${db.name}:`, error);
                }
            }
            
            return { 
                success: true, 
                message: `Cleared ${dbs.length} IndexedDB database(s)` 
            };
        } catch (error) {
            console.error('Error clearing IndexedDB:', error);
            return { success: false, message: `Error: ${error.message}` };
        }
    },
    
    // Perform all cleanup operations
    async cleanupAll() {
        console.log('=== Starting Cache Cleanup ===');
        
        const results = {
            serviceWorkers: await this.unregisterServiceWorkers(),
            caches: await this.clearAllCaches(),
            localStorage: this.clearLocalStorage(),
            indexedDB: await this.clearIndexedDB()
        };
        
        console.log('=== Cache Cleanup Complete ===');
        console.log('Results:', results);
        
        return results;
    },
    
    // Reload page after cleanup
    async cleanupAndReload() {
        const results = await this.cleanupAll();
        
        // Show results to user
        let message = 'Cache cleanup complete:\n';
        Object.entries(results).forEach(([key, result]) => {
            message += `\n${key}: ${result.message}`;
        });
        
        alert(message + '\n\nPage will now reload.');
        
        // Force reload (bypass cache)
        window.location.reload(true);
    },
    
    // Add cleanup button to page
    addCleanupButton() {
        // Remove existing button if present
        const existingButton = document.getElementById('cache-cleanup-button');
        if (existingButton) {
            existingButton.remove();
        }
        
        // Create button
        const button = document.createElement('button');
        button.id = 'cache-cleanup-button';
        button.innerHTML = '🔄 Clear All Caches';
        button.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 15px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        
        button.addEventListener('click', () => {
            if (confirm('Clear ALL browser caches and Service Workers?\n\nThis will log you out of some websites.')) {
                this.cleanupAndReload();
            }
        });
        
        document.body.appendChild(button);
        console.log('Cache cleanup button added to page');
    },
    
    // Auto-cleanup on page load (optional)
    autoCleanupOnLoad() {
        document.addEventListener('DOMContentLoaded', () => {
            // Check if we should auto-cleanup
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('clearcache')) {
                console.log('Auto-cache-cleanup triggered by URL parameter');
                this.cleanupAndReload();
            }
            
            // Add cleanup button
            this.addCleanupButton();
            
            // Check for Service Worker
            this.checkServiceWorkerStatus();
        });
    },
    
    // Check and report Service Worker status
    async checkServiceWorkerStatus() {
        if (!('serviceWorker' in navigator)) {
            return;
        }
        
        const registrations = await navigator.serviceWorker.getRegistrations();
        if (registrations.length > 0) {
            console.warn('⚠️ Service Worker is active. This may cause caching issues.');
            
            // Show warning to user
            const warning = document.createElement('div');
            warning.innerHTML = `
                <div style="
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #f59e0b;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    z-index: 9998;
                    font-family: sans-serif;
                    font-size: 14px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    max-width: 80%;
                    text-align: center;
                ">
                    ⚠️ Service Worker may be caching pages. 
                    <button onclick="CacheCleaner.cleanupAndReload()" style="
                        margin-left: 10px;
                        background: white;
                        color: #f59e0b;
                        border: none;
                        padding: 5px 10px;
                        border-radius: 3px;
                        cursor: pointer;
                    ">
                        Clear Cache
                    </button>
                </div>
            `;
            document.body.appendChild(warning);
            
            // Auto-remove warning after 10 seconds
            setTimeout(() => {
                warning.style.opacity = '0';
                warning.style.transition = 'opacity 1s';
                setTimeout(() => warning.remove(), 1000);
            }, 10000);
        }
    }
};

// Make it available globally
if (typeof window !== 'undefined') {
    window.CacheCleaner = CacheCleaner;
}

// Auto-initialize if this script is included in page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        CacheCleaner.autoCleanupOnLoad();
    });
} else {
    CacheCleaner.autoCleanupOnLoad();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CacheCleaner;
}

console.log('CacheCleaner loaded. Use CacheCleaner.cleanupAndReload() to clear caches.');