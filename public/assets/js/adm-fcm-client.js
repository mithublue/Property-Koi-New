/**
 * Firebase Cloud Messaging & PWA Client for Property Koi (Laravel)
 */
(function() {
    'use strict';

    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        console.warn('[FCM] Service Worker or Notifications not supported in this browser.');
        return;
    }

    const firebaseConfig = (window.admVars && window.admVars.firebase) ? window.admVars.firebase : {
        apiKey: "AIzaSyDE_xFL36AlFB-3ZhhP6eia_4LCfGpUzc4",
        authDomain: "nuzul-d2161.firebaseapp.com",
        projectId: "nuzul-d2161",
        storageBucket: "nuzul-d2161.firebasestorage.app",
        messagingSenderId: "1057782250848",
        appId: "1:1057782250848:web:5b56f7becb5974e9877205",
        measurementId: "G-3KQD4KMHVG"
    };

    const VAPID_KEY = (window.admVars && window.admVars.vapidKey)
        ? window.admVars.vapidKey
        : 'BJcfKVomEe-5fyPcnaLttqggMH-kzlwXR-XJ823qehOdwW6elZu3rUEq2jn54Hq7iKQ7ZfPnvIZVArzLaulxYSY';

    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    let localPrefix = isLocalhost ? '/projects/real-estate/laravel-site/public' : '';
    if (!isLocalhost && window.location.pathname.startsWith('/public')) {
        localPrefix = '/public';
    }

    function getSwPath() {
        if (window.admVars && window.admVars.swUrl) return window.admVars.swUrl;
        return window.location.origin + localPrefix + '/firebase-messaging-sw.js';
    }

    function getRegisterUrl() {
        if (window.admVars && window.admVars.registerTokenUrl) return window.admVars.registerTokenUrl;
        return window.location.origin + localPrefix + '/api/fcm/register-token';
    }

    function getScope() {
        if (window.admVars && window.admVars.scope) return window.admVars.scope;
        return window.location.origin + localPrefix + '/';
    }

    function getIconUrl() {
        if (window.admVars && window.admVars.baseUrl) {
            return window.admVars.baseUrl.replace(/\/+$/, '') + '/assets/icons/icon-192x192.png';
        }
        return window.location.origin + localPrefix + '/assets/icons/icon-192x192.png';
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                return resolve();
            }
            const s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) {}
    }

    function showInAppToast(title, body, url) {
        playNotificationSound();

        if (!document.getElementById('adm-toast-keyframes')) {
            const style = document.createElement('style');
            style.id = 'adm-toast-keyframes';
            style.innerHTML = `
                @keyframes admToastSlideUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);
        }

        const toast = document.createElement('div');
        toast.className = 'adm-fcm-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999999;background:#0f172a;color:#f8fafc;padding:14px 18px;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,0.35);border:1px solid #10b981;max-width:360px;font-family:inherit;display:flex;align-items:flex-start;gap:12px;cursor:pointer;transition:all 0.3s ease;animation:admToastSlideUp 0.3s ease forwards;';

        toast.innerHTML = `
            <div style="width:36px;height:36px;border-radius:10px;background:#10b981;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                🔔
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:13px;color:#fff;line-height:1.3;margin-bottom:2px;">${title}</div>
                <div style="font-size:11px;color:#94a3b8;line-height:1.4;">${body}</div>
            </div>
            <button type="button" style="background:none;border:none;color:#64748b;font-size:18px;cursor:pointer;padding:0;line-height:1;" onclick="event.stopPropagation();this.closest('.adm-fcm-toast').remove();">&times;</button>
        `;

        toast.addEventListener('click', () => {
            if (url) {
                window.location.href = url;
            }
        });

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 7000);
    }

    function showNotificationPermissionPrompt() {
        if (!('Notification' in window) || Notification.permission !== 'default') {
            return;
        }

        // Avoid showing if user already dismissed or registered
        if (sessionStorage.getItem('adm_fcm_prompt_dismissed') || localStorage.getItem('adm_fcm_prompt_dismissed') || localStorage.getItem('adm_fcm_registered_token')) {
            return;
        }

        if (document.getElementById('adm-fcm-prompt-banner')) {
            return;
        }

        const isAdmin = window.admVars && window.admVars.isAdmin;
        const isAgent = window.admVars && window.admVars.isAgent;

        let promptTitle = '🔔 পুশ নোটিফিকেশন';
        let promptDesc  = 'নতুন প্রপার্টি লিস্টিং, দর প্রস্তাব ও দাম কমার তাৎক্ষণিক নোটিফিকেশন পান।';

        if (isAdmin) {
            promptTitle = 'অ্যাডমিন পুশ নোটিফিকেশন';
            promptDesc  = 'নতুন প্রপার্টি সাবমিট, সাইট ভিজিট ও কাস্টমার লিডের তাৎক্ষণিক অ্যালার্ট পান।';
        } else if (isAgent) {
            promptTitle = 'এজেন্ট পুশ নোটিফিকেশন';
            promptDesc  = 'আপনার প্রপার্টির নতুন অফার, সাইট ভিজিট ও মেসেজের তাৎক্ষণিক অ্যালার্ট পান।';
        }

        if (!document.getElementById('adm-fcm-keyframes')) {
            const style = document.createElement('style');
            style.id = 'adm-fcm-keyframes';
            style.innerHTML = `
                @keyframes admBannerSlideIn {
                    from { opacity: 0; transform: translateY(-20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @media (max-width: 640px) {
                    #adm-fcm-prompt-banner {
                        top: auto !important;
                        bottom: 20px !important;
                        left: 12px !important;
                        right: 12px !important;
                        max-width: none !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        const banner = document.createElement('div');
        banner.id = 'adm-fcm-prompt-banner';
        banner.style.cssText = 'position:fixed;top:24px;right:20px;z-index:999999;background:#064e3b;color:#ecfdf5;padding:14px 18px;border-radius:18px;box-shadow:0 12px 35px rgba(0,0,0,0.3);border:1px solid #10b981;display:flex;align-items:center;gap:12px;font-size:12px;font-family:inherit;max-width:400px;animation:admBannerSlideIn 0.4s ease forwards;';

        banner.innerHTML = `
            <div style="width:36px;height:36px;border-radius:10px;background:#10b981;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">
                🔔
            </div>
            <div style="flex:1;min-width:0;">
                <strong style="display:block;font-size:13px;color:#fff;line-height:1.3;margin-bottom:2px;">${promptTitle}</strong>
                <div style="font-size:11px;color:#a7f3d0;line-height:1.4;">${promptDesc}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <button type="button" id="adm-enable-push-btn" style="background:#10b981;color:#fff;border:none;padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;font-size:12px;white-space:nowrap;transition:background 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.15);">চালু করুন</button>
                <button type="button" id="adm-dismiss-push-btn" style="background:none;border:none;color:#6ee7b7;cursor:pointer;font-size:20px;line-height:1;padding:4px;" title="বন্ধ করুন">&times;</button>
            </div>
        `;

        document.body.appendChild(banner);

        document.getElementById('adm-enable-push-btn').addEventListener('click', async function() {
            sessionStorage.setItem('adm_fcm_prompt_dismissed', '1');
            localStorage.setItem('adm_fcm_prompt_dismissed', '1');
            banner.remove();
            await window.admRequestPushPermission();
        });

        document.getElementById('adm-dismiss-push-btn').addEventListener('click', function() {
            sessionStorage.setItem('adm_fcm_prompt_dismissed', '1');
            localStorage.setItem('adm_fcm_prompt_dismissed', '1');
            banner.remove();
        });
    }

    async function saveTokenToBackend(token, callback) {
        if (!token) return;
        const registerUrl = getRegisterUrl();
        const csrfToken = (window.admVars && window.admVars.csrfToken)
            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || '';

        try {
            const res = await fetch(registerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ token: token })
            });
            const data = await res.json();
            if (data.success) {
                localStorage.setItem('adm_fcm_registered_token', token);
                sessionStorage.setItem('adm_fcm_synced_session', '1');
                console.log('[FCM] Token registered to Laravel backend successfully!');
                if (typeof callback === 'function') callback(data);
            }
        } catch (err) {
            console.warn('[FCM] Failed to save token to backend:', err);
        }
    }

    window.admShowInAppToast = showInAppToast;

    async function getActiveRegistration() {
        const SW_PATH = getSwPath();
        const SW_SCOPE = getScope();
        let registration = await navigator.serviceWorker.getRegistration(SW_SCOPE);
        if (!registration) {
            registration = await navigator.serviceWorker.register(SW_PATH, { scope: SW_SCOPE });
            console.log('[FCM] ServiceWorker registered with scope:', SW_SCOPE);
        }
        try {
            registration.update().catch(() => {});
        } catch (e) {}

        try {
            return await navigator.serviceWorker.ready;
        } catch (e) {
            return registration;
        }
    }

    async function initFCM() {
        try {
            const registration = await getActiveRegistration();

            await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
            await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

            if (!window.firebase.apps.length) {
                window.firebase.initializeApp(firebaseConfig);
            }

            const messaging = window.firebase.messaging();

            // Foreground message listener: show both native browser notification and in-app toast
            messaging.onMessage(async function(payload) {
                console.log('[FCM] Foreground message received:', payload);
                const title = payload.notification?.title || payload.data?.title || 'Property Koi';
                const body  = payload.notification?.body  || payload.data?.body  || '';
                const url   = payload.data?.url || payload.fcmOptions?.link || '';

                showInAppToast(title, body, url);

                // Display native OS/browser push notification banner
                if (Notification.permission === 'granted') {
                    try {
                        const iconUrl = getIconUrl();
                        const activeReg = await getActiveRegistration();
                        if (activeReg && activeReg.showNotification) {
                            activeReg.showNotification(title, {
                                body: body,
                                icon: iconUrl,
                                badge: iconUrl,
                                data: { url: url }
                            });
                        } else {
                            const n = new Notification(title, {
                                body: body,
                                icon: iconUrl,
                                data: { url: url }
                            });
                            n.onclick = function() {
                                window.focus();
                                if (url) window.location.href = url;
                            };
                        }
                    } catch (e) {
                        console.warn('[FCM] Native notification notice:', e);
                    }
                }
            });

            // If permission is already granted, verify/sync token
            if (Notification.permission === 'granted') {
                const currentToken = await messaging.getToken({
                    vapidKey: VAPID_KEY,
                    serviceWorkerRegistration: registration
                });

                if (currentToken) {
                    const savedToken = localStorage.getItem('adm_fcm_registered_token');
                    const isSessionSynced = sessionStorage.getItem('adm_fcm_synced_session') === '1';

                    if (savedToken !== currentToken || !isSessionSynced) {
                        saveTokenToBackend(currentToken, () => {
                            const badge = document.querySelector('.adm-fcm-device-badge');
                            if (badge && (badge.textContent.includes('0টি') || badge.textContent.includes('০টি'))) {
                                badge.textContent = '📱 ১টি ডিভাইস সংযুক্ত';
                            }
                        });
                    }
                }
            } else if (Notification.permission === 'default') {
                // If user hasn't chosen yet, display prompt popup banner after 2.5 seconds
                setTimeout(showNotificationPermissionPrompt, 2500);
            }
        } catch (err) {
            console.warn('[FCM] Initialization notice:', err);
        }
    }

    window.admEnsureTokenSynced = async function() {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return false;
        }
        try {
            await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
            await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');
            if (!window.firebase.apps.length) {
                window.firebase.initializeApp(firebaseConfig);
            }

            const registration = await getActiveRegistration();
            const messaging = window.firebase.messaging();
            const token = await messaging.getToken({
                vapidKey: VAPID_KEY,
                serviceWorkerRegistration: registration
            });
            if (token) {
                return new Promise((resolve) => {
                    saveTokenToBackend(token, () => resolve(true));
                });
            }
        } catch (e) {
            console.warn('[FCM] ensureTokenSynced notice:', e);
        }
        return false;
    };

    window.admRequestPushPermission = async function() {
        try {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
                await loadScript('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');
                if (!window.firebase.apps.length) {
                    window.firebase.initializeApp(firebaseConfig);
                }

                const registration = await getActiveRegistration();
                const messaging = window.firebase.messaging();
                const token = await messaging.getToken({
                    vapidKey: VAPID_KEY,
                    serviceWorkerRegistration: registration
                });
                if (token) {
                    saveTokenToBackend(token, () => {
                        showInAppToast('ধন্যবাদ!', 'পুশ নোটিফিকেশন সফলভাবে চালু হয়েছে এবং ডিভাইসটি সংযুক্ত হয়েছে।', '');
                        const badge = document.querySelector('.adm-fcm-device-badge');
                        if (badge) {
                            badge.textContent = '📱 ১টি ডিভাইস সংযুক্ত';
                        }
                        if (window.location.search.includes('notifications')) {
                            window.location.reload();
                        }
                    });
                } else {
                    alert('টোকেন সংগ্রহ করা যায়নি। ব্রাউজার রিফ্রেশ করে আবার চেষ্টা করুন।');
                }
            } else {
                alert('নোটিফিকেশন পারমিশন দেওয়া হয়নি। ব্রাউজার সাইট সেটিংস থেকে নোটিফিকেশন Allow করুন।');
            }
        } catch (e) {
            console.error('[FCM] Permission request error:', e);
            alert('নোটিফিকেশন চালু করতে সমস্যা হয়েছে: ' + (e.message || e));
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFCM);
    } else {
        initFCM();
    }
})();
