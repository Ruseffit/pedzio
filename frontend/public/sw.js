/**
 * public/sw.js
 * Service Worker de Pedzio.
 *
 * Responsabilidades:
 *  1. Cachear el "app shell" básico para que la app abra offline.
 *  2. Estrategia network-first para todo lo demás (con fallback a caché).
 *  3. Recibir eventos push reales (enviados por
 *     backend/public_html/api/notificaciones/enviar.php a través de
 *     PushSenderService) y mostrarlos como notificación del sistema.
 *  4. Manejar el click en la notificación, llevando al usuario a la
 *     pantalla correspondiente (ej. /cliente/pedidos).
 *
 * NOTA: no cachea nunca /api/* — las respuestas de la API son datos vivos
 * (sesión, carrito, pedidos) y cachearlas causaría bugs de datos viejos.
 */

const CACHE_NAME = 'pedzio-cache-v2';
const APP_SHELL = [
  '/',
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)).catch(() => {
      // Si algún recurso del shell falla (ej. en dev sin esas rutas listas),
      // no se debe bloquear la instalación del SW.
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((nombres) =>
      Promise.all(
        nombres
          .filter((nombre) => nombre !== CACHE_NAME)
          .map((nombre) => caches.delete(nombre))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Nunca interceptar llamadas a la API PHP: siempre deben ir a la red.
  // Se usa includes() (no startsWith) porque la API puede estar servida
  // detrás de una ruta más larga según cómo se configuró el backend
  // (ej. http://localhost:8000/api/... o http://localhost/backend/public_html/api/...).
  if (url.pathname.includes('/api/') || event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((respuesta) => {
        const copia = respuesta.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copia));
        return respuesta;
      })
      .catch(() =>
        caches.match(event.request).then(
          (coincidencia) =>
            coincidencia ||
            new Response('Sin conexión y sin versión en caché.', {
              status: 503,
              statusText: 'Offline',
            })
        )
      )
  );
});

// ── Push real: PushSenderService manda JSON con { titulo, mensaje, url } ───
self.addEventListener('push', (event) => {
  let datos = { titulo: 'Pedzio', mensaje: 'Tienes una notificación nueva.', url: '/' };

  if (event.data) {
    try {
      datos = { ...datos, ...event.data.json() };
    } catch {
      datos.mensaje = event.data.text();
    }
  }

  event.waitUntil(
    self.registration.showNotification(datos.titulo, {
      body: datos.mensaje,
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      tag: datos.url || 'pedzio-notif',
      data: { url: datos.url || '/' },
      vibrate: [100, 50, 100],
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const cliente of clientList) {
        if (cliente.url.includes(url) && 'focus' in cliente) {
          return cliente.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
