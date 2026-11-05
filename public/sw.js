/*
 * Service worker do Ritmo (PWA).
 * - Páginas: rede primeiro; sem conexão, mostra /offline.html
 * - Arquivos do build (/build/), ícones e fontes: cache primeiro
 * - Nunca intercepta POST nem as chamadas do Livewire
 * - Push: mostra a notificação e, ao tocar, abre o link (marcando o aviso como lido)
 */
const VERSION = 'ritmo-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.pathname.startsWith('/livewire')) {
        return;
    }

    // Navegação: sempre tenta a rede (dados do treino precisam estar atualizados)
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const isStatic = url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')
        || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';

    if (isStatic) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok || response.type === 'opaque') {
                    const copy = response.clone();
                    caches.open(VERSION).then((cache) => cache.put(request, copy));
                }
                return response;
            }))
        );
    }
});

/* ---------- Push no celular ---------- */

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'Ritmo';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: payload.badge || '/icons/badge-96.png',
        tag: payload.tag,
        lang: payload.lang || 'pt-BR',
        data: payload.data || {},
        renotify: Boolean(payload.tag),
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL((event.notification.data && event.notification.data.url) || '/', self.location.origin);
    if (target.origin !== self.location.origin) {
        return;
    }

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        // Reaproveita uma janela do app que já esteja aberta
        const open = windows.find((client) => new URL(client.url).origin === target.origin);
        if (open) {
            try {
                await open.focus();
                return await open.navigate(target.href);
            } catch (error) {
                // janela não controlada pelo service worker: abre outra
            }
        }
        return self.clients.openWindow(target.href);
    })());
});
