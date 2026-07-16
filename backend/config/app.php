<?php
/**
 * Configuración general de la aplicación + autoloader simple (sin Composer).
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/constants.php';

date_default_timezone_set('America/Lima');

if (env('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

/**
 * Autoloader PSR-4 simplificado para src/Controllers, src/Models, src/Services, src/Helpers.
 * Ej: new Pedzio\Models\Pedido() -> src/Models/Pedido.php
 */
spl_autoload_register(function (string $clase) {
    $prefijo = 'Pedzio\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }
    $rutaRelativa = str_replace('\\', '/', substr($clase, strlen($prefijo)));
    $rutaArchivo = __DIR__ . '/../src/' . $rutaRelativa . '.php';
    if (is_file($rutaArchivo)) {
        require_once $rutaArchivo;
    }
});

/**
 * Autoload de Composer (librerías de terceros, ej. minishlink/web-push-php).
 * Se registra DESPUÉS del autoloader manual, como respaldo: para clases
 * Pedzio\* sigue resolviendo primero el autoloader de arriba (sin cambios de
 * comportamiento); para clases de librerías externas (Minishlink\WebPush\*)
 * cae aquí. Si vendor/ no existe (ej. todavía no se corrió "composer
 * install"), la app sigue funcionando normal para todo lo que no dependa de
 * una librería externa.
 */
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}
