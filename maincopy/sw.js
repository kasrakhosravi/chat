'use strict';

const sb_cache_name = 'sb-pwa-cache';
const sb_start_page = 'admin.php';
const sb_offline_page = 'admin.php';
const sb_cache_files = [sb_start_page, sb_offline_page];
const sb_never_cache = [/\/wp-admin/, /\/wp-login/, /preview=true/, /\/uploads/];

self.addEventListener('install', function (e) {
    e.waitUntil(caches.open(sb_cache_name).then(function (cache) {
            sb_cache_files.map(function (url) {
                return cache.add(url).catch(function (reason) {
                    return console.log('PWA install error: ' + String(reason) + ' ' + url);
                });
            });
        })
    );
});

self.addEventListener('activate', function (e) {
    e.waitUntil(caches.keys().then(function (keyList) {
            return Promise.all(keyList.map(function (key) {
                if (key !== sb_cache_name) {
                    return caches.delete(key);
                }
            }));
        })
    );
    return self.clients.claim();
});

self.addEventListener('fetch', function (e) {

    if (!sb_never_cache.every(checkNeverCacheList, e.request.url)) {
        return;
    }

    if (!e.request.url.match(/^(http|https):\/\//i))
        return;

    if (new URL(e.request.url).origin !== location.origin)
        return;

    if (e.request.method !== 'GET') {
        e.respondWith(
            fetch(e.request).catch(function () {
                return caches.match(sb_offline_page);
            })
        );
        return;
    }

    if (e.request.mode === 'navigate' && navigator.onLine) {
        e.respondWith(
            fetch(e.request).then(function (response) {
                return caches.open(sb_cache_name).then(function (cache) {
                    cache.put(e.request, response.clone());
                    return response;
                });
            })
        );
        return;
    }

    e.respondWith(
        caches.match(e.request).then(function (response) {
            return response || fetch(e.request).then(function (response) {
                return caches.open(sb_cache_name).then(function (cache) {
                    cache.put(e.request, response.clone());
                    return response;
                });
            });
        }).catch(function () {
            return caches.match(sb_offline_page);
        })
    );
});

function checkNeverCacheList(url) {
    if (this.match(url)) {
        return false;
    }
    return true;
}
