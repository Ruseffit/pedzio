<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['superadmin'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Usuario;

$pdo = obtenerConexion();
$usuarioModelo = new Usuario($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    $id = (int) ($_POST['usuario_id'] ?? 0);
    $activo = $_POST['accion'] === 'activar';
    if ($id !== (int) $_SESSION['usuario_id']) {
        $usuarioModelo->activarDesactivar($id, $activo);
        flashSet('exito', 'Estado del usuario actualizado.');
    } else {
        flashSet('error', 'No puedes desactivar tu propia cuenta.');
    }
    redirigir('/superadmin/usuarios.php');
}

$usuarios = $usuarioModelo->listarTodos();

$tituloPagina = 'Usuarios de la plataforma';
$eyebrowPagina = 'SuperAdmin';
$itemActivo = '/superadmin/usuarios.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <table class="pedzio-tabla">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['nombre']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['rol'])) ?></td>
                    <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
                    <td>
                        <form method="post" action="/superadmin/usuarios.php" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                            <input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>">
                            <button type="submit" name="accion" value="<?= $u['activo'] ? 'desactivar' : 'activar' ?>"
                                    class="pedzio-btn <?= $u['activo'] ? 'pedzio-btn--peligro' : 'pedzio-btn--secundario' ?>">
                                <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
