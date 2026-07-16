<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Usuario;
use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Pedido;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Helpers\Formato;

$pdo = obtenerConexion();
$usuarioModelo = new Usuario($pdo);
$emprendimientoModelo = new Emprendimiento($pdo);
$pedidoModelo = new Pedido($pdo);
$movimientoModelo = new MovimientoFinanciero($pdo);

$totalUsuarios = count($usuarioModelo->listarPorRol('cliente')) + count($usuarioModelo->listarPorRol('emprendedor'));
$totalEmprendimientos = count($emprendimientoModelo->listarTodos());
$pedidos = $pedidoModelo->listarTodos();

// Clientes nuevos en los últimos 30 días (dato real, no inventado).
$clientes = $usuarioModelo->listarPorRol('cliente');
$clientesNuevos = 0;
$hace30dias = date('Y-m-d', strtotime('-30 days'));
foreach ($clientes as $c) {
    if (substr($c['creado_en'], 0, 10) >= $hace30dias) {
        $clientesNuevos++;
    }
}

$resumenGlobal = $movimientoModelo->resumenGlobal();
$ventasMensuales = $movimientoModelo->ventasMensualesGlobal(9);

$conteoPorEstado = [];
foreach ($pedidos as $pedido) {
    $conteoPorEstado[$pedido['estado']] = ($conteoPorEstado[$pedido['estado']] ?? 0) + 1;
}

$tituloPagina = 'Consola de Supervisión Global — Pedzio Lima';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/dashboard.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-kpi-row">
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">💰</div>
        <div class="pedzio-kpi-card__texto"><p>Total ventas</p><strong><?= formatearMoneda($resumenGlobal['total_ingresos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">🏪</div>
        <div class="pedzio-kpi-card__texto"><p>Emprendimientos activos</p><strong><?= $totalEmprendimientos ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">👥</div>
        <div class="pedzio-kpi-card__texto"><p>Nuevos clientes (30 días)</p><strong><?= $clientesNuevos ?></strong></div>
    </div>
</div>

<div class="pedzio-panel">
    <h2>Volumen total de ventas (S/) — Mensual</h2>
    <?php if (!empty($ventasMensuales['labels'])): ?>
        <canvas id="graficoVentas" height="90"></canvas>
    <?php else: ?>
        <p>Aún no hay movimientos financieros registrados en la plataforma.</p>
    <?php endif; ?>
</div>

<div class="pedzio-grid">
    <div class="pedzio-panel" style="margin-bottom:0;">
        <h2>Pedidos por estado</h2>
        <?php if (!empty($conteoPorEstado)): ?>
            <canvas id="graficoEstadosGlobal" height="180"></canvas>
        <?php else: ?>
            <p>Aún no hay pedidos registrados.</p>
        <?php endif; ?>
    </div>
    <div class="pedzio-panel" style="margin-bottom:0;">
        <h2>Ingresos vs. gastos (plataforma)</h2>
        <?php if ($resumenGlobal['total_ingresos'] > 0 || $resumenGlobal['total_gastos'] > 0): ?>
            <canvas id="graficoBalance" height="180"></canvas>
        <?php else: ?>
            <p>Aún no hay movimientos financieros registrados.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const ctxVentas = document.getElementById('graficoVentas');
    if (ctxVentas) {
        new Chart(ctxVentas, {
            type: 'line',
            data: {
                labels: <?= json_encode($ventasMensuales['labels']) ?>,
                datasets: [{
                    label: 'Ventas (S/)',
                    data: <?= json_encode($ventasMensuales['valores']) ?>,
                    borderColor: '#E85D2C',
                    backgroundColor: 'rgba(232,93,44,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });
    }

    const ctxEstados = document.getElementById('graficoEstadosGlobal');
    if (ctxEstados) {
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map([Formato::class, 'estadoPedidoLegible'], array_keys($conteoPorEstado))) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($conteoPorEstado)) ?>,
                    backgroundColor: ['#E85D2C', '#1565C0', '#6A3FA0', '#00838F', '#2E7D32', '#C62828']
                }]
            },
            options: { plugins: { legend: { position: 'right' } } }
        });
    }

    const ctxBalance = document.getElementById('graficoBalance');
    if (ctxBalance) {
        new Chart(ctxBalance, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Gastos'],
                datasets: [{
                    data: [<?= (float) $resumenGlobal['total_ingresos'] ?>, <?= (float) $resumenGlobal['total_gastos'] ?>],
                    backgroundColor: ['#2E7D32', '#C62828']
                }]
            },
            options: { plugins: { legend: { position: 'right' } } }
        });
    }
});
</script>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
