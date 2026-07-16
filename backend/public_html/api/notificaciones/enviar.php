<?php
/**
 * POST /api/notificaciones/enviar.php
 *
 * Envía una notificación push real al cliente dueño de un pedido, pensado
 * para llamarse justo después de actualizar el estado de ese pedido
 * (ver nota de integración con pedidos.php más abajo).
 *
 * Solo el emprendedor dueño del negocio al que pertenece el pedido puede
 * disparar esta notificación (evita que cualquiera le mande push a
 * cualquier cliente).
 *
 * Body JSON:
 *   { "pedido_id": 123 }
 *
 * El título y mensaje se arman automáticamente según el estado ACTUAL
 * del pedido en la base de datos (no hay que mandarlo por body, así no
 * se puede falsear el contenido del push).
 *
 * Respuesta (200):
 *   { "success": true, "enviadas": 1, "fallidas": 0, "eliminadas": 0 }
 *
 * Respuesta (200, sin suscripciones): { "success": true, "enviadas": 0, ... }
 *   (el cliente no tiene notificaciones push activadas; no es un error)
 *
 * Error (400 / 401 / 403 / 404 / 500): { "error": "..." }
 *
 * ── Integración con pedidos.php (opcional, no está hecha automáticamente) ──
 * Para que el push se dispare solo, agrega esto en pedidos.php justo
 * después de $modeloPedido->actualizarEstado(...) exitoso, sin tocar el
 * resto del archivo:
 *
 *   // Dispara el push en segundo plano; si falla, no debe romper la
 *   // respuesta de actualizar el pedido.
 *   try {
 *       $ch = curl_init('http://localhost/api/notificaciones/enviar.php');
 *       curl_setopt_array($ch, [
 *           CURLOPT_POST => true,
 *           CURLOPT_POSTFIELDS => json_encode(['pedido_id' => $pedidoId]),
 *           CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
 *           CURLOPT_COOKIE => 'PHPSESSID=' . session_id(),
 *           CURLOPT_TIMEOUT => 3,
 *       ]);
 *       curl_exec($ch);
 *   } catch (\Throwable $e) {
 *       // no interrumpir la respuesta principal si el push falla
 *   }
 *
 * (O, más simple y sin HTTP de por medio: usar directamente
 * `(new PushSenderService($pdo))->enviarAUsuario(...)` con el mismo
 * texto que arma este archivo más abajo.)
 */

require_once __DIR__ . '/../config.php';

use Pedzio\Services\PushSenderService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

$usuario = requireAuth(['emprendedor']);
$pdo = obtenerConexion();
$datos = jsonInput();

$pedidoId = (int) ($datos['pedido_id'] ?? 0);
if ($pedidoId <= 0) {
    jsonResponse(['error' => 'Falta pedido_id.'], 400);
}

// ── Verifica que el pedido exista y pertenezca al negocio del emprendedor ──
$stmt = $pdo->prepare(
    'SELECT p.id, p.estado, p.cliente_id, p.emprendimiento_id, e.usuario_id AS dueno_id, e.nombre_negocio
     FROM pedidos p
     JOIN emprendimientos e ON e.id = p.emprendimiento_id
     WHERE p.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $pedidoId]);
$pedido = $stmt->fetch();

if (!$pedido) {
    jsonResponse(['error' => 'Pedido no encontrado.'], 404);
}

if ((int) $pedido['dueno_id'] !== $usuario['id']) {
    jsonResponse(['error' => 'Este pedido no pertenece a tu negocio.'], 403);
}

// ── Arma el texto según el estado actual (fuente única de verdad: la BD) ───
$mensajesPorEstado = [
    'pendiente'      => ['titulo' => 'Pedido recibido', 'mensaje' => 'Tu pedido fue recibido y está pendiente de confirmación.'],
    'confirmado'     => ['titulo' => 'Pedido confirmado', 'mensaje' => 'Tu pedido fue confirmado por el negocio.'],
    'en_preparacion' => ['titulo' => 'Preparando tu pedido', 'mensaje' => 'Tu pedido ya se está preparando.'],
    'en_camino'      => ['titulo' => '¡Tu pedido está en camino!', 'mensaje' => 'El repartidor ya salió hacia tu dirección.'],
    'entregado'      => ['titulo' => 'Pedido entregado', 'mensaje' => 'Tu pedido fue entregado. ¡Buen provecho!'],
    'cancelado'      => ['titulo' => 'Pedido cancelado', 'mensaje' => 'Tu pedido fue cancelado.'],
];

$textos = $mensajesPorEstado[$pedido['estado']] ?? [
    'titulo'  => 'Actualización de tu pedido',
    'mensaje' => "Tu pedido en {$pedido['nombre_negocio']} cambió de estado.",
];

$titulo = "{$textos['titulo']} · {$pedido['nombre_negocio']}";
$mensaje = $textos['mensaje'];
$url = '/cliente/pedidos';

try {
    $resultado = (new PushSenderService($pdo))->enviarAUsuario(
        (int) $pedido['cliente_id'],
        $titulo,
        $mensaje,
        $url
    );
} catch (\RuntimeException $e) {
    // VAPID no configurado todavía: no es un error del cliente, es de
    // configuración del servidor.
    jsonResponse(['error' => $e->getMessage()], 500);
}

jsonResponse(['success' => true] + $resultado);
