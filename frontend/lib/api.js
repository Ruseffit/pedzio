/**
 * lib/api.js
 * Cliente HTTP para el backend Pedzio (PHP + sesión con cookies).
 *
 * Todas las peticiones incluyen credentials: 'include' para enviar
 * la cookie de sesión PHP en cada request.
 *
 * En caso de error, se lanza un Error con el mensaje devuelto por el
 * backend ({ error: "..." }), o un mensaje explicativo si la red/CORS
 * falló, o un mensaje genérico si la respuesta no es JSON.
 */

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// ── Núcleo ────────────────────────────────────────────────────────────────────

/**
 * Wrapper sobre fetch con configuración base.
 * Lanza Error si el servidor responde con un status >= 400, si la red/CORS
 * falla, o si la respuesta no es JSON válido.
 *
 * @param {string} path   Ruta relativa al API_BASE, ej. '/auth/login.php'
 * @param {RequestInit} options  Opciones fetch adicionales
 * @returns {Promise<any>}  Cuerpo JSON de la respuesta
 */
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
    // fetch() rechaza (no responde con un status) cuando el backend está
    // caído, la URL es incorrecta, o CORS bloqueó la respuesta. El navegador
    // no distingue estos casos por seguridad, así que damos un mensaje que
    // cubre las causas más comunes en desarrollo.
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
    // El servidor devolvió algo que no es JSON (error 500, HTML de PHP, etc.)
    throw new Error(`Error ${res.status}: respuesta inesperada del servidor.`);
  }

  if (!res.ok) {
    // El backend siempre devuelve { error: "..." } en casos de fallo
    throw new Error(data?.error || `Error ${res.status}`);
  }

  return data;
}

/** Shorthand para peticiones GET */
const get = (path) => request(path, { method: 'GET' });

/** Shorthand para peticiones POST con body JSON */
const post = (path, body) =>
  request(path, { method: 'POST', body: JSON.stringify(body) });

// ── Métodos por endpoint ──────────────────────────────────────────────────────

