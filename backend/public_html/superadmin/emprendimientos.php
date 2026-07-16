<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;

$pdo = obtenerConexion();
$emprendimientos = (new Emprendimiento($pdo))->listarTodos();

$tituloPagina = 'Emprendimientos registrados';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/emprendimientos.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <table class="pedzio-tabla">
        <thead><tr><th>Negocio</th><th>Dueño</th><th>Correo</th><th>Estado</th><th>Creado</th></tr></thead>
        <tbody>
            <?php foreach ($emprendimientos as $emp): ?>
                <tr>
                    <td><?= e($emp['nombre_negocio']) ?></td>
                    <td><?= e($emp['nombre_dueno']) ?></td>
                    <td><?= e($emp['email_dueno']) ?></td>
                    <td><?= $emp['activo'] ? 'Activo' : 'Inactivo' ?></td>
                    <td><?= formatearFecha($emp['creado_en'], 'd/m/Y') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($emprendimientos)): ?>
                <tr><td colspan="5">Aún no hay emprendimientos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
