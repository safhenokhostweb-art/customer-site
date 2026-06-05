/**
 * Service Worker — Ticket Push Notifications
 * Handles Web Push events and shows native browser notifications.
 * Works even when the browser tab is closed (as long as browser process is running).
 */

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

// ── Push event: fired by browser push service ────────────────────────────────
self.addEventListener('push', (e) => {
    if (!e.data) return;
    let payload = {};
    try { payload = e.data.json(); } catch (_) {
        payload = { title: 'اعلان جدید', body: e.data.text() };
    }
    const opts = {
        body:     payload.body    || '',
        icon:     payload.icon    || '/favicon.ico',
        badge:    '/favicon.ico',
        dir:      'rtl',
        lang:     'fa',
        tag:      'ticket-push',
        renotify: true,
        data:     { url: payload.url || '/' },
        actions:  [{ action: 'open', title: 'مشاهده' }],
    };
    e.waitUntil(self.registration.showNotification(payload.title || 'پشتیبانی', opts));
});

// ── Notification click: navigate to the relevant page ────────────────────────
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    if (e.action === 'dismiss') return;
    const url = e.notification.data?.url || '/';
    e.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const c of list) {
                if (c.url.startsWith(self.registration.scope) && 'focus' in c) {
                    c.navigate(url); return c.focus();
                }
            }
            if (self.clients.openWindow) return self.clients.openWindow(url);
        })
    );
});
