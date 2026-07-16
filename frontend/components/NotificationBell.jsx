'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { apiExtra } from '@/lib/apiExtra';

const INTERVALO_POLLING_MS = 30000;

function formatearRelativo(fechaISO) {
  const fecha = new Date(fechaISO.replace(' ', 'T'));
  const diffMs = Date.now() - fecha.getTime();
  const minutos = Math.floor(diffMs / 60000);
  if (minutos < 1) return 'ahora';
  if (minutos < 60) return `hace ${minutos} min`;
  const horas = Math.floor(minutos / 60);
  if (horas < 24) return `hace ${horas} h`;
  const dias = Math.floor(horas / 24);
  return `hace ${dias} d`;
}

/**
 * Campana de notificaciones. Hace polling al backend cada 30s y:
 *  - muestra el contador de no leídas
 *  - lista las últimas notificaciones en un dropdown
 *  - si el navegador tiene permiso de notificaciones, muestra también
 *    una notificación nativa del sistema para lo que llegó nuevo desde
 *    el último polling (esto es lo que da el efecto "push" sin depender
 *    de un servidor push externo).
 */
export default function NotificationBell() {
  const router = useRouter();
  const [abierto, setAbierto] = useState(false);
  const [notificaciones, setNotificaciones] = useState([]);
  const [noLeidas, setNoLeidas] = useState(0);
  const [permiso, setPermiso] = useState('default');
  const contenedorRef = useRef(null);
  const idsConocidosRef = useRef(new Set());
  const primeraCargaRef = useRef(true);

  const mostrarNotificacionNativa = useCallback((n) => {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;

    const mostrar = (registration) => {
      const opciones = {
        body: n.mensaje || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: `pedzio-notif-${n.id}`,
        data: { url: n.url || '/' },
      };
      if (registration) {
        registration.showNotification(n.titulo, opciones);
      } else {
        new Notification(n.titulo, opciones);
      }
    };

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.ready.then(mostrar).catch(() => mostrar(null));
    } else {
      mostrar(null);
    }
  }, []);

  const cargar = useCallback(async () => {
    try {
      const data = await apiExtra.getNotificaciones({ limite: 15 });
      setNotificaciones(data.notificaciones);
      setNoLeidas(data.no_leidas);

      if (!primeraCargaRef.current) {
        const nuevas = data.notificaciones.filter((n) => !idsConocidosRef.current.has(n.id));
        nuevas.forEach(mostrarNotificacionNativa);
      }
      data.notificaciones.forEach((n) => idsConocidosRef.current.add(n.id));
      primeraCargaRef.current = false;
    } catch {
      // El polling falla en silencio: no queremos interrumpir al usuario
      // por una notificación que no cargó, solo lo reintentamos luego.
    }
  }, [mostrarNotificacionNativa]);

  useEffect(() => {
    if (typeof window !== 'undefined' && 'Notification' in window) {
      setPermiso(Notification.permission);
    }
    cargar();
    const intervalo = setInterval(cargar, INTERVALO_POLLING_MS);
    return () => clearInterval(intervalo);
  }, [cargar]);

  useEffect(() => {
    function alHacerClickFuera(e) {
      if (contenedorRef.current && !contenedorRef.current.contains(e.target)) {
        setAbierto(false);
      }
    }
    document.addEventListener('mousedown', alHacerClickFuera);
    return () => document.removeEventListener('mousedown', alHacerClickFuera);
  }, []);

  async function pedirPermiso() {
    if (typeof window === 'undefined' || !('Notification' in window)) return;
    const resultado = await Notification.requestPermission();
    setPermiso(resultado);
  }

  async function alHacerClickNotificacion(n) {
    if (!n.leido) {
      try {
        await apiExtra.marcarNotificacionLeida(n.id);
        setNotificaciones((prev) => prev.map((x) => (x.id === n.id ? { ...x, leido: true } : x)));
        setNoLeidas((prev) => Math.max(0, prev - 1));
      } catch {
        // si falla el marcado, igual navegamos
      }
    }
    setAbierto(false);
    if (n.url) router.push(n.url);
  }

  async function marcarTodasLeidas() {
    try {
      await apiExtra.marcarTodasLeidas();
      setNotificaciones((prev) => prev.map((n) => ({ ...n, leido: true })));
      setNoLeidas(0);
    } catch {
      // sin acción visible; el próximo polling reintentará el estado real
    }
  }

  return (
    <div className="relative" ref={contenedorRef}>
      <button
        onClick={() => setAbierto((v) => !v)}
        className="relative w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-light transition"
        aria-label="Notificaciones"
      >
        <span className="text-lg">🔔</span>
        {noLeidas > 0 && (
          <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-red text-white text-[10px] font-bold rounded-full flex items-center justify-center">
            {noLeidas > 9 ? '9+' : noLeidas}
          </span>
        )}
      </button>

      {abierto && (
        <div className="absolute right-0 mt-2 w-80 bg-white rounded-DEFAULT shadow-card-lg border border-gray-border z-50 overflow-hidden animate-slideIn">
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-border">
            <p className="font-semibold text-dark text-sm">Notificaciones</p>
            {noLeidas > 0 && (
              <button onClick={marcarTodasLeidas} className="text-xs text-orange font-medium hover:underline">
                Marcar todas como leídas
              </button>
            )}
          </div>

          {permiso === 'default' && (
            <div className="px-4 py-3 bg-orange-light flex items-center justify-between gap-3 border-b border-gray-border">
              <p className="text-xs text-dark">Activa avisos en tu navegador para no perderte pedidos nuevos.</p>
              <button
                onClick={pedirPermiso}
                className="text-xs bg-orange text-white font-medium rounded px-2 py-1 flex-shrink-0 hover:opacity-90"
              >
                Activar
              </button>
            </div>
          )}

          <div className="max-h-96 overflow-y-auto">
            {notificaciones.length === 0 && (
              <p className="text-gray text-sm text-center py-8 px-4">No tienes notificaciones todavía.</p>
            )}
            {notificaciones.map((n) => (
              <button
                key={n.id}
                onClick={() => alHacerClickNotificacion(n)}
                className={`w-full text-left px-4 py-3 border-b border-gray-border last:border-0 hover:bg-gray-light transition flex gap-2 ${
                  !n.leido ? 'bg-orange-light/40' : ''
                }`}
              >
                {!n.leido && <span className="w-2 h-2 mt-1.5 rounded-full bg-orange flex-shrink-0" />}
                <div className={n.leido ? 'pl-4' : ''}>
                  <p className="text-sm font-medium text-dark">{n.titulo}</p>
                  {n.mensaje && <p className="text-xs text-gray mt-0.5">{n.mensaje}</p>}
                  <p className="text-[10px] text-gray mt-1">{formatearRelativo(n.creado_en)}</p>
                </div>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
