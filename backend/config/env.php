<?php
/**
 * Cargador minimalista de variables de entorno (.env).
 * No requiere dependencias de Composer para funcionar en cualquier hosting compartido.
 */

function pedzio_cargar_env(string $rutaArchivo): void
{
    if (!is_file($rutaArchivo)) {
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        // Quita comillas envolventes si existen
        $valor = trim($valor, "\"'");

        if ($clave !== '' && getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

// Carga el .env real; si no existe (ej. en desarrollo sin configurar), no falla.
pedzio_cargar_env(__DIR__ . '/../.env');

/**
 * Helper para leer variables de entorno con valor por defecto.
 */
function env(string $clave, $porDefecto = null)
{
    $valor = getenv($clave);
    if ($valor === false) {
        return $porDefecto;
    }
    // Convierte strings booleanas comunes
    $mapaBooleanos = ['true' => true, 'false' => false, 'null' => null];
    $valorMin = strtolower($valor);
    return array_key_exists($valorMin, $mapaBooleanos) ? $mapaBooleanos[$valorMin] : $valor;
}
