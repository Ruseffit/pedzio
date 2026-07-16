<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['cliente'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Pedido;
use Pedzio\Models\Producto;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Services\FinanzasService;
use Pedzio\Controllers\PedidoController;
use Pedzio\Services\CarritoService;

$pdo = obtenerConexion();
$pedidoController = new PedidoController(
    new Pedido($pdo),
    new Producto($pdo),
    new FinanzasService(new MovimientoFinanciero($pdo))
);

$emprendimientoId = (int) ($_GET['emprendimiento_id'] ?? $_POST['emprendimiento_id'] ?? 0);
$errores = [];

if (CarritoService::estaVacio()) {
    flashSet('error', 'Tu carrito está vacío.');
    redirigir('/cliente/catalogo.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
        $errores[] = 'Sesión expirada, intenta de nuevo.';
    } else {
        $direccion = trim($_POST['direccion_entrega'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        $resultado = $pedidoController->confirmarDesdeCarrito(
            (int) $_SESSION['usuario_id'],
            $emprendimientoId,
            $direccion,
            $notas ?: null
        );

        if ($resultado['ok']) {
            flashSet('exito', 'Pedido #' . $resultado['pedido_id'] . ' confirmado correctamente.');
            redirigir('/cliente/mis_pedidos.php');
        } else {
            $errores = $resultado['errores'];
        }
    }
}

$productoModelo = new Producto($pdo);
$productosPorId = [];
foreach (array_keys(CarritoService::obtener()) as $productoId) {
    $producto = $productoModelo->buscarPorId((int) $productoId);
    if ($producto) {
        $productosPorId[(int) $productoId] = $producto;
    }
}
$detalleCarrito = CarritoService::calcularDetalle($productosPorId);

$tituloPagina = 'Confirmar pedido';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-confirmar">
    <h1>Confirmar pedido</h1>

    <?php foreach ($errores as $error): ?>
        <div class="pedzio-flash pedzio-flash--error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <ul>
        <?php foreach ($detalleCarrito['items'] as $item): ?>
            <li><?= (int) $item['cantidad'] ?> x <?= e($item['nombre']) ?> — <?= formatearMoneda($item['subtotal']) ?></li>
        <?php endforeach; ?>
    </ul>
    <p class="pedzio-total">Total: <?= formatearMoneda($detalleCarrito['total']) ?></p>

    <form method="post" action="/cliente/pedido_confirmar.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
        <input type="hidden" name="emprendimiento_id" value="<?= $emprendimientoId ?>">

        <label for="direccion_entrega">Dirección de entrega</label>
        <input type="text" id="direccion_entrega" name="direccion_entrega" required>

        <label for="notas">Notas para el emprendedor (opcional)</label>
        <textarea id="notas" name="notas"></textarea>

        <button type="submit" class="pedzio-btn pedzio-btn--primario">Confirmar pedido</button>
    </form>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
