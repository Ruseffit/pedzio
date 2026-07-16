<?php
/**
 * Configuración segura de sesión PHP nativa.
 * Debe incluirse ANTES de cualquier session_start() o salida HTML.
 *
 * Nota sobre SameSite en desarrollo local:
 * http://localhost:3001 (frontend) y http://localhost:8000 (backend) son
 * distintos ORÍGENES (CORS los trata como cross-origin) pero el MISMO SITE
 * para efectos de cookies (mismo host "localhost", solo cambia el puerto).
 * Por eso SameSite=Lax SÍ viaja en peticiones fetch(credentials:'include')
 * entre esos dos puertos — no hace falta (ni conviene) usar SameSite=None.
 *
 * IMPORTANTE: SameSite=None sin Secure es rechazado por Chrome/Firefox
 * modernos (la cookie de sesión simplemente no se guardaría). Si algún día
 * despliegas frontend y backend en DOMINIOS distintos con HTTPS, ahí sí
 * necesitarás SameSite=None + Secure=true — este archivo ya lo detecta solo
 * a través de APP_ENV/HTTPS, sin que tengas que tocar código.
 */

require_once __DIR__ . '/env.php';

if (session_status() === PHP_SESSION_NONE) {
    $nombreSesion = env('SESSION_NAME', 'pedzio_session');
    $tiempoVida   = (int) env('SESSION_LIFETIME', 7200);
    $esHttps      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $esProduccion = env('APP_ENV', 'local') === 'production';

    // Dev local (http, mismo host "localhost", distinto puerto): Lax + secure=false.
    // Producción real (https, dominios distintos): None + secure=true.
    if ($esProduccion && $esHttps) {
        $sameSite = 'None';
        $secure   = true;
    } else {
        $sameSite = 'Lax';
        $secure   = $esHttps; // false en localhost sin certificado
    }

    session_name($nombreSesion);
    session_set_cookie_params([
        'lifetime' => $tiempoVida,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => $sameSite,
    ]);
    session_start();

    // Regenera el ID de sesión periódicamente para mitigar fijación de sesión.
    if (empty($_SESSION['_creada_en'])) {
        $_SESSION['_creada_en'] = time();
    } elseif (time() - $_SESSION['_creada_en'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_creada_en'] = time();
    }
}

/**
 * Genera y guarda un token CSRF si no existe uno en la sesión actual.
 */
function pedzio_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Valida un token CSRF recibido contra el guardado en sesión.
 */
function pedzio_csrf_valido(?string $tokenRecibido): bool
{
    return !empty($tokenRecibido)
        && !empty($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $tokenRecibido);
}
