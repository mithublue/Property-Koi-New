// Firebase Cloud Messaging & PWA Service Worker for Property Koi
// -------------------------------------------------------------

importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyDE_xFL36AlFB-3ZhhP6eia_4LCfGpUzc4",
    authDomain: "nuzul-d2161.firebaseapp.com",
    projectId: "nuzul-d2161",
    storageBucket: "nuzul-d2161.firebasestorage.app",
    messagingSenderId: "1057782250848",
    appId: "1:1057782250848:web:5b56f7becb5974e9877205",
    measurementId: "G-3KQD4KMHVG"
});

const messaging = firebase.messaging();

// Handle Background Messages (Runs when tabs are in background or browser is closed)
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] onBackgroundMessage received:', payload);

    const title = (payload.notification && payload.notification.title) 
        || (payload.data && payload.data.title) 
        || 'Property Koi';

    const body = (payload.notification && payload.notification.body) 
        || (payload.data && payload.data.body) 
        || '';

    const origin = self.location.origin;
    const isLocalhost = origin.includes('localhost') || origin.includes('127.0.0.1');
    let basePath = isLocalhost ? '/projects/real-estate/laravel-site/public' : '';
    if (!isLocalhost && self.location.pathname.startsWith('/public')) {
        basePath = '/public';
    }

    const icon = (payload.notification && payload.notification.icon)
        || (payload.data && payload.data.icon)
        || (origin + basePath + '/assets/icons/icon-192x192.png');

    const defaultTarget = (payload.data && payload.data.url) 
        || (payload.fcmOptions && payload.fcmOptions.link) 
        || (origin + basePath + '/');
    const targetUrl = defaultTarget;

    const options = {
        body: body,
        icon: icon,
        badge: icon,
        vibrate: [300, 100, 300, 100, 300],
        requireInteraction: true,
        tag: 'adm-fcm-' + encodeURIComponent(title),
        data: {
            url: targetUrl
        }
    };

    return self.registration.showNotification(title, options);
});

// Notification Click Handler: Focus existing tab or open target page
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const isLocalhost = origin.includes('localhost') || origin.includes('127.0.0.1');
    let basePath = isLocalhost ? '/projects/real-estate/laravel-site/public' : '';
    if (!isLocalhost && self.location.pathname.startsWith('/public')) {
        basePath = '/public';
    }
    const defaultTarget = origin + basePath + '/';
    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : defaultTarget;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if ('focus' in client) {
                    if ('navigate' in client) {
                        client.navigate(targetUrl);
                    }
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Lifecycle listeners
self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});
