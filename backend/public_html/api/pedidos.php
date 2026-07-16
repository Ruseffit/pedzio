<?php
/**
 * GET  /api/pedidos.php          → lista pedidos según rol del usuario autenticado
 * GET  /api/pedidos.php?id=123   → detalle completo de un pedido (nuevo)
 * POST /api/pedidos.php          → crea un pedido desde el carrito en sesión
 *
 * GET (lista) respuesta (200):
 *   { "success": true, "pedidos": [...] }
 *
 * GET (detalle) respuesta (200):
 *   {
 *     "success": true,
 *     "pedido": {
 *       "id", "estado", "total", "direccion_entrega", "notas", "creado_en",
 *       "emprendimiento": { "id", "nombre_negocio" },
 *       "productos": [{ "nombre", "cantidad", "precio_unitario", "subtotal" }, ...]
 *     }
 *   }
 *
 * POST body JSON:
 *   { "emprendimiento_id": 3, "direccion_entrega": "Av. Lima 123", "notas": "..." }
 *
 * POST respuesta exitosa (200):
 *   { "success": true, "pedido_id": 42 }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Pedido;
use Pedzio\Models\Producto;
use Pedzio\Models\Emprendimiento;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Services\CarritoService;
use Pedzio\Services\FinanzasService;
use Pedzio\Services\PushSenderService;
use Pedzio\Services\NotificacionService;
use Pedzio\Controllers\PedidoController;
use Pedzio\Helpers\Formato;

// ── Autenticación obligatoria ─────────────────────────────────────────────────
$usuario = requireAuth();

$pdo = obtenerConexion();

$pedidoController = new PedidoController(
    new Pedido($pdo),
    new Producto($pdo),
    new FinanzasService(new MovimientoFinanciero($pdo))
);

// ════════════════════════════════════════════════════════════════════════════
// GET → detalle de un pedido específico  (?id=123)
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {

    $pedidoId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($pedidoId === false || $pedidoId < 1) {
        jsonResponse(['error' => 'ID de pedido inválido.'], 400);
    }

    $pedidoModel = new Pedido($pdo);
    $pedido = $pedidoModel->buscarPorId((int) $pedidoId);

    if (!$pedido) {
        jsonResponse(['error' => 'Pedido no encontrado.'], 404);
    }

    // Control de acceso: el cliente solo puede ver sus propios pedidos
    if ($usuario['rol'] === 'cliente' && (int) $pedido['cliente_id'] !== (int) $usuario['id']) {
        jsonResponse(['error' => 'No tienes permiso para ver este pedido.'], 403);
    }

    // El emprendedor solo puede ver pedidos de su negocio
    if ($usuario['rol'] === 'emprendedor') {
        $emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);
        if (!$emprendimiento || (int) $pedido['emprendimiento_id'] !== (int) $emprendimiento['id']) {
            jsonResponse(['error' => 'No tienes permiso para ver este pedido.'], 403);
        }
    }

    // Obtener detalle de productos
    $productos = $pedidoModel->detalleDePedido((int) $pedidoId);

    // Obtener nombre del emprendimiento
    $emprendimiento = (new Emprendimiento($pdo))->buscarPorId((int) $pedido['emprendimiento_id']);

    jsonResponse([
        'success' => true,
        'pedido'  => [
            'id'                => $pedido['id'],
            'estado'            => $pedido['estado'],
            'estado_legible'    => Formato::estadoPedidoLegible($pedido['estado']),
            'total'             => (float) $pedido['total'],
            'direccion_entrega' => $pedido['direccion_entrega'],
            'notas'             => $pedido['notas'] ?? null,
            'creado_en'         => $pedido['creado_en'],
            'emprendimiento'    => [
                'id'            => $emprendimiento['id'] ?? null,
                'nombre_negocio'=> $emprendimiento['nombre_negocio'] ?? '—',
            ],
            'productos' => array_map(fn($item) => [
                'nombre'          => $item['producto_nombre'],
                'cantidad'        => (int) $item['cantidad'],
                'precio_unitario' => (float) $item['precio_unitario'],
                'subtotal'        => (float) $item['subtotal'],
            ], $productos),
        ],
    ]);
}

// ════════════════════════════════════════════════════════════════════════════
// GET → listar pedidos según rol
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    switch ($usuario['rol']) {

        case 'cliente':
            $filas = $pedidoController->listarPorCliente($usuario['id']);
            break;

        case 'emprendedor':
            $emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);
            if (!$emprendimiento) {
                jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
            }
            $filas = $pedidoController->listarPorEmprendimiento((int) $emprendimiento['id']);
            break;

        case 'superadmin':
            $filas = (new Pedido($pdo))->listarTodos();
            break;

        default:
            jsonResponse(['error' => 'Rol no autorizado para esta operación.'], 403);
    }

    // Se agrega 'estado_legible' (ej. "En preparación") sin tocar 'estado' (ej.
    // "en_preparacion"): así el frontend puede mostrar el texto en español sin
    // que se rompa ningún consumidor que ya dependa del valor original en
    // snake_case (comparaciones, lógica condicional, etc.).
    $filas = array_map(
        fn(array $fila) => $fila + ['estado_legible' => Formato::estadoPedidoLegible($fila['estado'])],
        $filas
    );

    jsonResponse(['success' => true, 'pedidos' => $filas]);
}

// ════════════════════════════════════════════════════════════════════════════
// POST → cambiar estado de un pedido (nuevo; solo emprendedor dueño del negocio)
//        Body: { "accion": "cambiar_estado", "pedido_id": 42, "estado": "en_camino" }
//        Respuesta (200): { "success": true, "pedido": {..., estado, estado_legible} }
//
//        Se distingue por el campo "accion" para no interferir con el POST de
//        creación de pedido de más abajo (que no manda ese campo) — así los
//        clientes existentes de esta API siguen funcionando exactamente igual.
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bodyPrevio = jsonInput();

    if (($bodyPrevio['accion'] ?? '') === 'cambiar_estado') {
        if ($usuario['rol'] !== 'emprendedor') {
            jsonResponse(['error' => 'Solo el emprendedor puede cambiar el estado de un pedido.'], 403);
        }

        $pedidoId = filter_var($bodyPrevio['pedido_id'] ?? null, FILTER_VALIDATE_INT);
        $nuevoEstado = trim((string) ($bodyPrevio['estado'] ?? ''));

        if ($pedidoId === false || $pedidoId < 1) {
            jsonResponse(['error' => 'pedido_id inválido.'], 400);
        }
        if (!in_array($nuevoEstado, PEDZIO_ESTADOS_PEDIDO, true)) {
            jsonResponse(['error' => 'Estado inválido. Usa uno de: ' . implode(', ', PEDZIO_ESTADOS_PEDIDO) . '.'], 400);
        }

        $emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);
        if (!$emprendimiento) {
            jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
        }
        $emprendimientoId = (int) $emprendimiento['id'];

        $pedidoModel = new Pedido($pdo);
        $pedidoExistente = $pedidoModel->buscarPorId((int) $pedidoId);

        if (!$pedidoExistente) {
            jsonResponse(['error' => 'Pedido no encontrado.'], 404);
        }
        if ((int) $pedidoExistente['emprendimiento_id'] !== $emprendimientoId) {
            jsonResponse(['error' => 'Este pedido no pertenece a tu negocio.'], 403);
        }

        // El propio modelo vuelve a filtrar por emprendimiento_id (control de
        // acceso a nivel de datos), redundante a propósito con el check de
        // arriba — mismo patrón que ya usa notificaciones/enviar.php.
        $actualizado = $pedidoController->cambiarEstado((int) $pedidoId, $nuevoEstado, $emprendimientoId);

        if (!$actualizado) {
            jsonResponse(['error' => 'No se pudo actualizar el estado del pedido.'], 500);
        }

        // ── Push al cliente dueño del pedido ─────────────────────────────────
        // No debe romper la respuesta de "estado actualizado" si el push falla
        // (VAPID sin configurar, cliente sin suscripción, etc.) — mismo criterio
        // que la vista legacy emprendedor/pedidos.php.
        $mensajesPorEstado = [
            'pendiente'      => ['titulo' => 'Pedido recibido', 'mensaje' => 'Tu pedido fue recibido y está pendiente de confirmación.'],
            'confirmado'     => ['titulo' => 'Pedido confirmado', 'mensaje' => 'Tu pedido fue confirmado por el negocio.'],
            'en_preparacion' => ['titulo' => 'Preparando tu pedido', 'mensaje' => 'Tu pedido ya se está preparando.'],
            'en_camino'      => ['titulo' => '¡Tu pedido está en camino!', 'mensaje' => 'El repartidor ya salió hacia tu dirección.'],
            'entregado'      => ['titulo' => 'Pedido entregado', 'mensaje' => 'Tu pedido fue entregado. ¡Buen provecho!'],
            'cancelado'      => ['titulo' => 'Pedido cancelado', 'mensaje' => 'Tu pedido fue cancelado.'],
        ];
        $textos = $mensajesPorEstado[$nuevoEstado] ?? [
            'titulo'  => 'Actualización de tu pedido',
            'mensaje' => "Tu pedido en {$emprendimiento['nombre_negocio']} cambió de estado.",
        ];

        try {
            (new PushSenderService($pdo))->enviarAUsuario(
                (int) $pedidoExistente['cliente_id'],
                "{$textos['titulo']} · {$emprendimiento['nombre_negocio']}",
                $textos['mensaje'],
                '/cliente/pedidos'
            );
        } catch (\Throwable $e) {
            // No interrumpe la respuesta principal; el estado ya se guardó.
        }

        // ── Notificación in-app (la que ve la campana) ───────────────────────
        // Independiente del push: aunque el cliente no tenga Web Push activado,
        // debe ver el cambio reflejado en su campana de notificaciones.
        try {
            (new NotificacionService($pdo))->crear(
                (int) $pedidoExistente['cliente_id'],
                'pedido_actualizado',
                "{$textos['titulo']} · {$emprendimiento['nombre_negocio']}",
                $textos['mensaje'],
                '/cliente/pedidos'
            );
        } catch (\Throwable $e) {
            // No interrumpe la respuesta principal; el estado ya se guardó.
        }

        jsonResponse([
            'success' => true,
            'pedido'  => [
                'id'             => (int) $pedidoId,
                'estado'         => $nuevoEstado,
                'estado_legible' => Formato::estadoPedidoLegible($nuevoEstado),
            ],
        ]);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// POST → crear pedido desde carrito
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Solo clientes pueden crear pedidos
    if ($usuario['rol'] !== 'cliente') {
        jsonResponse(['error' => 'Solo los clientes pueden crear pedidos.'], 403);
    }

    // Carrito no vacío
    if (CarritoService::estaVacio()) {
        jsonResponse(['error' => 'El carrito está vacío.'], 400);
    }

    // Leer y validar body (reutiliza $bodyPrevio, ya leído arriba para
    // detectar la acción "cambiar_estado" — php://input no es confiable de
    // leer dos veces en todos los SAPIs de PHP, incluido el servidor de
    // desarrollo `php -S` que probablemente estés usando en local).
    $body             = $bodyPrevio;
    $emprendimientoId = filter_var($body['emprendimiento_id']  ?? null, FILTER_VALIDATE_INT);
    $direccion        = trim($body['direccion_entrega'] ?? '');
    $notas            = trim($body['notas']             ?? '') ?: null;

    if ($emprendimientoId === false || $emprendimientoId < 1) {
        jsonResponse(['error' => 'emprendimiento_id inválido.'], 400);
    }
    if ($direccion === '') {
        jsonResponse(['error' => 'La dirección de entrega es obligatoria.'], 400);
    }

    // Crear pedido — confirmarDesdeCarrito() ya vacía el carrito internamente
    $resultado = $pedidoController->confirmarDesdeCarrito(
        $usuario['id'],
        $emprendimientoId,
        $direccion,
        $notas
    );

    if (!$resultado['ok']) {
        jsonResponse(['error' => implode(' ', $resultado['errores'])], 422);
    }

    jsonResponse(['success' => true, 'pedido_id' => $resultado['pedido_id']]);
}

// ── Método no permitido ───────────────────────────────────────────────────────
jsonResponse(['error' => 'Método no permitido. Usa GET o POST.'], 405);
