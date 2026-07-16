<?php
/**
 * Bootstrap de la API REST de Pedzio.
 * Incluir al inicio de todo endpoint: require_once __DIR__ . '/config.php';
 *
 * Proporciona:
 *   - Headers CORS (whitelist de orígenes) con soporte de credenciales
 *   - Respuesta automática a preflight OPTIONS, ANTES de tocar sesión/BD
 *   - jsonResponse()  → envía JSON y termina la ejecución
 *   - jsonInput()     → lee y decodifica el body JSON de la petición
 *   - requireAuth()   → verifica sesión PHP activa; aborta con 401 si no existe
 */

// ── 1. Headers CORS (whitelist de orígenes) ─────────────────────────────────
// IMPORTANTE: esto va ANTES de requerir _bootstrap.php a propósito. Así, si
// _bootstrap.php (sesión, BD, includes) fallara con un error fatal, el
// preflight OPTIONS igual recibe sus headers CORS y no rompe el navegador.
//
// La whitelist depende del entorno (.env → APP_ENV):
//   - En local, se agregan los puertos típicos de Next.js en desarrollo.
//   - En producción, solo se permiten los orígenes definidos en CORS_ORIGENES
//     (lista separada por comas, sin espacios), ej.:
//       CORS_ORIGENES=https://www.tudominio.com,https://tudominio.com
require_once __DIR__ . '/../../config/env.php';

$origenesPermitidos = [];

if (env('APP_ENV', 'production') === 'local') {
    // Next.js suele probar el puerto 3000 y, si está ocupado, sube a 3001, 3002...
    $origenesPermitidos = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ];
}

$origenesProduccion = env('CORS_ORIGENES', '');
if ($origenesProduccion !== '') {
    foreach (explode(',', $origenesProduccion) as $origen) {
        $origen = trim($origen);
        if ($origen !== '') {
            $origenesPermitidos[] = $origen;
        }
    }
}

$origenSolicitante = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origenSolicitante !== '' && in_array($origenSolicitante, $origenesPermitidos, true)) {
    header("Access-Control-Allow-Origin: {$origenSolicitante}");
    header('Access-Control-Allow-Credentials: true');
    // Vary: Origin evita que un proxy/cache mezcle respuestas de distintos orígenes.
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // caché preflight 24 h

// ── 2. Respuesta a preflight OPTIONS (sin tocar sesión/BD) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 3. Bootstrap del sistema (config, sesión, BD, includes) ────────────────
require_once __DIR__ . '/../_bootstrap.php';

// ── 4. Forzar Content-Type JSON en todas las respuestas reales de la API ───
header('Content-Type: application/json; charset=utf-8');

// ── 5. Funciones de utilidad ─────────────────────────────────────────────────

/**
 * Envía una respuesta JSON y termina la ejecución.
 *
 * @param mixed $datos    Datos a serializar (array, objeto o escalar).
 * @param int   $codigo   Código HTTP de respuesta (default 200).
 * @return never
 */
function jsonResponse(mixed $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Lee y decodifica el body JSON de la petición entrante.
 * Aborta con 400 si el body no es JSON válido.
 *
 * @return array<string, mixed>
 */
function jsonInput(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === '' || $raw === false) {
        return [];
    }

    $datos = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Clave 'error' (no 'mensaje') a propósito: es la que lee
        // frontend/lib/api.js (`data?.error`) en cada endpoint de la API.
        jsonResponse([
            'ok'    => false,
            'error' => 'Body inválido: se esperaba JSON bien formado. ' . json_last_error_msg(),
        ], 400);
    }

    return $datos ?? [];
}

/**
 * Verifica que exista una sesión PHP activa con usuario autenticado.
 * Si no hay sesión, responde 401 y termina.
 *
 * @param string[]|null $rolesPermitidos  Lista de roles aceptados; null = cualquier rol.
 * @return array{id: int, rol: string, nombre: string}  Datos del usuario de sesión.
 */
function requireAuth(?array $rolesPermitidos = null): array
{
    if (empty($_SESSION['usuario_id'])) {
        // Clave 'error' (no 'mensaje') a propósito: es la que lee
        // frontend/lib/api.js (`data?.error`) en cada endpoint de la API.
        jsonResponse([
            'ok'    => false,
            'error' => 'No autenticado. Inicia sesión para continuar.',
        ], 401);
    }

    $usuario = [
        'id'     => (int) $_SESSION['usuario_id'],
        'rol'    => $_SESSION['usuario_rol']    ?? '',
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
    ];

    if ($rolesPermitidos !== null && !in_array($usuario['rol'], $rolesPermitidos, true)) {
        jsonResponse([
            'ok'    => false,
            'error' => 'No tienes permiso para realizar esta acción.',
        ], 403);
    }

    return $usuario;
}
