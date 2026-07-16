<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth_check.php';

$rolesPermitidos = ['cliente'];
require_once __DIR__ . '/../../includes/role_check.php';

use Pedzio\Models\Pedido;
use Pedzio\Controllers\PedidoController;
use Pedzio\Models\Producto;
use Pedzio\Services\FinanzasService;
use Pedzio\Models\MovimientoFinanciero;

$pdo = obtenerConexion();
$pedidoController = new PedidoController(
    new Pedido($pdo),
    new Producto($pdo),
    new FinanzasService(new MovimientoFinanciero($pdo))
);
$misPedidos = $pedidoController->listarPorCliente((int) $_SESSION['usuario_id']);

$tituloPagina = 'Mi panel';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="pedzio-dashboard">
    <h1>Hola, <?= e($_SESSION['usuario_nombre']) ?></h1>
    <p>Tienes <strong><?= count($misPedidos) ?></strong> pedido(s) registrados.</p>
    <a class="pedzio-btn pedzio-btn--primario" href="/cliente/catalogo.php">Ver catálogo y pedir</a>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
