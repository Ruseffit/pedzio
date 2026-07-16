<?php
/**
 * GET /api/emprendimientos.php
 *
 * Lista todos los emprendimientos activos.
 *
 * Respuesta (200):
 *   { "success": true, "emprendimientos": [ { id, nombre_negocio, descripcion,
 *                                              direccion, telefono_contacto }, ... ] }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Emprendimiento;

// ── 1. Solo GET ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

// ── 2. Listar emprendimientos activos ─────────────────────────────────────────
$filas = (new Emprendimiento(obtenerConexion()))->listarActivos();

$emprendimientos = array_map(fn($e) => [
    'id'                => (int) $e['id'],
    'nombre_negocio'    =>       $e['nombre_negocio'],
    'descripcion'       =>       $e['descripcion'],
    'direccion'         =>       $e['direccion'],
    'telefono_contacto' =>       $e['telefono_contacto'],
], $filas);

jsonResponse(['success' => true, 'emprendimientos' => $emprendimientos]);
