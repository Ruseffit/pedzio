<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\MovimientoFinanciero;

$pdo = obtenerConexion();
$emprendimientos = (new Emprendimiento($pdo))->listarTodos();
$movimientoModelo = new MovimientoFinanciero($pdo);

$resumenes = [];
foreach ($emprendimientos as $emp) {
    $resumenes[] = [
        'negocio' => $emp['nombre_negocio'],
        'resumen' => $movimientoModelo->resumen((int) $emp['id']),
    ];
}

$tituloPagina = 'Auditoría Financiera';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/finanzas_global.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <table class="pedzio-tabla">
        <thead><tr><th>Negocio</th><th>Ingresos</th><th>Gastos</th><th>Balance</th></tr></thead>
        <tbody>
            <?php foreach ($resumenes as $r): ?>
                <tr>
                    <td><?= e($r['negocio']) ?></td>
                    <td><?= formatearMoneda($r['resumen']['total_ingresos']) ?></td>
                    <td><?= formatearMoneda($r['resumen']['total_gastos']) ?></td>
                    <td><?= formatearMoneda($r['resumen']['balance']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($resumenes)): ?>
                <tr><td colspan="4">Aún no hay emprendimientos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
