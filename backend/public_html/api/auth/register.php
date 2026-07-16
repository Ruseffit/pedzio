<?php
/**
 * POST /api/auth/register.php
 *
 * Registra un nuevo usuario (cliente o emprendedor).
 * Si el rol es "emprendedor", crea además un registro vacío en la tabla
 * emprendimientos e inicia la sesión automáticamente, para que el frontend
 * pueda redirigir directamente al panel sin pasar por /login.
 *
 * Body JSON:
 *   {
 *     "nombre":        string  (requerido),
 *     "email":         string  (requerido),
 *     "password":      string  (requerido, ≥ 8 chars con letra y número),
 *     "telefono":      string  (opcional),
 *     "rol":           "cliente" | "emprendedor"  (default: "cliente"),
 *     "nombre_negocio": string (requerido si rol = emprendedor)
 *   }
 *
 * Respuesta exitosa (201):
 *   {
 *     "success": true,
 *     "user": { "id": 1, "nombre": "...", "rol": "emprendedor" },
 *     "redirect": "/emprendedor/mi-negocio"   ← solo para emprendedores
 *   }
 *
 * Errores:
 *   400 – Validación fallida o email ya en uso
 *   405 – Método no permitido
 */

require_once __DIR__ . '/../config.php';

use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;
use Pedzio\Controllers\UsuarioController;

// ── Solo POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido. Usa POST.'], 405);
}

// ── Leer body ─────────────────────────────────────────────────────────────────
$body = jsonInput();

$nombre        = trim($body['nombre']         ?? '');
$email         = trim($body['email']          ?? '');
$password      =      $body['password']       ?? '';
$telefono      = trim($body['telefono']       ?? '') ?: null;
$rol           = trim($body['rol']            ?? 'cliente');
$nombreNegocio = trim($body['nombre_negocio'] ?? '');

// ── Validaciones extra antes de pasar al Controller ───────────────────────────
if ($rol === 'emprendedor' && $nombreNegocio === '') {
    jsonResponse(['error' => 'El nombre del negocio es obligatorio para emprendedores.'], 400);
}
if (mb_strlen($nombreNegocio) > 150) {
    jsonResponse(['error' => 'El nombre del negocio no puede superar los 150 caracteres.'], 400);
}

// ── Registro en una transacción ───────────────────────────────────────────────
$pdo         = obtenerConexion();
$controlador = new UsuarioController(new Usuario($pdo));

// El UsuarioController valida nombre, email, password y unicidad del email.
$resultado = $controlador->registrar($nombre, $email, $password, $rol, $telefono);

if (!$resultado['ok']) {
    // Devolver el primer error (el Controller puede devolver varios)
    jsonResponse(['error' => implode(' ', $resultado['errores'])], 400);
}

$usuarioId = $resultado['usuario_id'];

// ── Si es emprendedor: crear emprendimiento vacío ─────────────────────────────
if ($rol === 'emprendedor') {
    try {
        (new Emprendimiento($pdo))->crear(
            $usuarioId,
            $nombreNegocio,   // nombre obligatorio
            null,             // descripcion → vacía, el emprendedor la completa en Mi negocio
            null,             // dirección  → ídem
            null              // teléfono_contacto → ídem
        );
    } catch (\Throwable $e) {
        // En caso de fallo al crear el emprendimiento, eliminar el usuario
        // para no dejar un emprendedor sin negocio huérfano en la BD.
        $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(['id' => $usuarioId]);
        jsonResponse(['error' => 'Error interno al crear el emprendimiento. Intenta nuevamente.'], 500);
    }
}

// ── Iniciar sesión automáticamente tras el registro ───────────────────────────
// Así el frontend puede redirigir al panel sin pasar por /login.
session_regenerate_id(true);

$_SESSION['usuario_id']     = $usuarioId;
$_SESSION['usuario_nombre'] = $nombre;
$_SESSION['usuario_rol']    = $rol;

// ── Respuesta ─────────────────────────────────────────────────────────────────
$redirect = match ($rol) {
    'emprendedor' => '/emprendedor/mi-negocio',   // completar datos del negocio
    default       => '/cliente/catalogo',          // ir directo al catálogo
};

jsonResponse([
    'success'  => true,
    'user'     => [
        'id'     => $usuarioId,
        'nombre' => $nombre,
        'rol'    => $rol,
    ],
    'redirect' => $redirect,
], 201);
