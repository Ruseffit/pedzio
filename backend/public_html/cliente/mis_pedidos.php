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
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$pedidoController = new PedidoController(
    new Pedido($pdo),
    new Producto($pdo),
    new FinanzasService(new MovimientoFinanciero($pdo))
);

$pedidos = $pedidoController->listarPorCliente((int) $_SESSION['usuario_id']);

$tituloPagina = 'Mis pedidos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-pedidos">
    <h1>Mis pedidos</h1>

    <table class="pedzio-tabla">
        <thead><tr><th>#</th><th>Estado</th><th>Total</th><th>Fecha</th></tr></thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td><?= e(Formato::estadoPedidoLegible($pedido['estado'])) ?></td>
                    <td><?= formatearMoneda((float) $pedido['total']) ?></td>
                    <td><?= formatearFecha($pedido['creado_en']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="4">Aún no tienes pedidos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
