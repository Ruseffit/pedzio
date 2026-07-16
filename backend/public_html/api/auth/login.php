<?php
/**
 * POST /api/auth/login
 *
 * Body JSON:
 *   { "email": "...", "password": "..." }
 *
 * Respuesta exitosa (200):
 *   { "success": true, "user": { "id": 1, "nombre": "...", "rol": "..." } }
 *
 * Error de credenciales (401):
 *   { "error": "Correo o contraseña incorrectos." }
 *
 * Error de validación (400):
 *   { "error": "..." }
 */

require_once __DIR__ . '/../config.php';

use Pedzio\Models\Usuario;
use Pedzio\Controllers\UsuarioController;

// ── 1. Solo POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

// ── 2. Si ya hay sesión activa, devolver el usuario actual ───────────────────
if (!empty($_SESSION['usuario_id'])) {
    jsonResponse([
        'success' => true,
        'user'    => [
            'id'     => (int) $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
            'rol'    => $_SESSION['usuario_rol']    ?? '',
        ],
    ]);
}

// ── 3. Leer y validar body JSON ──────────────────────────────────────────────
$body = jsonInput();

$email    = trim($body['email']    ?? '');
$password =      $body['password'] ?? '';

if ($email === '') {
    jsonResponse(['error' => 'El campo email es obligatorio.'], 400);
}
if ($password === '') {
    jsonResponse(['error' => 'El campo password es obligatorio.'], 400);
}

// ── 4. Validar credenciales con UsuarioController ────────────────────────────
$pdo         = obtenerConexion();
$controlador = new UsuarioController(new Usuario($pdo));

$usuario = $controlador->iniciarSesion($email, $password);

if (!$usuario) {
    jsonResponse(['error' => 'Correo o contraseña incorrectos.'], 401);
}

// ── 5. Crear sesión PHP ───────────────────────────────────────────────────────
session_regenerate_id(true);

$_SESSION['usuario_id']     = (int) $usuario['id'];
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_rol']    = $usuario['rol'];

// ── 6. Respuesta exitosa ──────────────────────────────────────────────────────
jsonResponse([
    'success' => true,
    'user'    => [
        'id'     => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'rol'    => $usuario['rol'],
    ],
]);
