import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

window.Turbo = Turbo;
window.Alpine = Alpine;
Alpine.start();

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((registration) => {
            console.log('PropertyKoi ServiceWorker registered successfully:', registration.scope);
        }).catch((error) => {
            console.log('PropertyKoi ServiceWorker registration failed:', error);
        });
    });
}
