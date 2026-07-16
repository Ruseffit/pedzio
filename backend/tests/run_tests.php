#!/usr/bin/env php
<?php
/**
 * Ejecuta todas las pruebas unitarias e de integración y muestra un resumen final.
 * Uso: php tests/run_tests.php
 */

require_once __DIR__ . '/bootstrap.php';

$archivosTest = array_merge(
    glob(__DIR__ . '/unit/*Test.php'),
    glob(__DIR__ . '/integration/*Test.php')
);

sort($archivosTest);

foreach ($archivosTest as $archivo) {
    require $archivo;
    echo "\n";
}

pedzio_resumen_tests();
