# KhaboonForum PWA Testing Checklist

## Before Testing
- [ ] Run deployment script: `./deploy-pwa-fix.sh`
- [ ] Clear browser cache and Service Worker registrations
- [ ] Open browser DevTools (F12) → Console tab

## Phase 1: Basic Functionality

### Service Worker Registration
- [ ] Load `index.php` in browser
- [ ] Check console for: "Service Worker registered successfully"
- [ ] Verify no JavaScript errors in console
- [ ] Check Application → Service Workers in DevTools:
  - [ ] Service Worker shows as "activated and running"
  - [ ] Scope is correct (site root)

### Page Loading
- [ ] Posts load immediately on `index.php`
- [ ] No need for Shift+Refresh to see posts
- [ ] Click on a post → loads `post.php` with comments
- [ ] Click "Back to All Posts" → returns to `index.php`
- [ ] Navigation works without page reloads (if using AJAX)

## Phase 2: Image Handling

### Image Upload
- [ ] Go to `create.php`
- [ ] Create a new post with an image (JPG/PNG under 5MB)
- [ ] Verify post creates successfully
- [ ] Image displays on the post page
- [ ] Image URL points to `/uploads/` directory

### Image Caching
- [ ] View the post with image
- [ ] Check console for: "Image loaded successfully"
- [ ] Check console for: "Uploaded image - should be available offline"
- [ ] Go to Application → Cache Storage in DevTools:
  - [ ] Find `khaboonforum-pwa-v2` cache
  - [ ] Verify image is cached (should appear in cache list)

### Image Offline Access
- [ ] Go offline (DevTools → Network → Offline)
- [ ] Refresh the post page
- [ ] Verify image still loads (from cache)
- [ ] If image fails, check for offline placeholder/message

## Phase 3: PWA Features

### Installability
- [ ] Check for install button (bottom-right corner)
- [ ] Click install button (if shown)
- [ ] Follow PWA installation prompts
- [ ] Verify app installs successfully
- [ ] Launch installed PWA
- [ ] Verify it opens in standalone mode

### Standalone Mode
- [ ] App has no browser UI (address bar, etc.)
- [ ] Navigation works within app
- [ ] Back button (if any) works correctly
- [ ] App icon shows correctly (emoji 💬)

### Offline Functionality
- [ ] Go offline
- [ ] Navigate to previously visited pages
- [ ] Static assets (CSS, JS) load from cache
- [ ] Previously viewed images load from cache
- [ ] Offline page shows for unvisited pages
- [ ] Online/offline detection works (UI updates)

## Phase 4: Caching Strategy Verification

### Dynamic Pages (NOT Cached)
- [ ] Load `index.php` (network tab shows fresh request)
- [ ] Load `post.php?id=X` (network tab shows fresh request)
- [ ] Check Cache Storage: No PHP pages should be cached
- [ ] Verify new posts/comments appear immediately

### Static Assets (Cached)
- [ ] Load page → CSS/JS load from cache (fast)
- [ ] Go offline → CSS/JS still load
- [ ] Check Cache Storage: CSS/JS files should be cached

### Uploaded Images (Cached)
- [ ] First load: Image loads from network, gets cached
- [ ] Subsequent loads: Image loads from cache (fast)
- [ ] Offline: Image loads from cache
- [ ] Check Cache Storage: Images should be cached

## Phase 5: Performance

### Load Times
- [ ] First visit: Acceptable load time (under 3 seconds)
- [ ] Subsequent visits: Faster load (cached assets)
- [ ] Image loading: Progressive/lazy loading works

### Memory Usage
- [ ] Check memory usage in DevTools → Memory
- [ ] Cache size reasonable (under 50MB for typical usage)
- [ ] No memory leaks during navigation

## Phase 6: Cross-Browser Testing

### Chrome/Chromium
- [ ] All tests pass
- [ ] PWA installation works
- [ ] Service Worker functions correctly

### Firefox
- [ ] Basic functionality works
- [ ] Service Worker works
- [ ] Image caching works
- [ ] PWA installation (if supported)

### Safari (if applicable)
- [ ] Basic functionality works
- [ ] Service Worker support (iOS 11.3+)
- [ ] Image display works

## Phase 7: Edge Cases

### Large Images
- [ ] Upload image > 5MB → should be rejected
- [ ] Upload valid large image → should work
- [ ] Verify caching works for large images

### Multiple Images
- [ ] Create post with multiple images (if supported)
- [ ] Verify all images cache correctly
- [ ] Verify offline access for all images

### Cache Cleanup
- [ ] Force Service Worker update (change sw.js)
- [ ] Verify old cache is cleaned up
- [ ] Verify new cache is created

### Network Issues
- [ ] Slow network simulation (DevTools → Network → Slow 3G)
- [ ] Verify page still loads (maybe with degraded experience)
- [ ] Images load progressively

## Testing Results Summary

### Passed Tests:
- [ ] Service Worker registration
- [ ] Dynamic page loading (no caching issues)
- [ ] Image upload and display
- [ ] Image caching for offline
- [ ] PWA installation
- [ ] Offline functionality
- [ ] Performance acceptable
- [ ] Cross-browser compatibility

### Issues Found:
1. 
2. 
3. 

### Recommendations:
1. 
2. 
3. 

## Troubleshooting Common Issues

### Issue: Service Worker not registering
**Solution:**
1. Check console for errors
2. Verify `sw.js` file exists and is accessible
3. Clear all Service Worker registrations
4. Check HTTPS requirement (if applicable)

### Issue: Images not caching
**Solution:**
1. Check Service Worker fetch events in console
2. Verify image URLs are correct
3. Check CORS headers on image responses
4. Verify cache storage has space

### Issue: Posts not showing (caching returns)
**Solution:**
1. Verify PHP pages have `Cache-Control: no-cache` headers
2. Check Service Worker fetch strategy for PHP pages
3. Clear all caches (browser + Service Worker)

### Issue: PWA install prompt not showing
**Solution:**
1. Site must be served over HTTPS (required)
2. User must engage with site (click/tap)
3. Check `manifest.json` is valid and linked
4. Meet browser-specific criteria

## Test Environment
- **Browser:** 
- **Version:** 
- **OS:** 
- **Device:** 
- **Network:** 
- **Test Date:** 

## Tester:
- **Name:** 
- **Notes:** 

## Sign-off
- [ ] All critical tests passed
- [ ] Major issues resolved
- [ ] Ready for production deployment
- [ ] Documentation updated

**Approved by:** _________________________
**Date:** _________________________