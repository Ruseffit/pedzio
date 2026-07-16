<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['emprendedor'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Categoria;

$pdo = obtenerConexion();
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId((int) $_SESSION['usuario_id']);

if (!$emprendimiento) {
    flashSet('error', 'No se encontró un emprendimiento asociado a tu cuenta.');
    redirigir('/login.php');
}

$categoriaModelo = new Categoria($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    if (isset($_POST['nombre']) && trim($_POST['nombre']) !== '') {
        $categoriaModelo->crear((int) $emprendimiento['id'], trim($_POST['nombre']));
        flashSet('exito', 'Categoría creada.');
    } elseif (isset($_POST['eliminar_id'])) {
        $categoriaModelo->eliminar((int) $_POST['eliminar_id'], (int) $emprendimiento['id']);
        flashSet('exito', 'Categoría eliminada.');
    }
    redirigir('/emprendedor/categorias.php');
}

$categorias = $categoriaModelo->listarPorEmprendimiento((int) $emprendimiento['id']);

$tituloPagina = 'Categorías';
$eyebrowPagina = 'Emprendedor';
$itemActivo = '/emprendedor/categorias.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <h2>Nueva categoría</h2>
    <form method="post" action="/emprendedor/categorias.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
        <label for="nombre">Nombre de la categoría</label>
        <input type="text" id="nombre" name="nombre" required>
        <button type="submit" class="pedzio-btn pedzio-btn--primario">Agregar</button>
    </form>
</div>

<div class="pedzio-panel">
    <h2>Tus categorías</h2>
    <ul class="pedzio-lista">
        <?php foreach ($categorias as $cat): ?>
            <li style="display:flex; justify-content:space-between; align-items:center;">
                <?= e($cat['nombre']) ?>
                <form method="post" action="/emprendedor/categorias.php" style="display:inline">
                    <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                    <button type="submit" name="eliminar_id" value="<?= (int) $cat['id'] ?>"
                            class="pedzio-btn pedzio-btn--peligro">Eliminar</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php if (empty($categorias)): ?>
            <li>Aún no tienes categorías.</li>
        <?php endif; ?>
    </ul>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
