<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Pedido;
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$pedidos = (new Pedido($pdo))->listarTodos();

$conteoPorEstado = [];
foreach ($pedidos as $pedido) {
    $estado = $pedido['estado'];
    $conteoPorEstado[$estado] = ($conteoPorEstado[$estado] ?? 0) + 1;
}

$tituloPagina = 'Reportes globales';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/reportes_global.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <h2>Total de pedidos: <?= count($pedidos) ?></h2>
    <?php if (!empty($conteoPorEstado)): ?>
        <canvas id="graficoReportesGlobal" height="100"></canvas>
    <?php else: ?>
        <p>Aún no hay pedidos para mostrar.</p>
    <?php endif; ?>
</div>

<?php if (!empty($conteoPorEstado)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('graficoReportesGlobal');
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map([Formato::class, 'estadoPedidoLegible'], array_keys($conteoPorEstado))) ?>,
            datasets: [{
                label: 'Pedidos',
                data: <?= json_encode(array_values($conteoPorEstado)) ?>,
                backgroundColor: '#E85D2C'
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
});
</script>
<?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
