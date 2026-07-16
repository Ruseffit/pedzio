<?php
require_once __DIR__ . '/../bootstrap.php';

use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Categoria;
use Pedzio\Models\Producto;
use Pedzio\Models\Pedido;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Controllers\UsuarioController;
use Pedzio\Controllers\ProductoController;
use Pedzio\Controllers\PedidoController;
use Pedzio\Controllers\FinanzasController;
use Pedzio\Services\CarritoService;
use Pedzio\Services\FinanzasService;

echo "== tests/integration/FlujoPedidoTest.php ==\n";

$pdo = obtenerConexion();
$_SESSION = [];

// --- 1. Registro de usuarios (cliente + emprendedor) ---
$usuarioModelo = new Usuario($pdo);
$usuarioController = new UsuarioController($usuarioModelo);

$emailCliente = 'test_cliente_' . uniqid() . '@pedzio.test';
$emailEmprendedor = 'test_emprendedor_' . uniqid() . '@pedzio.test';

$resultadoCliente = $usuarioController->registrar('Cliente de Prueba', $emailCliente, 'ClavePrueba123', 'cliente');
assertVerdadero($resultadoCliente['ok'], 'registrar() crea un cliente correctamente');

$resultadoEmprendedor = $usuarioController->registrar('Emprendedor de Prueba', $emailEmprendedor, 'ClavePrueba123', 'emprendedor');
assertVerdadero($resultadoEmprendedor['ok'], 'registrar() crea un emprendedor correctamente');

// Email duplicado debe fallar
$resultadoDuplicado = $usuarioController->registrar('Otro Nombre', $emailCliente, 'ClavePrueba123', 'cliente');
assertVerdadero(!$resultadoDuplicado['ok'], 'registrar() rechaza un correo ya registrado');

// --- 2. Login ---
$sesionCliente = $usuarioController->iniciarSesion($emailCliente, 'ClavePrueba123');
assertVerdadero($sesionCliente !== null, 'iniciarSesion() valida credenciales correctas');

$sesionFallida = $usuarioController->iniciarSesion($emailCliente, 'claveIncorrecta');
assertVerdadero($sesionFallida === null, 'iniciarSesion() rechaza contraseña incorrecta');

// --- 3. Crear emprendimiento, categoría y producto ---
$emprendimientoModelo = new Emprendimiento($pdo);
$emprendimientoId = $emprendimientoModelo->crear($resultadoEmprendedor['usuario_id'], 'Negocio de Prueba');
assertVerdadero($emprendimientoId > 0, 'Emprendimiento se crea con ID válido');

$categoriaModelo = new Categoria($pdo);
$categoriaId = $categoriaModelo->crear($emprendimientoId, 'Categoría de prueba');

$productoModelo = new Producto($pdo);
$productoController = new ProductoController($productoModelo);
$resultadoProducto = $productoController->crear($emprendimientoId, 'Producto de prueba', 10.00, $categoriaId, 'Descripción de prueba');
assertVerdadero($resultadoProducto['ok'], 'ProductoController::crear() crea un producto válido');
$productoId = $resultadoProducto['producto_id'];

// Producto con precio inválido debe fallar
$resultadoProductoInvalido = $productoController->crear($emprendimientoId, 'Producto inválido', -5, null, null);
assertVerdadero(!$resultadoProductoInvalido['ok'], 'ProductoController::crear() rechaza precio negativo');

// --- 4. Flujo de carrito -> pedido (edición y consistencia) ---
CarritoService::vaciar();
CarritoService::agregar($productoId, 3);
assertVerdadero(!CarritoService::estaVacio(), 'El carrito no está vacío tras agregar un producto');

$pedidoModelo = new Pedido($pdo);
$movimientoModelo = new MovimientoFinanciero($pdo);
$finanzasService = new FinanzasService($movimientoModelo);
$pedidoController = new PedidoController($pedidoModelo, $productoModelo, $finanzasService);

$resultadoPedido = $pedidoController->confirmarDesdeCarrito(
    $resultadoCliente['usuario_id'],
    $emprendimientoId,
    'Av. de Prueba 123',
    'Pedido de prueba automatizada'
);
assertVerdadero($resultadoPedido['ok'], 'confirmarDesdeCarrito() confirma el pedido correctamente');
$pedidoId = $resultadoPedido['pedido_id'];

assertVerdadero(CarritoService::estaVacio(), 'El carrito se vacía tras confirmar el pedido');

$pedidoGuardado = $pedidoModelo->buscarPorId($pedidoId);
assertIgual(30.00, (float) $pedidoGuardado['total'], 'El total del pedido es correcto (3 x 10.00 = 30.00)');
assertIgual('pendiente', $pedidoGuardado['estado'], 'El pedido nuevo inicia en estado "pendiente"');

$detalle = $pedidoModelo->detalleDePedido($pedidoId);
assertIgual(1, count($detalle), 'El pedido tiene exactamente 1 línea de detalle');
assertIgual(3, (int) $detalle[0]['cantidad'], 'La cantidad del detalle es correcta');

// --- 5. Edición de estado del pedido (emprendedor) ---
$actualizado = $pedidoController->cambiarEstado($pedidoId, 'confirmado', $emprendimientoId);
assertVerdadero($actualizado, 'cambiarEstado() actualiza el estado del pedido');
$pedidoActualizado = $pedidoModelo->buscarPorId($pedidoId);
assertIgual('confirmado', $pedidoActualizado['estado'], 'El estado del pedido cambió a "confirmado"');

// Un emprendimiento_id incorrecto NO debe poder cambiar el estado (control de acceso a nivel de datos)
$actualizadoAjeno = $pedidoController->cambiarEstado($pedidoId, 'cancelado', 999999);
$pedidoNoAlterado = $pedidoModelo->buscarPorId($pedidoId);
assertIgual('confirmado', $pedidoNoAlterado['estado'], 'Un emprendimiento ajeno NO puede alterar el estado del pedido');

// --- 6. Registro de ingreso automático y gasto manual ---
$finanzasController = new FinanzasController($finanzasService);
$movimientos = $movimientoModelo->listarPorEmprendimiento($emprendimientoId);
$hayIngresoDelPedido = false;
foreach ($movimientos as $mov) {
    if ((int) ($mov['pedido_id'] ?? 0) === $pedidoId && $mov['tipo'] === 'ingreso') {
        $hayIngresoDelPedido = true;
    }
}
assertVerdadero($hayIngresoDelPedido, 'Se registró automáticamente el ingreso financiero al confirmar el pedido');

$resultadoGasto = $finanzasController->registrarGasto($emprendimientoId, 'insumos', 15.00, 'Compra de prueba', date('Y-m-d'));
assertVerdadero($resultadoGasto['ok'], 'FinanzasController::registrarGasto() registra un gasto válido');

$resumen = $finanzasController->resumenMensual($emprendimientoId);
assertVerdadero($resumen['total_ingresos'] >= 30.00, 'El resumen mensual refleja el ingreso del pedido de prueba');
assertVerdadero($resumen['total_gastos'] >= 15.00, 'El resumen mensual refleja el gasto manual registrado');

// --- 7. Validación de formularios (formato de datos) ---
$resultadoGastoInvalido = $finanzasController->registrarGasto($emprendimientoId, '', -5, '', date('Y-m-d'));
assertVerdadero(!$resultadoGastoInvalido['ok'], 'registrarGasto() rechaza categoría vacía y monto negativo');

echo "\n  (Datos de prueba creados con prefijo 'test_' / 'Producto de prueba' — no afectan datos reales de producción)\n";
