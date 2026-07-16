<?php
/**
 * Bootstrap mínimo para tests (sin PHPUnit, para no depender de Composer/internet
 * en el hosting compartido). Define un mini-framework de aserciones.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$GLOBALS['_pedzio_tests_total'] = 0;
$GLOBALS['_pedzio_tests_fallidos'] = 0;

function assertVerdadero(bool $condicion, string $mensaje): void
{
    $GLOBALS['_pedzio_tests_total']++;
    if ($condicion) {
        echo "  [OK] {$mensaje}\n";
    } else {
        $GLOBALS['_pedzio_tests_fallidos']++;
        echo "  [FALLÓ] {$mensaje}\n";
    }
}

function assertIgual($esperado, $real, string $mensaje): void
{
    assertVerdadero($esperado == $real, "{$mensaje} (esperado: " . var_export($esperado, true) . ', real: ' . var_export($real, true) . ')');
}

function pedzio_resumen_tests(): void
{
    $total = $GLOBALS['_pedzio_tests_total'];
    $fallidos = $GLOBALS['_pedzio_tests_fallidos'];
    $exitosos = $total - $fallidos;
    echo "\n---\nResultado: {$exitosos}/{$total} pruebas exitosas.\n";
    if ($fallidos > 0) {
        echo "{$fallidos} prueba(s) fallaron.\n";
        exit(1);
    }
    exit(0);
}
