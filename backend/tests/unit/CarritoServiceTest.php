<?php
require_once __DIR__ . '/../bootstrap.php';

use Pedzio\Services\CarritoService;

echo "== tests/unit/CarritoServiceTest.php ==\n";

// Simula una sesión sin necesidad de un servidor HTTP real.
$_SESSION = [];

CarritoService::agregar(1, 2);
CarritoService::agregar(2, 1);
assertIgual(2, count(CarritoService::obtener()), 'agregar() suma 2 productos distintos al carrito');

CarritoService::agregar(1, 3);
assertIgual(5, CarritoService::obtener()[1], 'agregar() acumula cantidad del mismo producto (2+3=5)');

CarritoService::actualizarCantidad(1, 1);
assertIgual(1, CarritoService::obtener()[1], 'actualizarCantidad() sobrescribe la cantidad');

CarritoService::actualizarCantidad(2, 0);
assertVerdadero(!isset(CarritoService::obtener()[2]), 'actualizarCantidad() con 0 elimina el producto');

$productosPorId = [
    1 => ['nombre' => 'Lomo saltado', 'precio' => 18.50, 'emprendimiento_id' => 1],
];
$detalle = CarritoService::calcularDetalle($productosPorId);
assertIgual(18.50, $detalle['total'], 'calcularDetalle() calcula el total correctamente (1 x 18.50)');

CarritoService::vaciar();
assertVerdadero(CarritoService::estaVacio(), 'vaciar() deja el carrito vacío');
