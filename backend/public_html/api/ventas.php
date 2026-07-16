<?php
/**
 * GET /api/ventas.php
 *
 * Devuelve los movimientos financieros (ingresos/gastos) del emprendimiento
 * del usuario emprendedor autenticado.
 *
 * Query params opcionales:
 *   ?desde=YYYY-MM-DD
 *   ?hasta=YYYY-MM-DD
 *
 * Respuesta (200):
 *   {
 *     "success": true,
 *     "ventas": [ { id, tipo, categoria, monto, descripcion, fecha } ],
 *     "resumen": { total_ingresos, total_gastos, balance }
 *   }
 *
 * Error (403 / 404):
 *   { "error": "..." }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Models\Emprendimiento;

// ── 1. Solo GET ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

// ── 2. Solo emprendedores autenticados ──────────────────────────────────────
$usuario = requireAuth(['emprendedor']);

$pdo = obtenerConexion();

// ── 3. Resolver el emprendimiento del usuario logueado ──────────────────────
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);

if (!$emprendimiento) {
    jsonResponse(['error' => 'No tienes un emprendimiento registrado.'], 404);
}

$emprendimientoId = (int) $emprendimiento['id'];

// ── 4. Filtros opcionales de fecha ──────────────────────────────────────────
$desde = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null;
$hasta = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null;

// ── 5. Listar movimientos financieros ───────────────────────────────────────
$modeloMovimiento = new MovimientoFinanciero($pdo);
$filas = $modeloMovimiento->listarPorEmprendimiento($emprendimientoId, $desde, $hasta);

$ventas = array_map(fn($m) => [
    'id'          => (int)   $m['id'],
    'tipo'        =>         $m['tipo'],
    'categoria'   =>         $m['categoria'],
    'monto'       => (float) $m['monto'],
    'descripcion' =>         $m['descripcion'],
    'fecha'       =>         $m['fecha'],
], $filas);

// ── 6. Resumen de totales (útil para tarjetas de resumen en el frontend) ────
$resumen = $modeloMovimiento->resumen($emprendimientoId, $desde, $hasta);

jsonResponse([
    'success' => true,
    'ventas'  => $ventas,
    'resumen' => $resumen,
]);
