import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

// Disable Turbo snapshot cache for dynamic real-time property listings
if (Turbo.config && Turbo.config.drive) {
    Turbo.config.drive.cache.size = 0;
}

window.Turbo = Turbo;
window.Alpine = Alpine;
Alpine.start();

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((registration) => {
            // Check for service worker updates immediately to activate v2
            registration.update();
            console.log('PropertyKoi ServiceWorker registered successfully:', registration.scope);
        }).catch((error) => {
            console.log('PropertyKoi ServiceWorker registration failed:', error);
        });
    });
}

