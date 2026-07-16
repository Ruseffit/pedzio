<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['emprendedor'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Pedido;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Services\ReporteService;
use Pedzio\Controllers\ReporteController;
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId((int) $_SESSION['usuario_id']);

if (!$emprendimiento) {
    flashSet('error', 'No se encontró un emprendimiento asociado a tu cuenta.');
    redirigir('/login.php');
}

$reporteController = new ReporteController(
    new ReporteService(new Pedido($pdo), new MovimientoFinanciero($pdo))
);
$reporte = $reporteController->reporteDeEmprendimiento((int) $emprendimiento['id']);

$tituloPagina = 'Reportes';
$eyebrowPagina = 'Emprendedor';
$itemActivo = '/emprendedor/reportes.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-kpi-row">
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📦</div>
        <div class="pedzio-kpi-card__texto"><p>Total de pedidos</p><strong><?= (int) $reporte['total_pedidos'] ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">💰</div>
        <div class="pedzio-kpi-card__texto"><p>Ingresos totales</p><strong><?= formatearMoneda($reporte['total_ingresos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📉</div>
        <div class="pedzio-kpi-card__texto"><p>Gastos totales</p><strong><?= formatearMoneda($reporte['total_gastos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📊</div>
        <div class="pedzio-kpi-card__texto"><p>Balance total</p><strong><?= formatearMoneda($reporte['balance']) ?></strong></div>
    </div>
</div>

<div class="pedzio-panel">
    <h2>Pedidos por estado</h2>
    <?php if (!empty($reporte['conteo_por_estado'])): ?>
        <canvas id="graficoEstados" height="90"></canvas>
    <?php else: ?>
        <p>Aún no hay pedidos para mostrar.</p>
    <?php endif; ?>
</div>

<?php if (!empty($reporte['conteo_por_estado'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('graficoEstados');
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map([Formato::class, 'estadoPedidoLegible'], array_keys($reporte['conteo_por_estado']))) ?>,
            datasets: [{
                data: <?= json_encode(array_values($reporte['conteo_por_estado'])) ?>,
                backgroundColor: ['#E85D2C', '#1565C0', '#6A3FA0', '#00838F', '#2E7D32', '#C62828']
            }]
        },
        options: { plugins: { legend: { position: 'right' } } }
    });
});
</script>
<?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
