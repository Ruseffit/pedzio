<?php
/**
 * GET /api/productos.php
 * GET /api/productos.php?emprendimiento_id=3
 *
 * Sin parámetro  → todos los productos activos de todos los emprendimientos activos.
 * Con parámetro  → productos del emprendimiento indicado (activos y no activos).
 *
 * Respuesta (200):
 *   { "success": true, "productos": [ { id, nombre, descripcion, precio, disponible,
 *                                        emprendimiento_id, emprendimiento_nombre }, ... ] }
 *
 * Error (400 / 404):
 *   { "error": "..." }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Producto;
use Pedzio\Models\Emprendimiento;

// ── 1. Solo GET ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

$pdo            = obtenerConexion();
$modeloProducto = new Producto($pdo);
$modeloEmpren   = new Emprendimiento($pdo);

// ── 2. Con ?emprendimiento_id → productos de ese emprendimiento ───────────────
if (isset($_GET['emprendimiento_id'])) {
    $emprendimientoId = filter_var($_GET['emprendimiento_id'], FILTER_VALIDATE_INT);

    if ($emprendimientoId === false || $emprendimientoId < 1) {
        jsonResponse(['error' => 'El parámetro emprendimiento_id debe ser un entero positivo.'], 400);
    }

    $emprendimiento = $modeloEmpren->buscarPorId($emprendimientoId);

    if (!$emprendimiento) {
        jsonResponse(['error' => 'Emprendimiento no encontrado.'], 404);
    }

    $filas     = $modeloProducto->listarPorEmprendimiento($emprendimientoId);
    $productos = array_map(fn($p) => [
        'id'                    => (int)   $p['id'],
        'nombre'                =>         $p['nombre'],
        'descripcion'           =>         $p['descripcion'],
        'precio'                => (float) $p['precio'],
        'disponible'            => (bool)  $p['disponible'],
        'emprendimiento_id'     => (int)   $p['emprendimiento_id'],
        'emprendimiento_nombre' =>         $emprendimiento['nombre_negocio'],
    ], $filas);

    jsonResponse(['success' => true, 'productos' => $productos]);
}

// ── 3. Sin parámetro → todos los productos activos con JOIN ──────────────────
$stmt = $pdo->query(
    'SELECT p.id, p.nombre, p.descripcion, p.precio, p.disponible,
            p.emprendimiento_id, e.nombre_negocio AS emprendimiento_nombre
     FROM productos p
     JOIN emprendimientos e ON e.id = p.emprendimiento_id
     WHERE p.disponible = 1
       AND e.activo     = 1
     ORDER BY e.nombre_negocio, p.nombre'
);

$productos = array_map(fn($p) => [
    'id'                    => (int)   $p['id'],
    'nombre'                =>         $p['nombre'],
    'descripcion'           =>         $p['descripcion'],
    'precio'                => (float) $p['precio'],
    'disponible'            => (bool)  $p['disponible'],
    'emprendimiento_id'     => (int)   $p['emprendimiento_id'],
    'emprendimiento_nombre' =>         $p['emprendimiento_nombre'],
], $stmt->fetchAll());

jsonResponse(['success' => true, 'productos' => $productos]);
