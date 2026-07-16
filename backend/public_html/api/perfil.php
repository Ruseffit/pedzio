<?php
/**
 * GET /api/perfil.php
 *
 * Devuelve los datos del usuario emprendedor autenticado y, si ya lo creó,
 * los datos de su emprendimiento.
 *
 * Respuesta (200):
 *   {
 *     "success": true,
 *     "perfil": { "nombre": "...", "email": "...", "telefono": "...", "rol": "emprendedor" },
 *     "emprendimiento": { "nombre_negocio": "...", "direccion": "...", "telefono_contacto": "..." } | null
 *   }
 *
 * Error (401 / 403):
 *   { "error": "..." }
 */

require_once __DIR__ . '/config.php';

use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;

// ── 1. Solo GET ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

// ── 2. Solo emprendedores autenticados ──────────────────────────────────────
$usuario = requireAuth(['emprendedor']);

$pdo = obtenerConexion();

// ── 3. Datos del usuario ─────────────────────────────────────────────────────
$datosUsuario = (new Usuario($pdo))->buscarPorId($usuario['id']);

if (!$datosUsuario) {
    // La sesión apunta a un usuario que ya no existe en la BD.
    jsonResponse(['error' => 'Usuario no encontrado.'], 404);
}

$perfil = [
    'nombre'   => $datosUsuario['nombre'],
    'email'    => $datosUsuario['email'],
    'telefono' => $datosUsuario['telefono'],
    'rol'      => $datosUsuario['rol'],
];

// ── 4. Datos del emprendimiento (puede no existir todavía) ──────────────────
$datosEmprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId($usuario['id']);

$emprendimiento = $datosEmprendimiento ? [
    'id'                => (int) $datosEmprendimiento['id'],
    'nombre_negocio'    =>       $datosEmprendimiento['nombre_negocio'],
    'descripcion'       =>       $datosEmprendimiento['descripcion'],
    'direccion'         =>       $datosEmprendimiento['direccion'],
    'telefono_contacto' =>       $datosEmprendimiento['telefono_contacto'],
] : null;

jsonResponse([
    'success'        => true,
    'perfil'         => $perfil,
    'emprendimiento' => $emprendimiento,
]);
