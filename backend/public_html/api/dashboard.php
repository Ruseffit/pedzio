<?php
/**
 * GET /api/dashboard.php
 *
 * Estadísticas agregadas del emprendimiento del emprendedor autenticado,
 * pensadas para tarjetas + gráfico de la pantalla de dashboard.
 * Es de solo lectura: no toca ninguna tabla, solo hace SELECT.
 *
 * Respuesta (200):
 *   {
 *     "success": true,
 *     "resumen": {
 *       "pedidos_hoy", "pedidos_pendientes", "ventas_mes_actual",
 *       "ventas_mes_anterior", "variacion_porcentual", "ticket_promedio",
 *       "clientes_totales", "productos_activos"
 *     },
 *     "pedidos_por_dia": [ { "fecha": "YYYY-MM-DD", "total": n }, ... ] (últimos 7 días),
 *     "pedidos_por_estado": [ { "estado": "...", "total": n }, ... ],
 *     "productos_top": [ { "nombre": "...", "unidades": n, "ingresos": n }, ... ] (top 5)
 *   }
 *
 * Error (403 / 404): { "error": "..." }
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

$usuario = requireAuth(['emprendedor']);
$pdo = obtenerConexion();

$stmt = $pdo->prepare('SELECT id FROM emprendimientos WHERE usuario_id = :uid LIMIT 1');
$stmt->execute(['uid' => $usuario['id']]);
$emprendimiento = $stmt->fetch();

if (!$emprendimiento) {
    jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
}

$eid = (int) $emprendimiento['id'];

// ── Pedidos de hoy ───────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS total FROM pedidos WHERE emprendimiento_id = :eid AND DATE(creado_en) = CURDATE()'
);
$stmt->execute(['eid' => $eid]);
$pedidosHoy = (int) $stmt->fetch()['total'];

// ── Pedidos pendientes de atención ───────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total FROM pedidos
     WHERE emprendimiento_id = :eid AND estado IN ('pendiente', 'confirmado', 'en_preparacion')"
);
$stmt->execute(['eid' => $eid]);
$pedidosPendientes = (int) $stmt->fetch()['total'];

// ── Ventas (ingresos) del mes actual vs mes anterior ────────────────────────
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(monto), 0) AS total FROM movimientos_financieros
     WHERE emprendimiento_id = :eid AND tipo = 'ingreso'
       AND YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())"
);
$stmt->execute(['eid' => $eid]);
$ventasMesActual = (float) $stmt->fetch()['total'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(monto), 0) AS total FROM movimientos_financieros
     WHERE emprendimiento_id = :eid AND tipo = 'ingreso'
       AND YEAR(fecha) = YEAR(CURDATE() - INTERVAL 1 MONTH)
       AND MONTH(fecha) = MONTH(CURDATE() - INTERVAL 1 MONTH)"
);
$stmt->execute(['eid' => $eid]);
$ventasMesAnterior = (float) $stmt->fetch()['total'];

$variacionPorcentual = $ventasMesAnterior > 0
    ? round((($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
    : ($ventasMesActual > 0 ? 100.0 : 0.0);

// ── Ticket promedio (sobre pedidos entregados, histórico) ───────────────────
$stmt = $pdo->prepare(
    "SELECT COALESCE(AVG(total), 0) AS promedio FROM pedidos
     WHERE emprendimiento_id = :eid AND estado = 'entregado'"
);
$stmt->execute(['eid' => $eid]);
$ticketPromedio = (float) $stmt->fetch()['promedio'];

// ── Clientes únicos que le han comprado ──────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT cliente_id) AS total FROM pedidos WHERE emprendimiento_id = :eid'
);
$stmt->execute(['eid' => $eid]);
$clientesTotales = (int) $stmt->fetch()['total'];

// ── Productos activos ────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS total FROM productos WHERE emprendimiento_id = :eid AND disponible = 1'
);
$stmt->execute(['eid' => $eid]);
$productosActivos = (int) $stmt->fetch()['total'];

// ── Pedidos por día (últimos 7 días, incluye días en 0) ─────────────────────
$stmt = $pdo->prepare(
    "SELECT DATE(creado_en) AS fecha, COUNT(*) AS total
     FROM pedidos
     WHERE emprendimiento_id = :eid AND creado_en >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(creado_en)"
);
$stmt->execute(['eid' => $eid]);
$filasPorDia = $stmt->fetchAll();
$mapaPorDia = [];
foreach ($filasPorDia as $fila) {
    $mapaPorDia[$fila['fecha']] = (int) $fila['total'];
}

$pedidosPorDia = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-{$i} days"));
    $pedidosPorDia[] = ['fecha' => $fecha, 'total' => $mapaPorDia[$fecha] ?? 0];
}

// ── Pedidos por estado (distribución actual) ─────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT estado, COUNT(*) AS total FROM pedidos WHERE emprendimiento_id = :eid GROUP BY estado'
);
$stmt->execute(['eid' => $eid]);
$pedidosPorEstado = array_map(
    fn($f) => ['estado' => $f['estado'], 'total' => (int) $f['total']],
    $stmt->fetchAll()
);

// ── Top 5 productos más vendidos (por unidades, histórico) ──────────────────
$stmt = $pdo->prepare(
    'SELECT p.nombre, SUM(dp.cantidad) AS unidades, SUM(dp.subtotal) AS ingresos
     FROM detalle_pedidos dp
     JOIN productos p ON p.id = dp.producto_id
     JOIN pedidos pe ON pe.id = dp.pedido_id
     WHERE pe.emprendimiento_id = :eid AND pe.estado != "cancelado"
     GROUP BY dp.producto_id, p.nombre
     ORDER BY unidades DESC
     LIMIT 5'
);
$stmt->execute(['eid' => $eid]);
$productosTop = array_map(
    fn($f) => [
        'nombre'   => $f['nombre'],
        'unidades' => (int) $f['unidades'],
        'ingresos' => (float) $f['ingresos'],
    ],
    $stmt->fetchAll()
);

jsonResponse([
    'success' => true,
    'resumen' => [
        'pedidos_hoy'          => $pedidosHoy,
        'pedidos_pendientes'   => $pedidosPendientes,
        'ventas_mes_actual'    => $ventasMesActual,
        'ventas_mes_anterior'  => $ventasMesAnterior,
        'variacion_porcentual' => $variacionPorcentual,
        'ticket_promedio'      => $ticketPromedio,
        'clientes_totales'     => $clientesTotales,
        'productos_activos'    => $productosActivos,
    ],
    'pedidos_por_dia'    => $pedidosPorDia,
    'pedidos_por_estado' => $pedidosPorEstado,
    'productos_top'      => $productosTop,
]);
