<?php
/**
 * GET  /api/carrito.php          → detalle del carrito con precios reales
 * POST /api/carrito.php          → mutaciones del carrito según "accion"
 *
 * Acciones POST (body JSON):
 *   { "accion": "agregar",     "producto_id": 5, "cantidad": 2 }
 *   { "accion": "actualizar",  "cantidades": { "5": 3, "12": 1 } }
 *   { "accion": "eliminar",    "producto_id": 5 }
 *   { "accion": "vaciar" }
 *
 * Respuesta GET (200):
 *   { "success": true, "carrito": { "items": [...], "total": 99.90 } }
 *
 * Respuesta POST exitosa (200):
 *   { "success": true }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Services\CarritoService;
use Pedzio\Models\Producto;

// ── Requiere sesión activa (el carrito vive en sesión) ────────────────────────
requireAuth();

$pdo           = obtenerConexion();
$modeloProducto = new Producto($pdo);

// ── Helper: construye el mapa [producto_id => fila] para calcularDetalle() ────
function mapProductosCarrito(Producto $modelo): array
{
    $carrito = CarritoService::obtener();
    if (empty($carrito)) {
        return [];
    }
    $mapa = [];
    foreach (array_keys($carrito) as $productoId) {
        $producto = $modelo->buscarPorId((int) $productoId);
        if ($producto && $producto['disponible']) {
            $mapa[$productoId] = $producto;
        }
    }
    return $mapa;
}

// ════════════════════════════════════════════════════════════════════════════
// GET → devolver detalle del carrito
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mapa    = mapProductosCarrito($modeloProducto);
    $detalle = CarritoService::calcularDetalle($mapa);
    jsonResponse(['success' => true, 'carrito' => $detalle]);
}

// ════════════════════════════════════════════════════════════════════════════
// POST → mutaciones
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = jsonInput();
    $accion = trim($body['accion'] ?? '');

    switch ($accion) {

        // ── agregar ──────────────────────────────────────────────────────────
        case 'agregar':
            $productoId = filter_var($body['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad   = filter_var($body['cantidad']    ?? 1,    FILTER_VALIDATE_INT);

            if ($productoId === false || $productoId < 1) {
                jsonResponse(['error' => 'producto_id inválido.'], 400);
            }
            if ($cantidad === false || $cantidad < 1) {
                jsonResponse(['error' => 'cantidad debe ser un entero mayor a 0.'], 400);
            }

            $producto = $modeloProducto->buscarPorId($productoId);
            if (!$producto || !$producto['disponible']) {
                jsonResponse(['error' => 'Producto no disponible.'], 404);
            }

            CarritoService::agregar($productoId, $cantidad);
            jsonResponse(['success' => true]);

        // ── actualizar ───────────────────────────────────────────────────────
        case 'actualizar':
            $cantidades = $body['cantidades'] ?? null;

            if (!is_array($cantidades) || empty($cantidades)) {
                jsonResponse(['error' => 'cantidades debe ser un objeto {producto_id: cantidad}.'], 400);
            }

            foreach ($cantidades as $productoId => $cantidad) {
                $productoId = filter_var($productoId, FILTER_VALIDATE_INT);
                $cantidad   = filter_var($cantidad,   FILTER_VALIDATE_INT);

                if ($productoId === false || $cantidad === false) {
                    continue; // omite entradas malformadas
                }

                CarritoService::actualizarCantidad($productoId, $cantidad);
            }

            jsonResponse(['success' => true]);

        // ── eliminar ─────────────────────────────────────────────────────────
        case 'eliminar':
            $productoId = filter_var($body['producto_id'] ?? null, FILTER_VALIDATE_INT);

            if ($productoId === false || $productoId < 1) {
                jsonResponse(['error' => 'producto_id inválido.'], 400);
            }

            CarritoService::eliminar($productoId);
            jsonResponse(['success' => true]);

        // ── vaciar ───────────────────────────────────────────────────────────
        case 'vaciar':
            CarritoService::vaciar();
            jsonResponse(['success' => true]);

        // ── acción desconocida ────────────────────────────────────────────────
        default:
            jsonResponse([
                'error'   => 'Acción no reconocida.',
                'acciones'=> ['agregar', 'actualizar', 'eliminar', 'vaciar'],
            ], 400);
    }
}

// ── Método no permitido ───────────────────────────────────────────────────────
jsonResponse(['error' => 'Método no permitido. Usa GET o POST.'], 405);
