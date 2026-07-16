<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['emprendedor'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Producto;
use Pedzio\Models\Categoria;
use Pedzio\Controllers\ProductoController;

$pdo = obtenerConexion();
$emprendimiento = (new Emprendimiento($pdo))->buscarPorUsuarioId((int) $_SESSION['usuario_id']);

if (!$emprendimiento) {
    flashSet('error', 'No se encontró un emprendimiento asociado a tu cuenta.');
    redirigir('/login.php');
}

$emprendimientoId = (int) $emprendimiento['id'];
$productoModelo = new Producto($pdo);
$categoriaModelo = new Categoria($pdo);
$productoController = new ProductoController($productoModelo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
    if (isset($_POST['crear'])) {
        $resultado = $productoController->crear(
            $emprendimientoId,
            trim($_POST['nombre'] ?? ''),
            $_POST['precio'] ?? 0,
            !empty($_POST['categoria_id']) ? (int) $_POST['categoria_id'] : null,
            trim($_POST['descripcion'] ?? '') ?: null
        );
        if ($resultado['ok']) {
            flashSet('exito', 'Producto creado.');
            redirigir('/emprendedor/productos.php');
        } else {
            $errores = $resultado['errores'];
        }
    } elseif (isset($_POST['eliminar_id'])) {
        $productoController->eliminar((int) $_POST['eliminar_id'], $emprendimientoId);
        flashSet('exito', 'Producto eliminado.');
        redirigir('/emprendedor/productos.php');
    } elseif (isset($_POST['toggle_id'])) {
        $producto = $productoModelo->buscarPorId((int) $_POST['toggle_id']);
        if ($producto && (int) $producto['emprendimiento_id'] === $emprendimientoId) {
            $productoController->actualizar((int) $producto['id'], $emprendimientoId, [
                'nombre'       => $producto['nombre'],
                'descripcion'  => $producto['descripcion'],
                'precio'       => $producto['precio'],
                'categoria_id' => $producto['categoria_id'],
                'disponible'   => !$producto['disponible'],
            ]);
        }
        redirigir('/emprendedor/productos.php');
    }
}

$productos = $productoController->listarPorEmprendimiento($emprendimientoId);
$categorias = $categoriaModelo->listarPorEmprendimiento($emprendimientoId);

$tituloPagina = 'Productos';
$eyebrowPagina = 'Emprendedor';
$itemActivo = '/emprendedor/productos.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/app_header.php';
?>
<div class="pedzio-panel">
    <h2>Nuevo producto</h2>
    <?php foreach ($errores as $error): ?>
        <div class="pedzio-flash pedzio-flash--error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/emprendedor/productos.php" class="pedzio-form">
        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
        <label for="nombre">Nombre del producto</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion"></textarea>

        <label for="precio">Precio (S/)</label>
        <input type="number" id="precio" name="precio" step="0.10" min="0.10" required>

        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id">
            <option value="">Sin categoría</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="crear" value="1" class="pedzio-btn pedzio-btn--primario">Crear producto</button>
    </form>
</div>

<div class="pedzio-panel">
    <h2>Tu catálogo</h2>
    <table class="pedzio-tabla">
        <thead><tr><th>Nombre</th><th>Precio</th><th>Disponible</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?= e($producto['nombre']) ?></td>
                    <td><?= formatearMoneda((float) $producto['precio']) ?></td>
                    <td><?= $producto['disponible'] ? 'Sí' : 'No' ?></td>
                    <td>
                        <form method="post" action="/emprendedor/productos.php" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                            <button type="submit" name="toggle_id" value="<?= (int) $producto['id'] ?>"
                                    class="pedzio-btn pedzio-btn--secundario">
                                <?= $producto['disponible'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                        <form method="post" action="/emprendedor/productos.php" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                            <button type="submit" name="eliminar_id" value="<?= (int) $producto['id'] ?>"
                                    class="pedzio-btn pedzio-btn--peligro">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="4">Aún no tienes productos.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/../../includes/app_footer.php';
require_once __DIR__ . '/../../includes/footer.php';
