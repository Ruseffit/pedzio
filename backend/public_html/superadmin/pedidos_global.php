<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Pedido;
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$pedidos = (new Pedido($pdo))->listarTodos();

$tituloPagina = 'Pedidos totales';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/pedidos_global.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <table class="pedzio-tabla">
        <thead><tr><th>ID Pedido</th><th>Negocio</th><th>Cliente</th><th>Estado</th><th>Total</th><th>Fecha</th></tr></thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td><?= e($pedido['nombre_negocio']) ?></td>
                    <td><?= e($pedido['nombre_cliente']) ?></td>
                    <td><span class="pedzio-badge <?= Formato::estadoPedidoClase($pedido['estado']) ?>">
                        <?= e(Formato::estadoPedidoLegible($pedido['estado'])) ?>
                    </span></td>
                    <td><?= formatearMoneda((float) $pedido['total']) ?></td>
                    <td><?= formatearFecha($pedido['creado_en']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pedidos)): ?>
                <tr><td colspan="6">Aún no hay pedidos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
