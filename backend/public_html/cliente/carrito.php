<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['cliente'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Producto;
use Pedzio\Services\CarritoService;

$pdo = obtenerConexion();
$productoModelo = new Producto($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    if (isset($_POST['eliminar_producto_id'])) {
        CarritoService::eliminar((int) $_POST['eliminar_producto_id']);
    } elseif (isset($_POST['actualizar']) && isset($_POST['cantidades']) && is_array($_POST['cantidades'])) {
        foreach ($_POST['cantidades'] as $productoId => $cantidad) {
            CarritoService::actualizarCantidad((int) $productoId, (int) $cantidad);
        }
    }
    redirigir('/cliente/carrito.php');
}

// Trae los productos reales del carrito para mostrar precios/actuales, nunca desde sesión directamente.
$productosPorId = [];
foreach (array_keys(CarritoService::obtener()) as $productoId) {
    $producto = $productoModelo->buscarPorId((int) $productoId);
    if ($producto) {
        $productosPorId[(int) $productoId] = $producto;
    }
}
$detalleCarrito = CarritoService::calcularDetalle($productosPorId);

$emprendimientoIdCarrito = null;
if (!empty($productosPorId)) {
    $primero = reset($productosPorId);
    $emprendimientoIdCarrito = (int) $primero['emprendimiento_id'];
}

$tituloPagina = 'Mi carrito';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-carrito">
    <h1>Mi carrito</h1>

    <?php if (empty($detalleCarrito['items'])): ?>
        <p>Tu carrito está vacío. <a href="/cliente/catalogo.php">Ver catálogo</a></p>
    <?php else: ?>
        <form method="post" action="/cliente/carrito.php">
            <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
            <table class="pedzio-tabla">
                <thead>
                    <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($detalleCarrito['items'] as $item): ?>
                        <tr>
                            <td><?= e($item['nombre']) ?></td>
                            <td>
                                <input type="number" min="1" name="cantidades[<?= $item['producto_id'] ?>]"
                                       value="<?= $item['cantidad'] ?>" style="width:60px">
                            </td>
                            <td><?= formatearMoneda($item['precio_unitario']) ?></td>
                            <td><?= formatearMoneda($item['subtotal']) ?></td>
                            <td>
                                <button type="submit" name="eliminar_producto_id" value="<?= $item['producto_id'] ?>"
                                        class="pedzio-btn pedzio-btn--peligro">Quitar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="pedzio-total">Total: <?= formatearMoneda($detalleCarrito['total']) ?></p>
            <button type="submit" name="actualizar" value="1" class="pedzio-btn pedzio-btn--secundario">Actualizar cantidades</button>
        </form>

        <a class="pedzio-btn pedzio-btn--primario"
           href="/cliente/pedido_confirmar.php?emprendimiento_id=<?= $emprendimientoIdCarrito ?>">
            Continuar y confirmar pedido
        </a>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
