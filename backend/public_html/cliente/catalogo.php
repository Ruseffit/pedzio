<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['cliente'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Emprendimiento;
use Pedzio\Models\Producto;
use Pedzio\Services\CarritoService;

$pdo = obtenerConexion();
$emprendimientoModelo = new Emprendimiento($pdo);
$productoModelo = new Producto($pdo);

$emprendimientoId = isset($_GET['emprendimiento_id']) ? (int) $_GET['emprendimiento_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    if (pedzio_csrf_valido($_POST['_csrf'] ?? null)) {
        CarritoService::agregar((int) $_POST['producto_id'], max(1, (int) ($_POST['cantidad'] ?? 1)));
        flashSet('exito', 'Producto agregado al carrito.');
    }
    redirigir('/cliente/catalogo.php' . ($emprendimientoId ? "?emprendimiento_id={$emprendimientoId}" : ''));
}

$emprendimientos = $emprendimientoModelo->listarActivos();
$productos = $emprendimientoId ? $productoModelo->listarPorEmprendimiento($emprendimientoId, true) : [];

$tituloPagina = 'Catálogo';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-catalogo">
    <h1>Catálogo</h1>

    <div class="pedzio-catalogo__emprendimientos">
        <?php foreach ($emprendimientos as $emp): ?>
            <a class="pedzio-chip <?= $emprendimientoId === (int) $emp['id'] ? 'pedzio-chip--activo' : '' ?>"
               href="/cliente/catalogo.php?emprendimiento_id=<?= (int) $emp['id'] ?>">
                <?= e($emp['nombre_negocio']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($emprendimientoId): ?>
        <div class="pedzio-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="pedzio-card">
                    <h3><?= e($producto['nombre']) ?></h3>
                    <p><?= e($producto['descripcion'] ?? '') ?></p>
                    <p class="pedzio-precio"><?= formatearMoneda((float) $producto['precio']) ?></p>
                    <form method="post" action="/cliente/catalogo.php?emprendimiento_id=<?= $emprendimientoId ?>">
                        <input type="hidden" name="_csrf" value="<?= e(pedzio_csrf_token()) ?>">
                        <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                        <input type="number" name="cantidad" value="1" min="1" style="width:60px">
                        <button type="submit" class="pedzio-btn pedzio-btn--secundario">Agregar al carrito</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <p>Este emprendimiento aún no tiene productos disponibles.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Selecciona un emprendimiento para ver su catálogo.</p>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