export const api = {

  // ── Auth ──────────────────────────────────────────────────────────────────

  /**
   * Inicia sesión. Crea la cookie de sesión PHP en el navegador.
   * @param {string} email
   * @param {string} password
   * @returns {{ success: true, user: { id, nombre, rol } }}
   */
  login: (email, password) =>
    post('/auth/login.php', { email, password }),

  /**
   * Registra un nuevo usuario. Si el rol es 'emprendedor' también crea el
   * emprendimiento, inicia la sesión y devuelve la ruta de redirección.
   * @param {{ nombre, email, password, telefono?, rol, nombre_negocio? }} datos
   * @returns {{ success: true, user: { id, nombre, rol }, redirect: string }}
   */
  register: (datos) =>
    post('/auth/register.php', datos),

  /**
   * Cierra sesión. Destruye la cookie de sesión en servidor y navegador.
   * @returns {{ success: true }}
   */
  logout: () =>
    post('/auth/logout.php'),

  /**
   * Devuelve el usuario de la sesión activa.
   * Útil para rehidratar el estado de auth al cargar la app.
   * Lanza Error con "No autenticado" si no hay sesión (401).
   * @returns {{ success: true, user: { id, nombre, rol } }}
   */
  me: () =>
    get('/auth/me.php'),

  // ── Emprendimientos ───────────────────────────────────────────────────────

  /**
   * Lista todos los emprendimientos activos.
   * @returns {{ success: true, emprendimientos: Array }}
   */
  getEmprendimientos: () =>
    get('/emprendimientos.php'),

  // ── Productos ─────────────────────────────────────────────────────────────

  /**
   * Lista productos activos. Si se pasa emprendimientoId filtra por negocio.
   * @param {number|null} emprendimientoId
   * @returns {{ success: true, productos: Array }}
   */
  getProductos: (emprendimientoId = null) => {
    const qs = emprendimientoId ? `?emprendimiento_id=${emprendimientoId}` : '';
    return get(`/productos.php${qs}`);
  },

  // ── Carrito ───────────────────────────────────────────────────────────────

  /**
   * Devuelve el contenido actual del carrito con precios reales.
   * @returns {{ success: true, carrito: { items: Array, total: number } }}
   */
  getCarrito: () =>
    get('/carrito.php'),

  /**
   * Agrega un producto al carrito.
   * @param {number} productoId
   * @param {number} cantidad
   * @returns {{ success: true }}
   */
  agregarAlCarrito: (productoId, cantidad = 1) =>
    post('/carrito.php', { accion: 'agregar', producto_id: productoId, cantidad }),

  /**
   * Actualiza cantidades de varios productos a la vez.
   * @param {{ [productoId: number]: number }} cantidades  Mapa id → cantidad
   * @returns {{ success: true }}
   */
  actualizarCarrito: (cantidades) =>
    post('/carrito.php', { accion: 'actualizar', cantidades }),

  /**
   * Elimina un producto del carrito.
   * @param {number} productoId
   * @returns {{ success: true }}
   */
  eliminarDelCarrito: (productoId) =>
    post('/carrito.php', { accion: 'eliminar', producto_id: productoId }),

  /**
   * Vacía el carrito completamente.
   * @returns {{ success: true }}
   */
  vaciarCarrito: () =>
    post('/carrito.php', { accion: 'vaciar' }),

  // ── Pedidos ───────────────────────────────────────────────────────────────

  /**
   * Lista pedidos según el rol del usuario autenticado:
   *   - cliente     → sus propios pedidos
   *   - emprendedor → pedidos de su negocio
   *   - superadmin  → todos los pedidos
   * @returns {{ success: true, pedidos: Array }}
   */
  getPedidos: () =>
    get('/pedidos.php'),

  /**
   * Devuelve el detalle de un pedido específico.
   * @param {number} id
   * @returns {{ success: true, pedido: object }}
   */
  getPedidoDetalle: (id) =>
    get(`/pedidos.php?id=${id}`),

  /**
   * Crea un pedido desde el carrito en sesión.
   * @param {number} emprendimientoId
   * @param {string} direccionEntrega
   * @param {string|null} notas
   * @returns {{ success: true, pedido_id: number }}
   */
  crearPedido: (emprendimientoId, direccionEntrega, notas = null) =>
    post('/pedidos.php', {
      emprendimiento_id: emprendimientoId,
      direccion_entrega: direccionEntrega,
      notas,
    }),

  /**
   * Cambia el estado de un pedido (solo emprendedor, dueño del negocio).
   * Dispara además, del lado del backend, el push al cliente y la
   * notificación in-app correspondiente.
   * @param {number} pedidoId
   * @param {string} estado  Uno de: pendiente, confirmado, en_preparacion, en_camino, entregado, cancelado
   * @returns {{ success: true, pedido: { id, estado, estado_legible } }}
   */
  cambiarEstadoPedido: (pedidoId, estado) =>
    post('/pedidos.php', { accion: 'cambiar_estado', pedido_id: pedidoId, estado }),

  // ── Panel de emprendedor ─────────────────────────────────────────────────────

  /**
   * Movimientos financieros (ingresos/gastos) del emprendimiento del
   * emprendedor autenticado, con resumen de totales.
   * @param {{ desde?: string, hasta?: string }} filtros  Fechas YYYY-MM-DD opcionales
   * @returns {{ success: true, ventas: Array, resumen: { total_ingresos, total_gastos, balance } }}
   */
  getVentas: (filtros = {}) => {
    const params = new URLSearchParams();
    if (filtros.desde) params.set('desde', filtros.desde);
    if (filtros.hasta) params.set('hasta', filtros.hasta);
    const qs = params.toString() ? `?${params.toString()}` : '';
    return get(`/ventas.php${qs}`);
  },

  /**
   * Clientes que han hecho al menos un pedido al emprendimiento del
   * emprendedor autenticado, con su cantidad total de pedidos.
   * @returns {{ success: true, clientes: Array }}
   */
  getClientes: () =>
    get('/clientes.php'),

  /**
   * Datos del emprendedor autenticado y de su emprendimiento (si ya lo creó).
   * @returns {{ success: true, perfil: object, emprendimiento: object|null }}
   */
  getPerfil: () =>
    get('/perfil.php'),

  /**
   * Datos del emprendimiento del emprendedor autenticado, con categorías.
   * @returns {{ success: true, emprendimiento: object }}
   */
  getNegocio: () =>
    get('/emprendimiento.php'),

  /**
   * Actualiza nombre, descripción, dirección, teléfono, categoría y logo del emprendimiento.
   * Usa request() (no fetch directo) para conservar el mismo manejo de errores
   * que el resto de la API; solo cambia el método a PUT.
   * @param {{ nombre_negocio, descripcion, direccion, telefono_contacto, categoria, logo_base64 }} datos
   * @returns {{ success: true, emprendimiento: object }}
   */
  actualizarNegocio: (datos) =>
    request('/emprendimiento.php', { method: 'PUT', body: JSON.stringify(datos) }),

  // ── Usuario (todos los roles) ────────────────────────────────────────────────

  /**
   * Sube o reemplaza la foto de perfil del usuario autenticado.
   * No usa request()/post() porque esos fuerzan Content-Type: application/json;
   * un upload de archivo necesita multipart/form-data con el boundary que arma
   * el propio navegador, así que NO se debe fijar el header Content-Type a mano.
   * @param {File} archivo  Imagen jpg/png/webp (límite definido en el backend).
   * @returns {{ success: true, foto_url: string }}
   */
  subirFotoPerfil: async (archivo) => {
    const formData = new FormData();
    formData.append('foto', archivo);

    let res;
    try {
      res = await fetch(`${API_BASE}/usuarios/foto.php`, {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });
    } catch (err) {
      throw new Error(
        `No se pudo conectar con la API en ${API_BASE}. Verifica que el backend esté ` +
        `corriendo. Detalle original: ${err.message}`
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
  },
};
