<?php
require_once __DIR__ . '/../bootstrap.php';

use Pedzio\Helpers\Formato;

echo "== tests/unit/FormatoTest.php ==\n";

assertIgual('S/ 18.50', Formato::moneda(18.5), 'moneda() formatea con 2 decimales y prefijo S/');
assertIgual('S/ 0.00', Formato::moneda(0), 'moneda() formatea cero correctamente');
assertIgual('Pendiente', Formato::estadoPedidoLegible('pendiente'), 'estadoPedidoLegible() traduce "pendiente"');
assertIgual('En preparación', Formato::estadoPedidoLegible('en_preparacion'), 'estadoPedidoLegible() traduce "en_preparacion"');
assertIgual('Otro', Formato::estadoPedidoLegible('otro'), 'estadoPedidoLegible() usa ucfirst() como fallback');
