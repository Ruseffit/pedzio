<?php
/**
 * GET /api/auth/me
 *
 * Devuelve el usuario autenticado en la sesión actual.
 *
 * Respuesta exitosa (200):
 *   { "success": true, "user": { "id": 1, "nombre": "...", "rol": "...", "foto_url": "..."|null } }
 *
 * Sin sesión (401):
 *   { "error": "No autenticado" }
 */

require_once __DIR__ . '/../config.php';

use Pedzio\Models\Usuario;

// ── 1. Solo GET ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido. Usa GET.'], 405);
}

// ── 2. Verificar sesión activa ────────────────────────────────────────────────
if (empty($_SESSION['usuario_id'])) {
    jsonResponse(['error' => 'No autenticado'], 401);
}

// ── 3. foto_url no vive en sesión (solo se guarda en BD) → una consulta liviana ──
$datosUsuario = (new Usuario(obtenerConexion()))->buscarPorId((int) $_SESSION['usuario_id']);
$fotoUrl      = pedzio_url_absoluta($datosUsuario['foto_url'] ?? null);

// ── 4. Devolver datos del usuario ─────────────────────────────────────────────
jsonResponse([
    'success' => true,
    'user'    => [
        'id'       => (int) $_SESSION['usuario_id'],
        'nombre'   => $_SESSION['usuario_nombre'] ?? '',
        'rol'      => $_SESSION['usuario_rol']    ?? '',
        'foto_url' => $fotoUrl,
    ],
]);
