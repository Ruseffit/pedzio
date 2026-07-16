/**
 * lib/apiExtra.js
 *
 * Cliente HTTP para los NUEVOS endpoints de Pedzio (configuración,
 * dashboard, notificaciones, push). Es un archivo separado de lib/api.js
 * a propósito, para no tocar ese archivo que ya funciona.
 *
 * Usa exactamente el mismo patrón que lib/api.js: fetch con
 * credentials: 'include' (cookie de sesión PHP) y manejo de errores
 * consistente con { error: "..." } del backend.
 */

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

async function request(path, options = {}) {
  const url = `${API_BASE}${path}`;

  let res;
  try {
    res = await fetch(url, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    });
  } catch (err) {
    throw new Error(
      `No se pudo conectar con la API en ${API_BASE}. Verifica que el backend ` +
      `esté corriendo y que su config.php permita el origen de este frontend (CORS). ` +
      `Detalle original: ${err.message}`
    );
  }

  let data;
  try {
    data = await res.json();
  } catch {
    throw new Error(`Error ${res.status}: respuesta inesperada del servidor.`);
  }

  if (!res.ok) {
    throw new Error(data?.error || `Error ${res.status}`);
  }

  return data;
}

const get = (path) => request(path, { method: 'GET' });
const post = (path, body) => request(path, { method: 'POST', body: JSON.stringify(body) });
const put = (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body) });

export const apiExtra = {

  // ── Configuración del emprendedor ────────────────────────────────────────

  /**
   * Devuelve la configuración avanzada del emprendimiento del emprendedor
   * autenticado (horario, pedido mínimo, radio de entrega, notificaciones).
   * @returns {{ success: true, configuracion: object }}
   */
  getConfiguracion: () =>
    get('/configuracion.php'),

  /**
   * Actualiza cualquier subconjunto de campos de configuración.
   * @param {object} cambios  Ej. { horario_apertura: '08:00', acepta_pedidos: true }
   * @returns {{ success: true, configuracion: object }}
   */
  actualizarConfiguracion: (cambios) =>
    put('/configuracion.php', cambios),

  // ── Dashboard ─────────────────────────────────────────────────────────────

  /**
   * Estadísticas agregadas para el dashboard del emprendedor.
   * @returns {{ success: true, resumen: object, pedidos_por_dia: Array, pedidos_por_estado: Array, productos_top: Array }}
   */
  getDashboard: () =>
    get('/dashboard.php'),

  // ── Notificaciones ────────────────────────────────────────────────────────

  /**
   * Lista las notificaciones del usuario autenticado.
   * @param {{ soloNoLeidas?: boolean, limite?: number }} opciones
   * @returns {{ success: true, notificaciones: Array, no_leidas: number }}
   */
  getNotificaciones: (opciones = {}) => {
    const params = new URLSearchParams();
    if (opciones.soloNoLeidas) params.set('solo_no_leidas', '1');
    if (opciones.limite) params.set('limite', String(opciones.limite));
    const qs = params.toString() ? `?${params.toString()}` : '';
    return get(`/notificaciones.php${qs}`);
  },

  /** Marca una notificación como leída. @param {number} id */
  marcarNotificacionLeida: (id) =>
    post('/notificaciones.php', { accion: 'marcar_leida', id }),

  /** Marca todas las notificaciones del usuario como leídas. */
  marcarTodasLeidas: () =>
    post('/notificaciones.php', { accion: 'marcar_todas_leidas' }),

  // ── Push (Service Worker) ────────────────────────────────────────────────

  /**
   * Guarda la suscripción push generada por el Service Worker.
   * @param {PushSubscription} subscription  Objeto nativo del navegador.
   */
  suscribirPush: (subscription) => {
    const json = subscription.toJSON();
    return post('/push-suscripcion.php', {
      accion: 'suscribir',
      endpoint: json.endpoint,
      keys: json.keys,
    });
  },

  /**
   * Elimina la suscripción push (ej. al desactivar notificaciones).
   * @param {string} endpoint
   */
  desuscribirPush: (endpoint) =>
    post('/push-suscripcion.php', { accion: 'desuscribir', endpoint }),
};
