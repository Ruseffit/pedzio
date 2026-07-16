'use client';

import { useState, useEffect, useCallback } from 'react';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
const VAPID_PUBLIC_KEY = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY || '';

/** Convierte la llave pública VAPID (base64url) al Uint8Array que pide pushManager.subscribe(). */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

async function llamarApi(path, body) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Error ${res.status}`);
  return data;
}

/**
 * Maneja todo el ciclo de vida de las notificaciones push del navegador:
 *
 *   const {
 *     soportado, permiso, suscrito, cargando, error,
 *     activar, desactivar,
 *   } = useNotifications();
 *
 * Flujo (el que pediste):
 *   1. activar() → pide permiso al navegador (Notification.requestPermission)
 *   2. si lo acepta → pushManager.subscribe() con la llave pública VAPID
 *   3. la suscripción se manda al backend, que la guarda en
 *      notificaciones_subscriptions (api/notificaciones/subscribir.php)
 *   4. cuando el emprendedor cambia el estado de un pedido, el backend
 *      (api/notificaciones/enviar.php) le manda el push a ese cliente.
 */
export function useNotifications() {
  const [soportado, setSoportado] = useState(false);
  const [permiso, setPermiso] = useState('default');
  const [suscrito, setSuscrito] = useState(false);
  const [cargando, setCargando] = useState(false);
  const [error, setError] = useState('');

  const revisarEstadoActual = useCallback(async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      setSoportado(false);
      return;
    }
    setSoportado(true);
    setPermiso(Notification.permission);

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();
      setSuscrito(!!subscription);
    } catch {
      setSuscrito(false);
    }
  }, []);

  useEffect(() => {
    revisarEstadoActual();
  }, [revisarEstadoActual]);

  const activar = useCallback(async () => {
    setError('');

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      setError('Este navegador no soporta notificaciones push.');
      return false;
    }

    if (!VAPID_PUBLIC_KEY) {
      setError('Falta configurar NEXT_PUBLIC_VAPID_PUBLIC_KEY en el frontend.');
      return false;
    }

    setCargando(true);
    try {
      // 1. Cliente acepta notificaciones
      const resultadoPermiso = await Notification.requestPermission();
      setPermiso(resultadoPermiso);

      if (resultadoPermiso !== 'granted') {
        setError('No se otorgó el permiso de notificaciones.');
        return false;
      }

      const registration = await navigator.serviceWorker.register('/sw.js');
      await navigator.serviceWorker.ready;

      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
        });
      }

      // 2. Backend guarda subscription
      const json = subscription.toJSON();
      await llamarApi('/notificaciones/subscribir.php', {
        accion: 'subscribir',
        endpoint: json.endpoint,
        keys: json.keys,
      });

      setSuscrito(true);
      return true;
    } catch (err) {
      setError(err.message || 'No se pudo activar las notificaciones.');
      return false;
    } finally {
      setCargando(false);
    }
  }, []);

  const desactivar = useCallback(async () => {
    setError('');
    setCargando(true);
    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();

      if (subscription) {
        const endpoint = subscription.endpoint;
        await subscription.unsubscribe();
        await llamarApi('/notificaciones/subscribir.php', { accion: 'desuscribir', endpoint });
      }

      setSuscrito(false);
      return true;
    } catch (err) {
      setError(err.message || 'No se pudo desactivar las notificaciones.');
      return false;
    } finally {
      setCargando(false);
    }
  }, []);

  return { soportado, permiso, suscrito, cargando, error, activar, desactivar };
}
