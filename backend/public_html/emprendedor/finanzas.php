<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['emprendedor'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\MovimientoFinanciero;
use Pedzio\Services\FinanzasService;
use Pedzio\Controllers\FinanzasController;

$pdo = obtenerConexion();
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId((int) $_SESSION['usuario_id']);

if (!$emprendimiento) {
    flashSet('error', 'No se encontró un emprendimiento asociado a tu cuenta.');
    redirigir('/login.php');
}

$emprendimientoId = (int) $emprendimiento['id'];
$movimientoModelo = new MovimientoFinanciero($pdo);
$finanzasController = new FinanzasController(new FinanzasService($movimientoModelo));

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    $resultado = $finanzasController->registrarGasto(
        $emprendimientoId,
        trim($_POST['categoria'] ?? ''),
        $_POST['monto'] ?? 0,
        trim($_POST['descripcion'] ?? ''),
        $_POST['fecha'] ?? date('Y-m-d')
    );
    if ($resultado['ok']) {
        flashSet('exito', 'Gasto registrado.');
        redirigir('/emprendedor/finanzas.php');
    } else {
        $errores = $resultado['errores'];
    }
}

$movimientos = $movimientoModelo->listarPorEmprendimiento($emprendimientoId);
$resumen = $finanzasController->resumenMensual($emprendimientoId);

$tituloPagina = 'Finanzas';
$eyebrowPagina = 'Emprendedor';
$itemActivo = '/emprendedor/finanzas.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-kpi-row">
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">💰</div>
        <div class="pedzio-kpi-card__texto"><p>Ingresos del mes</p><strong><?= formatearMoneda($resumen['total_ingresos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📉</div>
        <div class="pedzio-kpi-card__texto"><p>Gastos del mes</p><strong><?= formatearMoneda($resumen['total_gastos']) ?></strong></div>
    </div>
    <div class="pedzio-kpi-card">
        <div class="pedzio-kpi-card__icono">📊</div>
        <div class="pedzio-kpi-card__texto"><p>Balance del mes</p><strong><?= formatearMoneda($resumen['balance']) ?></strong></div>
    </div>
</div>

<div class="pedzio-panel">
    <h2>Registrar gasto</h2>
    <?php foreach ($errores as $error): ?>
        <div class="pedzio-flash pedzio-flash--error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/emprendedor/finanzas.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
        <label for="categoria">Categoría del gasto</label>
        <input type="text" id="categoria" name="categoria" placeholder="Ej: insumos, transporte" required>
        <label for="monto">Monto (S/)</label>
        <input type="number" id="monto" name="monto" step="0.10" min="0.10" required>
        <label for="descripcion">Descripción</label>
        <input type="text" id="descripcion" name="descripcion">
        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
        <button type="submit" class="pedzio-btn pedzio-btn--primario">Registrar gasto</button>
    </form>
</div>

<div class="pedzio-panel">
    <h2>Historial de movimientos</h2>
    <table class="pedzio-tabla">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Monto</th><th>Descripción</th></tr></thead>
        <tbody>
            <?php foreach ($movimientos as $mov): ?>
                <tr>
                    <td><?= formatearFecha($mov['fecha'], 'd/m/Y') ?></td>
                    <td><?= $mov['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto' ?></td>
                    <td><?= e($mov['categoria']) ?></td>
                    <td><?= formatearMoneda((float) $mov['monto']) ?></td>
                    <td><?= e($mov['descripcion'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($movimientos)): ?>
                <tr><td colspan="5">Aún no hay movimientos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
