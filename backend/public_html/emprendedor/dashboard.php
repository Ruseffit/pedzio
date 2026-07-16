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

$tituloPagina = $emprendimiento['nombre_negocio'];
$eyebrowPagina = 'Panel de control';
$itemActivo = '/emprendedor/dashboard.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-kpi-row">
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📦</div>
        <div class="pedzio-kpi-card__texto"><p>Pedidos totales</p><strong><?= (int) $reporte['total_pedidos'] ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">💰</div>
        <div class="pedzio-kpi-card__texto"><p>Ingresos</p><strong><?= formatearMoneda($reporte['total_ingresos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📉</div>
        <div class="pedzio-kpi-card__texto"><p>Gastos</p><strong><?= formatearMoneda($reporte['total_gastos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📊</div>
        <div class="pedzio-kpi-card__texto"><p>Balance</p><strong><?= formatearMoneda($reporte['balance']) ?></strong></div>
    </div>
</div>

<div class="pedzio-panel">
    <h2>Accesos rápidos</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="pedzio-btn pedzio-btn--primario" href="/emprendedor/pedidos.php">Ver pedidos recibidos</a>
        <a class="pedzio-btn pedzio-btn--secundario" href="/emprendedor/productos.php">Gestionar productos</a>
        <a class="pedzio-btn pedzio-btn--secundario" href="/emprendedor/finanzas.php">Registrar un gasto</a>
    </div>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
