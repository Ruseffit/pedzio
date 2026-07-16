<?php
require_once __DIR__ . '/../bootstrap.php';

echo "== tests/integration/ConexionBDTest.php ==\n";

try {
    $pdo = obtenerConexion();
    assertVerdadero($pdo instanceof PDO, 'obtenerConexion() devuelve una instancia PDO');

    $resultado = $pdo->query('SELECT 1 AS ok')->fetch();
    assertIgual(1, $resultado['ok'], 'La base de datos responde a una consulta simple (SELECT 1)');

    $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tablasEsperadas = ['usuarios', 'emprendimientos', 'categorias', 'productos', 'pedidos', 'detalle_pedidos', 'movimientos_financieros'];
    foreach ($tablasEsperadas as $tabla) {
        assertVerdadero(in_array($tabla, $tablas, true), "La tabla '{$tabla}' existe en la base de datos");
    }
} catch (\Throwable $e) {
    assertVerdadero(false, 'Conexión a la base de datos falló: ' . $e->getMessage());
}
