<?php
/**
 * POST /api/auth/logout
 *
 * Destruye la sesión PHP activa.
 *
 * Respuesta (200):
 *   { "success": true }
 */

require_once __DIR__ . '/../config.php';

// ── 1. Solo POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

// ── 2. Destruir sesión ────────────────────────────────────────────────────────
$_SESSION = [];

// Expira la cookie de sesión en el navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// ── 3. Respuesta ───────────────────────────────────────────────────────────────
jsonResponse(['success' => true]);
